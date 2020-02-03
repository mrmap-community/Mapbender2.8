<html>

<head>

<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';
#require_once(dirname(__FILE__)."/../../conf/mapbender.conf");
#require_once(dirname(__FILE__)."/../classes/class_administration.php");
#require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";
require_once dirname(__FILE__) . "/../classes/class_user.php";
?>

<title>BPlanID f&uuml;r Verb&auml;nde</title>

<style type="text/css">
body
{
font-family: Arial, Helvetica, sans-serif;	
}
h1
{
color: #A52A2A;
font-family: arial, verdana, sans serif;
font-style: italic;
font-weight: bold;
font-size: 175%;
}
</style>

<script language="JavaScript" type="text/javascript">
</script>

</head>

<body>

<table>

<?php
//prefix
$prefix = "07G";
$user = new User();
$userId = $user->id;

if (isset($_REQUEST["id"])) {
	#---------------------------
	$uuid = new Uuid();
	$sql = "SELECT mb_user_name, mb_user_email FROM mapbender.mb_user WHERE mb_user_id=$1";
	$v = array($userId);
	$t = array('i');
	$res = db_prep_query($sql, $v, $t);
	while($row = db_fetch_array($res)) {
		$mb_user_name=$row['mb_user_name'];
		$mb_user_email=$row['mb_user_email'];
	}

	#-----------------------------
 	$sql = "INSERT INTO bplan_id (fkey_mb_user_id, uuid, fkey_mb_user_name, fkey_mb_user_email) VALUES ($1, $2, $3, $4)";
	//$sql = "INSERT INTO public.bplan_id (fkey_mb_user_id, uuid, fkey_mb_user_name, fkey_mb_user_email) VALUES (".$userId.", '".$uuid."', '".$mb_user_name."','".$mb_user_email."')";
	#$e = new mb_exception("user_id: ".$_SESSION["mb_user_id"]);
	#$e = new mb_exception("uuid: ".$uuid);
	#$e = new mb_exception("mb_user_name: ".$mb_user_name);
	#$e = new mb_exception("mb_user_email: ".$mb_user_email);
	
	$v = array($userId, $uuid, $mb_user_name, $mb_user_email);
	$t = array('i','s','s','s');
	$res = db_prep_query($sql, $v, $t);
	#---------------------------------------------
	$sql = "SELECT id FROM bplan_id WHERE uuid = $1";
	$v = array($uuid);
	$t = array('s');
	$res = db_prep_query($sql, $v, $t);
	while($row=db_fetch_array($res)) {
		$id = $row['id'];
	}
}

echo "<form  method=\"POST\" action=".$_SERVER['PHP_SELF'].">";
echo " <h1>ID f&uuml;r einen Bebauungsplan</h1>";
echo "<table border='0'>";

echo "<tr height=50>";
echo "<td>";
echo "<font size=\"3\">Sie sind momentan eingeloggt als: &nbsp;  </font>";
echo "<font size=\"3\"><b>".$_SESSION["mb_user_name"]."</b></font>";
echo "</td>";
echo "</tr>";

echo "<tr height=50>";
echo "<td>";
echo "<font size=\"4\">Eine ID f&uuml;r einen Bebauungsplan anfordern &nbsp; </font>";
echo "<input type=\"submit\" name=\"id\" value=\"Anfordern\" onclick=\"return confirm('Wollen Sie wirklich eine ID f&uuml;r einen Bebauungsplan anfordern?');\">";
echo "</td>";
echo "</tr>";
if (isset($_REQUEST["id"])) {
	//generate id string
	$idString = $prefix.str_repeat("0", (5 - strlen((string)$id))). $id;
	echo "<tr height=50>";
	echo "<td>";
	echo "<font size=\"4\">Die n&auml;chstfreie ID f&uuml;r einen Bebauungsplan lautet: &nbsp; </font>";
	echo "<font color=#A52A2A size=\"5\"><b>".$idString."</b></font>";
	echo "</td>";
	echo "</tr>";
}
echo "</form>";	
?>
</table>
</body>
</html>


