<?php 
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_universal_wfs_factory.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
$admin = new Administration();

$json_conf = 
<<<JSON
{
  "wfs_id": 24,
  "featuretype_id": 64,
  "element_ids": [766, 767],
  "select_id": "test123",
  "option_empty": false,
  "option_value_template": "%%element[0]%%",
  "option_text_template": "%%element[0]%% (%%element[1]%%)" 
}
JSON;

$user = new User(Mapbender::session()->get("mb_user_id"));

$ajaxResponse = new AjaxResponse($_REQUEST);
if ($ajaxResponse->getMethod() == 'getSelectField') {
    $data = $ajaxResponse->getParameter('data');
    $wfs_select_conf = json_decode($data);
    $myWfsFactory = new UniversalWfsFactory ();
    $wfs = $myWfsFactory->createFromDb ( $wfs_select_conf->wfs_id );
    $is_secured = $admin->getWFSOWSstring( $wfs_select_conf->wfs_id );
    //only allow unsecured wfs or wfs where user is allowed to use
    if ($is_secured == false) {
        $elementInfo = $wfs->getElementInfoByIds ( $wfs_select_conf->featuretype_id, $wfs_select_conf->element_ids );
        $result = $wfs->getFeatureElementList($elementInfo->featuretype_name, $elementInfo->element_names, $elementInfo->namespace, $elementInfo->namespace_location, $filter=null, $version=false, $method="GET");
        //build select html
        $html_snippet = "<select id='" . $wfs_select_conf->select_id . "'>\n";
        if ($wfs_select_conf->option_empty) {
            $html_snippet .= "    <option>" . $wfs_select_conf->option_empty . "</option>";
        }
        $list_index = 0;
        foreach ($result[$elementInfo->element_names[0]] as $element_0) {
            $value = $wfs_select_conf->option_value_template;
            $text = $wfs_select_conf->option_text_template;
            $element_index = 0;
            foreach ($elementInfo->element_names as $element_name) {
                $value = str_replace("%%element[" . $element_index . "]%%", $result[$elementInfo->element_names[$element_index]][$list_index], $value);
                $text = str_replace("%%element[" . $element_index . "]%%", $result[$elementInfo->element_names[$element_index]][$list_index], $text);
                $element_index++;
            }
            $html_snippet .= "<option value='" . $value . "'>" . $text . "</option>\n";
            $list_index++;
        }
        $html_snippet .= "</select>\n";
        $ajaxResponse->setSuccess(true);
        $ajaxResponse->setMessage("Select options generated from wfs");
  
        $resultObj['select'] = $html_snippet;
        
        $ajaxResponse->setResult($resultObj);
        $ajaxResponse->send();
        die();
    } else {
        $ajaxResponse->setSuccess(false);
        $ajaxResponse->setMessage("WFS is secured - could not be used to pull options!");
        $ajaxResponse->send();
        die();
    }
} else {
    $ajaxResponse->setSuccess(false);
    $ajaxResponse->setMessage("Method not supported");
    $ajaxResponse->send();
    die();
}
?>