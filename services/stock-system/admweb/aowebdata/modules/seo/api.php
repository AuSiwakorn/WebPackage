<?php
/*
$keys = 
AddOnScriptBeforCloseHeader
AddOnScriptAfterOpenBody
AddOnScriptBeforCloseBody
*/  
if (!function_exists('Seo_ensureScriptsTable')) {
    function Seo_ensureScriptsTable()
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $db = DB::singleton();
            $table = _DBPREFIX_ . 'site_seo_scripts';
            $db->query("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `code` LONGTEXT NOT NULL,
                    `position` VARCHAR(50) NOT NULL,
                    `block` TINYINT(1) DEFAULT 0,
                    `status` TINYINT(1) DEFAULT 1,
                    `sort` INT DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Exception $e) {}
    }
}

function Seo_processScriptCode($code, $block = 0)
{
    if (empty($code)) return '';
    if (empty($block)) return $code;

    if (stripos($code, '<script') === false && !empty(trim($code))) {
        $code = '<script>' . trim($code) . '</script>';
    }

    return preg_replace_callback('/<script\b([^>]*)>(.*?)<\/script>/is', function ($matches) {
        $attrs_str = $matches[1];
        $content   = $matches[2];

        $attrs_str_clean = preg_replace('/\btype=["\']([^"\']+)["\']/i', '', $attrs_str);
        $attrs_str_clean = preg_replace('/\bdata-consent=["\']([^"\']+)["\']/i', '', $attrs_str_clean);
        $attrs_str_clean = trim(preg_replace('/\s+/', ' ', $attrs_str_clean));

        $new_attrs = 'type="text/plain" data-consent="analytics"';
        if (!empty($attrs_str_clean)) {
            $new_attrs .= ' ' . $attrs_str_clean;
        }

        return '<script ' . $new_attrs . '>' . $content . '</script>';
    }, $code);
}

function AddOnScript($keys = '')
{
    if ($keys != '') {
        if (file_exists(PATH_UPLOAD . '/config_file/' . $keys . '.html')) {
            include(PATH_UPLOAD . '/config_file/' . $keys . '.html');
        }

        if (function_exists('Seo_ensureScriptsTable')) {
            $pos_map = [
                'AfterOpenHead'    => ['after_open_head'],
                'BeforCloseHeader' => ['head', 'before_close_head'],
                'AfterOpenBody'    => ['after_open_body'],
                'BeforCloseBody'   => ['body', 'before_close_body'],
            ];

            if (isset($pos_map[$keys])) {
                try {
                    Seo_ensureScriptsTable();
                    $scripts_list = DB_LIST('site_seo_scripts', ['position' => ['IN', $pos_map[$keys]], 'status' => 1], 1000, 1, 'ORDER BY sort ASC, id DESC');
                    if (is_array($scripts_list) && isset($scripts_list['data'])) {
                        foreach ($scripts_list['data'] as $s_row) {
                            echo "\n<!-- Script: " . htmlspecialchars($s_row['name']) . " -->\n";
                            $is_blk = isset($s_row['block']) ? intval($s_row['block']) : 0;
                            echo Seo_processScriptCode($s_row['code'], $is_blk);
                        }
                    }
                } catch (Exception $e) {}
            }
        }
    }
}

function SEORedir301()
{
    $chto = DB_GET('site_configs', ['keywords' => 'onoff_redirect_301']);
    if (isset($chto['val']) && $chto['val'] == 'on') {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $row = DB_GET('site_seo_redir', ['source' => $requestUri]);
        if ($row) {
            DB_UP('site_seo_redir', ['hit_count' => ($row['hit_count'] + 1)], ['redir_id' => $row['redir_id']]);
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $row['target']);
            exit();
        }
    }
}
