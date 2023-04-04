<?php
include_once 'config.php';
include_once 'telegram.php';
include_once 'class.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$class = new \netparadis\telegram(TOKEN);
// user
$result = $telegram->getTxt();
$userid = $result->message->from->id;
$text = $result->message->text;
$fname = $result->message->from->first_name;
$lname = $result->message->from->last_name;
$username = $result->message->from->username;
$time = time();
$msgid = $result->message->message_id;
$fwuid = $result->message->reply_to_message->forward_sender_name;
$fwuid2 = $result->message->reply_to_message->forward_from->id;
$fwtext = $result->message->reply_to_message->text;

// callback
$cid = $result->callback_query->id;
$cdata = $result->callback_query->data;
$cmsgid = $result->callback_query->message->message_id;
$chatid = $result->callback_query->message->chat->id;
$chatype = $result->callback_query->message->chat->type; // channel,private
$chatus = $result->callback_query->message->chat->username; // channelusername , normaluser-username
$cuserid = $result->callback_query->from->id;
$cfname = $result->callback_query->from->first_name;
if ($cdata) {$userid = $cuserid;}

// inline
$query = $result->inline_query->query;
$queryid = $result->inline_query->id;
$inlineUserId = $result->inline_query->from->id;
$inlinename = $result->inline_query->from->first_name;
$inlineusername = $result->inline_query->from->username;
$cancelop=array(array('❌ انصراف'));
function get_type($id){
    $url = "https://api.telegram.org/bot".TOKEN."/getFile?file_id=$id";
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}

// upload file
if ($result->message->document->file_id) {
    $fileid = $result->message->document->file_id;
} elseif ($result->message->audio->file_id) {
    $fileid = $result->message->audio->file_id;
} elseif ($result->message->photo[0]->file_id) {
    $fileid = $result->message->photo->file_id;
    if (isset($result->message->photo[2]->file_id)) {
        $fileid = $result->message->photo[2]->file_id;
    } elseif ($fileid = $result->message->photo[1]->file_id) {
        $fileid = $result->message->photo[1]->file_id;
    } else {
        $fileid = $result->message->photo[1]->file_id;
    }
} elseif ($result->message->voice->file_id) {
    $voiceid = $result->message->voice->file_id;
} elseif ($result->message->audio->file_id) {
    $fileid = $result->message->audio->file_id;
}
$caption = $result->message->caption;
//$telegram->sendMessage($userid,$fileid);
$startmsg = 'سلام .
به ربات فروشگاه انواع اکانت ها خوش اومدی.';


$userstate = 'state/' . $userid . '.txt';
if (!file_exists($userstate)) {
    $userfile = fopen('state/' . $userid . '.txt', "w");
    fclose($userfile);
    $userfile = fopen('state/' . $userid . '-free.txt', "w");
    fclose($userfile);
}
$state = file_get_contents('state/' . $userid . '.txt');

$finalop = array(
    array('🛍 فروشگاه','♻️تمدید اکانت' ),
    array('💎دریافت نرم افزار یا اپلیکیشن'),
	array('💳 کیف پول','👤 حساب کاربری'),
    array('💡راهنما', '👤ارتباط با ما'),
);
$cancelop = array(array('❌ انصراف'));
$imgop = array(array('رد کردن این مرحله'),array('❌ انصراف'));

if ($userid == ADMIN or isAdmin()) {
    $finalop[] = ['⚙️ مدیریت'];
    $adminop = array(
        array('➕ثبت پلن','مدیریت پلن ها'),
        array('افزودن دسته بندی','مدیریت دسته بندی ها'),
		array('➕ اضافه کردن موجودی','➖کسر موجودی'),
        array('📈آمار', '🗒 پیام همگانی'),
        array('🏠 منوی اصلی','🔐ادمین ها'),
    );
}
$productop = [
    ['مدیریت پلن ها','افزودن پلن جدید'],
    ['🏠 منوی اصلی']
];
$catop = [
    ['مدیریت دسته بندی ها','افزودن دسته بندی جدید'],
    ['🏠 منوی اصلی']
];


$channel = CHANNEL;
$channelmsg = "
🔑 قبل از استفاده از خدمات ما لطفا در کانال 
$channel
عضو شوید .بعد از آن, محدد ربات را /start بزنید
";
$status = bot('getChatMember', [
    'chat_id' => "$channel",
    'user_id' => $userid
])->result->status;

if ($status == "kicked" || $status == "left"){
    $telegram->sendMessage($userid,$channelmsg);
    exit;
}

if( ($fwuid or $fwuid2) and $userid==ADMIN){
    //$telegram->sendMessage($userid, $fwuid);
    if($fwuid2) $fuid = $fwuid2; else $fuid = $telegram->db->query("SELECT * FROM fl_user WHERE name='$fwuid'")->fetch(2)['userid'];
    //$telegram->sendMessage($userid, $fwuid);$telegram->sendMessage($userid, "SELECT * FROM fl_user WHERE name='$fwuid'");
    $text = " پیام شما : $fwtext

پاسخ مدیریت : $text
.";
	$telegram->sendMessage($fuid,$text);
	$res = get_type($fileid);
	$gftype = $res->result->file_path;
	if(preg_match('/music/',$gftype)){
		bot('sendaudio',[
			'chat_id' => $fuid,
			'audio' => $fileid,
			'caption' => $caption
		]);
	}elseif (preg_match('/video/',$gftype)){
		bot('sendvideo',[
			'chat_id' => $fuid,
			'video' => $fileid,
			'caption' => $caption
		]);
	}elseif (preg_match('/document/',$gftype)){
		bot('senddocument',[
			'chat_id' => $fuid,
			'document' => $fileid,
			'caption' => $caption
		]);
	}elseif (preg_match('/photo/',$gftype)) {
		bot('sendphoto', [
			'chat_id' => $fuid,
			'photo' => $fileid,
			'caption' => $caption
		]);
	}elseif($result->message->location){
		$latitude = $result->message->location->latitude;
		$longitude = $result->message->location->longitude;
		bot('sendLocation', [
			'chat_id' => $fuid,
			'latitude' => $latitude,
			'longitude' => $longitude
		]);
	};
	$telegram->sendMessage($userid,'ریپلای با موفقیت برای کاربر ارسال شد');
}

if (preg_match('/^\/([Ss]tart)/', $text) or $text == '🏠 منوی اصلی' or $text == '🔙بازگشت به منوی اصلی') {
    file_put_contents('state/' . $userid . '.txt', '');
    $count = $telegram->db->query("select * from fl_user where userid=$userid")->rowCount();
    if ($count == 0) {
        $sql = "INSERT INTO `fl_user` VALUES (NULL,'$userid','$fname','$username',$time,0)";
        $telegram->db->query($sql);
    }
    $telegram->sendMessageCURL($userid, $startmsg, $finalop);
}

if ($text == '🛍 فروشگاه' or $cdata=='cat'){
    $respd = $telegram->db->query("select * from fl_cat WHERE parent=0")->fetchAll();
    if(empty($respd)){
        $telegram->sendMessage($userid, 'هیچ دسته بندی در ربات تعریف نشده است');
        exit;
    }
    $keyboard = [];
    foreach($respd as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $keyboard[] = ['text' => "$name", 'callback_data' => "list#$id"];
    }
    $keyboard = array_chunk($keyboard,1);
    if(isset($cdata) and $cdata=='cat') {
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text'=> ' 📍 لطفا یکی از دسته بندی های زیر را انتخاب کنید👇',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else {
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> ' 📍 لطفا یکی از دسته بندی های زیر را انتخاب کنید👇',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}
if(preg_match('/list/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
    $respd = $telegram->db->query("select * from fl_file WHERE catid='$id' and active=1")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "
💡پلنی در این دسته بندی وجود ندارد
        ",
            'show_alert' => false
        ]);
    }else{
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "
📍در حال دریافت لیست پلن ها
        ",
            'show_alert' => false
        ]);
        $keyboard = [];
        foreach($respd as $file){
            $id = $file['id'];
            $name = $file['title'];
            $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
        }
        $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => "cat"];
        $keyboard = array_chunk($keyboard,1);
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text' => "
🔰 حالا یکی از موارد زیر را انتخاب کنید تا جزییات پلن برای شما نمایش داده شود👈
",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}
if(preg_match('/file/',$cdata)){
    $input = explode('#', $cdata);
    $id = $input[1];
    $rcount = $telegram->db->query("select * from fl_accounts WHERE fid={$id} and active=1 and sold=0")->rowCount();
    if($rcount == 0) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "در حال حاضر برای این پلن اکانت قابل فروشی وجود ندارد",
            'show_alert' => true
        ]);
        exit;
    }
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "
♻️در حال دریافت جزییات ...
        ",
        'show_alert' => false
    ]);
    $respd = $telegram->db->query("select * from fl_file WHERE id='$id' and active=1")->fetch(2);
    $catname = $telegram->db->query("select * from fl_cat WHERE id=".$respd['catid'])->fetch(2)['title'];
    $name = $catname." ".$respd['title'];
    $price = number_format($respd['price']);
    $desc = $respd['descr'];
    $fileImg = $respd['pic']."?".rand(0,999999999);
    $fileImg = "<a href='".baseURI."/$fileImg'>&#8194;</a>";
    if($price == 0 or ($userid == ADMIN or isAdmin() )){
        $keyboard = [[['text' => '📥 دریافت رایگان', 'callback_data' => "download#$id"]]];
    }else{
        $token = base64_encode("{$cuserid}.{$id}");
		$keyboard[] = [['text' => "پرداخت زرین پال - $price تومان", 'url' => baseURI."pay.php?token=$token"]];
		$keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."nextpay/pay.php?token=$token"]];
		$keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "walpay#$id#".$respd['price']]];
		$keyboard[] = [['text' => "کارت به کارت - $price تومان",  'callback_data' => "offpay#$id"]];
    }
    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "
🔻عنوان :$name

📃توضیحات :
$desc
$fileImg
",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/walpay/',$cdata)) {
    $input = explode('#', $cdata);
    $fid = $input[1];
    if(!$input[2]) {
        $telegram->sendMessage($userid,"مجدد روی خرید کانفیگ بزنید");exit;
    }
    $price = $input[2]; 

    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }

    $res = $telegram->db->query("select * from fl_accounts where fid=$fid and sold=0 and active=1 order by id ASC")->fetch(2);
    if(empty($res)){
        $telegram->sendMessage($userid,'در حال حاضر هیچ اکانت قابل فروشی وجود ندارد');
        exit;
    }
    $accid = $res['id'];
    $text = $res['text'];
    $res = $telegram->db->query("select * from fl_file where id=$fid")->fetch(2);
    $telegram->db->query("update fl_user set wallet = wallet - ".$res['price']." where userid='$userid'");
    $telegram->db->query("update fl_accounts set sold=1 where id=$accid");
    //$telegram->sendMessage($userid,$text);
     $telegram->sendHTML($userid,"
✅پرداخت شما با موفقیت تکمیل شد
🗒اطلاعات اکانت شما به شرح زیر است:

$text

🤖خریداری شده از ربات ".botid ,$finalop);
}
if(preg_match('/offpay/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendMessageCURL($userid,"لطفا تصویر فیش واریزی یا شماره پیگیری -  ساعت پرداخت - نام پرداخت کننده را در یک پیام ارسال کنید
🔸$cardinfo",$cancelop);
    exit;
}
if(preg_match('/offpay/',$state) and $text != '❌ انصراف'){
    $input = explode('#',$state);
    $fid = $input[1];
    file_put_contents("state/$userid.txt",'');
    $res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $uid = $res['userid'];
    $name = $res['name'];
    $username = $res['username'];

    $res = $telegram->db->query("select * from fl_file where id=$fid")->fetch(2);
    $catname = $telegram->db->query("select * from fl_cat where id=".$res['catid'])->fetch(2)['title'];
    $filename = $catname." ".$res['title']; $fileprice = $res['price'];

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption <a href='$fileurl'>&#8194;نمایش فیش</a>";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش, اطلاعات اکانت از طریق ربات برای شما ارسال می شود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷سفارش جدید خرید $filename ($fileprice تومان)
✖کد کاربری : $userid
👤نام و نام خانوادگی : $name
📧یوزرنیم : @$username
📝اطلاعات پرداخت کارت به کارت: $infoc
.";
    bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => 'تایید پرداخت', 'callback_data' => "enable#$uid#$fid"],
                    ['text' => 'عدم تایید', 'callback_data' => "disable#$uid"]
                ]
            ]
        ])
    ]);
}
if($text == '♻️تمدید اکانت'){
    file_put_contents("state/$userid.txt","renewacc");
    $telegram->sendMessageCURL($userid,"لطفا ابتدا آخرین تعرفه اکانت مورد نظر را که قبلا خرید کردید را از بخش فروشگاه چک کنید و بعد مبلغ را به شماره کارت زیر واریز کنید

🔸$cardinfo
    
بعد اطلاعات اکانت‏ی که قبلا از طریق ربات خرید کردید و قصد تمدید آن را دارید به همراه کد پیگیری و زمان پرداخت و مبلغ فیش ارسال کنید تا تمدید اکانت شما انجام شود "
    ,$cancelop);
}
if($state == 'renewacc' and $text != '❌ انصراف'){
    file_put_contents("state/$userid.txt",'');
    $res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $uid = $res['userid'];
    $name = $res['name'];
    $username = $res['username'];

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption <a href='$fileurl'>&#8194;نمایش فیش</a>";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش, اکانت شما تمدید و از طریق ربات اطلاع رسانی میشود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷سفارش جدید تمدید اکانت
✖کد کاربری : $userid
👤نام و نام خانوادگی : $name
📧یوزرنیم : @$username
📝اطلاعات اکانت و فیش پرداختی: $infoc
.";
    bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => 'تایید پرداخت', 'callback_data' => "enable#$uid"],
                    ['text' => 'عدم تایید', 'callback_data' => "disable#$uid"]
                ]
            ]
        ])
    ]);
}
if(preg_match('/enable/',$cdata) and $userid==ADMIN){
    file_put_contents("state/{$userid}.txt",$cdata);
    $telegram->sendMessageCURL($userid,'اگر تمدید است لطفا اطلاعات اکانت به همراه تاریخ انقضا وارد کنید تا برای کاربر ارسال شود در غیر اینصورت فقط عدد 1 را بفرستید تا اطلاعات اکانت مستقیما از لیست اکانت ها برای کاربر ارسال شود',$cancelop);
}
if(preg_match('/enable/',$state) and $text != '❌ انصراف'){
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$state);
    $uid = $input[1];
    $fid = $input[2];
    $acctxt = '';
    $telegram->sendMessageCURL($userid,'اطلاعات اکانت با موفقیت برای کاربر ارسال شد',$finalop);
    if($text == '1' or $text == '۱') {
        $res = $telegram->db->query("select * from fl_accounts where fid=$fid and sold=0 and active=1 order by id ASC")->fetch(2);
        $accid = $res['id'];
        $text = $res['text'];
        $telegram->db->query("update fl_accounts set sold=1 where id=$accid");
    }
    
    $telegram->sendHTML($uid,"اطلاعات اکانت برای سفارش با کارت به کارت به شرح زیر است :
$text",$finalop);
}
if(preg_match('/disable/',$cdata) and $userid==ADMIN){
    file_put_contents("state/{$userid}.txt",$cdata);
    $telegram->sendMessageCURL($userid,'لطفا دلیل عدم تایید تراکنش را وارد کنید (این متن برای مشتری ارسال می شود) ',$cancelop);
}
if(preg_match('/disable/',$state) and $text != '❌ انصراف'){
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$state);
    $uid = $input[1];
    $telegram->sendMessageCURL($userid,'متن پیام با موفقیت برای مشتری ارسال شد',$finalop);
    $telegram->sendMessage($uid,$text);
}
if(preg_match('/download/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
	
	$free = file_get_contents("state/{$userid}-free.txt");
    if($free == '1') {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '⚠️شما قبلا هدیه رایگان خود را دریافت کردید',
            'show_alert' => false
        ]); 
        exit;
    }else {
        file_put_contents("state/{$userid}-free.txt","1");
    }
	
    $respd = $telegram->db->query("select * from fl_accounts WHERE fid={$id} and active=1 and sold=0")->fetch(2);
    $acc_text = $respd['text'] . " \n 💵 خریداری شده از ربات @".botid."\n";
    $acc_id = $respd['id'];
    //$fileLink = "<a href='http://dfsd.ir/$filelink'>&#8194;</a>$name";
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => '♻️در حال ارسال اکانت ...',
        'show_alert' => false
    ]);
    $telegram->sendHTML($cuserid,$acc_text,$finalop);
    $telegram->db->query("update fl_accounts set sold=1 WHERE id={$acc_id}");
    /*bot('senddocument',[
        'chat_id' => $cuserid,
        'document' => $fileid,
        'caption' => $name
    ]);*/
}
if ($text == '➕ثبت پلن' and ($userid == ADMIN or isAdmin() )){
    $state = file_put_contents('state/'.$userid.'.txt','addproduct');
    $telegram->db->query("delete from fl_file WHERE active=0");
    $sql = "INSERT INTO `fl_file` VALUES (NULL, '', 0, '', 0, '', '',0,1, '$time');";
    $telegram->db->query($sql);
    $msg = '◀️ لطفا عنوان پلن را وارد کنید';
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
    exit;
}
// add product
if(preg_match('/addproduct/',$state) and $text!='❌ انصراف'){

    $catkey = [];
    $cats = $telegram->db->query("SELECT * FROM `fl_cat`")->fetchAll();
    foreach ($cats as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $catkey[] = ["$id - $name"];
    }
    $catkey[] = ['❌ انصراف'];

    $step = $telegram->checkStep('fl_file');
    if($step==1 and $text!='❌ انصراف'){
        $msg = '✅عنوان پلن با موفقیت ثبت شد
◀️ لطفا قیمت پلن را به تومان وارد کنید
* عدد 0 به معنای رایگان بودن است.
';
        if(strlen($text)>1){
            $telegram->db->query("update fl_file set title='$text',step=2 where active=0 and step=1");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 1
    if($step==2 and $text!='❌ انصراف'){
        $msg = '✅قیمت پلن با موفقیت ثبت شد . 
◀️ لطفا دسته بندی پلن را انتخاب کنید
.';
        if(is_numeric($text)){
            $telegram->db->query("update fl_file set price='$text',step=3 where step=2");
            $telegram->sendMessageCURL($userid,$msg,$catkey);
        }else{
            $msg = '‼️ لطفا یک مقدار عددی وارد کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 2
    if($step==3 and $text!='❌ انصراف'){
        $msg = '✅دسته بندی پلن موفقیت ثبت شد . 
◀️ لطفا توضیحات پلن را وارد کنید
.';
        $inarr = 0;
        foreach ($catkey as $op) {
            if (in_array($text, $op) and $text != '❌ انصراف') {
                $inarr = 1;
            }
        }
        if( $inarr==1 ){
            $input = explode(' - ',$text);
            $catid = $input[0];
            $telegram->db->query("update fl_file set catid='$catid',step=4 where step=3");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }else{
            $msg = '‼️ لطفا فقط یکی از گزینه های پیشنهادی زیر را انتخاب کنید';
            $telegram->sendMessageCURL($userid,$msg,$catkey);
        }
    } //step 3
    if($step==4 and $text!='❌ انصراف'){
        $msg = '✅توضیحات پلن با موفقیت ثبت شد . 
◀️ لطفا تصویر یا پیشنمایش را بصورت عکس ارسال کنید
.';
        if(strlen($text)>1 ){
            $telegram->db->query("update fl_file set descr='$text',step=5 where step=4");
            $telegram->sendMessageCURL($userid,$msg,$imgop);
        }

    } //step 4
    if($step==5 and $text!='❌ انصراف'){
        if($text != 'رد کردن این مرحله'){$imgtxt = '✅پیشنمایش  با موفقیت ثبت شد . ';}
        $msg = $imgtxt.' 
◀ حالا️ اکانت های این پلن  را بصورت زیر ارسال کنید
دقت کنید که تمامی اطلاعات اکانت را با عبارت seprator از هم جدا کنید 

توجه کنید که اگر میخواهید قابل کلیک باشد آن را به اینصورت وارد کنید :
<code>شارژ</code>
کلمه شارژ برای کاربر قابل کلیک خواهد شد

username: Test password: pwd...

seprator

link or vmess or giftcode or anything...


اگر تعداد اکانت ها زیاد است آن را با فرمت بالا در یک فایل .txt ارسال کنید
';
        if($text == 'رد کردن این مرحله'){
            $telegram->db->query("update fl_file set step=6 where step=5");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }elseif($fileid){
            $photoURL = $telegram->FileURL($fileid);
            $photoext = pathinfo(basename($photoURL),PATHINFO_EXTENSION);
            $image = "images/".time().".$photoext";
            $somecontent = get_web_page($photoURL."?".rand(0,999999999));
            $handle = fopen($image,"x+");
            fwrite($handle,$somecontent);
            fclose($handle);

            $telegram->db->query("update fl_file set pic='$image',step=6 where step=5");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }else{
            $msg = '‼️ لطفا تصویر را ارسال کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 5
    if($step==6 and $text!='❌ انصراف'){
		if($fileid){
            $textURL = $telegram->FileURL($fileid);
            $textext = pathinfo(basename($textURL),PATHINFO_EXTENSION);
            $text = get_web_page($textURL."?".rand(0,999999999));
        }
        if(preg_match('/seprator/',strtolower($text))){
            $telegram->db->query("update fl_file set fileid='$fileid',active=1,step=10 where step=6");
            $id = $telegram->db->query("select * from fl_file where active=1 order by id DESC limit 1")->fetch(2)['id'];

            $accs = explode('seprator',$text);
            foreach ($accs as $acc){
                if(strlen($acc) > 5)
                    $telegram->db->query("INSERT INTO `fl_accounts` (`id`, `fid`, `text`, `sold`, `active`) VALUES (NULL, $id, '$acc', '0', '1');");
            }
            $msg = "✅️ اکانت های این پلن  با موفقیت ثبت شد";
            $telegram->sendMessageCURL($userid,$msg,$finalop);
            file_put_contents('state/'.$userid.'.txt','');
        }else{
            $msg = '‼️ لطفا اکانت ها را با جداکننده معتبر ارسال کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 6
}
// end add product
if($text=='مدیریت پلن ها' and ($userid==ADMIN or isAdmin() )){
    $res = $telegram->db->query("select * from fl_file where active=1")->fetchAll();
    if(empty($res)){
        $msg = "موردی یافت نشد";
        $telegram->sendMessage($userid,$msg);
    }else {
        $product ='';
        foreach ($res as $pd){
            $id=$pd['id'];
            $name=$pd['title'];
            $price=$pd['price'];
            $accnum = $telegram->db->query("select * from fl_accounts where sold=1 and fid=$id")->rowCount();
            $accdnum = $telegram->db->query("select * from fl_accounts where sold=0 and fid=$id")->rowCount();
            $product = "
▪️#$id
🔻نام : $name /chpnm$id
💶قیمت : $price تومان /chpp$id
✴️ویرایش توضیحات : /desc$id
❌حذف : /delpd$id
تعداد اکانت های فروخته شده : $accnum
تعداد اکانت های باقیمانده : $accdnum
⚡دریافت لیست اکانت ها : /getlistpd$id
📝افزودن اکانت جدید : /addpd$id
=====";
            $telegram->sendMessage($userid,$product);
        }
    }
}
if(preg_match('/getlistpd/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/getlistpd','',$text);
    $res = $telegram->db->query("select * from fl_accounts where fid={$fid}")->fetchAll();
    $txt = '';
    foreach ($res as $acc){
        $sold = $acc['sold'] == '1' ? 'SOLD' : 'OK';
        $accid = $acc['id'];
        $txt = $acc['text']." \n $sold | ❌ /delacc$accid \n =========== \n";
		$telegram->sendMessage($userid,$txt);
    }
    //$telegram->sendMessage($userid,$txt);
}
if(preg_match('/delacc/',$text) and ($userid==ADMIN or isAdmin() )){
    $aid=str_ireplace('/delacc','',$text);
    $telegram->db->query("delete from fl_accounts where id={$aid}");
    $telegram->sendMessage($userid,"اکانت موردنظر با موفقیت حذف شد");
}
if(preg_match('/addpd/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"اکانت ها  را بصورت زیر ارسال کنید
دقت کنید که تمامی اطلاعات اکانت را با عبارت seprator از هم جدا کنید 

توجه کنید که اگر میخواهید قابل کلیک باشد آن را به اینصورت وارد کنید :
<code>شارژ</code>
کلمه شارژ برای کاربر قابل کلیک خواهد شد

username: Test password: pwd...

seprator

link or vmess or giftcode or anything...
",$cancelop);exit;
}
if(preg_match('/addpd/',$state)){
    $pid=str_ireplace('/addpd','',$state);
    if(preg_match('/seprator/',strtolower($text))){
        $accs = explode('seprator',$text); 
        foreach ($accs as $acc){
            if(strlen($acc) > 5)
                $telegram->db->query("INSERT INTO `fl_accounts` (`id`, `fid`, `text`, `sold`, `active`) VALUES (NULL, $pid, '$acc', '0', '1');");
        }
        $telegram->sendMessageCURL($userid,"✅اکانت های جدید با موفقیت اضافه شد",$finalop);
        file_put_contents('state/'.$userid.'.txt','');
    }else{
        $msg = '‼️ لطفا اکانت ها را با جداکننده معتبر ارسال کنید';
        $telegram->sendMessageCURL($userid,$msg,$cancelop);
    }
}

if(preg_match('/delpd/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/delpd','',$text);
    $telegram->db->query("delete from fl_file where id={$fid}");
    $fileImg = $telegram->db->query("select * from fl_file where id={$fid}")->fetch(2)['pic'];
    unlink(srvDir.$fileImg);
    $telegram->sendMessage($userid,"پلن موردنظر با موفقیت حذف شد");
}
if(preg_match('/chpnm/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"نام جدید پلن را وارد کتید:");exit;
}
if(preg_match('/chpnm/',$state)){
    $pid=str_ireplace('/chpnm','',$state);
    $telegram->db->query("update fl_file set title='$text' where id={$pid}");
    $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/desc/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"توضیحات جدید را وارد کتید:");exit;
}
if(preg_match('/desc/',$state)){
    $pid=str_ireplace('/desc','',$state);
    $telegram->db->query("update fl_file set descr='$text' where id={$pid}");
    $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/chpp/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"قیمت جدید را وارد کتید:");exit;
}
if(preg_match('/chpp/',$state)){
    $pid=str_ireplace('/chpp','',$state);
    if(is_numeric($text)){
        $telegram->db->query("update fl_file set price='$text' where id={$pid}");
        $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
        file_put_contents("state/$userid.txt",'');
    }else{
        $telegram->sendMessage($userid,"یک مقدار عددی و صحیح وارد کنید");
    }
}

if($text=='مدیریت دسته بندی ها' and ($userid==ADMIN or isAdmin() )){
    $cats = $telegram->db->query("SELECT * FROM `fl_cat`")->fetchAll();
    if(empty($cats)){
        $msg = "موردی یافت نشد";
    }else {
        $msg = '';
        foreach ($cats as $cty) {
            $id = $cty['id'];
            $cname = $cty['title'];
            $msg .= "
✅نام : $cname
♻️ویرایش : /editc$id
❌حذف : /delcat$id
====";
        }
    }
    $telegram->sendMessage($userid,$msg);
}
if($text=='افزودن دسته بندی' and ($userid == ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",'addcat');
    $telegram->sendMessage($userid,"نام دسته بندی را وارد کتید:");exit;
}
if(preg_match('/addcat/',$state)){
    $telegram->db->query("insert into fl_cat VALUES (NULL,'$text',0)");
    $telegram->sendMessage($userid,"✅دسته بندی جدید با موفقیت اضافه شد");
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/delcat/',$text) and ($userid==ADMIN or isAdmin() )){
    $pid=str_ireplace('/delcat','',$text);
    $telegram->db->query("delete from fl_cat where id={$pid}");
    $telegram->sendMessage($userid,"دسته بندی موردنظر با موفقیت حذف شد");
}
if(preg_match('/editc/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"نام جدید دسته بندی را وارد کتید:");exit;
}
if(preg_match('/editc/',$state)){
    $pid=str_ireplace('/editc','',$state);
    $telegram->db->query("update fl_cat set title='$text' where id={$pid}");
    $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
    file_put_contents("state/$userid.txt",'');
}

if ($text == '🗒 پیام همگانی' and ($userid == ADMIN or isAdmin() )){
    $state = file_put_contents('state/' . $userid . '.txt', 's2a');
    $msg = "لطفا پیام خود ارسال کنید. ";
    $telegram->sendAction($userid, 'typing');
    $telegram->sendHTML($userid, $msg, $cancelop);
    exit;
}
if ($state == 's2a' and $text != '❌ انصراف') {
    file_put_contents('state/' . $userid . '.txt', '');
    $result = $telegram->db->query("select * from fl_user")->fetchAll();
    $telegram->sendMessageCURL($userid, '👍🏻✅ پیام شما با موفقیت به تمام کاربران ربات ارسال شد ', $adminop);
    foreach ($result as $user) {
        if ($user['userid'] != ADMIN) {
            $telegram->sendMessage($user['userid'], $text);
        }
    }
}

if($text=='➖کسر موجودی'){
    file_put_contents('state/'.$userid.'.txt','delcash');
    $msg = "لطفا آی دی کاربر را وارد کنید";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
}
if($state=='delcash' and $userid==ADMIN and $text!='❌ انصراف'){
    $user = $telegram->db->query("select * from fl_user where userid='$text'")->fetch(2);
    if(!$user){
        $telegram->sendMessage($userid,'کاربر مورد نظر یافت نشد');
        exit;
    }
    $wallet = $user['wallet'];
    if($wallet == 0) {
        file_put_contents('state/'.$userid.'.txt','');
        $telegram->sendMessageCURL($userid,'موجودی این کاربر صفر است و امکان کسر کردن از آن وجود ندارد',$adminop);
        exit;
    }
    file_put_contents('state/'.$userid.'.txt','delcash'.$text);
    $msg = "موجودی کاربر $wallet تومان هست. لطفا مقداری که میخواهید از این موجودی کم شود را بصورت اعداد لاتین وارد کنید:";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
    exit;
}
if(preg_match('/delcash/',$state) and $userid==ADMIN and $text!='❌ انصراف'){
    $uid = str_replace('delcash','',$state);
    $telegram->db->query("update fl_user set wallet = wallet - $text where userid='$uid'");
    file_put_contents('state/'.$userid.'.txt','');
    $telegram->sendMessageCURL($userid,'موجودی کاربر با موفقیت کم شد',$adminop);exit;
}

if($text=='➕ اضافه کردن موجودی'){
    file_put_contents('state/'.$userid.'.txt','adcash');
    $msg = "لطفا آی دی کاربر را وارد کنید";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
}
if($state=='adcash' and $userid==ADMIN and $text!='❌ انصراف'){
    $user = $telegram->db->query("select * from fl_user where userid='$text'")->fetch(2);
    if(!$user){
        $telegram->sendMessage($userid,'کاربر مورد نظر یافت نشد');
        exit;
    }
    $wallet = $user['wallet'];
    file_put_contents('state/'.$userid.'.txt','adcash'.$text);
    $msg = "موجودی کاربر $wallet تومان هست. لطفا مقداری که میخواهید به این موجودی اضافه شود را بصورت اعداد لاتین وارد کنید:";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
    exit;
}
if(preg_match('/adcash/',$state) and $userid==ADMIN and $text!='❌ انصراف'){
    $uid = str_replace('adcash','',$state);
    $telegram->db->query("update fl_user set wallet = wallet + $text where userid='$uid'");
    file_put_contents('state/'.$userid.'.txt','');
    $telegram->sendMessageCURL($userid,'موجودی کاربر با موفقیت تغییر کرد',$adminop);exit;
}

if ($text == '📈آمار' and  ($userid == ADMIN or isAdmin() ) ) {
    file_put_contents('state/' . $userid . '.txt', '');
    $users = $telegram->db->query("select * from fl_user")->rowCount();
    $product = $telegram->db->query("select * from fl_file WHERE active=1")->rowCount();
    $fault = $telegram->db->query("select * from fl_order where status=0")->rowCount();
    $success = $telegram->db->query("select * from fl_order where status=1")->rowCount();
    $income = $telegram->db->query("select sum(amount) as amount from fl_order where status=1")->fetch(2)['amount'];
    $income = number_format($income);
    $msg = "
✅تعداد کل کاربران ربات :$users 

✅تعداد کل محصولات :$product 

⏩تعداد تراکنش های ناموفق :$fault 

✅تعداد تراکنش های موفق :$success

✅درآمد کل  :$income تومان

.
    ";
    $telegram->sendMessage($userid, $msg);
}

if(($text == '⚙️ مدیریت'  or $text == '↪️بازگشت' ) and ($userid == ADMIN or isAdmin() )){
    file_put_contents('state/' . $userid . '.txt', '');
    $msg = 'مدیریت عزیز خوش آمدید';
    $telegram->sendHTML($userid, $msg, $adminop);
}

if ($text == '💡راهنما') {
    $state = file_put_contents('state/' . $userid . '.txt', '');
    $msg = 'با سلام خدمت شما کاربر گرامی 
پشتیبانی ۲۴ ساعته با  آی دی زیر 👇
'.$supportus;
    $telegram->sendMessage($userid, $msg);exit;
}
if ($text == '👤ارتباط با ما') {
    $state = file_put_contents('state/' . $userid . '.txt', 'support');
    $msg = 'لطفا پیام خود را بفرستید. سعی ما بر این است که هر چه سریعتر با آن پاسخ دهیم:';
    $telegram->sendMessageCURL($userid, $msg, $cancelop);exit;
    
}
if($text!='❌ انصراف' and $state=='support'){
    file_put_contents('state/' . $userid . '.txt', '');
    $telegram->forwardmessage(ADMIN,$userid,$msgid);
    $telegram->sendMessageCURL($userid,'❇️پیام شما با موفقیت برای پشتیبانی ارسال شد. پیام شما بزودی بررسی و از طریق همین ربات اطلاع رسانی می شود',$finalop);
}

if($text == '🔐ادمین ها' and ($userid==ADMIN or isAdmin() )){
    $admins = file_get_contents('admins.php');
    $list = explode('\n',$admins);
    file_put_contents('state/' . $userid . '.txt', 'admin');
    $telegram->sendHTML($userid, "📝 لیست کاربران مدیر به صورت زیر است:
<b>$admins</b>
⚠️اگر قصد عزل یکی از کاربران این لیست را دارید
❇️یا اضافه کردن یک کاربر به عنوان ادمین را دارید, کافیست که آی دی عددی را همین جا ارسال کنید", [['↪️بازگشت']]);

    exit;
}
if ($state == 'admin' and $text != '↪️بازگشت' ) {
    if(is_numeric($text) and strlen($text)>4){
        file_put_contents('state/' . $userid . '.txt', '');
        $admins = file_get_contents('admins.php');
        if(!preg_match("/$text/",$admins)) {
            file_put_contents('admins.php',"\n".$text,FILE_APPEND);
            $msg = 'کاربر به دسترسی مدیریت ارتقا یافت';
        } else{
            $str = str_replace($text,'',$admins);
            //$str=str_replace("\n","",$str);
            file_put_contents('admins.php',$str);
            $msg = 'کاربر از لیست مدیران ربات حذف شد';
        };
        $telegram->sendHTML($userid,$msg,$adminop);
    }else{
        $telegram->sendMessage($userid, 'لطفا یک آی دی عددی و صحیح ارسال کنید');
    }
}
if($text == '💎دریافت نرم افزار یا اپلیکیشن') {
    $respd = $telegram->db->query("select * from fl_software WHERE status=1")->fetchAll(2);
    $keyboard = [];
    foreach($respd as $file){
        $link = $file['link'];
        $title = $file['title'];
        $keyboard[] = ['text' => "$title", 'url' => $link];
    }
    $keyboard = array_chunk($keyboard,1);
    bot('sendmessage', [
        'chat_id' => $userid,
        'text' => "
🔰لیست نرم افزار ها به شرح زیر است لطفا یکی از موارد را انتخاب کنید

🔸می توانید به راحتی همه فایل ها را (به صورت رایگان) دریافت کنید
.",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}

if($text=='💳 کیف پول'){
    
    $wallet = $telegram->db->query("SELECT * from `fl_user` WHERE userid=$userid")->fetch(2)['wallet'];
    $ttl = 0;
    $product = '';
    $ttl += $wallet;
    $product .= "
      💸 موجودی کل : ".number_format($ttl)." تومان ";
    
	if($ttl == 0) $product= '🔻موجودی کیف پول شما صفر است ';

	$telegram->sendAction($userid,'typing');

	$keyboard[] = [['text' => "افزایش موجودی", 'callback_data' => "addwalet"]];
	bot('sendmessage',[
		'chat_id' => $userid,
		'text'=> $product,
		'reply_markup' => json_encode([
			'inline_keyboard' => $keyboard
		])
	]);
}
if($cdata=='addwalet'){
	$state = file_put_contents('state/'.$userid.'.txt','addwalet');
	$msg = '🔻لطفا مبلغی که قصد شارژ حساب خود دارید را به تومان و اعداد لاتین وارد کنید.'; 
	$telegram->sendMessageCURL($userid,$msg,[['❌ انصراف']]);
}

if($state == 'addwalet' and $text != '❌ انصراف'){
	if(intval($text) and $text > 2000){
		$state = file_put_contents('state/'.$userid.'.txt','');
		$amount = number_format($text);
		$telegram->sendMessageCURL($userid,'برای پرداخت روی دکمه پایین بزنید :',$finalop);
		
		$keyboard[] = [['text' => "درگاه زرین پال", 'url' => baseURI."/wallet/pay.php?userid=$userid&amount=$text"]];
		$keyboard[] = [['text' => "درگاه نکست پی", 'url' => baseURI."/wallet/next/pay.php?userid=$userid&amount=$text"]];
		$keyboard[] = [['text' => "کارت به کارت",  'callback_data' => "crdwll#$text"]];
		
		$aa = bot('sendmessage',[
			'chat_id' => $userid,
			'text'=> "لینک پرداخت آنلاین برای شارژ حساب به مبلغ $amount تومان ایجاد شد :",
			'reply_markup' => json_encode([
				'inline_keyboard' => $keyboard
			])
		]);
	   // $telegram->sendMessage($userid,json_encode($aa));
		
	}else {
		$telegram->sendMessage($userid,'لطفا مبلغ را به تومان و بیشتر از 2هزار تومان وارد کنید');exit;
	}
}
if(preg_match('/crdwll/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"لطفا تصویر فیش واریزی یا شماره پیگیری -  ساعت پرداخت - نام پرداخت کننده را در یک پیام ارسال کنید
🔸<code>$cardinfo</code>",$cancelop);
    exit;
}
if(preg_match('/crdwll/',$state) and $text != '❌ انصراف'){
    $input = explode('#',$state);
    $amount = $input[1];
    file_put_contents("state/$userid.txt",'');
    $res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $uid = $res['userid'];
    $name = $res['name'];
    $username = $res['username'];
    
    $price = number_format($amount);

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption <a href='$fileurl'>&#8194;نمایش فیش</a>";
    $msg = "
✅✅درخواست افزایش موجودی شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش، موجودی شما به مبلغ $price تومان شارژ و از طریق ربات اطلاع رسانی می شود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷افزایش موجودی کاربر $name
✖کد کاربری: $userid
📧یوزرنیم: @$username
مبلغ درخواستی: $price تومان
📝اطلاعات پرداخت کارت به کارت: $infoc
 ";
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'تایید پرداخت', 'callback_data' => "aducash#$uid#$amount"],
				['text' => 'عدم تایید', 'callback_data' => "disable#$uid#wallet$amount"]
            ]
        ]
    ]);
    bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
}

if(preg_match('/aducash/',$cdata) and $text != '❌ انصراف'){
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata);
    $uid = $input[1];
    $amount = $input[2];
    $price = number_format($amount);
    $telegram->sendMessageCURL($userid,"موجودی کاربر به مقدار $price تومان شارژ شد",$finalop);
    
    $telegram->db->query("update fl_user set wallet = wallet + $amount WHERE userid=$uid");
	$telegram->sendHTML($uid,"💹کاربر گرامی موجودی شما به مقدار $price تومان شارژ شد",$finalop);
	
}
if($text=='👤 حساب کاربری'){
    file_put_contents('state/'.$userid.'.txt','');
    $fault = $telegram->db->query("select * from fl_order where status=0 and userid=$userid")->rowCount();
    $success = $telegram->db->query("select * from fl_order where status=1 and userid=$userid")->rowCount();
    $user = $telegram->db->query("select * from fl_user where userid=$userid ")->fetch(2);
    $msg = "🖥 اطلاعات حساب کاربری شما به شرح زیر میباشد :\n
🔢 ایدی عددی شما : ".$user['userid']."\n💎 موجودی شما : ".number_format($user['wallet'])." تومان \n .";
    $telegram->sendMessage($userid,$msg);
}
if ($text == '❌ انصراف') {
    file_put_contents('state/' . $userid . '.txt', '');
    $telegram->db->query("delete from fl_file where active=0");
    $telegram->sendHTML($userid, '‼️‼️عملیات مورد نظر لغو شد', $finalop);
}