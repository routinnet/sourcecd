<?php
require_once '../../lib/nusoap.php';
require_once '../../config.php';
include_once '../../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$woid = intval($_GET['woid']);
$res = $telegram->db->query("select * from fl_wallet where id='$woid'")->fetch(2);
$amount = $res['amount'];
$userid = $res['userid'];
$transid = $res['trans_id'];

    $MerchantID = NEXTPAYID;
    $Amount = $amount; //Amount will be based on Toman
    $Authority = $_GET['Authority'];
    
    
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
        CURLOPT_POSTFIELDS => 'api_key='.$MerchantID.'&amount='.$amount.'&currency=IRT&trans_id='.$transid,
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($response);

    if ($response->code=='0') {
		$refid = $result['RefID'];
            echo 'کیف پول شما با موفقیت شارژ شد . کد پیگیری شما : ' . $refid; 
            $telegram->db->query("update fl_user set wallet= wallet + $amount where userid='$userid'");
            $telegram->db->query("update fl_wallet set status=1 where id=$woid");
            $msg = "✅ کیف پول شما به مبلغ $amount تومان شارژ شد .";
            $telegram->sendMessage($userid, $msg);
    } else {
        echo 'خطایی در هنگام پرداخت بوجود آمد . لطفا دوباره تلاش کنید . در صورت کسر وجه ، در عرض 24 ساعت به حساب شما برگشت داده می شود';
    }
