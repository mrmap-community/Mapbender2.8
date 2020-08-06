<?php
# 
# Calculate digital terrain model
# Peter Lang
# Landesamt für Vermessung, Geoinformation und Landentwicklung
# 2020-02-03
#

require_once dirname(__FILE__)."/../../conf/altitudeProfile.conf";

# Use constants from configuration file
$imageFile = ALTITUDE_PROFILE_DTM_IMAGE_FILE;
$left = ALTITUDE_PROFILE_BBOX_MINX;
$right = ALTITUDE_PROFILE_BBOX_MAXX;
$bottom = ALTITUDE_PROFILE_BBOX_MINY;
$top = ALTITUDE_PROFILE_BBOX_MAXY;
$width_pix = ALTITUDE_PROFILE_DTM_IMAGE_FILE_WIDTH;
$height_pix = ALTITUDE_PROFILE_DTM_IMAGE_FILE_HEIGHT;

# Use frontend user input from POST
$json_unsafe = $_POST['xyz'];
$array = json_decode($json_unsafe);

$width_cor = $right - $left;
$height_cor = $top - $bottom;

$im = imagecreatefrompng($imageFile);

function getrgb_x($cor)
{
    global $left, $right, $width_cor, $width_pix;
    return ($cor - $left) * $width_pix / $width_cor;
}

function getrgb_Y($cor)
{
    global $top, $bottom, $height_cor, $height_pix;
    return $height_pix - ($cor - $bottom) * $height_pix / $height_cor;
}

function getrgb($cor_x, $cor_y)
{
    global $im;
    $x = (int) getrgb_x($cor_x);
    $y = (int) getrgb_y($cor_y);
    $rgb = imagecolorat($im, $x, $y);
    return (int)  ((695.0 / 255) * $rgb);
}

for ($i = 0; $i < count($array); $i = $i + 3) {
    $array[$i + 2] = getrgb($array[$i], $array[$i + 1]);
}

echo json_encode($array);
