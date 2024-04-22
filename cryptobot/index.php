<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6754127647:AAEswgp6TA1wRK8Y3g1nxKjx038yr0qgrrM');

// result request body{}
$resultTelegram = $telegram->getData();

$chat_id = $telegram->ChatID();
$text    = $telegram->Text();

$myCommands = false;

if($text == "/start") {
    // true myCommands (bool)
    $myCommands = true;

    // Loading Message
    $content = array('chat_id' => $chat_id, 'text' => "چند لحظه صبر کنید...🔃");
    $telegram->sendMessage($content);

    $response_api = translateRequestApi("277542:65be30c3eb3ce");

    if(!$response_api){
        $content = array('chat_id' => $chat_id, 'text' => "سرویس از دسترس خارج شده! بعدا امتحان کنید");
        $telegram->sendMessage($content);
    } else{
        $option = array(
            //First row
            array($telegram->buildInlineKeyBoardButton(($response_api[0]->name . " (" . $response_api[0]->key . ")"), '', '/'.$response_api[0]->key), $telegram->buildInlineKeyBoardButton(($response_api[1]->name . " (" . $response_api[1]->key . ")"), '', '/'.$response_api[1]->key)),
            array($telegram->buildInlineKeyBoardButton(($response_api[2]->name . " (" . $response_api[2]->key . ")"), '', '/'.$response_api[2]->key), $telegram->buildInlineKeyBoardButton(($response_api[3]->name . " (" . $response_api[3]->key . ")"), '', '/'.$response_api[3]->key)),
            array($telegram->buildInlineKeyBoardButton(($response_api[4]->name . " (" . $response_api[4]->key . ")"), '', '/'.$response_api[4]->key), $telegram->buildInlineKeyBoardButton(($response_api[5]->name . " (" . $response_api[5]->key . ")"), '', '/'.$response_api[5]->key)),
            array($telegram->buildInlineKeyBoardButton(($response_api[6]->name . " (" . $response_api[6]->key . ")"), '', '/'.$response_api[6]->key), $telegram->buildInlineKeyBoardButton(($response_api[7]->name . " (" . $response_api[7]->key . ")"), '', '/'.$response_api[7]->key)),
            array($telegram->buildInlineKeyBoardButton("جستجو در ارز ها🔍", '', '/search')),
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "سلام خوبی؟ به ربات قیمت ارز دیجیتال خوش اومدی🪙", 'message_id'=> $resultTelegram['message']['message_id']+1);
        $telegram->editMessageText($content);
    }
}

if($text == "/search"){
    // true myCommands (bool)
    $myCommands = true;

    $content = array('chat_id' => $chat_id, 'text' => "دنبال چه ارز دیجیتالی میگردی؟ :\n\n میتوانید عنوان فارسی؛ عنوان اصلی و مخفف ارز رو وارد کنید (مثال: بایننس کوین)");
    $telegram->sendMessage($content);
}

if(!$myCommands) {
    // Loading Message
    $content = array('chat_id' => $chat_id, 'text' => "چند لحظه صبر کنید...🔃");
    $telegram->sendMessage($content);

    $response_api = translateRequestApi("277542:65be30c3eb3ce");

    if($text[0] == '/'){
        $key = trim($text, '/');
        foreach ($response_api as $item){
            if($item->key == $key){
                $message_text = "✅💵" . $item->name . "\n" . "مخفف: " . $item->key . "\n" . "اسم انگلیسی: " . $item->name_en . "\n" . "قیمت: " . $item->price . "\n" . "بالاترین قیمت روزانه: " . $item->daily_high_price . "\n" . "تغییر یک ساعته: " . round($item->percent_change_1h, 2);
                $content = array('chat_id' => $chat_id, 'text' => $message_text);
                $telegram->sendMessage($content);
            }
        }
    } else {
        $response_search = searchInCrypto($response_api, $text);

        $option = array();

        foreach ($response_search as $item) array_push($option, array($telegram->buildInlineKeyBoardButton(($item->name . " (" . $item->key . ")"), '', '/'.$item->key)));

        $keyb = $telegram->buildInlineKeyBoard($option);
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "نتایج جستجو🔍:", 'message_id'=> $resultTelegram['message']['message_id']+1);
        $telegram->editMessageText($content);
    }
}

function searchInCrypto($list, $key)
{
    $resultSearched = [];
    foreach ($list as $item){
        if(str_contains($item->name, $key)) array_push($resultSearched, $item);
        if(str_contains($item->name_en, $key)) array_push($resultSearched, $item);
        if(str_contains($item->key, $key)) array_push($resultSearched, $item);

        if(count($resultSearched) == 8) break;
    }


    return $resultSearched;
}

function translateRequestApi($token){
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://one-api.ir/DigitalCurrency/?token=$token",
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

    if($result->status == 200) return $result->result;
    else return false;
}