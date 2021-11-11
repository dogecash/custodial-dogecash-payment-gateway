<?php
require_once('vendor/autoload.php');

use Denpa\Bitcoin\Client as DogecClient;
include 'config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$dogec_rpc = new DogecClient('http://' . $username . ':' . $password . '@' . $host . ':' . $port);
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_database);

if ($mysqli->connect_errno) {
    echo "Failed to connect to MYSQL database" . $mysqli->connect_errno;
    die();
}

$exists = $mysqli->query("SHOW TABLES LIKE 'invoice_status'")->num_rows == 1;

if (!$exists) {
    echo "Table does not exist. Make sure to import sql file into database";
    return;
}

if ($_GET) {
    if (array_key_exists("address", $_GET) && !array_key_exists("api_key", $_GET)) {
        $address = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['address']);
        $valid_address = $dogec_rpc->validateAddress($address)['isvalid'];
        if (!$valid_address) {
            echo json_encode([
                "status"=>400,
                "message"=>"Invald Address"
            ]);
            return;
        }
        $query = $mysqli->query("SELECT `key` FROM api_keys WHERE dogec_addr = '$address'");

        if ($query->num_rows == 1) {
            echo json_encode([
                "status"=>200,
                "api_key"=>$query->fetch_array(MYSQLI_NUM)[0]
            ]);
            return;
        }
        else {
            $api_key = md5(uniqid($prefix=$address));
            $query = "INSERT INTO api_keys(`dogec_addr`, `key`) VALUES('$address', '$api_key')";
            $mysqli->query($query);

            echo json_encode([
                "status"=>200,
                "api_key"=>$api_key
            ]);
            return;
        }
    }
    else {
        echo json_encode([
            "status"=>400,
            "message"=>"Invalid Request"
        ]);
    }
}