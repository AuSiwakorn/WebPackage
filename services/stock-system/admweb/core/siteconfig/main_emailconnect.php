<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Emailconnect ได้', 'redirect', 'SET');

$email_mode = GlobalConfig_get('email_mode', 'smtp');

$smtp_sender     = GlobalConfig_get('smtp_sender');
$smtp_sendername = GlobalConfig_get('smtp_sendername');

$smtp_host         = GlobalConfig_get('smtp_host');
$smtp_port         = GlobalConfig_get('smtp_port');
$smtp_user         = GlobalConfig_get('smtp_user');
$smtp_pass_encoded = GlobalConfig_get('smtp_pass');
$smtp_pass         = base64_decode(base64_decode($smtp_pass_encoded));
$SMTPSecure        = GlobalConfig_get('SMTPSecure');

$msgraph_client_id     = GlobalConfig_get('msgraph_client_id');
$msgraph_secret_encoded = GlobalConfig_get('msgraph_client_secret');
$msgraph_client_secret = base64_decode(base64_decode($msgraph_secret_encoded));
$msgraph_tenant_id     = GlobalConfig_get('msgraph_tenant_id');

$gmail_client_id       = GlobalConfig_get('gmail_client_id');
$gmail_secret_encoded = GlobalConfig_get('gmail_client_secret');
$gmail_client_secret = base64_decode(base64_decode($gmail_secret_encoded));
$gmail_token_encoded = GlobalConfig_get('gmail_refresh_token');
$gmail_refresh_token = base64_decode(base64_decode($gmail_token_encoded));

$smtpoauth_client_id     = GlobalConfig_get('smtpoauth_client_id');
$smtpoauth_secret_encoded = GlobalConfig_get('smtpoauth_client_secret');
$smtpoauth_client_secret = base64_decode(base64_decode($smtpoauth_secret_encoded));
$smtpoauth_tenant_id     = GlobalConfig_get('smtpoauth_tenant_id');

?>

<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">E-mail Connection</h1>
	</div>
	<ol class="breadcrumb">
		<li><a href="#"><i class="demo-pli-home"></i></a></li>
		<li class="active">E-mail Connection</li>
	</ol>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-lg-12"><?php displayRaiseMsg(); ?></div>

		<div class="col-lg-3">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">System Config</h3>
				</div>
				<div class="panel-body">
					<form id="savesmtp" action="">

						<div class="form-group bg-light p-2">
							<label>เลือกโหมดการส่ง</label>
							<select name="email_mode" id="email_mode" class="form-control" onchange="toggleConfigMode(this.value)">
								<option value="smtp" <?php echo ($email_mode == 'smtp') ? 'selected' : ''; ?>>SMTP (PHPMailer)</option>
								<option value="smtp_oauth2" <?php echo ($email_mode == 'smtp_oauth2') ? 'selected' : ''; ?>>SMTP (OAuth2 Microsoft)</option>
								<option value="msgraph" <?php echo ($email_mode == 'msgraph') ? 'selected' : ''; ?>>Microsoft Graph API</option>
								<option value="gmail" <?php echo ($email_mode == 'gmail') ? 'selected' : ''; ?>>Gmail API</option>
							</select>
						</div>

						<div id="config-smtp" class="config-block" style="display: <?php echo ($email_mode == 'smtp' || $email_mode == '') ? 'block' : 'none'; ?>;">
							<div class="form-group">
								<label>SMTP Host</label>
								<input type="text" class="form-control" name="smtp_host" value="<?php echo $smtp_host; ?>">
							</div>
							<div class="form-group">
								<label>SMTP Port</label>
								<input type="text" class="form-control" name="smtp_port" value="<?php echo $smtp_port; ?>">
							</div>
							<div class="form-group">
								<label>SMTP Username</label>
								<input type="text" class="form-control" name="smtp_user" value="<?php echo $smtp_user; ?>">
							</div>
							<div class="form-group">
								<label>SMTP Password</label>
								<input type="password" class="form-control" name="smtp_pass" value="" placeholder="<?php echo !empty($smtp_pass) ? 'ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน' : 'กรุณาใส่รหัสผ่าน'  ?>">
							</div>
							<div class="form-group">
								<label>SMTP Secure</label>
								<select name="SMTPSecure" class="form-control">
									<option <?php echo ($SMTPSecure == '') ? ' selected="selected" ' : ''; ?> value="">NONE</option>
									<option <?php echo ($SMTPSecure == 'tls') ? ' selected="selected" ' : ''; ?> value="tls">TLS</option>
									<option <?php echo ($SMTPSecure == 'ssl') ? ' selected="selected" ' : ''; ?> value="ssl">SSL</option>
								</select>
							</div>
						</div>

						<div id="config-msgraph" class="config-block" style="display: <?php echo ($email_mode == 'msgraph') ? 'block' : 'none'; ?>;">
							<div class="form-group">
								<label>Client ID</label>
								<input type="text" class="form-control" name="msgraph_client_id" value="<?php echo $msgraph_client_id; ?>">
							</div>
							<div class="form-group">
								<label>Tenant ID</label>
								<input type="text" class="form-control" name="msgraph_tenant_id" value="<?php echo $msgraph_tenant_id; ?>">
							</div>
							<div class="form-group">
								<label>Client Secret</label>
								<input type="password" class="form-control" name="msgraph_client_secret" value="" placeholder="<?php echo !empty($msgraph_client_secret) ? 'ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน' : 'กรุณาใส่ Client Secret'  ?>">
							</div>
						</div>

						<div id="config-smtp_oauth2" class="config-block" style="display: <?php echo ($email_mode == 'smtp_oauth2') ? 'block' : 'none'; ?>;">
							<div class="form-group">
								<label>SMTP Server / Port</label>
								<div class="input-group">
									<input type="text" class="form-control" value="smtp.office365.com" readonly>
									<span class="input-group-addon">:</span>
									<input type="text" class="form-control" style="width:80px;" value="587" readonly>
								</div>
							</div>
							<div class="form-group">
								<label>Client ID</label>
								<input type="text" class="form-control" name="smtpoauth_client_id" value="<?php echo $smtpoauth_client_id; ?>">
							</div>
							<div class="form-group">
								<label>Tenant ID</label>
								<input type="text" class="form-control" name="smtpoauth_tenant_id" value="<?php echo $smtpoauth_tenant_id; ?>" onkeyup="document.getElementById('token_url_preview').innerText = 'https://login.microsoftonline.com/'+this.value+'/oauth2/v2.0/token';">
							</div>
							<div class="form-group">
								<label>Client Secret</label>
								<input type="password" class="form-control" name="smtpoauth_client_secret" value="" placeholder="<?php echo !empty($smtpoauth_client_secret) ? 'ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน' : 'กรุณาใส่ Client Secret'  ?>">
							</div>
							<div class="form-group">
								<label class="text-muted"><small>Token Endpoint URL (Auto-generated)</small></label>
								<div class="well well-sm" style="word-break: break-all; margin-bottom: 0;">
									<small id="token_url_preview" class="text-info">https://login.microsoftonline.com/<?php echo empty($smtpoauth_tenant_id) ? '{tenant_id}' : $smtpoauth_tenant_id; ?>/oauth2/v2.0/token</small>
								</div>
							</div>
						</div>

						<div id="config-gmail" class="config-block" style="display: <?php echo ($email_mode == 'gmail') ? 'block' : 'none'; ?>;">
							<div class="form-group">
								<label>Client ID</label>
								<input type="text" class="form-control" name="gmail_client_id" value="<?php echo $gmail_client_id; ?>">
							</div>
							<div class="form-group">
								<label>Client Secret</label>
								<input type="password" class="form-control" name="gmail_client_secret" value="" placeholder="<?php echo !empty($gmail_client_secret) ? 'ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน' : 'กรุณาใส่ Client Secret'  ?>">
							</div>
							<div class="form-group">
								<label>Refresh Token</label>
								<input type="password" class="form-control" name="gmail_refresh_token" value="" placeholder="<?php echo !empty($gmail_refresh_token) ? 'ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน' : 'กรุณาใส่ Refresh Token'  ?>">
							</div>
						</div>

						<hr>
						<div class="form-group">
							<label class="text-primary">อีเมล์ผู้ส่งออกจากระบบ</label>
							<input type="email" class="form-control" name="smtp_sender" value="<?php echo $smtp_sender; ?>" required>
						</div>
						<div class="form-group">
							<label class="text-primary">ชื่อใช้ส่งออก</label>
							<input type="text" class="form-control" name="smtp_sendername" value="<?php echo $smtp_sendername; ?>" required>
						</div>

						<div class="form-group">
							<button type="button" class="btn btn-primary btn-block" onclick="SaveEmailConfig();">บันทึกข้อมูลตั้งค่า</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-3">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">TEST Sent Mail</h3>
				</div>
				<div class="panel-body" style="min-height: 551px;">
					<form action="" id="formtestmail">
						<div class="form-group">
							<label>ผู้ส่ง (ตามการตั้งค่า)</label>
							<input type="text" class="form-control sender2" value="<?php echo $smtp_sender; ?>" readonly="readonly">
						</div>
						<div class="form-group">
							<label>Email ผู้รับ</label>
							<input id="mailto" name="mailto" type="text" class="form-control" value="">
						</div>
						<div class="form-group">
							<label>Subject</label>
							<input id="title" name="title" type="text" class="form-control" value="Test sent email from web">
						</div>
						<div class="form-group">
							<label>Message</label>
							<textarea id="message" name="message" rows="10" class="form-control">Testing Email Connection</textarea>
						</div>
						<div class="form-group">
							<button type="button" id="gotestmain" class="btn btn-primary btn-block" onclick="TestSentEmail('formtestmail');">ส่งเมล์ทดสอบ</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">History Test</h3>
				</div>
				<div class="panel-body">
					<div class="HistoryTest">ยังไม่มีข้อมูล</div>
					<div class="loading" align="center" style="padding: 30px;display: none;">
						<style>
							.lds-facebook {
								display: inline-block;
								position: relative;
								width: 50px;
								height: 50px;
							}

							.lds-facebook div {
								display: inline-block;
								position: absolute;
								left: 8px;
								width: 16px;
								background: #CCCCCC;
								animation: lds-facebook 1.1s cubic-bezier(0, 0.5, 0.5, 1) infinite;
							}

							.lds-facebook div:nth-child(1) {
								left: 8px;
								animation-delay: -0.24s;
							}

							.lds-facebook div:nth-child(2) {
								left: 32px;
								animation-delay: -0.12s;
							}

							.lds-facebook div:nth-child(3) {
								left: 56px;
								animation-delay: 0;
							}

							@keyframes lds-facebook {
								0% {
									top: 8px;
									height: 64px;
								}

								50%,
								100% {
									top: 24px;
									height: 32px;
								}
							}
						</style>
						<div class="lds-facebook">
							<div></div>
							<div></div>
							<div></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	$(document).ready(function() {
		toggleConfigMode('<?php echo $email_mode; ?>');
	});

	function toggleConfigMode(mode) {
		$('.config-block').hide();
		$('#config-' + mode).fadeIn();
	}

	function SaveEmailConfig() {
		var formData = $('#savesmtp').serialize();
		$.ajax({
			type: "POST",
			url: "doAjax.php?module=siteconfig&mp=emailconnect&ac=save",
			data: formData,
			success: function(response) {
				$('.sender2').val($('input[name="smtp_sender"]').val());
				alert("บันทึกการตั้งค่าเรียบร้อยแล้ว");
			},
			error: function() {
				alert("เกิดข้อผิดพลาดในการบันทึก");
			}
		});
	}
</script>