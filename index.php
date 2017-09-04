<?php
require 'vendor/autoload.php';
/**
 * Create Database connection
 */

$config = json_decode(file_get_contents('./config.json'));
include "code.php";
?>

<!DOCTYPE html>
<html class="heightmax">

<head>
    <title>MyTableTest</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Language" content="de-DE" />

    <link rel="stylesheet" href="bootstrap/css/bootstrap.css">
    <link rel="stylesheet" href="datatables/datatables.min.css">
    <link rel="stylesheet" href="main.css">
    <script src="jquery/jquery-3.2.1.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="datatables/datatables.min.js"></script>
    <script src="jszip/jszip.min.js"></script>
    <script src="code.js"></script>
</head>
<?php
echo'
    <body>
        <div id="overlay" onclick="overlayOff();">
        </div>
        <div class="container">
        ';
include "tbl/display.php";
        echo'
        </div>
        </body>
</html>
';