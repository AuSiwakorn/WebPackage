<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Template</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }

        .preview-message {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            font-size: 1.2em;
            color: #333;
        }
    </style>
</head>

<body>
    <?php $keysname = $_SESSION['previewKeysname']; ?>
    <div class="preview-message">
        คุณยังไม่ได้สร้างไฟล์ preview_<?php echo $keysname; ?>.php
    </div>
</body>

</html>