<?php

$aSchema = DB_GET('site_metatags', ['meta_key' => $key_meta, 'lang' => $kLang]);
?>
<hr>
<div class="row" style="margin-top:10px;">
  <label class="col-md-2 control-label text-left">
    Template<br>
    <button onclick="getTemplateSchema('Home','<?php echo $kLang; ?>');" style="margin-top:10px;" type="button" class="btn btn-info">Home</button>&nbsp;
    <button onclick="getTemplateSchema('Service','<?php echo $kLang; ?>');" style="margin-top:10px;" type="button" class="btn btn-info">Service</button>&nbsp;
    <button onclick="getTemplateSchema('Product','<?php echo $kLang; ?>');" style="margin-top:10px;" type="button" class="btn btn-info">Product</button>&nbsp;
    <button onclick="getTemplateSchema('Review','<?php echo $kLang; ?>');" style="margin-top:10px;" type="button" class="btn btn-info">Review</button>&nbsp;
    <button onclick="getTemplateSchema('Article','<?php echo $kLang; ?>');" style="margin-top:10px;" type="button" class="btn btn-info">Article / Blog Post</button>&nbsp;
  </label>
  <div class="col-md-10">
    <div class="form-group">
      <label for="demo-oi-definput" class="control-label text-semibold">Schema JSON-LD (Raw)</label>
      <textarea class="form-control input_json_<?php echo $kLang; ?>" name="schema_json[<?php echo $kLang; ?>]" style="height: 600px;"><?php echo !empty($aSchema['schema_json']) ? $aSchema['schema_json'] : ''; ?></textarea>
    </div>
  </div>
</div>

<pre class="Home_<?php echo $kLang; ?>" style="display: none;">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?php echo $aSchema['author']; ?>",
  "url": "<?php echo URL_WEB_ROOT; ?>",
  "logo": "<?php echo ($aSchema['icon'] != '') ? URL_UPLOAD . '/' . $aSchema['icon'] : URL_WEB_ROOT . '/img/logo2.png'; ?>",
  "description": "<?php echo $aSchema['description']; ?>",
  "foundingDate": "บริษัทเปิดดำเนินการ",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "กรุณากรอกที่อยู่ของบริษัทของคุณที่นี่",
    "addressLocality": "กรอกจังหวัดของที่ตั้งบริษัท",
    "postalCode": "กรอกเลขรหัสไปรษณีย์",
    "addressCountry": "TH"
  },
  "contactPoint": [
    {
      "@type": "ContactPoint",
      "telephone": "+66-8-91400008",
      "contactType": "customer service",
      "areaServed": "TH",
      "availableLanguage": ["Thai", "English"]
    }
  ],
  "sameAs": [
    "https://www.facebook.com/aosoft.co.th",
    "https://www.instagram.com/aosoft.co.th",
    "https://www.linkedin.com/company/aosoft"
  ]
}
</pre>

<pre class="Product_<?php echo $kLang; ?>" style="display: none;">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Executive Anvil",
  "description": "Sleeker than ACME's Classic Anvil, perfect for business traveler.",
  "image": [
    "https://www.example.com/photos/16x9/photo.jpg",
    "https://www.example.com/photos/4x3/photo.jpg"
  ],
  "sku": "0446310786",
  "brand": {
    "@type": "Brand",
    "name": "ACME"
  },
  "offers": {
    "@type": "Offer",
    "url": "https://www.example.com/executive-anvil",
    "priceCurrency": "USD",
    "price": "119.99",
    "priceValidUntil": "2025-12-31",
    "itemCondition": "https://schema.org/NewCondition",
    "availability": "https://schema.org/InStock"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.4",
    "reviewCount": "89"
  },
  "review": [
    {
      "@type": "Review",
      "author": {
        "@type": "Person",
        "name": "Fred Benson"
      },
      "datePublished": "2025-05-01",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "4",
        "bestRating": "5"
      },
      "reviewBody": "Great product, meets my expectations."
    }
  ]
}
</pre>

<pre class="Service_<?php echo $kLang; ?>" style="display: none;">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "ชื่อบริการของคุณ",
  "description": "คำอธิบายบริการ",
  "provider": {
    "@type": "Organization",
    "name": "ชื่อบริษัทของคุณ",
    "url": "<?php echo URL_WEB_ROOT; ?>"
  },
  "serviceType": "ประเภทบริการ",
  "areaServed": "TH",
  "availableChannel": {
    "@type": "ServiceChannel",
    "serviceUrl": "https://www.yourdomain.com/service-page"
  },
  "termsOfService": "https://www.yourdomain.com/terms",
  "url": "https://www.yourdomain.com/service-page"
}
</pre>

<pre class="Article_<?php echo $kLang; ?>" style="display: none;">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "หัวข้อบทความ",
  "description": "บทคัดย่อ / คำอธิบายสั้น",
  "image": [
    "https://www.yourdomain.com/images/cover.jpg"
  ],
  "author": {
    "@type": "Person",
    "name": "ชื่อผู้เขียน"
  },
  "publisher": {
    "@type": "Organization",
    "name": "ชื่อสำนักพิมพ์ / เว็บไซต์",
    "logo": {
      "@type": "ImageObject",
      "url": "https://www.yourdomain.com/images/logo.png"
    }
  },
  "datePublished": "2025-10-15T08:00:00+07:00",
  "dateModified": "2025-10-16T10:00:00+07:00",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://www.yourdomain.com/your-article-url"
  }
}
</pre>

<pre class="Review_<?php echo $kLang; ?>" style="display: none;">
{
  "@context": "https://schema.org",
  "@type": "Review",
  "author": {
    "@type": "Person",
    "name": "ชื่อผู้รีวิว"
  },
  "datePublished": "2025-10-10",
  "reviewBody": "เนื้อหารีวิว …",
  "reviewRating": {
    "@type": "Rating",
    "ratingValue": "5",
    "bestRating": "5",
    "worstRating": "1"
  },
  "itemReviewed": {
    "@type": "Thing",
    "name": "ชื่อสินค้าหรือบริการที่รีวิว"
  }
}
</pre>