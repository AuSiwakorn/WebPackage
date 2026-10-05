<?php

/**
 * FILE: admbuilder/classes/class.builder.php
 * ROLE: เมธอด ADMBUILDER — AOSET/AOGET, AOGROUPLIST/AOLIST/AOLATEST, AOSUBPOSTLIST, AOITEM, isEditMode
 * DEPENDS: plugins/articles (getter), webbuilder table
 * TABLES: webbuilder, site_articles, site_articles_group, site_articles_subpost
 * TODO:
 *   - [ ] วิธีใช้/กับดัก: ดู AOBUILDER-THEME-GUIDE.md
 */

class ADMBUILDER
{
    public static function isLoginAdmin()
    {
        $isLogin = oApi::is_Login();
        $isAdmin = oApi::is_Admin();
        return ($isLogin && $isAdmin) ? true : false;
    }

    /**
     * อยู่ในโหมดแก้ไข AO Builder อยู่ไหม (แอดมินล็อกอิน + โหลดใน iframe พรีวิว + เปิดสวิตช์แก้ไข)
     * ใช้ในธีมเพื่อ "จองพื้นที่ที่ยังว่าง": ถ้าไม่มีเนื้อหาและไม่ได้แก้ไข → ไม่ต้องแสดง
     */
    public static function isEditMode()
    {
        return self::isLoginAdmin()
            && isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe'
            && SiteConfig_get('onoff_aobuilder') === 'on';
    }

    /**
     * เนื้อหา (โดยเฉพาะจาก rich editor) ว่างจริงไหม
     * summernote เซฟค่าว่างเป็น <p><br></p>/&nbsp; ซึ่ง !== '' — ต้องมองข้ามพวกนี้
     * แต่ถ้ามีสื่อ (รูป/วิดีโอ/ฝัง) ถือว่าไม่ว่าง
     */
    public static function isBlank($html)
    {
        if ($html === null || $html === '') return true;
        if (preg_match('#<\s*(img|iframe|video|audio|svg|embed|object|hr|input)\b#i', $html)) {
            return false;
        }
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\s\x{00A0}]+/u', '', $text);   // ตัด whitespace + &nbsp;
        return ($text === '' || $text === null);
    }

    /**
     * ภาษาที่เว็บรองรับ — อิง $aConfig['language'] ใน fix.<domain>.php ที่เดียว
     * คืน array [langkey => label] เช่น ['th' => 'Thai', 'en' => 'English']
     */
    public static function AOLANGS()
    {
        global $aConfig;
        return (isset($aConfig['language']) && is_array($aConfig['language']) && count($aConfig['language']) > 0)
            ? $aConfig['language']
            : array(DEFAULT_LANGEUAGE => DEFAULT_LANGEUAGE);
    }

    /**
     * โหลดค่า textbox ของทุกภาษาในครั้งเดียว
     * เก็บจริงใน webbuilder เป็นคนละแถวต่อภาษา โดยคีย์คือ <keysname><langkey>
     * (ธีมจึงอ่านด้วย AOGET($keysname . _LANG_, ...) ได้เหมือนเดิม)
     * คืน array [langkey => row] — ภาษาที่ยังไม่มีข้อมูลจะได้ array ว่าง
     */
    public static function AOBOXDATA($keysname)
    {
        $aOut = array();
        foreach (array_keys(ADMBUILDER::AOLANGS()) as $lk) {
            $row = DB_GET('webbuilder', ['web_keysname' => $keysname . $lk]);
            $aOut[$lk] = is_array($row) ? $row : array();
        }
        return $aOut;
    }

    public static function css($isMain = '')
    {
        if (!ADMBUILDER::isLoginAdmin()) return '';
        if ($isMain != 'main') {
            if ($_SERVER['HTTP_SEC_FETCH_DEST'] != 'iframe') {
                return '';
            }
        }

        include 'admbuilder/include/css.php';
        echo "\n";
    }

    public static function js($isMain = '')
    {
        $isMain = $isMain ?? '';
        if (!ADMBUILDER::isLoginAdmin()) return '';
        if ($isMain != 'main') {
            if ($_SERVER['HTTP_SEC_FETCH_DEST'] != 'iframe') {
                return '';
            }
        }

        include 'admbuilder/include/js.php';
        echo "\n";
    }

    public static function body($isMain = '')
    {
        global $aConfig; //เอาไปใช้ใน html.nav
        if (!ADMBUILDER::isLoginAdmin()) return '';
        if ($isMain != 'main') {
            if ($_SERVER['HTTP_SEC_FETCH_DEST'] != 'iframe') {
                return '';
            }
        }

        include 'admbuilder/views/html.model.php';
        //if ($isMain==='main') {
        //include 'admbuilder/views/html.nav.php';
        //echo "\n" . '<main class="ao_site-wrap">' . "\n" . '<div class="ao_viewport">' . "\n";
        //}
    }

    public static function end($isMain = '')
    {
        if (!ADMBUILDER::isLoginAdmin()) return '';
        if ($isMain != 'main') {
            if ($_SERVER['HTTP_SEC_FETCH_DEST'] != 'iframe') {
                return '';
            }
        }

        //if ($isMain==='main') {
        //echo '</div>' . "\n" . '</main>' . "\n";
        //}
    }

    /**
     * @param $options = [isTitle, isDesc, isLink, isImg, isMiniBox]
     */
    public static function AOSET($edtKey, $keysname, $options = [], $size = '90%')
    {
        if (!self::isEditMode()) return '';
        if ($edtKey !== '') {
            // ช่วง 3 (iframe isolation): เลิกพ่น hx-* (HTMX swap เข้า .ao-builder-content)
            // → พ่น data-* ให้ script.js อ่านไป set src ของ iframe #aoEditorFrame แทน
            $options = base64_encode(json_encode($options, JSON_UNESCAPED_UNICODE));
            echo '
                data-ao="aobuilder"
                data-ac="' . $edtKey . '"
                data-keysname="' . $keysname . '"
                data-vals="' . $options . '"
                data-size="' . $size . '"';
        }
    }

    public static function AOGET($keys, $keyreturn, $default = '', $return = 'echo')
    {
        $a = [
            'isTitle'   => 'web_title',
            'isDesc'    => 'web_desc',
            'isLink'    => 'web_link',
            'isImg'     => 'web_img',
            'isMiniBox' => 'web_minibox',
            'isContent' => 'web_content',
        ];

        $keysDb = array_key_exists($keyreturn, $a) ? $a[$keyreturn] : 'none';
        $aData = DB_GET('webbuilder', ['web_keysname' => $keys]);
        $value = !empty($aData[$keysDb]) ? $aData[$keysDb] : $default;

        if ($keysDb === 'web_img' && !empty($aData['web_img'])) {
            $value = URL_UPLOAD . '/' . $aData['web_img'];
        }

        return $return === 'echo' ? print($value) : $value;
    }

    // อ่านรายการ articles ของ keysname (คู่กับ AOSET edtKey='articles') — คืน array แถว, ว่าง = []
    public static function AOLIST($keysname, $num = 0, $page = 0)
    {
        if (!function_exists('plugin_articles_getAll')) return array();
        $r = plugin_articles_getAll($keysname, $num, $page);
        return (is_array($r) && !empty($r['data'])) ? $r['data'] : array();
    }

    // รวมบทความหลาย keysname เรียงใหม่→เก่า (displaytime ก่อน ไม่มีค่อย add_time) คืน $limit อันแรก
    public static function AOLATEST($keysnames, $limit = 3, $excludeId = 0)
    {
        $all = array();
        foreach ((array) $keysnames as $kn) {
            foreach (self::AOLIST($kn) as $row) {
                if ($excludeId && (int) ($row['articles_id'] ?? 0) === (int) $excludeId) continue;
                $all[] = $row;
            }
        }
        usort($all, function ($a, $b) {
            $ta = !empty($a['displaytime']) ? (int) $a['displaytime'] : (int) ($a['add_time'] ?? 0);
            $tb = !empty($b['displaytime']) ? (int) $b['displaytime'] : (int) ($b['add_time'] ?? 0);
            return $tb <=> $ta;
        });
        return array_slice($all, 0, (int) $limit);
    }

    // อ่าน article เดี่ยวด้วย id ภายใต้ keysname
    public static function AOITEM($keysname, $id)
    {
        if (!function_exists('plugin_articles_get')) return array();
        $r = plugin_articles_get($keysname, (int) $id);
        return (is_array($r) && !empty($r['articles_id'])) ? $r : array();
    }

    // อ่าน article เดี่ยวด้วย slug (ค้นข้ามทุก keysname)
    public static function AOITEMBYSLUG($slug)
    {
        if (!function_exists('plugin_articles_bySlug')) return array();
        $r = plugin_articles_bySlug($slug);
        return (is_array($r) && !empty($r['articles_id'])) ? $r : array();
    }

    // อ่านรายการกรุ๊ปของ keysname (คู่กับ AOSET edtKey='articles' ที่เปิด isGroup) — คืน array แถว, ว่าง = []
    public static function AOGROUPLIST($keysname, $num = 0, $page = 0)
    {
        if (!function_exists('plugin_getAllGroup')) return array();
        $r = plugin_getAllGroup($keysname, $num, $page);
        return (is_array($r) && !empty($r['data'])) ? $r['data'] : array();
    }

    // อ่านกรุ๊ปเดี่ยวด้วย id
    public static function AOGROUPITEM($id)
    {
        if (!function_exists('plugin_getArticlesGroupByID')) return array();
        $r = plugin_getArticlesGroupByID((int) $id);
        return (is_array($r) && !empty($r['group_id'])) ? $r : array();
    }

    // อ่านกรุ๊ปเดี่ยวด้วย slug (gc.group_slug)
    public static function AOGROUPBYSLUG($slug)
    {
        if (!function_exists('plugin_getArticlesGroupBySlug')) return array();
        $r = plugin_getArticlesGroupBySlug($slug);
        return (is_array($r) && !empty($r['group_id'])) ? $r : array();
    }

    // อ่านรายการ articles ในกรุ๊ป
    public static function AOLISTBYGROUP($keysname, $group_id, $num = 0, $page = 0)
    {
        if (!function_exists('plugin_articles_getAllByGroup')) return array();
        $r = plugin_articles_getAllByGroup($keysname, (int) $group_id, $num, $page);
        return (is_array($r) && !empty($r['data'])) ? $r['data'] : array();
    }

    // อ่านรายการ subpost ของ group/article (คู่กับ AOSET edtKey='subpost')
    public static function AOSUBPOSTLIST($group_id = 0, $articles_id = 0, $num = 0, $page = 0)
    {
        if (!function_exists('plugin_getAllSubpost')) return array();
        $r = plugin_getAllSubpost((int) $group_id, (int) $articles_id, $num, $page);
        return (is_array($r) && !empty($r['data'])) ? $r['data'] : array();
    }

    public static function USERONLINE()
    {
        return DB_LIST('member_online', ['onlineEndTime' => ['>=', _TIME_]]);
    }

    public static function AOUPFILE(
        array $file,
        array $allowed_extensions = ['jpg', 'png', 'gif'],
        string $target_dir = 'products'
    ): string {
        if (
            !isset($file['error'], $file['tmp_name'], $file['name']) ||
            $file['error'] !== UPLOAD_ERR_OK ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            return '';
        }

        if (!is_array($allowed_extensions) || empty($allowed_extensions)) {
            return '';
        }
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_extensions, true)) {
            return '';
        }
        $clean_target_dir = trim($target_dir, '/');
        $full_path_dir = rtrim(PATH_UPLOAD, '/') . '/' . $clean_target_dir;
        if (!is_dir($full_path_dir)) {
            if (!mkdir($full_path_dir, 0777, true) && !is_dir($full_path_dir)) {
                return '';
            }
        }
        $new_file_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
        $destination_relative = $clean_target_dir . '/' . $new_file_name;
        $destination_full = $full_path_dir . '/' . $new_file_name;
        if (!move_uploaded_file($file['tmp_name'], $destination_full)) {
            return '';
        }
        return $destination_relative;
    }

    /**
     * sanitizeHtml — กรอง HTML จาก editor ด้วย allowlist ก่อนเขียน DB (กัน Stored XSS)
     * TODO(ช่วง 7):
     *   - [x] ตัด tag อันตราย (script/style/iframe/object/embed/form/link/meta/base) ทั้งบล็อก
     *   - [x] allowlist เฉพาะ tag จัดรูปแบบที่ปลอดภัย
     *   - [x] ตัด attribute อันตราย: on*=, javascript:/data:(ที่ไม่ใช่รูป) ใน href/src, expression()
     *   - [ ] (ทางเลือก) สลับไปใช้ HTMLPurifier ถ้าต้องการความครบถ้วนสูงสุด
     * @param string|null $html
     * @return string
     */
    public static function sanitizeHtml($html)
    {
        if ($html === null || $html === '') return '';

        // 1) ตัด tag อันตรายพร้อมเนื้อหาข้างใน
        $html = preg_replace('#<\s*(script|style|iframe|object|embed|form|link|meta|base)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        // เผื่อ tag เปิด/ปิดลอยที่ไม่มีคู่
        $html = preg_replace('#<\s*/?\s*(script|style|iframe|object|embed|form|link|meta|base)\b[^>]*>#is', '', $html);

        // 2) allowlist tag ที่ยอมให้เหลือ
        $allowed = '<p><br><b><strong><i><em><u><s><strike><ul><ol><li>'
            . '<a><span><div><h1><h2><h3><h4><h5><h6><blockquote><pre><code>'
            . '<table><thead><tbody><tr><td><th><img><hr><font>';
        $html = strip_tags($html, $allowed);

        // 3) ตัด attribute อันตราย
        // 3.1 event handler: onclick/onerror/onload ฯลฯ
        $html = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#is', '', $html);
        // 3.2 javascript: ใน href/src
        $html = preg_replace('#(href|src)\s*=\s*("\s*javascript:[^"]*"|\'\s*javascript:[^\']*\'|javascript:[^\s>]+)#is', '$1="#"', $html);
        // 3.3 data: ใน href/src ยกเว้น data:image/ (รูป base64 จาก summernote)
        $html = preg_replace('#(href|src)\s*=\s*("\s*data:(?!image/)[^"]*"|\'\s*data:(?!image/)[^\']*\')#is', '$1="#"', $html);
        // 3.4 expression() ใน style
        $html = preg_replace('#style\s*=\s*("[^"]*expression\([^"]*"|\'[^\']*expression\([^\']*\')#is', '', $html);

        return $html;
    }
}
