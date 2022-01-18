<?php 
// should be invoked from cli!
require_once(dirname(__FILE__)."/../core/globalSettings.php");

require_once(dirname(__FILE__)."/../http/classes/class_syncCkan.php");

$syncCkan = new syncCkan();
// use default admin ckan api key
$syncCkan->ckanApiKey = API_KEY;
$connector = new Connector();


// load group list from remote ckan
$remoteCkanUrl = "https://daten.rlp.de";

$jsonResult = $connector->load($remoteCkanUrl . "/api/3/action/group_list");
$groupArray = array();
$resultObject = json_decode($jsonResult);
if ($resultObject->success == true) {
    foreach ($resultObject->result as $group_name) {
        echo $group_name . "\n";
        // load remote group information 
        $jsonGroupInfo = $connector->load($remoteCkanUrl . "/api/3/action/group_show?id=" . $group_name);
        $groupInfoObject = json_decode($jsonGroupInfo);
        // give back useful information to create new group
        // title, display_name, description, image_display_url, name, image_url
        $extractAttributes = array("title", "display_name", "description", "image_display_url", "name", "image_url");
        // defaults: type:group, approval_status:approved, state:active, is_organization:false
        $newGroupObject = new stdClass();
        foreach ($extractAttributes as $attribute) {
            $newGroupObject->{$attribute} = $groupInfoObject->result->{$attribute};
            
            //echo $attribute . ": " . $groupInfoObject->result->{$attribute} . "\n";
        }
        $groupArray[] = $newGroupObject;
        $jsonNewGroupObject = json_encode($newGroupObject) ."\n";
        // check if group already exists - if it does - update it else create new one
        $result = $syncCkan->getRemoteCkanGroup('{"id": "' . $newGroupObject->name . '"}');
        echo "Result for ckan group: " . $result;
        $resultGroupObject = json_decode($result);
        if ($resultGroupObject->success == false) {
            $result = $syncCkan->createRemoteCkanGroup($jsonNewGroupObject);
        } else {
            $newGroupObject->id = $resultGroupObject->result->id;
            $jsonNewGroupObject = json_encode($newGroupObject);
            $result = $syncCkan->updateRemoteCkanGroup($jsonNewGroupObject);
        }
        echo "Used ckan api key: " . $syncCkan->ckanApiKey . "\n";
        echo "Result from CKAN API: " . $result . "\n";
    }
} else {
    echo "Loading groups not successful!\n";
}
//load single group info

//create groups in coupled ckan instance
// save groups json to filesystem

echo json_encode($groupArray) . "\n";

/*
 * [{"title":"Bev\u00f6lkerung und Gesellschaft","display_name":"Bev\u00f6lkerung und Gesellschaft","description":"","image_display_url":"","name":"soci","image_url":"","id":"da621d79-0665-429a-bc9f-7ed3426cf286"},{"title":"Bildung, Kultur und Sport","display_name":"Bildung, Kultur und Sport","description":"","image_display_url":"","name":"educ","image_url":"","id":"3fc6e391-02e9-499d-99bb-7f0140e90a76"},{"title":"Energie","display_name":"Energie","description":"","image_display_url":"","name":"ener","image_url":"","id":"b0da75ae-5d81-4227-9b5d-7eea0747df26"},{"title":"Gesundheit","display_name":"Gesundheit","description":"","image_display_url":"","name":"heal","image_url":"","id":"6fa4505e-e436-45e0-85a1-68a36069a47d"},{"title":"Internationale Themen","display_name":"Internationale Themen","description":"","image_display_url":"","name":"intr","image_url":"","id":"59f0b286-e581-45cc-b7f4-deed40b8f634"},{"title":"Justiz, Rechtssystem und \u00f6ffentliche Sicherheit","display_name":"Justiz, Rechtssystem und \u00f6ffentliche Sicherheit","description":"","image_display_url":"","name":"just","image_url":"","id":"6762d2db-ec90-4f43-9ddf-fe1a81494906"},{"title":"Landwirtschaft, Fischerei, Forstwirtschaft und Nahrungsmittel","display_name":"Landwirtschaft, Fischerei, Forstwirtschaft und Nahrungsmittel","description":"","image_display_url":"","name":"agri","image_url":"","id":"b99e2591-a18e-4fd5-a51b-727234a22613"},{"title":"OpenData","display_name":"OpenData","description":"Dokumente und Daten der \u00d6ffentlichen Verwaltung, die unter einer OpenData kompatiblen Lizenz ver\u00f6ffentlicht werden.","image_display_url":"https:\/\/daten.rlp.de\/images\/hero.jpg","name":"opendata","image_url":"https:\/\/daten.rlp.de\/images\/hero.jpg","id":"e5c8445e-14eb-45d9-8e7f-64df039d94cb"},{"title":"Regierung und \u00f6ffentlicher Sektor","display_name":"Regierung und \u00f6ffentlicher Sektor","description":"","image_display_url":"","name":"gove","image_url":"","id":"99f373f1-86fa-42a6-8305-c34c46b24f20"},{"title":"Regionen und St\u00e4dte","display_name":"Regionen und St\u00e4dte","description":"","image_display_url":"","name":"regi","image_url":"","id":"09d20c6a-7174-4bb6-a25a-65d7f60e1add"},{"title":"Transparenzgesetz","display_name":"Transparenzgesetz","description":"Dokumente und Daten, die unter die Ver\u00f6ffentlichungspflichten des Landestransparenzgesetzes fallen","image_display_url":"https:\/\/tpp.rlp.de\/images\/assets\/slider\/tpp-willkommen.jpg","name":"transparenzgesetz","image_url":"https:\/\/tpp.rlp.de\/images\/assets\/slider\/tpp-willkommen.jpg","id":"d1e03775-5da6-41f0-beae-202016467c83"},{"title":"Umwelt","display_name":"Umwelt","description":"","image_display_url":"","name":"envi","image_url":"","id":"3ea6711f-cf00-47b0-89f7-9e71feb942d3"},{"title":"Verkehr","display_name":"Verkehr","description":"","image_display_url":"","name":"tran","image_url":"","id":"21e70e2e-3175-498c-8709-b7e6a7b6c1c3"},{"title":"Wirtschaft und Finanzen","display_name":"Wirtschaft und Finanzen","description":"","image_display_url":"","name":"econ","image_url":"","id":"f7ddb7a8-328b-4a55-b4d6-01177837d08b"},{"title":"Wissenschaft und Technologie","display_name":"Wissenschaft und Technologie","description":"","image_display_url":"","name":"tech","image_url":"","id":"646936e5-5c56-4293-9cce-0d1f2ec315bd"}]
 */

?>