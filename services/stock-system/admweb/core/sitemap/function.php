<?php
function DB_INSERT_SITEMAP($chunk)
{
    if (empty($chunk)) return false;
    $db = DB::singleton();
    $fields = array_keys($chunk[0]);
    $fieldList = "`" . implode("`, `", $fields) . "`";
    $allPlaceholders = [];
    $allValues = [];
    foreach ($chunk as $rowIndex => $row) {
        $rowPlaceholders = [];
        foreach ($row as $fieldName => $value) {
            $placeholder = ":" . $fieldName . "_" . $rowIndex;
            $rowPlaceholders[] = $placeholder;
            $allValues[$placeholder] = $value;
        }
        $allPlaceholders[] = "(" . implode(", ", $rowPlaceholders) . ")";
    }
    $sql = "INSERT INTO `" . _DBPREFIX_ . 'sitemap' . "` ($fieldList) VALUES " . implode(", ", $allPlaceholders);
    try {
        $db->prepare($sql, $allValues);
        return true;
    } catch (PDOException $e) {
        error_log("[BULK ERROR] MESSAGE: " . $e->getMessage());
        return false;
    }
}

function DB_CLEAR($table)
{
    if (empty($table)) {
        return false;
    }
    $db = DB::singleton();
    $sql = "TRUNCATE TABLE " . _DBPREFIX_ . $table . "";
    $db->query($sql, __FUNCTION__);
    return $sql;
}

function BuildLangURL($aConfigSitemap)
{
    global $aConfig;

    if (empty($aConfigSitemap) || empty($aConfig['language']) || count($aConfig['language']) == 1) {
        return $aConfigSitemap;
    }

    $newData = [];
    foreach ($aConfigSitemap as $key => $value) {
        foreach ($aConfig['language'] as $lang => $v) {
            if (is_int($key)) {
                $newData[] = "{$lang}/{$value}";
            } else {
                $newKey = "{$lang}/{$key}";
                $newData[$newKey] = $value;
            }
        }
    }

    return $newData;
}

function GenerateSitemap()
{
    DB_CLEAR('sitemap');
    global $aConfigSitemap, $aConfig;

    $aSitemap = BuildLangURL($aConfigSitemap);
    $root = rtrim(URL_WEB_ROOT, '/');
    $bulkData = [];
    $bulkData[] = [
        'loc' => "{$root}/",
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '1.0'
    ];
    $mutiLang = false;
    if (!empty($aConfig['language']) && count($aConfig['language']) != 1) {
        $mutiLang = true;
        foreach ($aConfig['language'] as $lang => $v) {
            $bulkData[] = [
                'loc' => "{$root}/{$lang}/",
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ];
        }
    }
    if (!empty($aSitemap)) {
        foreach ($aSitemap as $page => $xml) {
            if (is_int($page)) {
                $bulkData[] = [
                    'loc' => "{$root}/{$xml}/",
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => '0.6'
                ];
                continue;
            }
            if (empty($xml['keysname'])) continue;
            $bulkData[] = [
                'loc' => "{$root}/{$page}/",
                'lastmod' => date('Y-m-d'),
                'changefreq' => isset($xml['changefreq']) ? $xml['changefreq'] : 'monthly',
                'priority' => isset($xml['priority']) ? $xml['priority'] : '0.7'
            ];
            $aArticles = DB_LIST('site_articles', ['keysname' => $xml['keysname'], 'status' => 0, 'displaytime' => ['<=', _TIME_]]);
            if (!empty($aArticles['data'])) {
                $currentLang = 'th';
                foreach ($aArticles['data'] as $value) {
                    if (empty($value['articles_id'])) continue;
                    if ($mutiLang) {
                        $lang = explode('/', $page);
                        $currentLang = !empty($lang[0]) ? $lang[0] : 'th';
                    }
                    $aContents = DB_GET('site_articles_content', ['articles_id' => $value['articles_id'], 'langkeys' => $currentLang]);
                    if (empty($aContents)) continue;
                    $param = !empty($aContents['slug']) ? $aContents['slug'] : $value['articles_id'];
                    $loc = "{$root}/{$page}/{$param}";
                    $lastmod = isset($value['displaytime']) ? date('Y-m-d', (int)$value['displaytime']) : date('Y-m-d');
                    $changefreq = isset($xml['changefreq']) ? $xml['changefreq'] : 'weekly';
                    $priority = isset($xml['priority']) ? $xml['priority'] : '0.8';
                    $bulkData[] = [
                        'loc' => $loc,
                        'lastmod' => $lastmod,
                        'changefreq' => $changefreq,
                        'priority' => $priority,
                    ];
                }
            }
        }
    }
    if (!empty($bulkData)) {
        foreach (array_chunk($bulkData, 500) as $chunk) {
            DB_INSERT_SITEMAP($chunk);
        }
    }
}
