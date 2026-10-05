<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/htmx.org@1.9.12"></script>
<script src="/admbuilder/include/script.js?v=<?php echo time(); ?>" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<?php 
$editMode = SiteConfig_get('onoff_aobuilder');
if ($editMode == 'on') {
    echo '<script src="/admbuilder/include/script.php"></script>';
}
?>
<?php if ($isMain==='main') { ?>
    <script src="/admbuilder/include/script.main.js?v=<?php echo time(); ?>"></script>
<?php } ?>