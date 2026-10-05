<!--Bootstrap Validator [ OPTIONAL ]-->
<script src="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-validator/bootstrapValidator.min.js"></script>
<!--Masked Input [ OPTIONAL ]-->
<script src="<?php echo TEMPLATE_URL; ?>/plugins/masked-input/jquery.maskedinput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="text/javascript">
  $(document).on('nifty.ready', function() {
    $('.msk-phone').mask('(999) 999-9999');
    $('.msk-namecard').mask('9-9999-99999-99-9');
    $('.adminStatusChange').click(function() {
      v = $(this).val();
      if (v == 'admin') {
        $('.checkall').prop('checked', 'checked');
        checkAllList();
      }
    });
    refreshHeadMasters();
    if ($.fn.select2) { $('.preset-select2').select2({ width: '100%', placeholder: 'พิมพ์ค้นหา preset...' }); }
    if (typeof presetModules !== 'undefined' && $('.roperm').length) {
      window.roUpdateGrid = function() {
        var pid = parseInt($('select[name=preset_id]').val() || 0, 10);
        var mod = presetModules[pid] || '';
        var isAll = (mod === 'all');
        var keys = mod.split(',');
        $('.roperm').each(function() { this.checked = isAll || keys.indexOf(this.value) !== -1; });
        $('[id^=rocount-]').each(function() {
          var grp = this.id.replace('rocount-', '');
          var items = $('.roperm.rohead-' + grp);
          var total = items.length, on = items.filter(':checked').length;
          this.textContent = '(' + on + '/' + total + ')';
          var $c = $(this);
          $c.removeClass('perm-count-full perm-count-some');
          if (total > 0 && on === total) { $c.addClass('perm-count-full'); }
          else if (on > 0) { $c.addClass('perm-count-some'); }
          $('#rohead-master-' + grp).prop('checked', on > 0);
        });
      };
      $('select[name=preset_id]').on('change', window.roUpdateGrid);
      window.roUpdateGrid();
    }
  });

  flatpickr("#datePicker", {
    dateFormat: "d-m-Y",
  });

  function checkAllList() {
    if ($('.checkall').is(':checked')) {
      $(':checkbox.checkpoint').prop('checked', 'checked');
      $(':checkbox.checkallmodule').prop('disabled', true);
      $(':checkbox.checkallmodule').prop('checked', '');
    } else {
      $(':checkbox.checkpoint').prop('checked', '');
      $(':checkbox.checkallmodule').prop('disabled', false);
    }
    refreshHeadMasters();
  }

  function checkAllModule(module) {
    if ($('.checkall-' + module).is(':checked')) {
      $(':checkbox.' + module).prop('checked', 'checked');
    } else {
      $(':checkbox.' + module).prop('checked', '');
    }
    refreshHeadMasters();
  }

  function checkAllHead(grp) {
    if ($('.checkhead-' + grp).is(':checked')) {
      $(':checkbox.head-' + grp).prop('checked', 'checked');
    } else {
      $(':checkbox.head-' + grp).prop('checked', '');
    }
    syncHeadMaster(grp);
  }

  function syncHeadMaster(grp) {
    var $items = $(':checkbox.head-' + grp);
    var total = $items.length, on = $items.filter(':checked').length;
    $(':checkbox.checkhead-' + grp).prop('checked', on > 0);
    var $c = $('#permcount-' + grp);
    if ($c.length) {
      $c.text('(' + on + '/' + total + ')');
      $c.removeClass('perm-count-full perm-count-some');
      if (total > 0 && on === total) { $c.addClass('perm-count-full'); }
      else if (on > 0) { $c.addClass('perm-count-some'); }
    }
  }

  function refreshHeadMasters() {
    $(':checkbox.checkallhead').each(function() {
      syncHeadMaster(this.id.replace('checkallhead-', ''));
    });
  }

  $(document).on('change', '.perm-grouphead', function() {
    var grp = $(this).data('grp');
    if (grp) { $(':checkbox.head-' + grp).prop('checked', this.checked); syncHeadMaster(grp); }
  });

  $(':checkbox.checkpoint').on('change', function(e) {
    if ($(this).is(':checked') == false && $('.checkall').is(':checked') == true) {
      $('.checkall').prop('checked', '');
      $(':checkbox.checkallmodule').prop('disabled', false);
    }
    var cid = $(this).closest('.collapse').attr('id');
    if (cid && cid.indexOf('grp-') === 0) { syncHeadMaster(cid.substring(4)); }
  });

  function changepasscheck(c) {
    if (c.checked) {
      $('#changepassword').before('<div class="form-group"><ul id="cmessage"><li id="clangth">จำนวนตัวอักษร 8 ตัวขึ้นไป</li><li id="cletter">ต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว</li><li id="ccapital">ต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว</li><li id="cspacialchar">ต้องมีอักขระพิเศษอย่างน้อย 1 ตัว</li><li id="cnumber">ต้องมีตัวเลขอย่างน้อย 1 ตัว</li><li id="comfpass">ยืนยันรหัสผ่านตรงกัน</li></ul></div>');
      $('.changepasscheck').removeAttr('disabled');
      $('.changepasscheck2').removeAttr('disabled');
      $('.changepasscheck').after('<span toggle="#password" class="fa fa-fw fa-eye field-icon toggle-password" onclick="dpass(\'toggle-password\', \'password\');"></span>');
      $('.changepasscheck2').after('<span class="fa fa-fw fa-eye field-icon toggle-password2" onclick="dpass(\'toggle-password2\', \'password2\');"></span>');
      passwordValidate();
    } else {
      $('.changepasscheck').attr('disabled', 'disabled');
      $('.changepasscheck2').attr('disabled', 'disabled');
      $('#cmessage').parent().remove();
      $('.toggle-password').remove();
      $('.toggle-password2').remove();
    }
  }

  function passwordValidate() {
    var myInput = document.getElementById("pwdf") ? document.getElementById("pwdf") : document.getElementById("password");
    var myInputCf = document.getElementById("pwdfcf") ? document.getElementById("pwdfcf") : document.getElementById("password2");
    var letter = document.getElementById("cletter");
    var capital = document.getElementById("ccapital");
    var number = document.getElementById("cnumber");
    var length = document.getElementById("clangth");
    var cspacialchar = document.getElementById("cspacialchar");
    var comfpass = document.getElementById("comfpass");

    myInputCf.onkeyup = function() {
      if (myInput.value == myInputCf.value && myInput.value !== "" && myInputCf.value !== "") {
        comfpass.classList.remove("invalid");
        comfpass.classList.add("valid");
      } else {
        comfpass.classList.remove("valid");
        comfpass.classList.add("invalid");
      }
    }

    myInput.onkeyup = function() {

      // Validate lowercase letters
      var lowerCaseLetters = /[a-z]/g;
      if (myInput.value.match(lowerCaseLetters)) {
        letter.classList.remove("invalid");
        letter.classList.add("valid");
      } else {
        letter.classList.remove("valid");
        letter.classList.add("invalid");
      }

      // Validate capital letters
      var upperCaseLetters = /[A-Z]/g;
      if (myInput.value.match(upperCaseLetters)) {
        capital.classList.remove("invalid");
        capital.classList.add("valid");
      } else {
        capital.classList.remove("valid");
        capital.classList.add("invalid");
      }

      var cspacialcharCase = /[`!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/g;
      if (myInput.value.match(cspacialcharCase)) {
        cspacialchar.classList.remove("invalid");
        cspacialchar.classList.add("valid");
      } else {
        cspacialchar.classList.remove("valid");
        cspacialchar.classList.add("invalid");
      }

      // Validate numbers
      var numbers = /[0-9]/g;
      if (myInput.value.match(numbers)) {
        number.classList.remove("invalid");
        number.classList.add("valid");
      } else {
        number.classList.remove("valid");
        number.classList.add("invalid");
      }

      if (myInput.value == myInputCf.value && myInput.value !== "" && myInputCf.value !== "") {
        comfpass.classList.remove("invalid");
        comfpass.classList.add("valid");
      } else {
        comfpass.classList.remove("valid");
        comfpass.classList.add("invalid");
      }

      // Validate length
      if (myInput.value.length >= 8) {
        length.classList.remove("invalid");
        length.classList.add("valid");
      } else {
        length.classList.remove("valid");
        length.classList.add("invalid");
      }
    }

    var formSubmit = document.getElementById("changepass") ? document.getElementById("changepass") : document.getElementById("form1");

    formSubmit.onsubmit = function() {
      if (myInput.value == '') {
        return true;
      }
      var cspacialcharCase = /[`!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/g;
      if (myInput.value.match(cspacialcharCase) && myInput.value == myInputCf.value) {
        return true;
      } else {
        myInput.focus();
        return false;
      }
    };
  }

  <?php if ($_REQUEST['mp'] == 'changePass' || $_REQUEST['mp'] == 'admin_add') { ?>
    passwordValidate();
  <?php } ?>

  function AddResetPass() {
    document.getElementById("pwsf").value = '<?php echo PW_RESET; ?>';
  }

  function readURL(input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();

      reader.onload = function(e) {
        $('.blah')
          .attr('src', e.target.result).width(150);
      };

      reader.readAsDataURL(input.files[0]);
    }
    $('.displayselect').fadeIn();
  }

  function dpass(sname, inputid) {
    $('.' + sname).toggleClass("fa-eye fa-eye-slash");
    var input = $('#' + inputid);
    if (input.attr("type") == "password") {
      input.attr("type", "text");
    } else {
      input.attr("type", "password");
    }
  }

  function resetBirthday() {
    document.getElementById("datePicker").value = '';
  }
</script>
<script type="text/javascript">
  $(document).on('change', '.assign-preset', function () {
    var sel = $(this);
    var uid = sel.data('uid');
    var pid = sel.val();
    var st  = sel.siblings('.assign-status');
    st.html('<i class="fa fa-spinner fa-spin text-muted"></i>');
    $.getJSON('doAjax.php?module=member&mp=group&ac=assign_preset&user_id=' + uid + '&preset_id=' + pid, function (obj) {
      if (obj && obj.ok) {
        st.html('<span class="text-success"><i class="fa fa-check"></i> บันทึกแล้ว</span>');
        sel.closest('tr').css('background-color', '#dff0d8');
      } else {
        st.html('<span class="text-danger"><i class="fa fa-times"></i> ' + ((obj && obj.msg) ? obj.msg : 'ผิดพลาด') + '</span>');
      }
    }).fail(function () {
      st.html('<span class="text-danger"><i class="fa fa-times"></i> เชื่อมต่อไม่ได้</span>');
    });
  });
</script>
<?php if ((REQ_get('mp', 'get', 'str', '') === 'edit_group')) { ?>
<script src="<?php echo TEMPLATE_URL; ?>/plugins/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
	(function () {
		function wdgSyncOrder() {
			var ids = [];
			$('#wdgSortAll .wdg-item').each(function () { ids.push($(this).attr('data-id')); });
			$('#widget_order').val(ids.join(','));
		}
		function wdgSyncDim() {
			$('.wdg-item').each(function () {
				$(this).toggleClass('wdg-off', !$(this).find('.wdg-check').is(':checked'));
			});
		}
		$(function () {
			if ($.fn.sortable && $('#wdgSortAll').length) {
				$('#wdgSortAll').sortable({
					handle: '.wdg-handle',
					placeholder: 'wdg-placeholder',
					forcePlaceholderSize: true,
					axis: 'y',
					update: wdgSyncOrder
				});
				wdgSyncOrder();
				wdgSyncDim();
				$('#form1').on('change click', function () { setTimeout(wdgSyncDim, 0); });
				$('#form1').on('submit', wdgSyncOrder);
			}
		});
	})();
</script>
<?php } ?>