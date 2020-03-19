<?php

abstract class mbTemplatePdfDecorator extends mbTemplatePdf
{

    /* the template pdf object to decorate */
    public $pdf;
    /* the conf object for the desired decoration */
    public $conf;
    /* the controls object of the configuration */
    public $controls;
    /* possibly a zIndex will be needed */
    public $zIndex;
    /* manually setted values */
    private $manualValues;

    public function __construct($pdfObj, $mapConf, $controls, $manualValues = array())
    {
        $this->pdf = $pdfObj;
        $this->conf = $mapConf;
        $this->controls = $controls;
        $this->manualValues = $manualValues;
    }

    protected function overrideMembers()
    {
        $idsFromRequest = $this->getPageElementLink($this->elementId);

        foreach ($this->overrideMembersFromRequest as $overrideMemberFromRequest) {
            /* take the value of the config in every case */
            $this->{$overrideMemberFromRequest} = $this->conf->{$overrideMemberFromRequest};
            foreach ($idsFromRequest as $requestKey => $memberId) {
                $e = new mb_notice("mbOverviewDecorator: before override: set " . $memberId . " to " . $requestKey);
                if ($overrideMemberFromRequest == $memberId && $this->hasValue($requestKey)) {
                    $this->{$overrideMemberFromRequest} = $this->getValue($requestKey);
                    $e = new mb_notice("mbOverviewDecorator: override from Request: " . $overrideMemberFromRequest . " to " . $this->{$overrideMemberFromRequest});
                }
            }
        }
    }

    protected function hasValue($key)
    {
        return (array_key_exists($key, $this->manualValues) && $this->manualValues[$key] != null) || (array_key_exists($key, $_REQUEST) && $_REQUEST[$key] != "");
    }

    protected function getValue($key)
    {
        return array_key_exists($key, $this->manualValues) ? $this->manualValues[$key] : $_REQUEST[$key];
    }

    public function getPageElementLink($pageElementId)
    {
        $pageElementLinkArray = array();
//        $e = new mb_notice("mbTemplatePdfDecorator: pageElementId: " . $pageElementId);
        foreach ($this->controls as $control) {
            if (isset($control->pageElementsLink)) {
                foreach ($control->pageElementsLink as $pageElementLinkId => $pageElementLinkValue) {
//                $e = new mb_notice("mbTemplatePdfDecorator: pageElementsLink: " . $control->id);
                    if ($pageElementLinkId == $pageElementId)
                        #array_push($pageElementLinkArray, array($control->id => $pageElementLinkValue));
                        $pageElementLinkArray[$control->id] = $pageElementLinkValue;
                }
            }
        }
        return $pageElementLinkArray;
    }

    abstract public function override();

    abstract public function decorate();

}

?>
