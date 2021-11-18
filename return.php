<?php
require_once('vendor/autoload.php');

use Denpa\Bitcoin\Client as DogecClient;
use GuzzleHttp\Client;
include 'config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$dogec_rpc = new DogecClient('http://' . $username . ':' . $password . '@' . $host . ':' . $port);
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_database);
$client = new Client();

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
    if (array_key_exists("api_key", $_GET) && array_key_exists("invoice", $_GET)) {
        $api_key = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['api_key']);
        $invoice_num = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['invoice']);

        $apiKey = $mysqli->query("SELECT dogec_addr FROM api_keys WHERE `key` = '$api_key'");

        if ($apiKey->num_rows != 1) {
            echo json_encode([
                "status"=>400,
                "message"=>"Invalid API Key"
            ]);
            return;
        }

        if ($mysqli->query("SELECT invoice FROM invoice_status WHERE invoice = '$invoice_num'")->num_rows != 1) {
            echo json_encode([
                "status"=>400,
                "message"=>"Invalid Invoice"
            ]);
            return;
        }

        $invoice = $mysqli->query("SELECT dogec_addr,amount FROM invoice_status WHERE invoice = '$invoice_num'")->fetch_array(MYSQLI_NUM);
        $address = $invoice[0];
        $amount = $invoice[1];

        $apiAddress = ($apiKey->fetch_array(MYSQLI_NUM))[0];

        try {
            $request = $client->request('GET', "https://api2.dogecash.org/balance/$address");
        } catch(\Throwable $e) {
            echo json_encode([
                "status"=>400,
                "message"=>"Unable to get address balance. Please contact admins."
            ]);
            return;
        }
        $response = json_decode($request->getBody()->getContents(), true);
        $addr_bal = $response['result']['balance'] / 1e8;

        if ($addr_bal >= $amount) {
            $txid = $mysqli->query("SELECT txid from invoice_status WHERE invoice = '$invoice_num'")->fetch_array(MYSQLI_NUM)[0];

            if(empty($txid))
            {
                //send funds to original address
                $transaction = $dogec_rpc->sendToAddress($apiAddress, $amount)->get();

                if($transaction)
                {
                    $mysqli->query("UPDATE invoice_status SET `status` = 'paid',
                    `txid` = '$transaction' WHERE invoice = '$invoice_num'");
                    $txid = $transaction;
                }
            }
            
            echo json_encode([
                "status"=>200,
                "inv_status"=>"paid",
                "txid" => $txid
            ]);

            return;
        }
        else if ($addr_bal > 0 && !($addr_bal >= $amount)) {
            $mysqli->query("UPDATE invoice_status SET `status` = 'partial' WHERE invoice = '$invoice_num'");
            echo json_encode([
                "status"=>200,
                "inv_status"=>"partial"
            ]);
            return;
        }
        else {
            echo json_encode([
                "status"=>200,
                "inv_status"=>"unpaid"
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