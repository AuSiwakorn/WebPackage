<?php
////////////////////////////////////////// 1 //////////////////////////////////////////
# function ( writeTxtInImg ) and function ( writeLogoInImg ) for write text data in to images 
# mod : for smf_gallery forum
# developer by : siwakorn induang
# version : 0.1



class imgCreate
{
    var $align_x;
    var $align_y;
    
    function imgCreate($x='',$y='')
    {
        $this->align_x = $x;
        $this->align_y = $y;
    }
    
    function writeLicense($imageFile, $whatWrite='txt', $txt=false)
    {
        # Plaese set config
        $ty_write_conf = 'auto';
        if ($ty_write_conf != 'auto') {
           $whatWrite = $ty_write_conf; 
        }
        
        if ($whatWrite == 'logo') {
            $this->writeLogoInImg($imageFile);
        } else {
            $this->writeTxtInImg($imageFile, $txt);
        }
    }
    
    # $imageFile : {img path} / {img name}
    # $txt : text data for write in to imagesfile

    # Exsample
    # require_once('writetxt.php');
    # $imgCre = new imgCreate();
    # $imgCre->writeTxtInImg($modSettings['gallery_path'] . $filename);
    
    function writeTxtInImg($imageFile, $txt=false, $color=false)
    {
        if (!is_file($imageFile) || !is_writable($imageFile)) {
           return false; 
        }
        
        # Plaese set config
        require_once 'conf.ini.php';

        $co = (isset($color) && $color != '') ? $color : $co_conf;
        $aColor = sscanf($co, '#%2x%2x%2x');
        
        # set
        if ($ty_write_conf == 'auto') {
            $align_x = (isset($this->align_x) && $this->align_x != '') ? $this->align_x : $align_x_conf;
            $align_y = (isset($this->align_y) && $this->align_y != '') ? $this->align_y : $align_y_conf;
        } else {
            $align_x = $align_x_conf;
            $align_y = $align_y_conf;
        }
        
        # txt for write to img
        $string = (isset($txt) && $txt != '') ? $txt : $string_conf; // String

        # if use font
        $fontpath = realpath($font_path_conf);
        putenv('GDFONTPATH='.$fontpath);
        $font = (isset($font_name_conf) && $font_name_conf != '') ? $font_name_conf : 'Loma.ttf';
        
        # for write in images
        $imC = ImageCreateFromJpeg("$imageFile"); // Path Images
        # Not write in images
        //$imC = @imagecreate(600, 500);
        
        $color = ImageColorAllocate($imC, $aColor[0], $aColor[1], $aColor[2]); // Text Color
        
        ////////////////////////////////////////////////////////////////////////////////
        //////////////////// right /////// center /////// left ///////////////////
        if ($align_x == 'right') {
            $pxX = Imagesx($imC) - 180;
        } elseif ($align_x == 'center') {
            $pxX = (Imagesx($imC) - 6.0 * strlen($string))/2; // X
        } elseif ($align_x == 'left') {
            $pxX = 10;
        } else {
            $pxX = Imagesx($imC) - 180;
        }
        
        ////////////////////////////////////////////////////////////////////////////////////
        ///////////////////// top /////// center /////// down ////////////////////
        if ($align_y == 'down') {
            $pxY = Imagesy($imC)- 10; // Y For Auto
        } elseif ($align_y == 'center') {
            $pxY = (Imagesy($imC) / 2);
        } elseif ($align_y == 'top') {
            $pxY = 20;
        } else {
            $pxY = Imagesy($imC)- 10;
        }
        ////////////////////////////////////////////////////////////////////////////////////
		
        # For use Font
		$font = (is_file($fontpath.'/'.$font)) ? $fontpath.'/'.$font : $font;
        $r = ImagettfText($imC, 9, 0, $pxX, $pxY, $color, $font, $string);
        
        # Not use Font
        //ImageString($im, 2, $pxX, $pxY, $string, $color);
        
        # create png
        //imagePng($im,"test2.png");
        
        # create jpg
        imagejpeg($imC,"$imageFile");
        ImageDestroy($imC);
        return true;
    }
    
    
    # $imageFile : {img path} / {img name}
    # Exsample
    # require_once('writelogo.php');
    # $imgCre = new imgCreate();
    # $imgCre->writeLogoInImg($modSettings['gallery_path'] . $filename);
    
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
        
        if ($ty_write_conf == 'auto') {
            $align_x = (isset($this->align_x) && $this->align_x != '') ? $this->align_x : $align_x_conf;
            $align_y = (isset($this->align_y) && $this->align_y != '') ? $this->align_y : $align_y_conf;
        } else {
            $align_x = $align_x_conf;
            $align_y = $align_y_conf;
        }
        
		
        $transparency = (isset($transparency) && $transparency != '') ? $transparency : $transparency_conf;
        
        $myImage = imagecreatefromjpeg("$imageFile");
        $myCopyright = imagecreatefromgif("$logo_path_conf");

        $destWidth = imagesx($myImage);
        $destHeight = imagesy($myImage);
        $srcWidth = imagesx($myCopyright);
        $srcHeight = imagesy($myCopyright);

        ///////////////////////////////////////////////////////////////////////////
        //////////////// center //////// right /////// left ////////////////
        if ($align_x == 'right') {
            $destX = ($destWidth - $srcWidth) - 10;
        } elseif ($align_x == 'center') {
            $destX = ($destWidth - $srcWidth) / 2;
        } elseif ($align_x == 'left') {
            $destX = 10;
        } else {
            $destX = ($destWidth - $srcWidth) - 10;
        }
        ///////////////////////////////////////////////////////////////////////////
        ///////////// center //////// top /////////// down //////////////
        if ($align_y == 'down') {
            $destY = ($destHeight - $srcHeight) - 10;
        } elseif ($align_y == 'center') {
            $destY = ($destHeight - $srcHeight) / 2;
        } elseif ($align_y == 'top') {
            $destY = 10;
        } else {
            $destY = ($destHeight - $srcHeight) - 10; 
        }
        //////////////////////////////////////////////////////////////////////////
        
        imagecopymerge($myImage, $myCopyright, $destX+10, $destY+10, 0, 0, $srcWidth, $srcHeight, $transparency);

        imagejpeg($myImage,"$imageFile");
        imagedestroy($myImage);
        imagedestroy($myCopyright);
        return true;
    }
}

# ------------------------------------------------------------------------------------------------
# add code to gallery.template.php To form Line (509)
# ------------------------------------------------------------------------------------------------
/*
   ////////////////////////////////// siwakorn ////////////////////////////////
   echo '<tr>
            <td colspan="2" bgcolor="#EEEEEE">&nbsp;</td>
         </tr>
          <tr class="windowbg2">
            <td align="right" valign="top"><b>License : </b></td>
            <td>
            <input type="radio" name="whatwrite" value="txt" checked="checked" id="txt"> <label for="txt">เขียน Lisense เป็นชื่อเว็บ</label><br>
            <input type="radio" name="whatwrite" value="logo" id="logo"> <label for="logo">เขียน Lisense เป็น logo</label></td>
          </tr>
          <tr class="windowbg2">
              <td align="right"><b>License align : </b></td>
              <td>&nbsp;X <select name="license_align_x">
                    <option value="right">right</option>
                    <option value="left">left</option>
                    <option value="center">center</option>
                </select>
                Y <select name="license_align_y">
                    <option value="down">down</option>
                    <option value="center">center</option>
                    <option value="top">top</option>
                </select>
            </td>
        </tr>';
    ////////////////////////////////// siwakorn ////////////////////////////////
*/



# ------------------------------------------------------------------------------------------------
# add code to sources/gallery.php to add Line (594)
# ------------------------------------------------------------------------------------------------
/*
                ////////////////////////////////// siwakorn ////////////////////////////////
                # logo | txt
                $whatWrite = (isset($_POST['whatwrite']) && $_POST['whatwrite'] != '') ? $_POST['whatwrite'] : 'txt';
                
                $align_x = (isset($_POST['license_align_x']) && $_POST['license_align_x'] != '') ? $_POST['license_align_x'] : 'right';
                $align_y = (isset($_POST['license_align_y']) && $_POST['license_align_y'] != '') ? $_POST['license_align_y'] : 'down';
                $moddir = dirname(dirname(__FILE__)) . '/mod';
                if (is_file($moddir . '/writelicense/writelicense.php')) {
                    require_once($moddir . '/writelicense/writelicense.php');
                    $imgCre = new imgCreate($align_x, $align_y);
                    $imgCre->writeLicense($modSettings['gallery_path'] . $filename, $whatWrite, 'www.thaipetonline.com (' . $ID_MEMBER . ')');
                }
                ////////////////////////////////// siwakorn ////////////////////////////////
*/
?> 