<?php
require_once(dirname(__FILE__) . "/../../extensions/fpdf/mb_fpdi.php");
require_once(dirname(__FILE__) . "/../../php/log_error_exec.php");
require_once(dirname(__FILE__) . "/../../classes/class_connector.php");

class mbTemplatePdf extends mbPdf
{
    /* it seems several decorators are going to need this information */
    public $mapInfo = array();
    public $unlinkFiles = false;
    public $logRequests = false;
    public $logType = "file";
    public $featureInfo;
    private $insertPages = array();

    public function __construct($jsonConf)
    {
        $this->confPdf = $jsonConf;
        if (!$this->confPdf->orientation || !$this->confPdf->units || !$this->confPdf->format) {
            die("no valid config");
        }
        $this->objPdf = new mb_fpdi($this->confPdf->orientation, $this->confPdf->units, $this->confPdf->format);
        $this->outputFileName = $this->generateOutputFileName("map", "pdf");
    }

    public function setMapInfo($x_ul, $y_ul, $width, $height, $aBboxString)
    {
        $this->mapinfo["x_ul"] = $x_ul;
        $this->mapinfo["y_ul"] = $y_ul;
        $this->mapinfo["width"] = $width;
        $this->mapinfo["height"] = $height;
        $this->mapinfo["extent"] = $aBboxString;
        $e = new mb_notice("mbTemplatePdf: setting mapInfo ...");
    }

    public function getMapInfo()
    {
        $e = new mb_notice("mbTemplatePdf: getting mapInfo ..");
        return $this->mapinfo;
    }

    public function setMapExtent($aBboxString)
    {
        $this->mapinfo["extent"] = $aBboxString;
        $e = new mb_notice("mbTemplatePdf: setting mapExtent to " . $this->mapinfo["extent"]);
    }

    public function getMapExtent()
    {
        $e = new mb_notice("mbTemplatePdf: getting mapExtent as " . $this->mapinfo["extent"]);
        return $this->mapinfo["extent"];
    }

    public function adjustBbox($elementConf, $aBboxArray, $aSrsString)
    {
        $aMbBbox = new Mapbender_bbox($aBboxArray[0], $aBboxArray[1], $aBboxArray[2], $aBboxArray[3],
            $aSrsString);
        $aMap = new Map();
        $aMap->setWidth($elementConf->width);
        $aMap->setHeight($elementConf->height);
        $aMap->calculateExtent($aMbBbox);
        $this->mapinfo["scale"] = isset($_REQUEST["scale"]) ? $_REQUEST["scale"] : $aMap->getScale($elementConf->res_dpi);
        $adjustedMapExt = $aMap->getExtentInfo();
        return implode(",", $adjustedMapExt);
    }

    private function pageElementsContainsType($pageConf, $type)
    {
        foreach ($pageConf->elements as $_ => $pageElementConf) {
            if ($pageElementConf->type == $type) {
                return true;
            }
        }
        return false;
    }

    private function renderElements($pageConf, $manualValues = array())
    {
        $controls = $this->confPdf->controls;

        foreach ($pageConf->elements as $pageElementId => $pageElementConf) {
            switch ($pageElementConf->type) {
                case "map":
                    $err = new mbMapDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    $err = new mbSvgDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues, "map_svg_kml");
                    $err = new mbSvgDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues, "map_svg_measures");
                    break;
                case "overview":
                    $err = new mbOverviewDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "text":
                    $err = new mbTextDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "para":
                    $err = new mbParagraphDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
//                    case "measure": ignored, s. case "map":
//                        $err = new mbSvgDecorator($this, $pageElementId, $pageElementConf, $controls, "map_svg_measures");
//                        $err = new mbMeasureDecorator($this, $pageElementId, $pageElementConf, $controls);
//                        break;
                case "image":
                    $err = new mbImageDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "legend":
                    if ($this->printLegend == 'true') {
                        $err = new mbLegendDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    }
                    break;
                case "permanentImage":
                    $err = new mbPermanentImgDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "marker":
                    break;
            }
        }
    }

    public function render()
    {
        foreach ($this->confPdf->pages as $pageConf) {
            $this->objPdf->setSourceFile(dirname(__FILE__) . "/../" . $pageConf->tpl);
            $tplidx = $this->objPdf->importPage($pageConf->useTplPage);

            if (count($pageConf->elements) == 1 && $this->printLegend == 'false' && $this->pageElementsContainsType($pageConf, "legend")) {
                break;
            } else if ($pageConf->featureInfo) {
                $this->renderFeatureInfos($tplidx, $pageConf);
                break;
            } else {
                $this->objPdf->addPage();
                $this->objPdf->useTemplate($tplidx);
                $this->renderElements($pageConf);
            }
        }

        $this->isRendered = true;
    }

    public function save()
    {
        if ($this->isRendered) {
            $this->objPdf->Output(TMPDIR . "/" . $this->outputFileName, "F");
            $this->isSaved = true;
            if (!empty($this->insertPages)) {
                new mb_notice("inserting pages");

                $dir = TMPDIR;
                $base = $this->baseOutputFileName();
                log_error_exec("pdfseparate $dir/$this->outputFileName $dir/$base-%d.pdf");
                $origPages = $this->objPdf->PageNo();
                $mergePdfs = array();
                for ($i = 1; $i <= $origPages; $i++) {
                    $mergePdfs[] = "$dir/$base-$i.pdf";
                    if (array_key_exists($i, $this->insertPages)) {
                        $mergePdfs[] = $this->insertPages[$i];
                    }
                }
                $mergeNames = join(" ", $mergePdfs);
                log_error_exec("pdfunite $mergeNames $dir/$this->outputFileName");
                log_error_exec("rm $mergeNames");
            }
        }
    }

    public function unlink($filename)
    {
        if ($this->unlinkFiles && $this->unlinkFiles == 'true') {
            unlink($filename);
        }
    }

    public function logWmsRequests($requestType, $wmsRequest)
    {
        if ($this->logRequests && $this->logRequests == 'true') {
            include_once(dirname(__FILE__) . "/../../classes/class_log.php");
            $logMessage = new log("printPDF_" . $requestType, $wmsRequest, "", $this->logType);
        }
    }

    public function baseOutputFileName()
    {
        return preg_replace("/\\.pdf$/", "", $this->outputFileName);
    }

    private function renderFeatureInfos($tplidx, $pageConf)
    {
        new mb_notice("print featureinfo");

        $mapUrls = explode("___", $_REQUEST["map_url"]);

        new mb_notice("print featureinfo: mapUrls: " . join(", ", $mapUrls));

        $backgroundUrls = array();

        foreach ($this->featureInfo->backgroundWMS as $index) {
            $backgroundUrls[] = $mapUrls[$index];
        }

        new mb_notice("print featureinfo: " . json_encode($backgroundUrls));

        foreach ($this->featureInfo->urls as $url) {
            if (!$url->inBbox) {
                continue;
            }
            $this->objPdf->addPage();
            $this->objPdf->useTemplate($tplidx);

            // extract specific wms layer from feature info request

            $matches = array();
            preg_match("/^[^?]*/", $url->request, $matches);
            $host = $matches[0];
            preg_match("/LAYERS=([^&]*)/", $url->request, $matches);
            $wmsLayer = $matches[1];

            new mb_notice("print featureinfo: host: $host, layer: $wmsLayer");

            // find wms url in mapUrls that contains the wms layer

            new mb_notice("print featureinfo: pattern: ". "/" . preg_quote($host, '/') . ".*&LAYERS=[^&]*" . preg_quote($wmsLayer, '/') . "/");

            $wmsUrls = preg_grep("/" . preg_quote($host, '/') . ".*&LAYERS=[^&]*" . preg_quote($wmsLayer, '/') . "/", $mapUrls);
            if (count($wmsUrls) > 1) {
                new mb_exception("print featureinfo: Found more than one fitting layer for feature info request.");
                continue;
            } else if (count($wmsUrls) < 1) {
                new mb_exception("print featureinfo: Found no fitting layer for feature info request.");
                continue;
            }
            $wmsUrl = reset($wmsUrls);

            new mb_notice("print featureinfo: found url: $wmsUrl");

            // find position of the wms layer in the found map url

            preg_match("/LAYERS=([^&]*)/", $wmsUrl, $matches);
            $allWmsLayers = explode(",", $matches[1]);
            $layerPosition = array_search($wmsLayer, $allWmsLayers);

            new mb_notice("print featureinfo: layer position: $layerPosition");

            $mapUrl = preg_replace("/LAYERS=[^&]*/", "LAYERS=$wmsLayer", $wmsUrl);

            // find fitting style in map url

            if (preg_match("/STYLES=([^&]*)/", $wmsUrl, $matches)) {
                $allStyles = explode(",", $matches[1]);
                $style = $allStyles[$layerPosition];

                new mb_notice("print featureinfo: style: $style");

                $mapUrl = preg_replace("/STYLES=[^&]*/", "STYLES=$style", $mapUrl);
            }

            // construct new map url

            $mapUrl = join("___", array_merge($backgroundUrls, array($mapUrl)));

            new mb_notice("print featureinfo: new url: $mapUrl");

            $legendUrl = $url->legendurl !== "empty" ? $url->legendurl : "";

            $manualValues = array(
                "title" => $url->title,
                "map_url" => $mapUrl,
                "legend_url" => json_encode(array(
                    array(
                        "Legende" => array(
                            array(
                                "title" => $url->title,
                                "legendUrl" => $legendUrl
                            )
                        )
                    )
                ))
            );

            $this->renderElements($pageConf, $manualValues);

            require_once(dirname(__FILE__) . "/../../extensions/dompdf/autoload.inc.php");

            $featureInfoConnector = new connector();
            $featureInfoConnector->set("timeOut", "10");
            $featureInfoConnector->load($url->request);
            $result = $featureInfoConnector->file;

            if ($errors) {
                new mb_exception("Error getting feature info request: " . $errors);
            }

            if ($result) {
                $dompdf = new Dompdf\Dompdf();

                $format = strtoupper($this->confPdf->format);
                $orientationMap = array(
                    "P" => "portrait",
                    "L" => "landscape"
                );
                $orientation = $orientationMap[$this->confPdf->orientation];

                $dompdf->setPaper($format, $orientation);

                if (preg_match("/[?&]INFO_FORMAT=text\/plain/i", $url->request)) {
                    $result = nl2br(wordwrap($result, 75, "\n", true));
                }

                $dompdf->loadHtml("$result");
                $dompdf->render();

                $pageNo = $this->objPdf->PageNo();
                $fileName = TMPDIR . "/" . $this->baseOutputFileName() . "-$pageNo-fi.pdf";
                file_put_contents($fileName, $dompdf->output());
                $this->insertPages[$pageNo] = $fileName;
            }
        }
    }
}

?>
