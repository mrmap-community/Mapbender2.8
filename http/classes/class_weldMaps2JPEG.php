<?php
# $Id: class_weldMaps2PNG.php 5529 2010-02-19 15:54:30Z christoph $
# http://www.mapbender.org/index.php/class_weldMaps2JPEG.php
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
* class_weldMaps2JPEG
* @version 1.0.0
* get/post '___' separated maprequests
*
**/
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/class_stripRequest.php");
require_once(dirname(__FILE__)."/class_connector.php");

class weldMaps2JPEG{
	function __construct($urls,$filename, $encode = true){
		if(!$urls || $urls == ""){
			$e = new mb_exception("weldMaps2JPEG: no maprequests delivered");
		}
		$url = explode("___", $urls);
		for($i=0; $i<count($url); $i++){
                	if ($url[$i] != false) {
				$obj1 = new stripRequest($url[$i]);
		  	break;
			}
		}
		//following is only possible, if parameters with and height are given :-( - when getlegendgraphic is used - otherwise we have to get the original width and height from capabilities - or mapbender database itself!
		//check if url is of type getlegendgraphic or getmap - these are considered here
		$request = strtoupper($obj1->get("REQUEST"));
//$e = new mb_exception(strtoupper($obj1->get("REQUEST")));
//$e = new mb_exception("count url: ".count($url));
		if ($request !== "GETMAP" && $request !== "GETLEGENDGRAPHIC") {
			//no width or height exists - e.g. legend graphics
			//$e = new mb_exception("No GetMap or GetLegendGraphic");
			//$e = new mb_exception("class_welsMaps2JPEG.php: ".$url[0]);
			if ($url[0] != false){
				$url[0] = urldecode($url[0]);
				//$e = new mb_exception("class_weldMaps2JPEG.php: ".$url[0]);
				//list($width, $height, $type, $attr) = getimagesizefromstring($this->loadpng($url[0]));
				$img = $this->loadpng($url[0]);	
				$width = imagesx($img);
				$height = imagesy($img);
				//list($width, $height, $type, $attr) = getimagesize($img);
				//$width = 200;
				//$height = 200;
				$image = imagecreatetruecolor($width, $height);
				$white = ImageColorAllocate($image,255,255,255); 
				ImageFilledRectangle($image,0,0,$width,$height,$white); 
				imagecopy($image, $img, 0, 0, 0, 0, $width, $height);
				//free space
				@imagedestroy($img); 
				imagejpeg($image,$filename);
				//free space
				imagedestroy($image); 
				return true;
			}
		} else {
			$width = $obj1->get("width");
			$height = $obj1->get("height");
			if (!$width || $width == '' || !$height || $height == '') {
				$e = new mb_exception("classes/class_weldMaps2JPEG.php: Paremeters width and/or height are not given in $request request! Try to get it from image size.");
				if ($url[0] != false){
					$url[0] = urldecode($url[0]);
					//$e = new mb_exception("class_weldMaps2JPEG.php: ".$url[0]);
					//list($width, $height, $type, $attr) = getimagesizefromstring($this->loadpng($url[0]));
					$img = $this->loadpng($url[0]);	
					$width = imagesx($img);
					$height = imagesy($img);
				}
			}
		}
		$image = imagecreatetruecolor($width, $height);
		$white = ImageColorAllocate($image,255,255,255); 
		ImageFilledRectangle($image,0,0,$width,$height,$white); 
		for($i=0; $i<count($url); $i++){
			if ($url[$i] != false) { //sometimes some false urls will be send? - don't use them
				//before encode the url it should be decoded to be secure that a decoded url is used!
				$url[$i] = urldecode($url[$i]);
				$obj = new stripRequest($url[$i]);
				$url[$i] = $obj->setPNG();
				$url[$i] = $obj->encodeGET($encode);
				$img = $this->loadpng($url[$i]);
				if($img != false){
					imagecopy($image, $img, 0, 0, 0, 0, $width, $height);
					@imagedestroy($img); 
				}
				else{
					$e = new mb_exception("weldMaps2JPEG: unable to load image: " . $url[$i]);
				}
			}
		}
		imagejpeg($image,$filename);
		imagedestroy($image); 
	}
        /**
	 * Old constructor to keep PHP downward compatibility
	 */
        function weldMaps2JPEG($urls,$filename, $encode = true){
		self::__construct($urls,$filename, $encode);
	}
	
	function loadpng ($imgurl) {
		$obj = new stripRequest($imgurl);
		$x = new connector();
		//set header
		//$x->set("curlSendCustomHeaders",true);
		//$e = new mb_exception($x->file);
		//$x->set("curlSendCustomHeaders",false);
		//
		//Accept		text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
		//Accept-Encoding	gzip, deflate
		//Accept-Language	de-de,de;q=0.8,en-us;q=0.5,en;q=0.3
		//Cache-Control	max-age=0
		//Connection	keep-alive
		//Host	geodaten-luwg.rlp.de
		//User-Agent	Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:25.0) Gecko/20100101 Firefox/25.0
		//$headers = array(
		//		"GET: ".$path." HTTP/1.1",
		//		"User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:25.0) Gecko/20100101 Firefox/25.0",
		//		"Accept-Encoding: gzip, deflate",
		//		"Accept-Language: de-de,de;q=0.8,en-us;q=0.5,en;q=0.3",
           	//		"Host: geodaten-luwg.rlp.de",
		//		"Cache-Control:	max-age=0",
	        //  		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
		//		"Connection: Keep-Alive"
		//);
		//$x->set("curlSendCustomHeaders",true);
		//$x->set("externalHeaders", $headers);
		//$f = $obj->get("format");
//$e = new mb_exception($imgurl);
		$im = imagecreatefromstring($x->load($imgurl));
		if(!$im){
			$im = false;
			$e = new mb_exception("weldMaps2JPEG: loadpng: unable to load image: ".$imgurl);
		}
		return $im;
		
	}
	
}

?>
