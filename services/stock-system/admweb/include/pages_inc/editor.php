<?php
/*
 class="ckeditor"
 http://docs.ckeditor.com/#!/api/CKEDITOR.config
 
 [
 { name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates' ] },
 { name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
 { name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
 { name: 'forms', items: [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
 '/',
 { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
 { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl' ] },
 { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
 { name: 'insert', items: [ 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe' ] },
 '/',
 { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
 { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
 { name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
 { name: 'others', items: [ '-' ] },
 { name: 'about', items: [ 'About' ] }
 ];
 */

$isSecurMode = GlobalConfig_get('isSecurMode');
if ($isOpens['EDTNAME'] == 'ckeditor5') { ?>
	<link rel="stylesheet" href="/admweb/include/editor/ckeditor5/ckeditor5.css">
	<script type="importmap">
		{
        "imports": {
            "ckeditor5": "/admweb/include/editor/ckeditor5/ckeditor5.js",
            "ckeditor5/": "/admweb/include/editor/ckeditor5/"
        }
    }
</script>
	<script>
		const editorLanguages = <?php echo json_encode(array_keys($aConfig['language'])); ?>;
		const ckeditorFeatureConfig = {
			codeBlock: <?php echo $aConfig['codeBlock'] ? 'true' : 'false'; ?>,
			insertHTML: <?php echo $aConfig['insertHTML'] ? 'true' : 'false'; ?>
		};
	</script>
	<script type="module" src="/admweb/include/editor/ckeditor5/main.js"></script>
	<script src="/admweb/include/editor/ckfinder/ckfinder.js"></script>
<?php } else { ?>
	<script type="text/javascript" src="include/editor/ckeditor/ckeditor.js"></script>
	<script type="text/javascript">
		CKEDITOR.config.height = <?php echo @$aConfig['editor_height']; ?>;

		<?php if ($isSecurMode == 1) { ?>
			CKEDITOR.config.toolbar = [{
					name: 'document',
					groups: ['mode', 'document', 'doctools'],
					items: ['Source', '-', 'Print']
				},
				{
					name: 'basicstyles',
					groups: ['basicstyles', 'cleanup'],
					items: ['Bold', 'Italic', 'Underline', 'Strike']
				},
				{
					name: 'paragraph',
					groups: ['list', 'indent', 'blocks', 'align', 'bidi'],
					items: ['NumberedList', 'BulletedList', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-']
				},
				{
					name: 'links',
					items: ['Link', 'Unlink', 'HorizontalRule']
				},
				{
					name: 'styles',
					items: ['Format', 'FontSize', 'TextColor', 'BGColor', 'Maximize']
				}
			];
		<?php } else { ?>
			CKEDITOR.config.toolbar = [{
					name: 'document',
					groups: ['mode', 'document', 'doctools'],
					items: ['Source', '-', 'Save', 'Print']
				},
				{
					name: 'clipboard',
					groups: ['clipboard', 'undo'],
					items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']
				},
				{
					name: 'basicstyles',
					groups: ['basicstyles', 'cleanup'],
					items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript']
				},
				{
					name: 'paragraph',
					groups: ['list', 'indent', 'blocks', 'align', 'bidi'],
					items: ['NumberedList', 'BulletedList', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-']
				},
				'/',
				{
					name: 'links',
					items: ['Link', 'Unlink', 'Image', 'Flash', 'Table', 'HorizontalRule', 'SpecialChar', 'Iframe']
				},
				{
					name: 'styles',
					items: ['Styles', 'Format', 'Font', 'FontSize', 'TextColor', 'BGColor', 'Maximize']
				}
			];
		<?php } ?>
		<?php if (@$aConfig['editor_hide_upload'] != true && $isSecurMode != 1) { ?>
			CKEDITOR.config.filebrowserBrowseUrl = 'include/editor/ckfinder/ckfinder.html';
			CKEDITOR.config.filebrowserImageBrowseUrl = 'include/editor/ckfinder/ckfinder.html?Type=Images';
			CKEDITOR.config.filebrowserFlashBrowseUrl = 'include/editor/ckfinder/ckfinder.html?Type=Flash';
			CKEDITOR.config.filebrowserUploadUrl = 'include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
			CKEDITOR.config.filebrowserImageUploadUrl = 'include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
			CKEDITOR.config.filebrowserFlashUploadUrl = 'include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';
		<?php } ?>
	</script>
<?php } ?>

<?php
/*
$aConfig['editor_custom'] = "
			CKEDITOR.stylesSet.add( 'default',
			[
				  { name : 'dbozonex', element : 'span', attributes : { 'class' : 'dbozonex' } },
				  { name : 'dbozonex size 12', element : 'span', attributes : { 'class' : 'dbozonex_size12' } },
				  { name : 'dbozonex size 14', element : 'span', attributes : { 'class' : 'dbozonex_size14' } },
				  { name : 'dbozonex size 16', element : 'span', attributes : { 'class' : 'dbozonex_size16' } },
				  { name : 'dbozonex size 18', element : 'span', attributes : { 'class' : 'dbozonex_size18' } },
				  { name : 'dbozonex size 20', element : 'span', attributes : { 'class' : 'dbozonex_size20' } },
			]);
";
*/
?>