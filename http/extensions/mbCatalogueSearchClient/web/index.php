<?php
    require_once __DIR__ . '/../src/conf/system.php';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=9;IE=10;IE=Edge,chrome=1"/>
        <title><?php echo $conf->get('title'); ?></title>
        <link rel="stylesheet" href="<?php echo isset($_REQUEST['style']) ? $_REQUEST['style'] : 'css/main.css' ?>">
        <link rel="stylesheet" href="./vendor/leaflet/leaflet.css" />
        <link rel="stylesheet" href="./vendor/zebra/css/default.css" type="text/css">
        <script type="text/javascript" src="./vendor/leaflet/leaflet.js"></script>
        <script>
            var BASEDIR = '<?php echo $conf->get('system:basedir'); ?>';
        </script>
        <script type="text/javascript" src="js/jquery-1.12.4.min.js"></script>
        <script type="text/javascript" src="vendor/zebra/javascript/zebra_datepicker.js"></script>
        <script type="text/javascript" src="js/all.js"></script>
    </head>
    <body>
        <?php $templating->parseView($conf->get('template:base'), array(
            'placeholder' => $conf->get('placeholder'),
            'searchitems' => $conf->get('search')
        )); ?>
    </body>
</html>
<?php
