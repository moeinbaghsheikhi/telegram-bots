<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6717825070:AAFfeSyWOIXNyrvtOSyDOfZyzfupm1SeUYU');

// result request body{}
$resultTelegram = $telegram->getData();

$api_token  = "277542:65be30c3eb3ce";
$chat_id    = $telegram->ChatID();
$text       = $telegram->Text();
$mesasge_id = null;
if(isset($resultTelegram['callback_query'])) $mesasge_id = $resultTelegram['callback_query']['message']['message_id'];


// my Functions
function sendMessage($chat_id, $text, $keyboard = false, $editMessage = false)
{
    global $telegram;

    $content = array('chat_id' => $chat_id, 'text' => $text);

    if($keyboard) $content["reply_markup"]  = $keyboard;
    if($editMessage)
    {
        $content['message_id'] = $editMessage;
        return $telegram->editMessageText($content);
    }

    return $telegram->sendMessage($content);
}

function sendMainKeyboardMenu($chat_id, $mesasge_id= false)
{
    global $telegram, $resultTelegram;

    $option = array(
        array($telegram->buildInlineKeyBoardButton("تبدیل متن به گفتار 🗣️", '', '/texttotalk')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "به ربات متن به گفتار خوش اومدی❤️", $keyb, $mesasge_id);
}

function query($action, $table, $fields = false, $wheres = false, $isfetchall = false, $orderBy = false)
{
    global $conn, $chat_id;

    // CREATE
    if($action == "CREATE"){
        $values = [];
        $sql = "INSERT INTO $table SET ";
        $item = 1;
        foreach ($fields as $key => $value){
            $sql .= ($key . '=? ');
            array_push($values, $value);
            if($item < count($fields)) $sql .= ', ';
            $item++;
        }

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($fields); $i++)
            $statment->bindValue($i, $values[$i-1]);
        $statment->execute();
    }

    // UPDATE
    if($action == "UPDATE"){
        $values = [];
        $sql = "UPDATE $table SET ";

        $item = 1;
        foreach ($fields as $key => $value){
            $sql .= ($key . '=? ');
            array_push($values, $value);
            if($item < count($fields)) $sql .= ', ';
            $item++;
        }

        if($wheres){
            $sql .= "WHERE ";
            $item = 1;
            foreach ($wheres as $where){
                $sql .= $where["key"] . $where["condition"]  ."?";
                array_push($values, $where["value"]);
                if($item < count($wheres)) $sql .= ' AND ';
                $item++;
            }
        }
        if($orderBy) $sql .= (" " . $orderBy);

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($values); $i++)
            $statment->bindValue($i, $values[$i-1]);
        $statment->execute();
    }

    // SELECT
    if ($action == "SELECT"){
        $values = [];
        $sql = "SELECT * FROM $table ";

        if($wheres){
            $sql .= "WHERE ";
            $item = 1;
            foreach ($wheres as $where){
                $sql .= $where["key"] . $where["condition"]  ."?";
                array_push($values, $where["value"]);
                if($item < count($wheres)) $sql .= ' AND ';
                $item++;
            }
        }

        if($orderBy) $sql .= (" " . $orderBy);

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($values); $i++)
            $statment->bindValue($i, $values[$i-1]);
        $statment->execute();

        if($isfetchall) return $statment->fetchAll(PDO::FETCH_OBJ);
        return $statment->fetch(PDO::FETCH_OBJ);
    }
}

// end

$commandsList = ["/start", "/texttotalk"];
$myCommands = false;
if(in_array($text, $commandsList)) $myCommands = true;


if($text == "/start") sendMainKeyboardMenu($chat_id);

if($text == "/texttotalk") {
    sendMessage($chat_id, "لطفا متن خود را ارسال کنید:", false, $mesasge_id);
}

if(!$myCommands){
    // آدرس API
    $url = "https://one-api.ir/tts/?token=$api_token&action=microsoft&lang=fa-IR-FaridNeural&q=" . urlencode($text);

// ارسال درخواست به API برای دریافت فایل صوتی
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_status == 200) {
        // ذخیره فایل صوتی
        $path_file = 'upload/' . time() . '-voice' . '.mp3'; // نام فایل مورد نظر برای ذخیره
        file_put_contents($path_file, $response);


        $option = array('chat_id' => $chat_id, 'voice' => new CURLFile($path_file));
        $telegram->sendVoice($option);

        sendMessage($chat_id, "ویس شما آماده شد ✅");
    } else {
        sendMessage($chat_id , "مشکلی رخ داده بعدا دوباره امتحان کنید!");
    }
}