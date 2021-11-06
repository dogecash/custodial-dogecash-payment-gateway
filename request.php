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
    if (array_key_exists("api_key", $_GET) && array_key_exists("invoice", $_GET) && array_key_exists("amount", $_GET)) {
        $api_key = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['api_key']);
        $invoice = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['invoice']);
        $amount = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['amount']);

        if ($mysqli->query("SELECT dogec_addr FROM api_keys WHERE `key` = '$api_key'")->num_rows != 1) {
            echo json_encode([
                "status"=>400,
                "message"=>"Invalid API Key"
            ]);
            return;
        }

        try{
            $dogec_rpc->getblockchaininfo();
        } 
        catch (\Throwable $e) {
            echo json_encode([
                "status"=>500,
                "message"=>"Error connecting to dogecash daemon. Inform the system admin."
            ]);
            return;
        }

        $address = $mysqli->query("SELECT dogec_addr FROM invoice_status WHERE invoice = '$invoice'");

        if ($address->num_rows == 1) {
            echo json_encode([
                "status"=>200,
                "address"=>($address->fetch_array(MYSQLI_NUM))[0]
            ]);
            return;
        }

        $req_address = $dogec_rpc->getnewaddress()[0];
        $sql = "INSERT INTO invoice_status(`dogec_addr`, `invoice`, `amount`, `status`) VALUES('$req_address', '$invoice', '$amount', 'unpaid')";
        $mysqli->query($sql);

        echo json_encode([
            "status"=>200,
            "address"=>$req_address
        ]);
        return;
    }
    else {
        echo json_encode([
            "status"=>400,
            "message"=>"Invalid Request"
        ]);
    }
}