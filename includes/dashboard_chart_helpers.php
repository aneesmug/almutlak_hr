<?php

if (defined('DASHBOARD_CHART_HELPERS_INCLUDED')) {
    return;
}
define('DASHBOARD_CHART_HELPERS_INCLUDED', true);

/**
 * Builds an extra WHERE clause from user-selected dashboard filters (the
 * Department/Company dropdowns, plus a clicked pie slice - country/sex/saudi),
 * on top of the existing session-based scope filters (company_filter_alias /
 * department_filter_alias / employee_filter_alias) which stay authoritative
 * and are always applied separately.
 *
 * $filters keys: department_ids (array), company_ids (array), sex (int|null),
 * country_id (int|null), saudi ('1'|'0'|null - 1=Saudi only, 0=Non-Saudi only)
 */
function dashboard_chart_extra_filter_sql($conDB, array $filters, $useAlias = true) {
    $sql = '';
    $prefix = $useAlias ? 'e.' : '';

    $departmentIds = array_filter(array_map('intval', $filters['department_ids'] ?? []));
    $companyIds = array_filter(array_map('intval', $filters['company_ids'] ?? []));
    $sex = isset($filters['sex']) && $filters['sex'] !== '' && $filters['sex'] !== null ? (int)$filters['sex'] : null;
    $countryId = isset($filters['country_id']) && $filters['country_id'] !== '' && $filters['country_id'] !== null ? (int)$filters['country_id'] : null;
    $saudi = isset($filters['saudi']) && $filters['saudi'] !== '' && $filters['saudi'] !== null ? (string)$filters['saudi'] : null;

    if (!empty($departmentIds)) {
        $sql .= " AND {$prefix}dept IN (" . implode(',', $departmentIds) . ")";
    }
    if (!empty($companyIds)) {
        $sql .= " AND {$prefix}comp_no IN (" . implode(',', $companyIds) . ")";
    }
    if ($sex !== null) {
        $sql .= " AND {$prefix}sex = " . $sex;
    }
    if ($countryId !== null) {
        $sql .= " AND {$prefix}country = " . $countryId;
    }
    if ($saudi === '1') {
        $sql .= " AND {$prefix}country IN (SELECT `id` FROM `countries` WHERE `code` = 'SA')";
    } elseif ($saudi === '0') {
        $sql .= " AND ({$prefix}country IS NULL OR {$prefix}country NOT IN (SELECT `id` FROM `countries` WHERE `code` = 'SA'))";
    }

    return $sql;
}

function get_dashboard_country_chart_data($conDB, $scopeFilterSql, $extraFilterSql, $isRtl) {
    $labels = [];
    $data = [];

    $sql = "SELECT `co`.`id`, `co`.`name`, `co`.`name_ar`, COUNT(*) AS `cnt`
        FROM `employees` `e`
        LEFT JOIN `countries` `co` ON `co`.`id` = `e`.`country`
        WHERE `e`.`status` = 1 " . $scopeFilterSql . $extraFilterSql . "
        GROUP BY `co`.`id`, `co`.`name`, `co`.`name_ar`
        ORDER BY `cnt` DESC";

    $result = mysqli_query($conDB, $sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    $top = array_slice($rows, 0, 7);
    $rest = array_slice($rows, 7);
    $ids = [];

    foreach ($top as $row) {
        $name = $isRtl && !empty($row['name_ar']) ? $row['name_ar'] : $row['name'];
        $labels[] = !empty($name) ? $name : __('unspecified');
        $data[] = (int)$row['cnt'];
        $ids[] = $row['id'] !== null ? (int)$row['id'] : null;
    }

    if (!empty($rest)) {
        $otherSum = array_sum(array_column($rest, 'cnt'));
        if ($otherSum > 0) {
            $labels[] = __('other');
            $data[] = $otherSum;
            $ids[] = null; // "Other" bucket has no single country id - not clickable as a slice filter
        }
    }

    return ['labels' => $labels, 'data' => $data, 'ids' => $ids];
}

function get_dashboard_gender_chart_data($conDB, $scopeFilterSqlNoAlias, $extraFilterSqlNoAlias) {
    $labels = [];
    $data = [];
    $ids = [];

    $sql = "SELECT `sex`, COUNT(*) AS `cnt` FROM `employees` WHERE `status` = 1 " . $scopeFilterSqlNoAlias . $extraFilterSqlNoAlias . " GROUP BY `sex`";
    $result = mysqli_query($conDB, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $sexVal = (int)$row['sex'];
            $label = $sexVal === 1 ? __('male') : ($sexVal === 2 ? __('female') : __('unspecified'));
            $labels[] = $label;
            $data[] = (int)$row['cnt'];
            $ids[] = $sexVal;
        }
    }

    return ['labels' => $labels, 'data' => $data, 'ids' => $ids];
}

function get_dashboard_company_chart_data($conDB, $scopeFilterSql, $extraFilterSql, $isRtl) {
    $labels = [];
    $data = [];
    $ids = [];

    $sql = "SELECT `cm`.`comp_id`, `cm`.`comp_name`, `cm`.`comp_name_ar`, COUNT(*) AS `cnt`
        FROM `employees` `e`
        LEFT JOIN `companies` `cm` ON `cm`.`comp_id` = `e`.`comp_no`
        WHERE `e`.`status` = 1 " . $scopeFilterSql . $extraFilterSql . "
        GROUP BY `cm`.`comp_id`, `cm`.`comp_name`, `cm`.`comp_name_ar`
        ORDER BY `cnt` DESC";

    $result = mysqli_query($conDB, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $name = $isRtl && !empty($row['comp_name_ar']) ? $row['comp_name_ar'] : $row['comp_name'];
            $labels[] = !empty($name) ? $name : __('unspecified');
            $data[] = (int)$row['cnt'];
            $ids[] = $row['comp_id'] !== null ? (int)$row['comp_id'] : null;
        }
    }

    return ['labels' => $labels, 'data' => $data, 'ids' => $ids];
}

function get_dashboard_saudi_chart_data($conDB, $scopeFilterSql, $extraFilterSql) {
    $sql = "SELECT
            SUM(CASE WHEN `co`.`code` = 'SA' THEN 1 ELSE 0 END) AS `saudi_cnt`,
            SUM(CASE WHEN `co`.`code` IS NULL OR `co`.`code` != 'SA' THEN 1 ELSE 0 END) AS `non_saudi_cnt`
        FROM `employees` `e`
        LEFT JOIN `countries` `co` ON `co`.`id` = `e`.`country`
        WHERE `e`.`status` = 1 " . $scopeFilterSql . $extraFilterSql;

    $result = mysqli_query($conDB, $sql);
    $data = [0, 0];
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $data = [(int)$row['saudi_cnt'], (int)$row['non_saudi_cnt']];
    }

    return ['labels' => [__('saudi'), __('non_saudi')], 'data' => $data, 'ids' => ['1', '0']];
}

/**
 * @param array $filters department_ids, company_ids, sex, country_id, saudi (see dashboard_chart_extra_filter_sql)
 */
function get_all_dashboard_chart_data($conDB, $scopeFilterSqlAlias, $scopeFilterSqlNoAlias, array $filters, $isRtl) {
    $extraAlias = dashboard_chart_extra_filter_sql($conDB, $filters, true);
    $extraNoAlias = dashboard_chart_extra_filter_sql($conDB, $filters, false);

    return [
        'country' => get_dashboard_country_chart_data($conDB, $scopeFilterSqlAlias, $extraAlias, $isRtl),
        'gender' => get_dashboard_gender_chart_data($conDB, $scopeFilterSqlNoAlias, $extraNoAlias),
        'company' => get_dashboard_company_chart_data($conDB, $scopeFilterSqlAlias, $extraAlias, $isRtl),
        'saudi' => get_dashboard_saudi_chart_data($conDB, $scopeFilterSqlAlias, $extraAlias),
    ];
}
