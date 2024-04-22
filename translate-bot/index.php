<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6533298739:AAGRNMkjEQMHXrj69ACzf_ijO9cg3G04UqQ');

// result request body{}
$result = $telegram->getData();

$chat_id = $telegram->ChatID();
$text    = $telegram->Text();

$myCommands = false;

if($text == "/start") {
    // true myCommands (bool)
    $myCommands = true;

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("بزن بریم😎", '', '/home')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "سلام خوبی؟ به ربات ترجمه خوش اومدی");
    $telegram->sendMessage($content);
}

if($text == "/home") {
    // true myCommands (bool)
    $myCommands = true;

    // send welcome mesasage& create keyboard
    $option = array(
        //First row
//        array($telegram->buildInlineKeyBoardButton("شروع😎", '', '/start')),
        //Second row
        array($telegram->buildInlineKeyBoardButton(" گوگل(Google) 🇺🇸", '', '/google'), $telegram->buildInlineKeyBoardButton("مایکروسافت (Microsoft) 🇺🇸", '', '/microsoft')),
        array($telegram->buildInlineKeyBoardButton("ترگمان 🇮🇷", '', '/targoman'), $telegram->buildInlineKeyBoardButton("فرازین 🇮🇷", '', '/faraazin')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "حالا وقتشه موتور ترجمه مد نظرت رو انتخاب کنی!",  'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

// Targoman
if($text == "/targoman") {
    $myCommands = true;

    // Create Record
    $query = "INSERT INTO translate_request SET chat_id=?, action=?, updated_at=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->bindValue(2, "targoman");
    $stmt->bindValue(3, time());
    $stmt->execute();

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("ترجمه فارسی به انگلیسی", '', '/fa2en'), $telegram->buildInlineKeyBoardButton("ترجمه انگلیسی به فارسی", '', '/en2fa')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "حالت ترجمه رو انتخاب کن", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/fa2en") {
    $myCommands = true;

    $query = "UPDATE translate_request SET lang=?, updated_at=? WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "fa2en");
    $stmt->bindValue(2, time());
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();

    $content = array('chat_id' => $chat_id, 'reply_markup' => [], 'text' => "حالا متنی که میخوای از فارسی به انگلیسی ترجمه کنی رو بده:", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/en2fa") {
    $myCommands = true;

    $query = "UPDATE translate_request SET lang=?, updated_at=? WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "en2fa");
    $stmt->bindValue(2, time());
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();

    $content = array('chat_id' => $chat_id, 'reply_markup' => [], 'text' => "حالا متنی که میخوای از انگلیسی به فارسی ترجمه کنی رو بده:", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

// Google & microsoft
if($text == "/google") {
    $myCommands = true;

    // Create Record
    $query = "INSERT INTO translate_request SET chat_id=?, action=?, updated_at=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->bindValue(2, "google");
    $stmt->bindValue(3, time());
    $stmt->execute();

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("ترجمه به انگلیسی", '', '/en'), $telegram->buildInlineKeyBoardButton("ترجمه به فارسی", '', '/fa')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "حالت ترجمه رو انتخاب کن", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/microsoft") {
    $myCommands = true;

    // Create Record
    $query = "INSERT INTO translate_request SET chat_id=?, action=?, updated_at=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->bindValue(2, "microsoft");
    $stmt->bindValue(3, time());
    $stmt->execute();

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("ترجمه به انگلیسی", '', '/en'), $telegram->buildInlineKeyBoardButton("ترجمه به فارسی", '', '/fa')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "حالت ترجمه رو انتخاب کن", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/fa") {
    $myCommands = true;

    $query = "UPDATE translate_request SET lang=?, updated_at=? WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "fa");
    $stmt->bindValue(2, time());
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();

    $content = array('chat_id' => $chat_id, 'reply_markup' => [], 'text' => "حالا متنی که میخوای ترجمه کنی رو بده:", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/en") {
    $myCommands = true;

    $query = "UPDATE translate_request SET lang=?, updated_at=? WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "en");
    $stmt->bindValue(2, time());
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();

    $content = array('chat_id' => $chat_id, 'reply_markup' => [], 'text' => "حالا متنی که میخوای ترجمه کنی رو بده:", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

// Faraazin
if($text == "/faraazin") {
    $myCommands = true;

    // Create Record
    $query = "INSERT INTO translate_request SET chat_id=?, action=?, updated_at=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->bindValue(2, "faraazin");
    $stmt->bindValue(3, time());
    $stmt->execute();

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("ترجمه فارسی به انگلیسی", '', '/fa_en'), $telegram->buildInlineKeyBoardButton("ترجمه انگلیسی به فارسی", '', '/en_fa')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "حالت ترجمه رو انتخاب کن", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/fa_en") {
    $myCommands = true;

    $query = "UPDATE translate_request SET lang=?, updated_at=? WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "fa_en");
    $stmt->bindValue(2, time());
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();

    $content = array('chat_id' => $chat_id, 'reply_markup' => [], 'text' => "حالا متنی که میخوای از فارسی به انگلیسی ترجمه کنی رو بده:", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/en_fa") {
    $myCommands = true;

    $query = "UPDATE translate_request SET lang=?, updated_at=? WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "en_fa");
    $stmt->bindValue(2, time());
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();

    $content = array('chat_id' => $chat_id, 'reply_markup' => [], 'text' => "حالا متنی که میخوای از انگلیسی به فارسی ترجمه کنی رو بده:", 'message_id'=> $result['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}


if(!$myCommands) {
    $query = "SELECT * FROM translate_request WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    if($stmt->rowCount() && isset($result->lang)){
        $query = "UPDATE translate_request SET q=?, updated_at=? WHERE chat_id=? ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $text);
        $stmt->bindValue(2, time());
        $stmt->bindValue(3, $chat_id);
        $stmt->execute();


        $translated_text = translateRequestApi("277542:65be30c3eb3ce", $result->action, $result->lang, $text);

        $content = array('chat_id' => $chat_id, 'text' => $translated_text);
        $telegram->sendMessage($content);


        $option = array(
            array($telegram->buildInlineKeyBoardButton(" گوگل(Google) 🇺🇸", '', '/google'), $telegram->buildInlineKeyBoardButton("مایکروسافت (Microsoft) 🇺🇸", '', '/microsoft')),
            array($telegram->buildInlineKeyBoardButton("ترگمان 🇮🇷", '', '/targoman'), $telegram->buildInlineKeyBoardButton("فرازین 🇮🇷", '', '/faraazin')),
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "حالا وقتشه موتور ترجمه مد نظرت رو انتخاب کنی!");
        $telegram->sendMessage($content);
    } else {
        $content = array('chat_id' => $chat_id, 'text' => "دستور وارد شده معتبر نیست!");
        $telegram->sendMessage($content);
    }

}


function translateRequestApi($token, $action, $lang, $query){
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://one-api.ir/translate/?token=$token&action=$action&lang=$lang&q=". urlencode($query),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $result = (json_decode($response));

    $translated = "ترجمه یافت نشد";
    if($action == "faraazin") $translated = $result->result->base[0][1];
    else $translated  = $result->result;

    if($result->status == 200) return $translated;
    else var_dump($result); die();
}