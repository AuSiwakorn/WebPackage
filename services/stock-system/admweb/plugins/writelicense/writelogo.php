<?php
# function writeLogoInImg for write logo data in to images 
# mod : for smf forum
# developer by : siwakorn induang
# version : 0.1

# $imageFile : {img path} / {img name}
# Exsample
# require_once('writelogo.php');
# $imgCre = new imgCreate();
# $imgCre->writeLogoInImg($modSettings['gallery_path'] . $filename);

class imgCreate
{
    function imgCreate()
    {
        
    }
    
    function writeLogoInImg($imageFile, $transparency='')
    {
        # validate 1
        if (!is_file($imageFile) || !is_writable($imageFile)) {
            return false;
        }
        
        # Plaese set config
        require_once 'conf.ini.php';
        
        # validate 2
        if (!is_file($logo_path_conf) || $logo_path_conf == '') {
            return false;
        }
        
        $transparency = (isset($transparency) && $transparency != '') ? $transparency : $transparency_conf;
        
        $myImage = imagecreatefromjpeg("$imageFile");
        $myCopyright = imagecreatefromgif("$logo_path_conf");
		//$myCopyright = imagecreatefrompng("$logo_path_conf");
		
        $destWidth = imagesx($myImage);
        $destHeight = imagesy($myImage);
        $srcWidth = imagesx($myCopyright);
        $srcHeight = imagesy($myCopyright);

        ///////////////////////////////////////////////////////////////////////////
        //////////////// center //////// right /////// left ////////////////
        if ($align_x_conf == 'right') {
            $destX = ($destWidth - $srcWidth) - 10;
        } elseif ($align_x_conf == 'center') {
            $destX = ($destWidth - $srcWidth) / 2;
        } elseif ($align_x_conf == 'left') {
            $destX = 10;
        } else {
            $destX = ($destWidth - $srcWidth) - 10;
        }
        ///////////////////////////////////////////////////////////////////////////
        ///////////// center //////// top /////////// down //////////////
        if ($align_y_conf == 'down') {
            $destY = ($destHeight - $srcHeight) - 10;
        } elseif ($align_y_conf == 'center') {
            $destY = ($destHeight - $srcHeight) / 2;
        } elseif ($align_y_conf == 'top') {
            $destY = 10;
        } else {
            $destY = ($destHeight - $srcHeight) - 10;
        }
        //////////////////////////////////////////////////////////////////////////
        
        imagecopymerge($myImage, $myCopyright, $destX, $destY, 0, 0, $srcWidth, $srcHeight, $transparency);

        imagejpeg($myImage,"$imageFile");
        imagedestroy($myImage);
        imagedestroy($myCopyright);
        return true;
    }
}
?>