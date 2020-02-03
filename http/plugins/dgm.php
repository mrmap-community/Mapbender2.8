<?php


$json = $_POST['xyz'];


$array = json_decode($json);

$left = 2524972.024;
$right = 2602802.024;
$width_cor = $right-$left;
$width_pix = 15566;

$top = 5512053.598;
$bottom = 5436918.598;
$height_cor = $top - $bottom;
$height_pix = 15027;
$im = imagecreatefrompng("/data/mapbender/http/img/hoehenprofil/dhm_sl.png");


function getrgb_x($cor)
{
global $left, $right, $width_cor, $width_pix; 
    
    return ($cor-$left)* $width_pix/$width_cor;
}

function getrgb_Y($cor)
{
global $top, $bottom, $height_cor, $height_pix;

	
	return $height_pix - ($cor-$bottom)* $height_pix/$height_cor;
}

function getrgb($cor_x,$cor_y)
{
	
global $im;
$x = (int)getrgb_x($cor_x);
$y = (int) getrgb_y($cor_y);


$rgb = imagecolorat($im, $x, $y);

return (int)  ((695.0/255) * $rgb);

}





for ($i = 0; $i < count($array); $i=$i+3) {
    $array[$i + 2] = getrgb($array[$i],$array[$i + 1]);
}

echo json_encode($array);



?>