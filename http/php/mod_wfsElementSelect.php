<?php 
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_universal_wfs_factory.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
$admin = new Administration();

/*
 * https://stackoverflow.com/questions/797251/transposing-multidimensional-arrays-in-php
 * Apr 28, 2009 at 12:17
 */
function transpose($array, &$out, $indices = array())
{
    if (is_array($array))
    {
        foreach ($array as $key => $val)
        {
            //push onto the stack of indices
            $temp = $indices;
            $temp[] = $key;
            transpose($val, $out, $temp);
        }
    }
    else
    {
        //go through the stack in reverse - make the new array
        $ref = &$out;
        foreach (array_reverse($indices) as $idx)
            $ref = &$ref[$idx];
            $ref = $array;
    }
}

/*
 * https://www.php.net/manual/en/function.usort.php - Example #4
 */
function build_sorter($key) {
    return function ($a, $b) use ($key) {
        return strnatcmp($a[$key], $b[$key]);
    };
}

$json_conf = 
<<<JSON
{
  "wfs_id": 24,
  "featuretype_id": 64,
  "element_ids": [766, 767],
  "element_id_order" : 1,
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
        //$e = new mb_exception("php/mod_wfsElementSelect.php: elementInfo: " . json_encode($elementInfo));
        //$e = new mb_exception("php/mod_wfsElementSelect.php: elementInfo: " . json_encode($wfs->version));
        $result = $wfs->getFeatureElementList($elementInfo->featuretype_name, $elementInfo->element_names, $elementInfo->namespace, $elementInfo->namespace_location, $filter=null, $version=false, $method="GET");
        //order by name if defined
        if (isset($wfs_select_conf->element_id_order) && is_int($wfs_select_conf->element_id_order)) {
            //transpose
            //https://stackoverflow.com/questions/797251/transposing-multidimensional-arrays-in-php
            $resultT = array();
            transpose($result, $resultT);
            //https://www.php.net/manual/en/function.usort.php Example #4
            usort($resultT, build_sorter($elementInfo->element_names[$wfs_select_conf->element_id_order]));
            transpose($resultT, $result);
            unset($resultT);
        }
        //build select html
        $html_snippet = "<select id='" . $wfs_select_conf->select_id . "'>\n";
        if ($wfs_select_conf->option_empty) {
            $html_snippet .= "    <option>" . $wfs_select_conf->option_empty . "</option>\n";
        } else {
            $html_snippet .= "    <option></option>\n";
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