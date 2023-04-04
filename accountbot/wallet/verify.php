<?php
require_once '../lib/nusoap.php';
require_once '../config.php';
include_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$woid = intval($_GET['woid']);
$res = $telegram->db->query("select * from fl_wallet where id='$woid'")->fetch(2);
$amount = $res['amount'];
$userid = $res['userid'];

    $MerchantID = ZARINPALMID;
    $Amount = $amount; //Amount will be based on Toman
    $Authority = $_GET['Authority'];

    if ($_GET['Status'] == 'OK') {
        $client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
        $client->soap_defencoding = 'UTF-8';
        $result = $client->call('PaymentVerification', [
            [
                'MerchantID'     => $MerchantID,
                'Authority'      => $Authority,
                'Amount'         => $Amount,
            ],
        ]);

        if ($result['Status'] == 100) {
			$refid = $result['RefID'];
                echo 'کیف پول شما با موفقیت شارژ شد . کد پیگیری شما : ' . $refid; 
                $telegram->db->query("update fl_user set wallet= wallet + $amount where userid='$userid'");
                $telegram->db->query("update fl_wallet set status=1 where id=$woid");
                $msg = "
✅ کیف پول شما به مبلغ $amount تومان شارژ شد .
📝 شماره تراکنش : $refid
			";
                $telegram->sendMessage($userid, $msg);
        } else {
            echo 'خطایی در هنگام پرداخت بوجود آمد . لطفا دوباره تلاش کنید . در صورت کسر وجه ، در عرض 24 ساعت به حساب شما برگشت داده می شود'.$result['Status'];
        }
    } else {
        echo 'پرداخت توسط کاربر کنسل شد و تایید شده نمی باشد';
    }
