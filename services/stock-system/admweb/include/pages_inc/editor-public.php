<?php
/*
class="ckeditor"
http://docs.ckeditor.com/#!/api/CKEDITOR.config
*/
?>
<script src="<?php echo URL_ADMIN; ?>/include/editor/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
CKEDITOR.config.height = <?php echo @$aConfig['editor_height']; ?>;
CKEDITOR.config.toolbar = [
                       	{ name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source', '-', 'Save', 'Print' ] },
                    	{ name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
                    	{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript' ] },
                    	{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-' ] },
                    	'/',
                    	{ name: 'links', items: [ 'Link', 'Unlink', 'Image', 'Flash', 'Table', 'HorizontalRule', 'SpecialChar', 'Iframe' ] },
                    	{ name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize', 'TextColor', 'BGColor', 'Maximize'] }
                    ];
<?php if (@$aConfig['editor_hide_upload'] != true) { ?>
CKEDITOR.config.filebrowserBrowseUrl = '<?php echo URL_ADMIN; ?>/include/editor/ckfinder/ckfinder.html';
CKEDITOR.config.filebrowserImageBrowseUrl = '<?php echo URL_ADMIN; ?>/include/editor/ckfinder/ckfinder.html?Type=Images';
CKEDITOR.config.filebrowserFlashBrowseUrl = '<?php echo URL_ADMIN; ?>/include/editor/ckfinder/ckfinder.html?Type=Flash';
CKEDITOR.config.filebrowserUploadUrl =  '<?php echo URL_ADMIN; ?>/include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
CKEDITOR.config.filebrowserImageUploadUrl = '<?php echo URL_ADMIN; ?>/include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
CKEDITOR.config.filebrowserFlashUploadUrl = '<?php echo URL_ADMIN; ?>/include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';
<?php } ?>
</script>
