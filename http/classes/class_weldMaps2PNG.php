<?php
# $Id: class_weldMaps2PNG.php 9936 2018-08-09 12:30:02Z armin11 $
# http://www.mapbender.org/index.php/class_weldMaps2PNG.php
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

/*
* class_weldMaps2PNG
* @version 1.0.0
* get/post '___' separated maprequests
*
**/
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/class_stripRequest.php");
require_once(dirname(__FILE__)."/class_connector.php");

class weldMaps2PNG{

	private static $cache = array();

	function __construct($urls,$filename, $encode = true, $opacities=""){
		if(!$urls || $urls == ""){
			$e = new mb_exception("weldMaps2PNG: no maprequests delivered");
		}
		$url = explode("___", $urls);
		$opacities = explode("___",$opacities);
		$obj1 = new stripRequest($url[0]);
		$width = $obj1->get("width");
		$height = $obj1->get("height");
		
		$image = imagecreatetruecolor($width, $height);
		imagealphablending($image,true);
		$white = ImageColorAllocate($image,255,255,255); 
		ImageFilledRectangle($image,0,0,$width,$height,$white); 

		for($i=0; $i<count($url); $i++){
			$obj = new stripRequest($url[$i]);

			$url[$i] = $obj->setPNG();
			$url[$i] = $obj->encodeGET($encode);
			$img = $this->loadpng($url[$i]);
			
			$opacity = $opacities[$i] *100;
			
			if($img != false){
				if ($opacity != 100 && $opacity != "") {
					if(imagecolortransparent($img) > -1 ){
						imagecopymerge($image, $img, 0, 0, 0, 0, $width, $height,$opacity);
					} else {
						$this->filter_opacity($img,$opacity);
						imagecopy($image, $img, 0, 0, 0, 0, $width, $height);
					}
				} else {
					imagecopy($image, $img, 0, 0, 0, 0, $width, $height);
				}
				@imagedestroy($img); 
			}
			else{
				$e = new mb_exception("weldMaps2PNG: unable to load image: " . $url[$i]);
			}
		}
		imagepng($image,$filename);
		imagedestroy($image); 

	}
        
        /**
        * Old constructor to keep PHP downward compatibility
        */
        function weldMaps2PNG($urls,$filename, $encode = true, $opacities=""){
		self::__construct($urls,$filename, $encode, $opacities);
	}
	
	function loadpng ($imgurl) {
		if (array_key_exists($imgurl, weldMaps2PNG::$cache)) {
			new mb_notice("weldMaps2PNG: using cache");
			$file =  weldMaps2PNG::$cache[$imgurl];
		} else {
			$file = (new connector($imgurl))->file;
			weldMaps2PNG::$cache[$imgurl] = $file;
		}

		$im = @imagecreatefromstring($file);
		if(!$im){
			new mb_exception("weldMaps2PNG: unable to load image: ".$imgurl);
			return false;
		}

		return $im;
	}
	
	function filter_opacity( &$img, $opacity ) //params: image resource id, opacity in percentage (eg. 80)
	{
		if( !isset( $opacity ) )
		{ return false; }
		$opacity /= 100;
		 
		//get image width and height
		$w = imagesx( $img );
		$h = imagesy( $img );
		 
		//turn alpha blending off
		imagealphablending( $img, false );
		 
		//find the most opaque pixel in the image (the one with the smallest alpha value)
		$minalpha = 0;
		for( $x = 0; $x < $w; $x++ )
			for( $y = 0; $y < $h; $y++ )
			{
				$alpha = ( imagecolorat( $img, $x, $y ) >> 24 ) & 0xFF;
				if( $alpha < $minalpha )
				{ $minalpha = $alpha; }
			}
			 
			//loop through image pixels and modify alpha for each
			for( $x = 0; $x < $w; $x++ )
			{
				for( $y = 0; $y < $h; $y++ )
				{
					//get current alpha value (represents the TANSPARENCY!)
					$colorxy = imagecolorat( $img, $x, $y );
					$alpha = ( $colorxy >> 24 ) & 0xFF;
					//calculate new alpha
					if( $minalpha !== 127 )
					{ $alpha = 127 + 127 * $opacity * ( $alpha - 127 ) / ( 127 - $minalpha ); }
					else
					{ $alpha += 127 * $opacity; }
					//get the color index with new alpha
					$alphacolorxy = imagecolorallocatealpha( $img, ( $colorxy >> 16 ) & 0xFF, ( $colorxy >> 8 ) & 0xFF, $colorxy & 0xFF, $alpha );
					//set pixel with the new color + opacity
					if( !imagesetpixel( $img, $x, $y, $alphacolorxy ) )
					{ return false; }
				}
			}
			return true;
	}
}

?>
