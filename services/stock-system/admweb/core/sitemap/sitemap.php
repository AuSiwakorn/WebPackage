<?php
include 'admweb/include/conf.ini.php';
include PATH_PLUGIN . '/db/db.php';
header("Content-Type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
try {
    $aSitemap = DB_LIST('sitemap', [], 0, 0, "ORDER BY priority DESC, lastmod DESC");
    $aCustom = DB_LIST('sitemap_custom', [], 0, 0, "ORDER BY priority DESC, lastmod DESC");
    if (!empty($aSitemap['data'])) {
        foreach ($aSitemap['data'] as $value) {
            echo "\n\t<url>";
            echo "\n\t\t<loc>" . htmlspecialchars($value['loc'], ENT_QUOTES, 'UTF-8') . "</loc>";
            echo "\n\t\t<lastmod>" . $value['lastmod'] . "</lastmod>";
            echo "\n\t\t<changefreq>" . $value['changefreq'] . "</changefreq>";
            echo "\n\t\t<priority>" . $value['priority'] . "</priority>";
            echo "\n\t</url>";
        }
    }
    if (!empty($aCustom['data'])) {
        foreach ($aCustom['data'] as $value) {
            echo "\n\t<url>";
            echo "\n\t\t<loc>" . htmlspecialchars($value['loc'], ENT_QUOTES, 'UTF-8') . "</loc>";
            echo "\n\t\t<lastmod>" . $value['lastmod'] . "</lastmod>";
            echo "\n\t\t<changefreq>" . $value['changefreq'] . "</changefreq>";
            echo "\n\t\t<priority>" . $value['priority'] . "</priority>";
            echo "\n\t</url>";
        }
    }
} catch (Exception $e) {
    error_log("[SITEMAP XML ERROR]: " . $e->getMessage());
}
echo "\n" . '</urlset>';
