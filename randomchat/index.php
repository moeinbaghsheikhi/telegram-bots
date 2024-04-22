<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6914279620:AAF3XDwFCuPsh3kr2rbaV09KksrfkUJgbdI');

// result request body{}
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
        array($telegram->buildInlineKeyBoardButton("شروع چت تصادفی🔍", '', '/random')),
        array($telegram->buildInlineKeyBoardButton("تکمیل اطلاعات پروفایل🧑‍🦲", '', '/set_profile'), $telegram->buildInlineKeyBoardButton("نمایش اطلاعات پروفایل👁️", '', '/show_profile')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "برای پیدا کردن کاربر تصادفی روی دکمه زیر کلیک کن!", $keyb, $mesasge_id);
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

function findActiveChat(){
    $query = "SELECT * FROM chats WHERE status=? AND (user_1=? OR user_2=?) ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, "doing");
    $stmt->bindValue(2, $chat_id);
    $stmt->bindValue(3, $chat_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ);
}
// end

$commandsList = ["/start", "/home", "/set_profile", "/show_profile", "/random", "نمایش پروفایل👁️", "پایان چت⛔"];
$myCommands = false;
if(in_array($text, $commandsList)) $myCommands = true;

if($text == "/start") {
    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("بزن بریم😎", '', '/home')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "سلام خوبی؟ به ربات چت ناشناس خوش اومدی", $keyb);

    // select user by chat_id
    $result = query("SELECT", "users", false, [["key" => "chat_id", "condition"=> "=", "value" =>$chat_id]]);

    if(!$result) query("INSERT", "users", ["chat_id" => $chat_id]);
}

if($text == "/home") sendMainKeyboardMenu($chat_id, $mesasge_id);

if($text == "/set_profile"){
    query("CREATE", "commands_history", ["chat_id" => $chat_id, "command" => "set_profile", "time" => time()]);
    query("UPDATE", "users", ["name"=>null, "age"=>null, "gender"=>null], [["key" => "chat_id", "condition" => "=" ,"value" => $chat_id]]);

    // send edit profile message
    sendMessage($chat_id, "اسم خودت رو وارد کن: ");
}

if($text == "/show_profile"){
    // find profile
    $profile = query('SELECT','users', false, [["key" => "chat_id", "condition" => "=" ,"value" => $chat_id]]);
    $gender = "پسر";
    if($profile->gender == "women") $gender = "دختر";

    sendMessage($chat_id, "اسم شما: " . $profile->name . "\n" . "جنسیت: " . $gender . "\n" . "سن: " . $profile->age);

    // send Menu
    sendMainKeyboardMenu($chat_id);
}

if($text == "/random"){
    query('UPDATE', 'requests', ["status" => "failed"], [["key" => "time", "condition"=> "=","value" => (time() - 300)]]);

    // Loading
    sendMessage($chat_id, "چند لحظه منتظر بمونید⌛");

    $result = query("SELECT", "requests", false, [["key"=> "status", "condition"=> "=", "value" => "pending"], ["key"=> "time", "condition"=> ">=", "value" => (time() - 300)], ["key"=> "chat_id", "condition"=> "!=", "value" => $chat_id]], true);

    if(count($result) == 0){
        query("INSERT", "requests", ["chat_id" => $chat_id, "status" => "pending", "time" => time()]);
    } else {
        $target_request = $result[0];

        query("INSERT", "chats", ["user_1" => $chat_id, "user_2" => $target_request->chat_id, "status" => "doing" ,"time" => time()]);
        query("UPDATE", "requests", ["status" => "done"], [["key"=> "id", "condition"=> "=", "value"=> $target_request->id]]);

        $option = array(
            //First row
            array($telegram->buildKeyboardButton("پایان چت⛔"), $telegram->buildKeyboardButton("نمایش پروفایل👁️")),
        );
        $keyb = $telegram->buildKeyBoard($option, $onetime=false);

        // Start Chat Message
        $message_text = "کاربر تصادفی با موفقیت پیدا شد. درحال چت هستید🧑‍🤝";
        sendMessage($chat_id, $message_text, $keyb);
        sendMessage($target_request->chat_id, $message_text, $keyb);
    }
}

if($text == "نمایش پروفایل👁️"){
    // Find active chat
    $result = findActiveChat();

    if($result){
        $target_chatid;
        if($result->user_1 == $chat_id) $target_chatid = $result->user_2;
        else if($result->user_2 == $chat_id) $target_chatid = $result->user_1;

        // find profile
        $profile = query("SELECT", "users", false, [["key" => "chat_id", "condition"=> "=", "value" =>$target_chatid]]);

        $gender = "پسر";
        if($profile->gender == "women") $gender = "دختر";

        sendMessage($chat_id, "اسم کاربر مقابل: " . $profile->name . "\n" . "جنسیت: " . $gender . "\n" . "سن: " . $profile->age);
    }
}

if($text == "پایان چت⛔"){
    // Find active chat
    $result = findActiveChat();

    if(!$result){
        // Not found commands
        sendMessage($chat_id, "خطا⛔");
    }
    else{
        // Update Chate to Finished
        query("UPDATE", "chats", ["status" => "finished"], [["key" => "id", "condition"=> "=", "value" =>"finished"]]);

        // Delete keyboard
        $reply_markup = array(
            'remove_keyboard' => true
        );

        $reply_markup = json_encode($reply_markup);

        // EndChat Message
        sendMessage($result->user_1, "چت شما به پایان رسید⚠️", $reply_markup);
        sendMessage($result->user_2, "چت شما به پایان رسید⚠️", $reply_markup);

        sendMainKeyboardMenu($result->user_1);
        sendMainKeyboardMenu($result->user_2);
    }
}

if(!$myCommands){
    // Find active chat
    $result = findActiveChat();

    // find befor commands
    $commands = query("SELECT", "commands_history", false, [["key" => "chat_id", "condition"=> "=", "value" =>$target_chatid], ["key" => "time", "condition"=> ">=", "value" => (time()-120)]]);

    if($result){
        $target_chatid;
        if($chat_id == $result->user_1) $target_chatid = $result->user_2;
        else if ($chat_id == $result->user_2) $target_chatid = $result->user_1;

        sendMessage($target_chatid, $text);
    }
    else if($commands){
        if($commands->command == "set_profile"){
            // find profile
            $profile = query("SELCET", "users", false, ["key" => "chat_id", "condition"=> "=", "value" =>$chat_id]);

            if(!$profile->name){
                // update name in profile (users)
                query("UPDATE", "users", ["name" => $text], ["key" => "chat_id", "condition"=> "=", "value" =>$chat_id]);
                sendMessage($chat_id, "سن خود را وارد کنید:");
            }
            else if(!$profile->age){
                // update name in profile (users)
                query("UPDATE", "users", ["age" => $text], ["key" => "chat_id", "condition"=> "=", "value" =>$chat_id]);

                $option = array(
                    array($telegram->buildInlineKeyBoardButton("پسر 🚹", '', 'men'), $telegram->buildInlineKeyBoardButton("دختر 🚺", '', 'women')),
                );
                $keyb = $telegram->buildInlineKeyBoard($option);
                sendMessage($chat_id, "جنسیت خود را انتخاب کنید:", $keyb);
            } else if(!$profile->gender){
                query("UPDATE", "users", ["gender" => $text], ["key" => "chat_id", "condition"=> "=", "value" =>$chat_id]);
                sendMessage($chat_id, "پروفایل شما با موفقیت آپدیت شد ✅");
                sendMainKeyboardMenu($chat_id);
            }
        }
    }
    else{
        // Not found commands
        sendMessage($chat_id, "دستور وارد شده صحیح نیست⛔");
    }
}