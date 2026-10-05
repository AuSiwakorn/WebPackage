<?php
$__schema_conf = SEO::schema_page_type();
$__schema_type = SEO::get('schema_type');
$__saved       = SEO::get('schema');
$__savedJson   = (is_array($__saved) && !empty($__saved)) ? json_encode($__saved, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
$__autoJson    = SEO::schema_get();
?>
<input type="hidden" name="keysname" value="<?php echo SEO::get('key'); ?>" />
<input type="hidden" name="ac" value="saveschema" />
<input type="hidden" name="savelang" value="<?php echo SEO::editLang(); ?>" />

<div class="form-group">
    <label>Schema JSON-LD <small style="color:#888;">(เว้นว่าง = ใช้ auto-gen อัปเดตตามเนื้อหาเอง)</small></label>
    <textarea id="ao_schema_editor" name="schema_json" rows="12" style="font-family:monospace; font-size:12px; width:100%;" placeholder="เว้นว่าง = ใช้ auto-gen&#10;หรือกด 'โหลด auto-gen มาแก้' แล้วปรับ"><?php echo htmlspecialchars($__savedJson); ?></textarea>
</div>

<div class="d-flex gap-2 mt-2">
    <button type="button" class="btn btn-outline-secondary btn-sm"
        onclick="document.getElementById('ao_schema_editor').value = document.getElementById('ao_schema_auto').textContent;">
        โหลด auto-gen มาแก้
    </button>
    <button type="submit" class="btn-save">บันทึก</button>
</div>

<div style="margin-top:15px;">
    <div style="font-size:12px;color:#888;margin-bottom:4px;">ตัวอย่าง auto-gen (type: <?php echo $__schema_type; ?> — <?php echo $__schema_conf[$__schema_type] ?? '-'; ?>)</div>
    <pre id="ao_schema_auto" style="background:#1e1e1e;color:#dcdcdc;padding:12px;border-radius:8px;font-size:11px;overflow:auto;max-height:300px;"><?php echo htmlspecialchars($__autoJson); ?></pre>
</div>

<div style="border:1px solid #ddd;padding:12px;font-size:12px;border-radius:12px;background:#eee;margin-top:15px;">
    KEYSNAME : <?php echo SEO::get('key'); ?> &nbsp;|&nbsp; LANG : <?php echo strtoupper(SEO::editLang()); ?>
</div>
