<?php
$__optionseo = SEO::get('aOptionSeo');
$__robots    = $__optionseo['robots'] ?? 'index, follow';
$__img       = $__optionseo['image'] ?? '';
$__author    = $__optionseo['author'] ?? '';
$__robotOpts = [
    'index, follow'               => 'หน้าทั่วไป (index, follow)',
    'noindex, nofollow'           => 'ไม่ให้ Google เก็บ (noindex, nofollow)',
    'index, follow, noarchive'    => 'ไม่ให้เก็บ cache (noarchive)',
    'index, follow, noimageindex' => 'ไม่ให้รูปขึ้น image search (noimageindex)',
];
?>
<input type="hidden" name="keysname" value="<?php echo SEO::get('key'); ?>" />
<input type="hidden" name="ac" value="savemeta" />
<input type="hidden" name="savelang" value="<?php echo SEO::editLang(); ?>" />

<div class="form-group">
    <label>Meta Title <small class="ao-counter" data-for="ao_meta_title" data-max="60"></small></label>
    <input type="text" id="ao_meta_title" name="meta_title" maxlength="120"
        placeholder="ชื่อหน้าเว็บ (แนะนำ ≤ 60 ตัวอักษร)" value="<?php echo SEO::get('title'); ?>" />
</div>

<div class="form-group">
    <label>Meta Description <small class="ao-counter" data-for="ao_meta_desc" data-max="160"></small></label>
    <textarea id="ao_meta_desc" name="meta_description" rows="3" maxlength="320"
        placeholder="คำอธิบายสั้น ๆ (แนะนำ ≤ 160 ตัวอักษร)"><?php echo SEO::get('description'); ?></textarea>
</div>

<div class="form-group">
    <label>Meta Keywords</label>
    <input type="text" name="meta_keywords" placeholder="คั่นด้วยจุลภาค เช่น คีย์เวิร์ดหลัก, บริการ, บริษัท"
        value="<?php echo SEO::get('keywords'); ?>" />
</div>

<div class="form-group">
    <label>Robots</label>
    <select name="robots">
        <?php foreach ($__robotOpts as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo ($__robots === $val ? 'selected' : ''); ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Author</label>
    <input type="text" name="author" placeholder="เช่น Your Company Co., Ltd." value="<?php echo $__author; ?>" />
</div>

<div class="form-group">
    <label>OG Image (รูปตอนแชร์)</label>
    <?php if (!empty($__img)): ?>
        <div style="margin-bottom:8px;"><img src="<?php echo $__img; ?>" alt="OG image" style="max-width:100%;border-radius:8px;" /></div>
    <?php endif; ?>
    <input type="file" name="filemeta" accept="image/*" />
</div>

<div class="text-end mt-3">
    <button type="submit" class="btn-save">บันทึก</button>
</div>

<div style="border:1px solid #ddd;padding:12px;font-size:12px;border-radius:12px;background:#eee;margin-top:15px;">
    KEYSNAME : <?php echo SEO::get('key'); ?> &nbsp;|&nbsp; LANG : <?php echo strtoupper(SEO::editLang()); ?><br>
    Copyright : <?php echo $__optionseo['copyright'] ?? ''; ?>
</div>
