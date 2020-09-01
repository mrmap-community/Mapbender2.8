<?php

class mbSvgDecorator extends mbTemplatePdfDecorator
{
//    protected $pageElementType = "svg";
//    protected $elementId;

    protected $pageElementType = "map";
    protected $elementId;
    protected $filename;
    /* a decorator should declare which parameters could be overwritten through the request object */
    protected $overrideMembersFromRequest = array("res_dpi", "angle");
    protected $res_dpi;
    protected $angle = 0;

    public function __construct($pdfObj, $elementId, $mapConf, $controls, $manualValues, $svgParam)
    {
        parent::__construct($pdfObj, $mapConf, $controls, $manualValues);
        $this->elementId = $elementId;
        $this->filename = TMPDIR . "/" . parent::generateOutputFileName($svgParam, "png");
        $this->svgParam = $svgParam;
        $this->override();
        $this->decorate();
    }

    public function override()
    {

    }

    public function decorate()
    {
        require_once(dirname(__FILE__) . "/../print_functions.php");

        global $mapOffset_left, $mapOffset_bottom, $map_height, $map_width, $coord;
        global $yAxisOrientation;
        $yAxisOrientation = 1;
        $doc = new \DOMDocument();
        if ($this->hasValue("svg_extent") && count(explode(',', $this->getValue("svg_extent"))) === 4 &&
            $this->hasValue($this->svgParam) && @$doc->loadXML($this->getValue($this->svgParam))) {
            $e = new mb_notice("mbSvgDecorator: svg: " . $this->getValue($this->svgParam));
        } else {
            return "No svg found.";
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
        $xpath->registerNamespace("svg", "http://www.w3.org/2000/svg");
        $coord = mb_split(",", $this->pdf->getMapExtent());
        $mapInfo = $this->pdf->getMapInfo();
        foreach ($mapInfo as $k => $v) {
            $e = new mb_notice("mbSvgDecorator: mapInfo: " . $k . "=" . $v);
        }
        $mapOffset_left = $mapInfo["x_ul"];
        $mapOffset_bottom = $mapInfo["y_ul"];
        $map_height = $mapInfo["height"];
        $map_width = $mapInfo["width"];
        $map_extent = explode(',', $mapInfo["extent"]);

        $oext = explode(',', $this->getValue("svg_extent"));
        $angle = $this->hasValue('angle') ? floatval($this->getValue('angle')) : 0;
        $svg_w = intval(preg_replace('[^0-9]', '', $doc->documentElement->getAttribute("width")));
        $svg_h = intval(preg_replace('[^0-9]', '', $doc->documentElement->getAttribute("height")));
        $res = $this->pdf->objPdf->k * ($this->conf->res_dpi / 72);
        $map_width_px = intval(round($map_width * $res));
        $map_height_px = intval(round($map_height * $res));

        // calculate factors for x and y
        $k_svg_x = ($oext[2] - $oext[0]) / $svg_w;
        $k_svg_y = ($oext[3] - $oext[1]) / $svg_h;
        // calculate offsets for x and y
        $offset_svg_x_px = ($oext[0] - $map_extent[0]) / $k_svg_x;
        $offset_svg_y_px = ($oext[3] - $map_extent[3]) / $k_svg_y;
        $svg_bbox_w = ($map_extent[2] - $map_extent[0]) / $k_svg_x;
        $svg_bbox_h = ($map_extent[3] - $map_extent[1]) / $k_svg_y;

        $scale = 1;
        $padding = 2;
        if ($svg_bbox_w > $svg_bbox_h) {
            $scale = ($map_width_px - $padding) / $svg_bbox_w;
        } else {
            $scale = ($map_height_px - $padding) / $svg_bbox_h;
        }
        if ($angle != 0) {
            $neededHeight = round(abs(sin(deg2rad($angle)) * $map_width_px) + abs(cos(deg2rad($angle)) * $map_width_px));
            $neededWidth = round(abs(sin(deg2rad($angle)) * $map_height_px) + abs(cos(deg2rad($angle)) * $map_height_px));
            $x = $offset_svg_x_px * $scale + ($neededWidth - $map_width_px) / 2;
            $y = (-$offset_svg_y_px * $scale) + ($neededHeight - $map_height_px) / 2;
            $doc->documentElement->setAttribute("height", $neededHeight);
            $doc->documentElement->setAttribute("width", $neededWidth);
        } else {
            $x = $offset_svg_x_px * $scale;
            $y = -$offset_svg_y_px * $scale;
            $doc->documentElement->setAttribute("height", $map_height_px);
            $doc->documentElement->setAttribute("width", $map_width_px);
        }
        foreach ($xpath->query("//*[@d]", $doc->documentElement) as $elm) {
            $elm->setAttribute('transform', "translate($x,$y) scale($scale,$scale)"); #rotate($angle, $rx0, $ry0)
        }
        foreach ($xpath->query("//svg:text", $doc->documentElement) as $elm) {
            $elm->setAttribute('transform', "translate($x,$y) scale($scale,$scale)"); #rotate($angle, $rx0, $ry0)
        }
//        foreach ($xpath->query("//*[@style]", $doc->documentElement) as $elm) {
//            $elm->removeAttribute('style');
//        }
        $imagick = new \Imagick();
        $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
        $imagick->readImageBlob($doc->saveXML());
        $imagick->setImageFormat("png32");
        if ($angle != 0) {
            $imagick->rotateImage(new ImagickPixel('transparent'), $angle);
//            $imgWidth = $imagick->getImageWidth();
//            $imgHeight = $imagick->getImageHeight();
//            $imagick->cropImage($map_width_px, $map_height_px, ($imgWidth-$map_width_px)/2, ($imgHeight-$map_height_px)/2); orig, imagick bug?
            $imagick->cropImage($map_width_px, $map_height_px, ($neededWidth - $map_width_px) / 2,
                ($neededHeight - $map_height_px) / 2);
        }
        file_put_contents($this->filename, $imagick->getImageBlob());
        $this->pdf->objPdf->Image($this->filename, $mapOffset_left, $mapOffset_bottom, $map_width, $map_height, 'png');
        $this->pdf->unlink($this->filename);
    }
}
