<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('7108233013:AAGgOEQDmhTiGzVmzSk-uI13IRfa4O3RrBc');

// result request body{}7108233013:AAGgOEQDmhTiGzVmzSk-uI13IRfa4O3RrBc
$resultTelegram = $telegram->getData();

$chat_id    = $telegram->ChatID();
$text       = $telegram->Text();
$mesasge_id = $resultTelegram['callback_query']['message']['message_id'];


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
        array($telegram->buildInlineKeyBoardButton("ثبت یادآوری ⌛", '', '/add_alarm')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "برای ثبت آلارم روی دکمه زیر کلیک کن!", $keyb, $mesasge_id);
}

function query($action, $table, $fields = false, $wheres = false, $isfetchall = false)
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

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($values); $i++)
            $statment->bindValue($i, $values[$i-1]);
        $statment->execute();

        if($isfetchall) return $statment->fetchAll(PDO::FETCH_OBJ);
        return $statment->fetch(PDO::FETCH_OBJ);
    }
}

// end

$commandsList = ["/start"];
$myCommands = false;
if(in_array($text, $commandsList)) $myCommands = true;
$myCommands = false;

if($text == "/start") {
    // true myCommands (bool)
    sendMainKeyboardMenu($chat_id);

}

if($text == "/add_alarm"){
    $option = array(
        array($telegram->buildInlineKeyBoardButton("15 دقیقه", '', '+15'), $telegram->buildInlineKeyBoardButton("30 دقیقه", '', '+30'), $telegram->buildInlineKeyBoardButton("1 ساعت", '', '+60')),
        array($telegram->buildInlineKeyBoardButton("2 ساعت", '', '+120'), $telegram->buildInlineKeyBoardButton("3 ساعت", '', '+180'), $telegram->buildInlineKeyBoardButton("4 ساعت", '', '+240'))
        );

    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "میخوای برای کِی یادآوری ثبت کنی؟", $keyb, $mesasge_id);
}

if($text[0] == "+") {
    $alarm_value = trim($text, '+');
    $alarm_time = time() + ($alarm_value * 60);

    query("CREATE", "alarm", ["alarm_time" => $alarm_time, "chat_id" => $chat_id, "status" => "pending"]);

    sendMessage($chat_id, "یادآوری شما با موفقیت ساخته شد! ✅⌛");
}





