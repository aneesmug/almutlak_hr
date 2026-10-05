<?php
/**
 * Which financial dimensions a ledger account accepts in each D365 legal entity.
 *
 * Every company's ledger has its own account structures (e.g. MHO "HO P&L Accounts" = MainAccount-CostCenter-Department,
 * MTL "MTL-PL" = MainAccount-Company) and advanced rules that add dimensions for some main accounts
 * (e.g. Worker on 510101*). D365 rejects an account that carries a dimension its structure does not have,
 * so payroll lines only send the allowed ones.
 *
 * Read from D365 (Ledgers, AccountStructures, AccountStructureConstraints, AdvancedRules, AdvancedRuleCriteria,
 * LedgerAdvancedRuleStructures) and cached for 1 hour per environment in the system temp dir.
 */
require_once __DIR__ . '/D365Client.php';

class D365AccountRules
{
    /** @var array */
    private $data;

    public function __construct(D365Client $client, $refresh = false)
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_account_rules_' . md5($client->getResourceUrl()) . '.json';
        $data = (!$refresh && is_file($file) && time() - filemtime($file) < 3600) ? json_decode((string)file_get_contents($file), true) : null;
        if (!is_array($data)) {
            $data = self::load($client);
            @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
        $this->data = $data;
    }

    private static function load(D365Client $client)
    {
        $get = function ($entity, array $query = []) use ($client) {
            $r = $client->getAll($entity, $query, true);
            if ($r['error']) {
                throw new RuntimeException("D365 $entity: " . $r['error']);
            }
            return $r['data']['value'] ?? [];
        };
        $segments = function (array $row) {
            $out = [];
            for ($i = 1; $i <= 11; $i++) {
                $name = trim((string)($row[sprintf('SegmentName%02d', $i)] ?? ''));
                if ($name !== '') {
                    $out[] = $name;
                }
            }
            return $out;
        };

        $data = ['companies' => [], 'structures' => [], 'constraints' => [], 'rules' => [], 'ruleStructures' => []];
        foreach ($get('Ledgers') as $l) {
            $company = strtoupper((string)$l['LegalEntityId']);
            $list = [];
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($l['AccountStructureName' . $i])) {
                    $list[] = $l['AccountStructureName' . $i];
                }
            }
            if ($list) {
                $data['companies'][$company] = ['name' => (string)($l['Description'] ?? ''), 'structures' => $list];
            }
        }
        foreach ($get('AccountStructures') as $s) {
            if (($s['Status'] ?? '') === 'Active') {
                $data['structures'][$s['AccountStructureName']] = $segments($s);
            }
        }
        foreach ($get('AccountStructureConstraints') as $c) {
            if (($c['Status'] ?? '') === 'Active') {
                $data['constraints'][$c['AccountStructure']][] = (string)($c['SegmentCriteria01'] ?? '');
            }
        }
        foreach ($get('LedgerAdvancedRuleStructures') as $s) {
            if (($s['Status'] ?? '') === 'Active') {
                $data['ruleStructures'][$s['AccountRuleStructureName']] = $segments($s);
            }
        }
        $rules = [];
        foreach ($get('AdvancedRules') as $r) {
            if (($r['Status'] ?? '') !== 'Active') {
                continue;
            }
            $adds = [];
            for ($i = 1; $i <= 6; $i++) {
                if (!empty($r['AdvancedRuleStructure' . $i])) {
                    $adds[] = $r['AdvancedRuleStructure' . $i];
                }
            }
            $rules[$r['AccountStructure'] . '|' . $r['AdvancedRuleName']] = ['structure' => $r['AccountStructure'], 'adds' => $adds, 'criteria' => []];
        }
        foreach ($get('AdvancedRuleCriteria') as $c) {
            $key = $c['AccountStructure'] . '|' . $c['AdvancedRule'];
            if (($c['Status'] ?? '') === 'Active' && ($c['SegmentName'] ?? '') === 'MainAccount' && isset($rules[$key])) {
                $rules[$key]['criteria'][] = (string)$c['AdvancedRuleCriterion'];
            }
        }
        $data['rules'] = array_values($rules);
        return $data;
    }

    /** D365 criteria syntax: "1*;2*", "51010200..59999999", "510101*", exact value, "\"\"" (blank) */
    public static function matches($value, $criteria)
    {
        foreach (preg_split('/;/', (string)$criteria) as $c) {
            $c = trim($c);
            if ($c === '' || $c === '""') {
                continue;
            }
            if (strpos($c, '..') !== false) {
                [$from, $to] = array_map('trim', explode('..', $c, 2));
                if (strcmp($value, $from) >= 0 && strcmp($value, $to) <= 0) {
                    return true;
                }
            } elseif (substr($c, -1) === '*') {
                if (strpos($value, substr($c, 0, -1)) === 0) {
                    return true;
                }
            } elseif (strcasecmp($value, $c) === 0) {
                return true;
            }
        }
        return false;
    }

    /** Legal entities with a ledger: ['MHO' => 'شركة الاداره العامه', ...] */
    public function companies()
    {
        $out = [];
        foreach ($this->data['companies'] as $code => $c) {
            $out[$code] = $c['name'];
        }
        ksort($out);
        return $out;
    }

    /**
     * Dimensions (lower-case names) a main account accepts in a company, or null when the company /
     * account is not covered by any structure (then no filtering is applied).
     */
    public function allowedDimensions($company, $mainAccount)
    {
        $company = strtoupper((string)$company);
        $structures = $this->data['companies'][$company]['structures'] ?? null;
        if (!$structures) {
            return null;
        }
        foreach ($structures as $structure) {
            $hit = false;
            foreach ($this->data['constraints'][$structure] ?? [] as $criteria) {
                if (self::matches($mainAccount, $criteria)) {
                    $hit = true;
                    break;
                }
            }
            if (!$hit) {
                continue;
            }
            $dims = [];
            foreach ($this->data['structures'][$structure] ?? [] as $seg) {
                $dims[strtolower($seg)] = true;
            }
            foreach ($this->data['rules'] as $rule) {
                if ($rule['structure'] !== $structure) {
                    continue;
                }
                foreach ($rule['criteria'] as $criteria) {
                    if (self::matches($mainAccount, $criteria)) {
                        foreach ($rule['adds'] as $add) {
                            foreach ($this->data['ruleStructures'][$add] ?? [] as $seg) {
                                $dims[strtolower($seg)] = true;
                            }
                        }
                        break;
                    }
                }
            }
            unset($dims['mainaccount']);
            return array_keys($dims);
        }
        return null;
    }

    /** Companies whose structures can carry this dimension anywhere (e.g. CostCenter -> ['MHO']) */
    public function companiesWithDimension($dimension)
    {
        $dimension = strtolower($dimension);
        $out = [];
        foreach ($this->data['companies'] as $code => $c) {
            foreach ($c['structures'] as $structure) {
                if (in_array($dimension, array_map('strtolower', $this->data['structures'][$structure] ?? []), true)) {
                    $out[] = $code;
                    break;
                }
            }
        }
        sort($out);
        return $out;
    }
}
