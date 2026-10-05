<?php
    @session_start();
    $imgFile = 'mailweb/captcha/captcha.php';
?>
<p><img src="<?php echo $imgFile; ?>" alt="" name="captcha" width="129" height="49" id="captcha" border="1" /></p>
<p>Enter the text shown above : </p>
<form id="form1" name="form1" method="post" action="">
  <p>
    <input name="txt_captcha" type="text" id="txt_captcha" />
</p>
  <p>
    <input type="submit" name="Submit" value="Submit" />
</p>
</form>