<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6740049986:AAFpWrEAZSkl697-Xv-iknyD_oi3T-GoJWA');

// result request body{}
$resultTelegram = $telegram->getData();

$chat_id    = $telegram->ChatID();
$text       = $telegram->Text();
$mesasge_id = false;
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
        array($telegram->buildInlineKeyBoardButton("ثبت نام در خبرنامه 📝", '', '/register')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $text = "سلام " . $telegram->FirstName();
    $text .= "\n به خبرنامه خبرنگاران جوان خوش اومدی! ";

    sendMessage($chat_id, $text, $keyb, $mesasge_id);
}

function query($action, $table, $fields = false, $wheres = false, $isfetchall = false, $order_by= false)
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

        if($order_by) $sql .= (" ORDER BY " . $order_by);

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($values); $i++)
            $statment->bindValue($i, $values[$i-1]);
        $statment->execute();

        if($isfetchall) return $statment->fetchAll(PDO::FETCH_OBJ);
        return $statment->fetch(PDO::FETCH_OBJ);
    }
}

// end

$commandsList = ["/start", "/register"];
$myCommands = false;
if(in_array($text, $commandsList)) $myCommands = true;

if($text == "/start") sendMainKeyboardMenu($chat_id);

if($text == "/register"){
    $getUser = query("SELECT", "users", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]]);
    if(!$getUser){
        // last news
        $last_news = query("SELECT", "news", false, false, false, "id DESC LIMIT 1");

        query("CREATE", "users", ["chat_id" => $chat_id, "name" => $telegram->FirstName()]);
        sendMessage($chat_id, "شما با موفقیت توی خبرنامه ما عوض شدید ✅");
        if($last_news)
        {
            $sended_text = $last_news->title . "\n\n" . $last_news->description;
            sendMessage($chat_id, $sended_text);
        }
    } else{
        sendMessage($chat_id, "شما عضو خبرنامه هستید! ⚠️");
    }
    sendMainKeyboardMenu($chat_id);
}


if(!$myCommands) sendMessage($chat_id, "دستور وارد شده معتبر نیست ⛔");