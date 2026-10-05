    <script src="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-tagsinput/bootstrap-tagsinput.min.js?<?php echo CACHE_VERSION; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/summernote/summernote.min.js?<?php echo CACHE_VERSION; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/js/demo/form-text-editor.js?<?php echo CACHE_VERSION; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-select/bootstrap-select.min.js?<?php echo CACHE_VERSION; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/select2/js/select2.min.js?<?php echo CACHE_VERSION; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js?<?php echo CACHE_VERSION; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/datatables/media/js/jquery.dataTables.js?v=<?php echo _TIME_; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/datatables/media/js/dataTables.bootstrap.js?v=<?php echo _TIME_; ?>"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/datatables/extensions/Responsive/js/dataTables.responsive.min.js?v=<?php echo _TIME_; ?>"></script>


    <script type="text/javascript">
        $('#demo-dp-component .input-group.date').datepicker({
            todayBtn: "linked",
            autoclose: true,
            todayHighlight: true
        });

        function confirmPostAction() {
            if (confirm('Please confirm delete selected article?')) {
                $('.ac').val('deletearticle');
                return true;
            } else {
                return false;
            }
        }

        function readImageURL(input, num = '') {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const result = e.target.result;
                    $('.blah' + num).attr('src', result)
                    $('.icon' + num).attr('value', result);
                    $('.displayselect' + num + ' a').off('click').on('click', (event) => {
                        event.preventDefault();
                        const newWindow = window.open();
                        newWindow.document.write(`
                            <html>
                                <head>
                                    <title>Image Preview</title>
                                    <style>
                                        body {
                                            margin: 0;
                                            display: flex;
                                            justify-content: center;
                                            align-items: center;
                                            height: 100vh;
                                            background-color: rgb(14, 14, 14);
                                        }
                                        img {
                                            max-width: 100%;
                                            max-height: 100%;
                                        }
                                    </style>
                                </head>
                                <body>
                                    <img src="${result}" alt="Image Preview">
                                </body>
                            </html>
                        `);
                        newWindow.document.close();
                    });
                };
                reader.readAsDataURL(input.files[0]);
            }
            $('#isIconView' + num).val('isicontrue');
            $('#showfile' + num).css('display', '');
            $('.displayselect' + num).css('display', '');
        }

        function hiddenImage(num = '') {
            $('#iconview' + num).val('');
            $('.icon' + num).attr('value', '');
            $('#isIconView' + num).val('isiconfalse');
            $('#textDel' + num).css('display', 'none');
            $('#showfile' + num).css('display', 'none');
            $('.displayselect' + num).css('display', 'none');
        }

        $(document).ready(function() {
            $('#picture').DataTable({
                "paging": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "lengthChange": true,
                "order": [
                    [0, "desc"]
                ],
            });
        });
        $(document).ready(function() {
            $('#fileAttach').DataTable({
                "paging": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "lengthChange": true,
                "order": [
                    [0, "desc"]
                ],
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-icon').forEach(icon => {
                icon.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const isExpanded = this.textContent === '[-]';

                    if (isExpanded) {
                        this.textContent = '[+]';
                        hideChildren(id, true);
                    } else {
                        this.textContent = '[-]';
                        document.querySelectorAll('tr[data-parent="' + id + '"]').forEach(row => {
                            row.dataset.hidden = "false";
                            row.style.display = "table-row";
                        });
                    }
                });
            });

            function hideChildren(id, resetToggle = false) {
                document.querySelectorAll('tr[data-parent="' + id + '"]').forEach(child => {
                    child.dataset.hidden = "true";
                    child.style.display = "none";
                    if (resetToggle) {
                        const toggle = document.querySelector('.toggle-icon[data-id="' + child.dataset.id + '"]');
                        if (toggle) toggle.textContent = '[+]';
                    }
                    hideChildren(child.dataset.id, resetToggle);
                });
            }
        });
    </script>

    <!-- 
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/bootbox/bootbox.min.js?<?php echo CACHE_VERSION; ?>"></script>
	<script type="text/javascript">
	$('#demo-bootbox-flip').on('click', function(){
        bootbox.confirm({
            message : 
                "<p class='text-semibold text-main'> Title XXX </p>"+
                "<p> Content XXX </p>",
            buttons: {
                confirm: {
                    label: "Save"
                }
            },
            callback : function(result) {
                //Callback function here
            },
            animateIn: 'flipInX',
            animateOut : 'flipOutX'
        });
    });
	</script>
	 -->