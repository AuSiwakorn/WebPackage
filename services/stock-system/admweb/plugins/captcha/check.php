<?php 
    # set session
    @session_start();

    if ($_POST) {
        if ($_POST['txt_captcha'] != "") {
            if ($_POST['txt_captcha'] != $_SESSION['session_captchaText']) {

                /* ----------------- set error ------------- */
                $captcha_er = true;
                
                /* ----------------- remove --------------- */
                $_SESSION['session_captchaText'] = '';
                unset($_SESSION['session_captchaText']);
                
                /* ----------------- redirect --------------- */
				$_SESSION['sendthank'] = 'error';
                $refer = $_SERVER['HTTP_REFERER'];
				$refer = 'thankMail.php';
                header('Location: ' . $refer. '?mod=e');
                exit;
            } else {
                $captcha_er = '';
                $captcha_er = false;
				$_SESSION['sendthank'] = 'y';
            }
        } else {
            $captcha_er = true;
			/* ----------------- redirect --------------- */
				$_SESSION['sendthank'] = 'error';
                $refer = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $refer. '?mod=e');
                exit;
        }
    }

?>