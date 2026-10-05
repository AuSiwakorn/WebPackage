<?php

/**
 * FILE: admbuilder/classes/class.seo.php
 * ROLE: SEO meta + schema JSON-LD — set/show/schema/setContentMeta/loadFromDB (per page/lang)
 * DEPENDS: config.php ($__aDefaultSchemaTypes/$__seoBase), site_metatags
 * TABLES: site_metatags
 * TODO:
 *   - [ ] title/desc รับ 2 ทาง (theme raw / DB encoded) — decode ตอน input; ดู AOBUILDER-THEME-GUIDE.md §5
 */

class SEO
{
    protected static $key = '';
    protected static $title = '';
    protected static $description = '';
    protected static $keywords = '';
    protected static $schema = [];
    protected static $schema_data = [];
    protected static $dataLoaded = false;
    protected static $schema_type = '';
    protected static $aOptionSeo = [];
    protected static $editLang = ''; // ภาษาที่เลือกแก้ใน panel SEO (override _LANG_ ตอนโหลด/เซฟ)

    /**
     * เลือกภาษาที่จะแก้ใน panel (แยก th/en จากในตัว panel เอง)
     */
    public static function useLang($lang)
    {
        self::$editLang = $lang;
        self::$dataLoaded = false; // บังคับโหลดใหม่ตามภาษาที่เลือก
    }

    public static function editLang()
    {
        return self::$editLang !== '' ? self::$editLang : _LANG_;
    }

    public static function test()
    {
        pre($_REQUEST);
    }
    /*
     * ตั้งค่า default SEO
     * th_TH = thailand
     $aOptionSeo = [
        'site_name' => '',
        'locale' => 'th_TH', 
        'url' => 'http://www......', 
        'image' => 'http://www.....',
        'logo' => 'http://www.....',
        'alternate' => [
            'th' => 'http://www.....',
            'en' => 'http://www.....',
            'x-default' => 'http://www.....',
        ]
    ];

     */
    public static function set($key, $schema_type = 'WebPage', $title='', $desc = '', $keywords = '', $aOptionSeo=[])
    {
        if (isset($aOptionSeo['url']) && $aOptionSeo['url'] != '') {
            $aOptionSeo['canonical'] = $aOptionSeo['url'];
        }

        self::$key = $key;
        self::$title = $title;
        self::$description = $desc;
        self::$keywords = $keywords;
        self::$schema_type = $schema_type;
        self::$aOptionSeo = $aOptionSeo;
    }

    /**
     * โหลด SEO จากฐานข้อมูล (ถ้ามี)
     */
    public static function loadFromDB()
    {
        if (self::$dataLoaded) return; // ป้องกันโหลดซ้ำ
        self::$dataLoaded = true;
        
        $savelang = self::$editLang !== '' ? self::$editLang : _LANG_;
        $aData = DB_GET('site_metatags', ['meta_key' => self::$key, 'lang' => $savelang]);
        
        
        if (isset($aData['meta_id']) && !empty($aData['meta_id'])) {
            // ค่าจาก DB ถูก htmlspecialchars ไว้แล้ว → decode เป็น raw กัน double-encode ตอน output
            self::$title = $aData['title'] ? html_entity_decode($aData['title'], ENT_QUOTES) : self::$title;
            self::$description = $aData['description'] ? html_entity_decode($aData['description'], ENT_QUOTES) : self::$description;
            self::$keywords = $aData['keywords'] ? html_entity_decode($aData['keywords'], ENT_QUOTES) : self::$keywords;
            

            $aOptionSeo_Set = self::$aOptionSeo;
            $aOptionSeo_Set['title']        = self::$title;
            $aOptionSeo_Set['description']  = self::$description;
            $aOptionSeo_Set['keywords']     = self::$keywords;
            $aOptionSeo_Set['robots']       = $aData['robots'];
            $aOptionSeo_Set['googlebot']    = $aData['googlebot'];
            $aOptionSeo_Set['bingbot']       = $aData['robots'];
            $aOptionSeo_Set['author']       = html_entity_decode($aData['author'] ?? '', ENT_QUOTES);
            $aOptionSeo_Set['copyright']    = ($aData['copyright'] != '') 
            ? $aData['copyright'] 
            : ($aData['author'] != '' ? '© '._YY_.' '.$aData['author'].' All rights reserved.' : '');

            if (!empty($aData['icon'])) {
                $aOptionSeo_Set['image'] = URL_UPLOAD . '/' . $aData['icon'];
            }

            self::$aOptionSeo = $aOptionSeo_Set;

            // ถ้ามีข้อมูล schema ใน DB ให้แปลง JSON เป็น array
            if (!empty($aData['schema_json'])) {
                $decoded = json_decode($aData['schema_json'], true);
                self::$schema = $decoded;
                self::$schema_data = $aData;                
            }
        }
    }

    /**
     * เติม scheme ให้ URL แบบ protocol-relative (//host) → absolute (https://host) เพื่อ SEO/social
     */
    protected static function absUrl($url)
    {
        if (is_string($url) && strncmp($url, '//', 2) === 0) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
            return $scheme . $url;
        }
        return $url;
    }

    /**
     * Phase 2 — เติม SEO จาก record บทความ/กรุ๊ป (SEO อยู่กับ content record)
     * fallback: meta_title→title/group_name, meta_description→shortMessage/detail, og_image→icon/img
     */
    public static function setContentMeta($rec)
    {
        if (!is_array($rec) || empty($rec)) return;

        $metaTitle = trim($rec['meta_title'] ?? '');
        $metaDesc  = trim($rec['meta_description'] ?? '');
        $baseTitle = $rec['title'] ?? ($rec['group_name'] ?? '');
        $baseDesc  = $rec['shortMessage'] ?? ($rec['detail'] ?? '');

        // ค่าจาก DB ถูก htmlspecialchars ไว้แล้ว → decode เป็น raw กัน double-encode ตอน output
        self::$title       = html_entity_decode($metaTitle !== '' ? $metaTitle : $baseTitle, ENT_QUOTES);
        self::$description = html_entity_decode($metaDesc  !== '' ? $metaDesc  : mb_substr(trim(strip_tags($baseDesc)), 0, 300), ENT_QUOTES);
        self::$keywords    = html_entity_decode(trim($rec['keywords'] ?? ''), ENT_QUOTES);

        $img = trim($rec['og_image'] ?? '');
        if ($img === '') $img = trim($rec['icon'] ?? ($rec['img'] ?? ($rec['content_icon'] ?? '')));
        if ($img !== '') self::$aOptionSeo['image'] = URL_UPLOAD . '/' . $img;

        $robots = trim($rec['meta_robots'] ?? '');
        if ($robots !== '') self::$aOptionSeo['robots'] = $robots;

        // schema Article: เติมข้อมูลจริง
        global $__aDefaultSchemaTypes;
        if (is_array($__aDefaultSchemaTypes)) {
            if (!empty(self::$aOptionSeo['image'])) $__aDefaultSchemaTypes['Articles_image'] = self::absUrl(self::$aOptionSeo['image']);
            $dt = (int) ($rec['displaytime'] ?? ($rec['create_time'] ?? 0));
            if ($dt > 0) $__aDefaultSchemaTypes['Articles_datePublished'] = date('Y-m-d', $dt);
            $__aDefaultSchemaTypes['Articles_category_name'] = $rec['group_name'] ?? ($rec['category_name'] ?? '');
        }
    }

    /**
     * เติม default ระดับเว็บ (org) + สร้าง url/canonical/alternate จาก path ปัจจุบัน
     * เรียกหลัง loadFromDB() — ไม่ทับค่าที่มาจาก DB
     */
    protected static function applyDefaults()
    {
        global $__aDefaultSchemaTypes;
        $org = is_array($__aDefaultSchemaTypes) ? $__aDefaultSchemaTypes : [];
        $opt = self::$aOptionSeo;

        if (empty($opt['site_name'])) $opt['site_name'] = $org['site_name'] ?? ($org['Company'] ?? '');
        if (empty($opt['locale']))    $opt['locale']    = $org['locale'] ?? (_LANG_ === 'en' ? 'en_US' : 'th_TH');
        if (empty($opt['robots']))    $opt['robots']    = 'index, follow';

        // URL ปัจจุบัน (ไม่คำนวณในธีม) — บังคับ absolute https สำหรับ SEO/social
        $baseUrl = self::absUrl(defined('URL_WEB_ROOT') ? rtrim(URL_WEB_ROOT, '/') : '');
        $opt['domain'] = parse_url($baseUrl, PHP_URL_HOST) ?: '';
        $route   = defined('_ROUTE_NOLANG_') ? _ROUTE_NOLANG_ : '';
        $suffix  = ($route !== '' ? '/' . $route : '');
        if (empty($opt['url'])) {
            $opt['url'] = $baseUrl . '/' . _LANG_ . $suffix;
        }
        $opt['canonical'] = $opt['url'];
        if (empty($opt['alternate'])) {
            $opt['alternate'] = [
                'th'        => $baseUrl . '/th' . $suffix,
                'en'        => $baseUrl . '/en' . $suffix,
                'x-default' => $baseUrl . '/th' . $suffix,
            ];
        }

        // og image: DB icon (โหลดใน loadFromDB) > default ของ org
        if (empty($opt['image'])) {
            $opt['image'] = $org['default_image'] ?? ($org['logo'] ?? '');
        }
        $opt['image'] = self::absUrl($opt['image']);

        // sync ค่า text ให้ og/twitter ใช้ผ่าน aOption
        if (empty($opt['title']))       $opt['title']       = self::$title;
        if (empty($opt['description'])) $opt['description'] = self::$description;
        if (empty($opt['keywords']))    $opt['keywords']    = self::$keywords;

        self::$aOptionSeo = $opt;
    }

    /**
     * แสดง meta tag หลัก
     */
    public static function show()
    {
        self::loadFromDB();
        self::applyDefaults();
        if (ADMBUILDER::isLoginAdmin() && self::$key !== '') {
            echo "\n";
            echo '<meta name="aosoft-meta-key" content="' . htmlspecialchars(self::$key) . '">' . "\n";
            echo '<meta name="aosoft-schema-key" content="' . htmlspecialchars(self::$schema_type) . '">' . "\n";
            echo '<meta name="aosoft-lang" content="' . htmlspecialchars(_LANG_) . '">' . "\n";
        }

        $aOption = self::$aOptionSeo;
        echo "\n";
        echo "<title>" . htmlspecialchars(self::$title) . "</title>\n";
        $a = ['description', 'keywords', 'robots','googlebot', 'bingbot', 'author', 'copyright'];
        foreach ($a as $key) {
            if (isset($aOption[$key]) && !empty($aOption[$key])) {
                echo '<meta name="' . $key . '" content="' . htmlspecialchars($aOption[$key], ENT_QUOTES) . '">' . "\n";
            }
        }

        // Open Graph Meta Tags
        echo "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        $a = ['site_name', 'title', 'description', 'locale', 'url', 'image'];
        foreach ($a as $key) {
            if (isset($aOption[$key]) && !empty($aOption[$key])) {
                echo '<meta property="og:' . $key . '" content="' . htmlspecialchars($aOption[$key], ENT_QUOTES) . '">' . "\n";
            }
        }

        // Twitter Card Meta Tags
        echo "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:domain" content="'.htmlspecialchars($aOption['domain'] ?? '').'">' . "\n";
        $a = ['title', 'description', 'url', 'image'];
        foreach ($a as $key) {
            if (isset($aOption[$key]) && !empty($aOption[$key])) {
                echo '<meta name="twitter:' . $key . '" content="' . htmlspecialchars($aOption[$key], ENT_QUOTES) . '">' . "\n";
            }
        }

        echo "\n";
        if (isset($aOption['canonical']) && !empty($aOption['canonical'])) {
            echo '<link rel="canonical" href="'.htmlspecialchars($aOption['canonical']).'">' . "\n";
        }

        if (isset($aOption['alternate']) && count($aOption['alternate']) > 0) {
            foreach ($aOption['alternate'] as $key => $v) {
                if (!empty($v)) {
                    echo '<link rel="alternate" href="'.$v.'" hreflang="'.$key.'">' . "\n";
                }
            }
        }
    }
    
    /**
     * ดึงค่าต่าง ๆ ออกไปใช้ภายนอก
     */
    public static function get($field = null)
    {
        self::loadFromDB();
        self::applyDefaults();
        $data = [
            'key' => self::$key,
            'title' => self::$title,
            'description' => self::$description,
            'keywords' => self::$keywords,
            'schema' => self::$schema,
            'schema_type' => self::$schema_type,
            'aOptionSeo' => self::$aOptionSeo,
        ];
        return $field ? ($data[$field] ?? null) : $data;
    }

    /**
     * ตรวจจับ URL ปัจจุบัน (ใช้ใน canonical)
     */
    protected static function detectCurrentURL()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * แสดง JSON-LD (Schema.org)
     */
    public static function schema_get()
    {
        global $__aDefaultSchemaTypes;
        self::loadFromDB();
        self::applyDefaults();

        $aSchData = $__aDefaultSchemaTypes;
        $aOption = self::$aOptionSeo;

        // ถ้าไม่มี schema ใน DB ใช้ default basic schema
        $defaultSchema = [
            "@context" => "https://schema.org",
            "@type" => "WebPage",
            "name" => $aOption['title'],
            "description" => $aOption['description'],
            "url" => $aOption['url'],
        ];

        ##########################################
        ///////////////// HomePage ///////////////
        ##########################################
        $aDefschema['HomePage'] = [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => $aOption['title'],
            "url" => $aOption['url'],
            "logo" => $aSchData['logo'],
            "description" => $aOption['description'],
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $aSchData['streetAddress'],
                "addressLocality" => $aSchData['addressLocality'],
                "addressRegion" => $aSchData['addressRegion'],
                "postalCode" => $aSchData['postalCode'],
                "addressCountry" => $aSchData['addressCountry']
            ],
            "contactPoint" => [
                [
                    "@type" => "ContactPoint",
                    "telephone" => $aSchData['telephone'],
                    "contactType" => $aSchData['contactType']
                ]
            ],
            "sameAs" => $aSchData['social']
        ];

        ##########################################
        ////////////////// Service ///////////////
        ##########################################
        $aDefschema['Service'] = [
            "@context" => "https://schema.org",
            "@type" => "Service",
            "name" => $aOption['title'],
            "url" => $aOption['url'],
            "description" => $aOption['description'],
            "inLanguage" => "th",
            "provider" => [
                "@type" => "Organization",
                "name" => $aSchData['Company'],
                "sameAs" => $aSchData['CompanyBaseUrl']
            ],
            "areaServed" => [
                "@type" => "Country",
                "name" => $aSchData['areaServed']
            ],
            "serviceType" => $aSchData['serviceType']
        ];

        ##########################################
        ////////////////// WebPage ///////////////
        ##########################################
        $aDefschema['WebPage'] = [
            "@context" => "https://schema.org",
            "@type" => "WebPage",
            "name" => $aOption['title'],
            "url" => $aOption['url'],
            "description" => $aOption['description'],
            "inLanguage" => _LANG_,
            "isPartOf" => [
                "@type" => "WebSite",
                "name" => $aSchData['Company'],
                "url" => $aSchData['CompanyBaseUrl']
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => $aSchData['Company'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $aSchData['logo']
                ],
            ]
        ];

        ##########################################
        //////////////// ContactPage /////////////
        ##########################################
        $aDefschema['ContactPage'] = [
            "@context" => "https://schema.org",
            "@type" => "ContactPage",
            "name" => $aOption['title'],
            "url" => $aOption['url'],
            "description" => $aOption['description'],
            "inLanguage" => _LANG_,
            "isPartOf" => [
                "@type" => "WebSite",
                "name" => $aSchData['Company'],
                "url" => $aSchData['CompanyBaseUrl']
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => $aSchData['Company'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $aSchData['logo']
                ]
            ],
            "contactPoint" => [
                    "@type" => "ContactPoint",
                    "telephone" => $aSchData['telephone'],
                    "contactType" => $aSchData['contactType'],
                    "areaServed" => $aSchData['areaServed'],
                    "availableLanguage" => $aSchData['availableLanguage'],
                    "email" => $aSchData['email']
            ]
        ];

        ##########################################
        ///////////////// FAQPage ////////////////
        ##########################################
        $aDefschema['FAQPage'] = [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "name" => $aOption['title'],
            "mainEntity" => $aSchData['FAQList']
        ];

        ##########################################
        /////////////// ProductInner /////////////
        ##########################################
        $aDefschema['ProductInner'] = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $aSchData['ProductData']['product_name'],
            "image" => $aSchData['ProductData']['product_image'],
            "description" => $aOption['description'],
            "sku" => $aSchData['ProductData']['product_sku'],
            "brand" => [
                "@type" => "Brand",
                "name" => $aSchData['ProductData']['product_brand']
            ],
            "offers" => [
                "@type" => "Offer",
                "url" => $aOption['url'],
                "availability" => "https://schema.org/InStock",
                "seller" => [
                    "@type" => "Organization",
                    "name" => $aSchData['Company']
                ]
            ]
        ];

        ##########################################
        /////////////// ArticlesList /////////////
        ##########################################
        $aDefschema['ArticlesList'] = [
            "@context" => "https://schema.org",
            "@type" => "CollectionPage",
            "name" => $aOption['title'],
            "headline" => $aOption['title'],
            "description" => $aOption['description'],
            "url" => $aOption['url'],
            "inLanguage" => _LANG_,
            "isPartOf" => [
                "@type" => "WebSite",
                "name" => $aSchData['Company'],
                "url" => $aSchData['CompanyBaseUrl']
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => $aSchData['Company'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $aSchData['logo']
                ]
            ],
            "mainEntity" => [
                [
                    "@type" => "ItemList",
                    "itemListElement" => $aSchData['ArticlesList']
                ]
            ]
        ];

        ##########################################
        /////////////// ArticlesInner ////////////
        ##########################################
        $aDefschema['ArticlesInner'] = [
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $aOption['title'],
            "alternativeHeadline" => $aOption['title'],
            "description" => $aOption['description'],
            "image" => $aSchData['Articles_image'],
            "author" => [
                "@type" => "Person",
                "name" => $aSchData['Company']
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => $aSchData['Company'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $aSchData['logo']
                ]
            ],
            "datePublished" => $aSchData['Articles_datePublished'],
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => $aOption['url']
            ],
            "articleSection" => $aSchData['Articles_category_name'],
            "keywords" => $aOption['keywords'],
            "url" => $aOption['url'],
            "inLanguage" => _LANG_
        ];

        $aDefschemaType = self::$schema_type ?: 'WebPage';
        if (isset($aDefschema[$aDefschemaType])) {
            $defaultSchema = $aDefschema[$aDefschemaType];
        }

        return json_encode($defaultSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public static function schema_page_type()
    {
        return [
            'HomePage'  => 'Organization / บริษัททั่วไป',
            'Service'       => 'Service / บริการ',
            'WebPage'       => 'Web Page / หน้าเว็บทั่วไป',
            'ContactPage'       => 'หน้าติดต่อเรา',
            'FAQPage'       => 'FAQ Page / คำถามที่พบบ่อย',
            'ProductInner'       => 'Product / สินค้า',
            'ArticlesList'       => 'Article / บทความหรือข่าว',
            'ArticlesInner'       => 'Article / บทความหรือข่าว',
        ];
    }

    public static function schema()
    {
        self::loadFromDB();

        // แอดมินเซฟ schema_json ไว้ → ใช้ตัวนั้น (แก้ทับ auto)
        if (is_array(self::$schema) && !empty(self::$schema)) {
            echo "\n<script type='application/ld+json'>\n";
            echo json_encode(self::$schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "\n</script>\n";
            return;
        }

        // ไม่มี → auto-gen ตาม type (type ที่ไม่รู้จัก = ไม่พ่น)
        if (!array_key_exists(self::$schema_type, self::schema_page_type())) {
            return;
        }
        echo "\n<script type='application/ld+json'>\n";
        echo self::schema_get();
        echo "\n</script>\n";
    }
}
