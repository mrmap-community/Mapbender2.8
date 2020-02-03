<?php

/**
 * @version   Changed: ### 2015-02-26 13:38:03 UTC ###
 * @author    Raphael.Syed <raphael.syed@WhereGroup.com> http://WhereGroup.com
 */


/**
 * Gets an image for layer or wmc preview.
 * Convert the image to the right format and save it in the @preview_dir
 */

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");

$user_id = Mapbender::session()->get("mb_user_id");
$source_id = $_POST['source_id'];
//set variables
$width;
$height;
$new_name;
//get the http referer and the service (wms,wmc)
$referer = explode('=',$_SERVER["HTTP_REFERER"]);
$service = end($referer);
// get the file informations
$info = pathinfo($_FILES['image']['name']);
// get the extension of the file
$ext = $info['extension'];
// set the new fileName

$type = $_POST['type'];

if ($type == 'wmc') {
    $new_name = $source_id."_wmc_preview.jpg";//.$ext;
}
else {
    $new_name = $source_id."_layer_map_preview.jpg";//.$ext;
}

$new_image = dirname(__FILE__)."/../geoportal/preview/".$new_name;

if ($_POST["upload_action"] === "upload") {
    // get the ímage
    $image = $_FILES['image']['tmp_name'];
    //resize the image to 200px * 200px
    // get image size
    $size = Getimagesize($image);
    $images_orig;
    //create an gd-image-object from the source file
    switch ($ext) {
        case 'jpg':

            $images_orig = ImageCreateFromJPEG($image);
            break;

        case 'jpeg':

            $images_orig = ImageCreateFromJPEG($image);
            break;

        case 'png':

            $images_orig = ImageCreateFrompng($image);
            break;

        case 'gif':

            $images_orig = ImageCreateFromgif($image);
            break;

        default:

            return;
            break;
    }
    //if width and height are to big
    if ($size[0] >= 200 || $size[1] >= 200) {
        // width of the origin image
        $photoW = ImagesX($images_orig);
        // height of the origin image
        $photoH = ImagesY($images_orig);
        // create new image with the calculated size
        $images_target = ImageCreateTrueColor(200, 200);
        //fill the new image with transparency background
        $color = imagecolorallocatealpha($images_target, 255, 255, 255, 0); //fill white background
        imagefill($images_target, 0, 0, $color);
        imagealphablending( $images_target, false );
        imagesavealpha($images_target, true);
        //set the new image width and height
        if ($size[0] > $size[1] || $size[0] == $size[1]) {

            $width = 200;
            $height = round($width*$size[1]/$size[0]);
            // calculate the height of the src_image in the target_image
            $startHeight = round((200-$height)/2);
            // resize the image:
            ImageCopyResampled($images_target, $images_orig, 0, $startHeight, 0, 0, $width, $height, $photoW, $photoH);

        }else{

            $height = 200;
            $width = round($height*$size[0]/$size[1]);
            $startWidth = round((200-$width)/2);
            ImageCopyResampled($images_target, $images_orig, $startWidth, 0, 0, 0, $width, $height, $photoW, $photoH);
        }
        // move File to the new target directory --> always save as png
        imagejpeg($images_target,$new_image);
        // free space
        ImageDestroy($images_orig);
        ImageDestroy($images_target);
        echo "<img src=\""."../geoportal/preview/".$new_name."\">";
    } // if image-width and height are to small
    else if($size[0] < 200 && $size[1] < 200){
        //set the new image width
        $width = $size[0];
        // scale the height
        $height = $size[1];
        // width of the origin image
        $photoW = ImagesX($images_orig);
        // height of the origin image
        $photoH = ImagesY($images_orig);
        // create new image with the calculated size
        $images_target = ImageCreateTrueColor(200, 200);
        //fill the new image with transparency background
        $color = imagecolorallocatealpha($images_target, 255, 255, 255, 0); //fill white background
        imagefill($images_target, 0, 0, $color);
        imagealphablending( $images_target, false );
        imagesavealpha($images_target, true);
        // calculate the height of the src_image in the target_image
        $startHeight = round((200-$height)/2);
        $startWidth = round((200-$width)/2);
        // resize the image
        ImageCopyResampled($images_target, $images_orig, $startWidth, $startHeight, 0, 0, $width, $height, $photoW, $photoH);
        // move File to the new target directory --> always save as png
        imagejpeg($images_target,$new_image);
        // free space
        ImageDestroy($images_orig);
        ImageDestroy($images_target);
        echo "<img src=\""."../geoportal/preview/".$new_name."\">";
    }
} else if ($_POST["upload_action"] === "delete") {
    unlink($new_image);
    echo "<img class=\"defaultPreview\" src=\"../geoportal/preview/keinevorschau.jpg\">";
} else if ($_POST["upload_action"] === "getImage") {
    if(!file_exists($new_image)) {
      echo "<img class=\"defaultPreview\" src=\"../geoportal/preview/keinevorschau.jpg\">";
    } else {
      echo "<img src=\"../geoportal/preview/" . $new_name . "?nocache=" . time() . "\">";
    }    
}
