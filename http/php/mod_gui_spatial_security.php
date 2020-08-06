<?php

$e_id="gui_spatial_security";
require_once(dirname(__FILE__)."/../php/mb_validatePermission.php");

function get_guis() {
    $conn = db_connect();

    $sql = 'select gui_id, gui_name, asgml(spatial_security) as gml_value from gui order by gui_name';

    $result = pg_query($conn, $sql);

    $guis = [];

    while ($row = pg_fetch_assoc($result)) {
        $guis[] = $row;
    }

    return $guis;
}

function update_spatial_security($id, $gml_value) {
    $conn = db_connect();

    $sql = 'update gui set spatial_security = st_geomfromgml($1::text) where gui_id = $2';

    return pg_query_params($conn, $sql, [$gml_value, $id]);
}

function show_form($guis) {
    global $self;

    echo "<h3>Räumliche Absicherung der GUIS</h3>";
    echo "<form id=\"gui_spatial_security\" action=\"$self\" method=\"post\">";
    echo "<select size=14 name=\"gui_id\">";
    foreach ($guis as $gui) {
        $gml = htmlentities($gui['gml_value']);
        $onclick = "document.querySelector('#gui_spatial_security [name=gml_value]').value = '{$gml}'";
        echo "<option value=\"{$gui['gui_id']}\" onclick=\"$onclick\">{$gui['gui_name']}</option>";
    }
    echo "<input name=\"gml_value\" type=\"text\">";
    echo "<input type=\"submit\" name=\"update\" value=\"Speichern\">";
    echo "</form>";
}

function main() {
    if (array_key_exists('update', $_REQUEST)) {
        if (update_spatial_security($_REQUEST['gui_id'], $_REQUEST['gml_value'])) {
            echo "Speicherung erfolgreich.";
            new mb_notice("Saved spatial security for gui {$_REQUEST['gui_id']}");
        } else {
            $error = pg_last_error();
            echo "Speicherung fehlgeschlagen. $error";
        }
    }

    $guis = get_guis();

    show_form($guis);
}

main();

?>