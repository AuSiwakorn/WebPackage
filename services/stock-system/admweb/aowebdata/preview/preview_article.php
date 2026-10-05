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
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item"><a class="nav-link active" id="th-tab" data-toggle="tab" href="#th" role="tab" aria-controls="th" aria-selected="true">TH</a></li>
        <li class="nav-item"><a class="nav-link" id="en-tab" data-toggle="tab" href="#en" role="tab" aria-controls="en" aria-selected="false">EN</a></li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="th" role="tabpanel" aria-labelledby="th-tab">
            <br>
            <div><?php echo $_SESSION['frm[th][subject]']; ?></div>
            <div><?php echo $_SESSION['frm[th][content]']; ?></div>
            <?php if (!empty($_SESSION['icon'])) { ?>
                <img src="<?php echo $_SESSION['icon']; ?>" width="287px" height="135px" alt="">
            <?php } ?>
        </div>
        <div class="tab-pane fade" id="en" role="tabpanel" aria-labelledby="en-tab">
            <br>
            <div><?php echo $_SESSION['frm[en][subject]']; ?></div>
            <div><?php echo $_SESSION['frm[en][content]']; ?></div>
            <?php if (!empty($_SESSION['icon2'])) { ?>
                <img src="<?php echo $_SESSION['icon2']; ?>" width="287px" height="135px" alt="">
            <?php } ?>
        </div>
    </div>

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