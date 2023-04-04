<?php
require_once '../lib/nusoap.php';
require_once '../config.php';
include_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$order_id = base64_decode($_GET['token']);
$orderDetails = $telegram->db->query("select * from fl_order where id={$order_id}")->fetch(2);
$userid = $orderDetails['userid'];
$fid = $orderDetails['fileid'];
$price = $orderDetails['amount'];

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://nextpay.org/nx/gateway/verify',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => 'api_key='.NEXTPAYID.'&amount='.$amount.'&currency=IRT&trans_id='.$transid,
));

$response = curl_exec($curl);
curl_close($curl);
$response = json_decode($response);


if ($response->code=='0') {
    $refid = $transid;
    echo 'تراکنش با موفقیت انجام شد . کد پیگیری شما : ' . $refid;
    $telegram->db->query("update fl_order set status=1 where id='$order_id'");
    $msg = "
✅سفارش شما با موفقیت ثبت شد .
📝 شماره تراکنش : $refid
.";
    $telegram->sendMessage($userid, $msg);
    $respd = $telegram->db->query("select * from fl_accounts WHERE fid={$fid} and active=1 and sold=0")->fetch(2);
    $acc_text = $respd['text'] . " \n 💵 خریداری شده از ربات @".botid."\n";
    $acc_id = $respd['id'];
    bot('sendmessage',[
		'chat_id' => $userid,
		'text'=> $acc_text,
		'parse_mode' => 'HTML',
	]);
    $telegram->db->query("update fl_accounts set sold=1 WHERE id={$acc_id}");
    
} else {
    echo 'پرداخت ناموفق است. لطفا درگاه دیگری را تست کنید';
}
