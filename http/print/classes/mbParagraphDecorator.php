<?php

class mbParagraphDecorator extends mbTemplatePdfDecorator
{

    protected $pageElementType = "para";
    protected $elementId;
    /* a decorator should declare which parameters could be overwritten through the request object */
    protected $overrideMembersFromRequest = array("value");
    /* the actual text that should be printed */
    protected $value = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, ...";

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
        /* some of these values should or could be set in the config */
        $this->pdf->objPdf->setTextColor(0, 0, 0);
        $this->pdf->objPdf->setFillColor(255, 255, 255);
        $this->pdf->objPdf->setDrawColor(0, 0, 0);
        $this->pdf->objPdf->SetLineWidth($this->conf->border_width);
        $this->pdf->objPdf->SetXY($this->conf->x_ul, $this->conf->y_ul);
        $this->pdf->objPdf->setFont($this->conf->font_family, "", $this->conf->font_size);
        #$this->pdf->objPdf->Cell($this->conf->width, $this->conf->height, utf8_decode($this->value), $this->conf->border, 0, $this->conf->align, $this->conf->fill);
        //use MultiCell for line breaks after cell end is reached
        $this->pdf->objPdf->MultiCell($this->conf->width, $this->conf->height, utf8_decode($this->value), $this->conf->border, $this->conf->align, $this->conf->fill);
    }
}

?>