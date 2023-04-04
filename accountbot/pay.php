<?php ob_start();
require_once 'lib/nusoap.php';
require_once 'config.php';
require_once 'telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
if(!isset($_GET['token'])){
    header('location:https://t.me/'.botid);
}
$token = base64_decode($_GET['token']);
$token = explode('.',$token);
$userid = $token[0];
$fid = $token[1];
$date = time();
$price = $telegram->db->query("select * from fl_file where id={$fid}")->fetch(2)['price'];
$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$userid}, '', {$fid}, $price,0, '$date');");
$order_id = $telegram->db->lastInsertId();

    $MerchantID = ZARINPALMID;  // Required
    $Amount = $price; // Amount will be based on Toman  - Required
    $Description = DESC;  // Required
    $token = base64_encode($order_id);
    $CallbackURL = baseURI."/verify.php?token=$token";  // Required


    $client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
    $client->soap_defencoding = 'UTF-8';
    $result = $client->call('PaymentRequest', [
        [
            'MerchantID'     => $MerchantID,
            'Amount'         => $Amount,
            'Description'    => $Description,
            'Email'          => $Email,
            'Mobile'         => $Mobile,
            'CallbackURL'    => $CallbackURL,
        ],
    ]);

    //Redirect to URL You can do it also by creating a form
    if ($result['Status'] == 100) {
        $telegram->db->query("UPDATE `fl_order` SET `transid` = '{$result['Authority']}' where id= $order_id");
        header('Location: https://www.zarinpal.com/pg/StartPay/'.$result['Authority']);
    } else {
        echo'تراکنش با خطا مواجه شد';
    }
