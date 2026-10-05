<script src="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-tagsinput/bootstrap-tagsinput.min.js?<?php echo CACHE_VERSION; ?>"></script>
<script src="<?php echo TEMPLATE_URL; ?>/plugins/summernote/summernote.min.js?<?php echo CACHE_VERSION; ?>"></script>
<script src="<?php echo TEMPLATE_URL; ?>/js/demo/form-text-editor.js?<?php echo CACHE_VERSION; ?>"></script>
<script src="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-select/bootstrap-select.min.js?<?php echo CACHE_VERSION; ?>"></script>
<script src="<?php echo TEMPLATE_URL; ?>/plugins/select2/js/select2.min.js?<?php echo CACHE_VERSION; ?>"></script>
<script src="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js?<?php echo CACHE_VERSION; ?>"></script>

<script type="text/javascript">
    $('#demo-dp-component .input-group.date').datepicker({
        todayBtn: "linked",
        autoclose: true,
        todayHighlight: true
    });

    function mOvr(src, clrOver) {
        src.style.cursor = 'hand';
        src.bgColor = clrOver;
    }

    function mOut(src, clrIn) {
        src.style.cursor = 'default';
        src.bgColor = clrIn;
    }

    function ConfirmDelete() {
        if (confirm('Do you really want to delete the information you choose?')) {
            document.frmList.submit();
        }
    }

    function selectRow(row) {
        document.getElementById(row).style.background = "#D6DEEC";
    }

    function deselectRow(row, color) {
        document.getElementById(row).style.background = color;
    }

    function TestSentEmail(formtestmail) {
        $('.HistoryTest').html('Loading...');
        $('#gotestmain').hide();
        $('.loading').fadeIn();
        $.ajax({
            type: "POST",
            url: "doAjax.php?module=siteconfig&mp=emailconnect&ac=test",
            data: $("#" + formtestmail).serialize(),
            success: function(data) {
                $('.sender2').val($('input[name="smtp_sender"]').val());
                $('.HistoryTest').html(data);
                $('.loading').hide();
                $('#gotestmain').fadeIn();
            },
            error: function(data) {
                $('.HistoryTest').html(data.responseText);
                $('.loading').hide();
                $('#gotestmain').fadeIn();
            }
        });
    }

    function setHashAction(ac) {
        $('#ac').val(ac);
    }

    function chackHashAll(hash) {
        if ($('.checkHash-' + hash).is(':checked')) {
            $(':checkbox.' + hash).prop('disabled', true);
            $(':checkbox.' + hash).prop('checked', false);
            $('label.' + hash).css('color', 'gray');
        } else {
            $(':checkbox.' + hash).prop('disabled', false);
            $('label.' + hash).css('color', '');
        }
    }

    function modalIgnore(folder) {
        $('#modal-head').html(folder);
        $.ajax({
            url: 'doAjax.php?module=siteconfig&mp=read_ignore',
            method: 'POST',
            data: {
                folder: folder,
            },
            dataType: 'html',
            success: function(data) {
                $("#modal-data").html(data);
            },
            error: function(xhr, status, error) {
                console.error("Error: " + error);
            }
        });
    }

    function resetIgnore() {
        $('#modal-data input[type="checkbox"]').prop('checked', false);
        $('#modal-data input[type="checkbox"]').prop('disabled', false);
        $('#modal-data label').css('color', '');
    }
</script>