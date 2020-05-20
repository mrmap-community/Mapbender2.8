<?php

namespace spatial_security {

    require_once(dirname(__FILE__) . "/../conf/mapbender.conf");
    require_once(dirname(__FILE__) . "/../http/classes/class_group.php");
    use Group;
    use Imagick;
    use ImagickException;
    use mb_exception;
    use mb_notice;
    use User;

    function read_post() {
        $spatialSecurity = "";
        if (array_key_exists("spatial_security", $_POST)) {
            $spatialSecurity = $_POST["spatial_security"];
            if (!is_string($spatialSecurity)) {
                $spatialSecurity = implode(",", $spatialSecurity);
            }
        }
        return $spatialSecurity;
    }

    function database_write($type, $id, $value)
    {
        if (SPATIAL_SECURITY) {
            switch ($type) {
                case 'gui':
                    $sql = "UPDATE gui SET spatial_security = ST_GeomFromText($1) WHERE gui_id = $2";
                    break;
                case 'user':
                    $sql = "UPDATE mb_user SET spatial_security = $1 WHERE mb_user_id = $2";
                    break;
                case 'group':
                    $sql = "UPDATE mb_group SET spatial_security = $1 WHERE mb_group_id = $2";
                    break;
                default:
                    return;
            }

            $conn = db_connect();
            pg_query_params($conn, $sql, array($value, $id));
        }
    }

    function database_read($type, $id) {
        if (!SPATIAL_SECURITY) {
            return '';
        } else {
            switch ($type) {
                case 'gui':
                    $sql = "SELECT ST_AsText(spatial_security) FROM gui WHERE gui_id = $1";
                    break;
                case 'user':
                    $sql = "SELECT spatial_security FROM mb_user WHERE mb_user_id = $1";
                    break;
                case 'group':
                    $sql = "SELECT spatial_security FROM mb_group WHERE mb_group_id = $1";
                    break;
                default:
                    return '';
            }

            $conn = db_connect();
            $result = pg_query_params($conn, $sql, array($id));
            return pg_fetch_row($result)[0];
        }
    }

    function show_input($currentValue, $table)
    {
        if (SPATIAL_SECURITY) {
            echo '<div style="float:right;width:250px;height:400px;">';
            echo "Räumliche Absicherung<br/>";
            if (SPATIAL_SECURITY_ROLETYPE === "user_group") {
                echo "<select name=\"spatial_security[]\" multiple style=\"width:250px;height:400px;background-color:white;\">";

                $conn = db_connect();
                $values = !empty($currentValue) ? explode(",", $currentValue) : array();
                if ($conn) {
                    $result = pg_query($conn, "SELECT id, name FROM spatial_security;");
                    while ($row = pg_fetch_assoc($result)) {
                        $value = $row["id"];
                        $name = $row["name"];
                        $selected = in_array($value, $values) ? 'selected="selected"' : '';

                        echo "\n<option value=\"$value\" $selected>$name</option>";
                    }
                }

                echo "</select>";
            } else if (SPATIAL_SECURITY_ROLETYPE === "gui") {
                echo '<input type="text" name="spatial_security">';
            }
            echo "</div>";
        }
    }

    function get_mapserver_keys($session) {
        if (SPATIAL_SECURITY_ROLETYPE === "user_group") {
            $user = new User($session->get('mb_user_id'));
            $user->load();

            $keys = empty($user->spatialSecurity) ? array() : explode(",", $user->spatialSecurity);

            foreach ($user->getGroupsByUser() as $groupId) {
                $group = new Group($groupId);
                if (!empty($group->spatialSecurity)) {
                    $keys = array_merge($keys, explode(",", $group->spatialSecurity));
                }
            }

            return join(",", array_unique($keys));
        } else if (SPATIAL_SECURITY_ROLETYPE === "gui") {
            new mb_notice('spatial: gui_id: ' . $session->get("mb_user_gui"));
            return $session->get("mb_user_gui");
        } else {
            return '';
        }
    }

    function get_mask_url($reqParams, $keys) {
        $srs = urlencode(empty($reqParams["srs"]) ? $reqParams["crs"] : $reqParams["srs"]);
        $bbox = urlencode($reqParams["bbox"]);
        $width = $reqParams["width"];
        $height = $reqParams["height"];

        $server = MAPSERVER;
        $map = realpath(__DIR__ . "/../mapserver/spatial_security.map");

        if (SPATIAL_SECURITY_ROLETYPE === "user_group") {
            $table = 'spatial_security';
            $key_column = 'id';
            $geom_column = 'geom';
        } else if (SPATIAL_SECURITY_ROLETYPE === "gui") {
            $table = 'gui';
            $key_column = 'gui_id';
            $geom_column = 'spatial_security';
            $keys = "'$keys'";
        } else {
            $table = '';
            $key_column = '';
            $geom_column = '';
        }

        $url ="$server?map=$map&version=1.1.1&request=GetMap&service=WMS&format=image%2Fpng&layers=mask"
            . "&srs=$srs&bbox=$bbox&width=$width&height=$height&keys=$keys&table=$table&key_column=$key_column&geom_column=$geom_column";

        new mb_notice("spatial security: mask wms url: $url");

        return $url;
    }

    function get_mask($reqParams, $session) {
        $keys = get_mapserver_keys($session);

        if ($keys === '') {
            return null;
        }

        $url = get_mask_url($reqParams, $keys);
        //$e = new mb_exception("maskurl:".$url);
        $mask = new Imagick();
        $imageBlob = file_get_contents($url);
        try {
            $mask->readImageBlob($imageBlob);
        } catch (ImagickException $e) {
            new mb_exception("Error loading image. Response: $imageBlob");
            return null;
        }

        return $mask;
    }
}
?>