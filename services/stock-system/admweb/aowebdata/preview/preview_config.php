<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreviewDemo</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div><?php echo $_SESSION['keys[enshortmessagefooter]']; ?></div>
    <div><?php echo $_SESSION['keys[facebooklinken]']; ?></div>
    <div><?php echo $_SESSION['keys[linkedinlinken]']; ?></div>
    <div><?php echo $_SESSION['keys[encopyright]']; ?></div>
    <div><?php echo $_SESSION['keys[addressen]']; ?></div>
    <div><?php echo $_SESSION['keys[phoneen]']; ?></div>
    <div><?php echo $_SESSION['keys[workingTimeen]']; ?></div>
    <div><?php echo $_SESSION['keys[workingDateen]']; ?></div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>

<!-- <pre>Array
(
    [formData] => 
    [keysname] => 
    [login] => 
    [logout_time] => 
    [login_time] => 
    [mdusertext] => 
    [status] =>
    [group] =>
    [displaytime] =>
    [endDate] =>
    [isEndDateShow] =>
    [icon] => 
    [icon2] => 
    [extra1] => 
    [extra2] => 
    [extra3] => 
    [extra4] => 
    [extra5] => 
    [extra6] => 
    [extra7] => 
    [extra8] => 
    [extra9] => 
    [extra10] => 
    [frm[th][content_id]] => 
    [frm[th][subject]] => 
    [frm[th][keywords]] => 
    [frm[th][author]] => 
    [frm[th][shortMessage]] => 
    [frm[th][content]] => 
    [frm[th][content2]] => 
    [frm[th][content3]] => 
    [frm[th][content4]] => 
    [frm[th][content_extra1]] => 
    [frm[th][content_extra2]] => 
    [frm[th][content_extra3]] => 
    [frm[en][content_id]] => 
    [frm[en][subject]] => 
    [frm[en][keywords]] => 
    [frm[en][author]] => 
    [frm[en][shortMessage]] => 
    [frm[en][content]] =>
    [frm[en][content2]] =>
    [frm[en][content3]] =>
    [frm[en][content4]] =>
    [frm[en][content_extra1]] =>
    [frm[en][content_extra2]] =>
    [frm[en][content_extra3]] =>
    [attachTextFileList[0]] =>
    [attachTextFileList[1]] =>
    [attachTextFileList[2]] =>
    [attachTextFileList[3]] =>
    [attachTextFileList[4]] =>
    [attachTextFileList[5]] =>
    [attachTextFileList[6]] =>
    [attachTextFileList[7]] =>
    [ac] =>
    [keys[enshortmessagefooter]] =>
    [keys[facebooklinken]] =>
    [keys[linkedinlinken]] =>
    [keys[encopyright]] =>
    [keys[addressen]] =>
    [keys[phoneen]] =>
    [keys[workingTimeen]] =>
    [keys[workingDateen]] =>
)
</pre> -->