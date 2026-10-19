<?php
/**
 * PAYROLL VACATION RULES - the single place that decides which vacations remove an
 * employee from a payroll month. Every payroll page, API and report builds its
 * vacation filter from here, so a rule change is made once:
 *
 *   An employee is dropped from payroll month M when an approved VAC-* vacation
 *   (review 'A', status approved/completed) overlaps M and either
 *     1. lasts >= vacation_payroll_dropout_days (setting, default 30; 0 disables all), or
 *     2. its salary is paid by the vacation settlement - Fly annual, or Local annual
 *        removed from payroll (see payrollSettlementVacationSql()).
 *
 * LV-* leave requests (sick, marriage, hajj ...) never drop anyone.
 * Loaded by includes/helper_functions.php (which session_check.php includes).
 *
 * Usage:
 *   $cond = payrollVacationDropoutCondition($conDB, 'e.emp_id', ':m_start', ':m_end');
 *   if ($cond !== '') { $sql .= " AND NOT {$cond}"; }   // bind :m_start / :m_end yourself
 */

if (!function_exists('getPayrollVacationDropoutDays')) {
    /** Rule 1 threshold (vacation_payroll_dropout_days). 0 = vacation dropout disabled. */
    function getPayrollVacationDropoutDays($conDB): int
    {
        static $cache = null;
        if ($cache === null) {
            $cache = function_exists('get_setting_num')
                ? max(0, (int)get_setting_num($conDB, 'vacation_payroll_dropout_days', 30))
                : 30;
        }
        return $cache;
    }
}

if (!function_exists('payrollSettlementVacationSql')) {
    /**
     * Rule 2 - SQL twin of isSettlementPayableVacation() (+ resolveVacationSalaryType())
     * for an emp_vacation alias: TRUE when the vacation's salary is paid by the vacation
     * settlement (Fly annual, or Local annual removed from payroll - Saudi, >= minimum
     * days, vacation salary 'payroll'; employees.allow_vacation_salary_below_min_days
     * waives country + minimum days).
     */
    function payrollSettlementVacationSql(string $v = 'vd'): string
    {
        $rule = getLocalAnnualPayrollRemovalRuleConfig();
        $localType = addslashes((string)$rule['vac_type']);
        $localFly = addslashes((string)$rule['fly_type']);
        $country = (int)$rule['country_id'];
        $minDays = (float)$rule['minimum_days_exclusive'];

        $days = "COALESCE({$v}.vacdays, 0)";
        $salaryType = "TRIM(COALESCE({$v}.vacation_salary_type, ''))";
        $allowBelow = "COALESCE((SELECT ex.allow_vacation_salary_below_min_days FROM employees ex WHERE ex.emp_id = {$v}.emp_id LIMIT 1), 0) = 1";
        $countryOk = "COALESCE((SELECT ex.country FROM employees ex WHERE ex.emp_id = {$v}.emp_id LIMIT 1), 0) = {$country}";

        return "(
            (LOWER(TRIM(COALESCE({$v}.vac_type, ''))) = 'fly' AND LOWER(TRIM(COALESCE({$v}.fly_type, ''))) = 'annual')
            OR (
                TRIM(COALESCE({$v}.vac_type, '')) = '{$localType}'
                AND TRIM(COALESCE({$v}.fly_type, '')) = '{$localFly}'
                AND ({$countryOk} OR {$allowBelow})
                AND ({$days} >= {$minDays} OR {$allowBelow})
                AND (
                    ({$allowBelow} AND {$days} < {$minDays})
                    OR {$salaryType} = 'payroll'
                    OR ({$salaryType} NOT IN ('payroll', 'end_of_service') AND COALESCE({$v}.is_deductible, 0) = 1)
                )
            )
        )";
    }
}

if (!function_exists('payrollVacationDropoutQualifierSql')) {
    /**
     * Does THIS vacation row (alias $v) qualify to drop its employee? Rules 1 + 2 only,
     * no date/status checks - for queries that already join emp_vacation themselves.
     * Returns '1=0' when vacation dropout is disabled.
     */
    function payrollVacationDropoutQualifierSql($conDB, string $v = 'vd'): string
    {
        $days = getPayrollVacationDropoutDays($conDB);
        if ($days <= 0) {
            return '1=0';
        }
        return "(COALESCE({$v}.vacdays, 0) >= {$days} OR " . payrollSettlementVacationSql($v) . ")";
    }
}

if (!function_exists('payrollVacationDropoutCondition')) {
    /**
     * Full "employee is dropped from this payroll month by a vacation" condition:
     *   EXISTS (approved VAC-* vacation overlapping the month that qualifies).
     * $empIdExpr / $monthStartExpr / $monthEndExpr are SQL expressions or placeholders
     * (e.g. 'e.emp_id', ':dropout_month_start', "LAST_DAY(CONCAT(p.month_year,'-01'))");
     * the caller binds any placeholders. Returns '' when vacation dropout is disabled.
     */
    function payrollVacationDropoutCondition($conDB, string $empIdExpr, string $monthStartExpr, string $monthEndExpr, string $v = 'vd'): string
    {
        if (getPayrollVacationDropoutDays($conDB) <= 0) {
            return '';
        }
        return "EXISTS (
                SELECT 1 FROM emp_vacation {$v}
                WHERE {$v}.emp_id = {$empIdExpr}
                    AND {$v}.review = 'A'
                    AND {$v}.current_status IN ('approved', 'completed')
                    AND {$v}.request_inv_no LIKE 'VAC-%'
                    AND {$v}.start_date <= {$monthEndExpr}
                    AND COALESCE({$v}.return_date, {$v}.start_date) >= {$monthStartExpr}
                    AND " . payrollVacationDropoutQualifierSql($conDB, $v) . "
            )";
    }
}

/*
 * PAID VACATION SETTLEMENT RULE - the employee is also dropped from payroll month M when a
 * PAID annual-vacation settlement (completed/processed, payment_date set) covers M:
 *   - its vacation overlaps M and is still running at M's end (actual return = latest
 *     approved rejoin date = first day back, else the day after return_date - return_date
 *     is the last vacation day), or
 *   - the vacation row no longer exists (imported data) and the settlement was paid in M.
 *
 * Two ways to use it:
 *   payrollPaidSettlementCondition()  - EXISTS condition for one employee (most queries)
 *   payrollPaidSettlementWindowsSql() + payrollPaidSettlementWindowPeriodSql()
 *       - one row per paid settlement with its window, for queries that check many
 *         months at once (all_payroll_approvals.php); the rejoin lookup then runs once
 *         per settlement instead of once per month x settlement.
 */

if (!function_exists('payrollPaidSettlementBaseSql')) {
    /** A paid annual-vacation settlement row (alias $sr). */
    function payrollPaidSettlementBaseSql(string $sr = 'sr'): string
    {
        return "{$sr}.request_type = 'annual_vacation'
                AND {$sr}.settlement_status IN ('completed', 'processed')
                AND {$sr}.payment_date IS NOT NULL";
    }
}

if (!function_exists('payrollPaidSettlementRejoinSql')) {
    /** Latest approved rejoin date (first day back) of vacation $vacIdExpr. */
    function payrollPaidSettlementRejoinSql(string $vacIdExpr): string
    {
        return "(SELECT pst_rr.final_approved_date FROM rejoin_requests pst_rr
                WHERE pst_rr.vacation_id = {$vacIdExpr} AND pst_rr.status = 'approved'
                    AND pst_rr.final_approved_date IS NOT NULL
                ORDER BY pst_rr.id DESC LIMIT 1)";
    }
}

if (!function_exists('payrollPaidSettlementPeriodSqlFor')) {
    /**
     * Core "settlement covers the month" predicate over column expressions:
     * $c = [vac_id, vac_status, vac_start, vac_return, rejoin, paid_at].
     * $monthStart / $monthEnd: SQL expressions, or callables returning a fresh expression
     * per use (native prepares can't repeat a named placeholder).
     */
    function payrollPaidSettlementPeriodSqlFor($monthStart, $monthEnd, array $c): string
    {
        $s = is_callable($monthStart) ? $monthStart : static fn() => $monthStart;
        $e = is_callable($monthEnd) ? $monthEnd : static fn() => $monthEnd;
        $actualReturn = "COALESCE({$c['rejoin']}, {$c['vac_return']})";

        return "(
                (
                    {$c['vac_id']} IS NOT NULL
                    AND {$c['vac_status']} IN ('approved', 'completed')
                    AND {$c['vac_start']} <= " . $e() . "
                    AND COALESCE({$actualReturn}, {$c['vac_start']}) >= " . $s() . "
                    AND (
                        {$actualReturn} IS NULL
                        OR COALESCE({$c['rejoin']}, DATE_ADD({$c['vac_return']}, INTERVAL 1 DAY)) > " . $e() . "
                    )
                )
                OR (
                    {$c['vac_id']} IS NULL
                    AND {$c['paid_at']} BETWEEN " . $s() . " AND " . $e() . "
                )
            )";
    }
}

if (!function_exists('payrollPaidSettlementCondition')) {
    /**
     * Full "employee already paid by a vacation settlement for this month" condition.
     * Returns [sql, params]:
     *   - $monthStart/$monthEnd as 'Y-m-d' values -> unique placeholders are generated
     *     (prefix $paramPrefix) and returned in params, ready to merge into yours;
     *   - as SQL expressions (e.g. "CONCAT(p.month_year, '-01')") -> params is [].
     */
    function payrollPaidSettlementCondition(string $empIdExpr, string $monthStart, string $monthEnd, string $paramPrefix = 'pst'): array
    {
        $params = [];
        $make = static function (string $value, string $tag) use (&$params, $paramPrefix) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return static fn() => $value;
            }
            return static function () use (&$params, $paramPrefix, $tag, $value) {
                $key = ':' . $paramPrefix . '_' . $tag . (count($params) + 1);
                $params[$key] = $value;
                return $key;
            };
        };

        $period = payrollPaidSettlementPeriodSqlFor($make($monthStart, 's'), $make($monthEnd, 'e'), [
            'vac_id' => 'pst_sv.id',
            'vac_status' => 'pst_sv.current_status',
            'vac_start' => 'pst_sv.start_date',
            'vac_return' => 'pst_sv.return_date',
            'rejoin' => payrollPaidSettlementRejoinSql('pst_sv.id'),
            'paid_at' => 'sr.payment_date',
        ]);

        $sql = "EXISTS (
                SELECT 1 FROM settlement_records sr
                LEFT JOIN emp_vacation pst_sv ON pst_sv.request_inv_no = SUBSTRING(sr.request_inv_no, 6)
                WHERE sr.emp_id = {$empIdExpr}
                    AND " . payrollPaidSettlementBaseSql('sr') . "
                    AND {$period}
            )";

        return [$sql, $params];
    }
}

if (!function_exists('payrollPaidSettlementWindowsSql')) {
    /**
     * Derived table: one row per paid annual-vacation settlement with columns
     * emp_id, paid_at, vac_id, vac_status, vac_start, vac_return, rejoin_date.
     * Use as "(" . payrollPaidSettlementWindowsSql() . ") w" with
     * payrollPaidSettlementWindowPeriodSql($start, $end, 'w').
     */
    function payrollPaidSettlementWindowsSql(): string
    {
        // DISTINCT keeps MariaDB from merging this derived table into the outer query
        // (which re-ran the rejoin lookup once per month x settlement: ~7s vs ~0.5s).
        return "SELECT DISTINCT sr.emp_id, sr.payment_date AS paid_at,
                    pst_sv.id AS vac_id, pst_sv.current_status AS vac_status,
                    pst_sv.start_date AS vac_start, pst_sv.return_date AS vac_return,
                    " . payrollPaidSettlementRejoinSql('pst_sv.id') . " AS rejoin_date
                FROM settlement_records sr
                LEFT JOIN emp_vacation pst_sv ON pst_sv.request_inv_no = SUBSTRING(sr.request_inv_no, 6)
                WHERE " . payrollPaidSettlementBaseSql('sr');
    }
}

if (!function_exists('payrollPaidSettlementWindowPeriodSql')) {
    /** Period predicate over a payrollPaidSettlementWindowsSql() alias. */
    function payrollPaidSettlementWindowPeriodSql($monthStart, $monthEnd, string $w = 'w'): string
    {
        return payrollPaidSettlementPeriodSqlFor($monthStart, $monthEnd, [
            'vac_id' => "{$w}.vac_id",
            'vac_status' => "{$w}.vac_status",
            'vac_start' => "{$w}.vac_start",
            'vac_return' => "{$w}.vac_return",
            'rejoin' => "{$w}.rejoin_date",
            'paid_at' => "{$w}.paid_at",
        ]);
    }
}

if (!function_exists('payrollVacationDropoutReason')) {
    /** Human text for "skipped" lists / logs. */
    function payrollVacationDropoutReason($conDB): string
    {
        return 'On a vacation of ' . getPayrollVacationDropoutDays($conDB)
            . '+ days, or one paid through the vacation settlement, in this payroll month';
    }
}
