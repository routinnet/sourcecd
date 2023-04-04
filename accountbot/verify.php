<?php
require_once 'lib/nusoap.php';
require_once 'config.php';
include_once 'telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$order_id = base64_decode($_GET['token']);
$orderDetails = $telegram->db->query("select * from fl_order where id={$order_id}")->fetch(2);
$userid = $orderDetails['userid'];
$fid = $orderDetails['fileid'];
$price = $orderDetails['amount'];

$Authority = $_GET['Authority'];
if ($_GET['Status'] == 'OK') {
    $client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
    $client->soap_defencoding = 'UTF-8';
    $result = $client->call('PaymentVerification', [
        [
            'MerchantID'     => ZARINPALMID,
            'Authority'      => $Authority,
            'Amount'         => $price,
        ],
    ]);

    if ($result['Status'] == 100) {
        $refid = ltrim($Authority, "0");
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
        echo 'خطایی در هنگام پرداخت بوجود آمد . لطفا دوباره تلاش کنید . در صورت کسر وجه ، در عرض 24 ساعت به حساب شما برگشت داده می شود'.$result['Status'];
    }
} else {
    echo 'پرداخت توسط کاربر کنسل شد و تایید شده نمی باشد';
}
