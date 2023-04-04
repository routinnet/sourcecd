<?php ob_start();
require_once '../lib/nusoap.php';
require_once '../config.php';
require_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
if(!isset($_GET['token'])){
    header('location:https://t.me/'.botid);
}
$token = base64_decode($_GET['token']);
$token = explode('.',$token);
$userid = $token[0];
$fid = $token[1];

$respd = $telegram->db->query("select * from fl_accounts WHERE fid={$fid} and active=1 and sold=0")->fetch(2);
if(empty($respd)) {
    echo 'اکانت قابل فروشی وجود ندارد';die;
}
$date = time();
$price = $telegram->db->query("select * from fl_file where id={$fid}")->fetch(2)['price'];
$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$userid}, '', {$fid}, $price,0, '$date');");
$order_id = $telegram->db->lastInsertId();
    $MerchantID = NEXTPAYID;  // Required
    $Amount = $price; // Amount will be based on Toman  - Required
    $Description = DESC;  // Required
    $token = base64_encode($order_id);
    $CallbackURL = baseURI."/nextpay/verify.php?token=$token";  // Required


    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://nextpay.org/nx/gateway/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'api_key='.$MerchantID.'&amount='.$Amount.'&order_id='.$order_id.'&currency=IRT&callback_uri='.$CallbackURL,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($response);

    //Redirect to URL You can do it also by creating a form
    if ($response->code == '-1'){
        $telegram->db->query("UPDATE `fl_order` SET `transid` = '{$response->trans_id}' where id= $order_id");
        $startGateWayUrl = "https://nextpay.org/nx/gateway/payment/".$response->trans_id;
        header('location: '.$startGateWayUrl); exit;
    } else {
        echo'تراکنش با خطا مواجه شد';
    }
