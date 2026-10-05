<!--  
แบบที่ 1

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>class</title>
<script type="text/javascript" src="ckeditor/ckeditor.js"></script>
</head>
<body>
<textarea cols="80" id="message" name="message" rows="10" class="ckeditor">ทดสอบความหล่อ
</textarea>
</body>
</html>

-->
<?php
header("X-Robots-Tag: noindex, nofollow", true);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
// header("Content-Security-Policy: default-src 'self'; 
//         script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; 
//         style-src  'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; 
//         font-src   'self' https://fonts.gstatic.com;");
$message = stripslashes(isset($_POST['message']) ? $_POST['message'] : 'ทดสอบความหล่อ');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Code</title>
    <script type="text/javascript" src="ckeditor/ckeditor.js"></script>
</head>

<body>
    <form action="" method="post">
        <textarea cols="80" id="message" name="message" rows="10"><?php echo $message; ?></textarea>
        <input type="submit" />




        <script type="text/javascript">
            //<![CDATA[
            CKEDITOR.replace('message', {
                /*skin            : 'kama',*/
                language: 'th',
                /*extraPlugins    : 'uicolor',
                uiColor            : '#006699',*/
                height: 400,
                /*width            : 750,*/

                toolbar: [
                    ['Source', '-', 'Templates'],
                    ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'],
                    ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote'],
                    ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
                    /*['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],*/
                    ['Image', 'Flash'],
                ],

                filebrowserBrowseUrl: 'ckfinder/ckfinder.html',
                filebrowserImageBrowseUrl: 'ckfinder/ckfinder.html?Type=Images',
                filebrowserFlashBrowseUrl: 'ckfinder/ckfinder.html?Type=Flash',
                filebrowserUploadUrl: 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
                filebrowserImageUploadUrl: 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
                filebrowserFlashUploadUrl: 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash'

            });
            //]]>
        </script>
    </form>
    <hr />
    <?php echo $message; ?>
</body>

</html>