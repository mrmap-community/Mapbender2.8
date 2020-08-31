<?php

class mbImageDecorator extends mbTemplatePdfDecorator
{

    protected $pageElementType = "image";
    protected $elementId;
    protected $angle = 0;
    protected $overrideMembersFromRequest = array("angle");

    public function __construct($pdfObj, $elementId, $mapConf, $controls, $manualValues)
    {
        parent::__construct($pdfObj, $mapConf, $controls, $manualValues);
        $this->elementId = $elementId;
        $this->override();
        $this->decorate();
    }

    public function override()
    {
        $this->overrideMembers();
    }

    public function decorate()
    {
        #Image($file,$x,$y,$w=0,$h=0,$type='',$link='', $isMask=false, $maskImg=0, $angle=0)
        $this->pdf->objPdf->Image($this->conf->filename, $this->conf->x_ul, $this->conf->y_ul, $this->conf->width, $this->conf->height, '', '', false, 0, 5, -1 * $this->angle);
    }
}


?>
