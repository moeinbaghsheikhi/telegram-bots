<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6719221415:AAGZJK9pQ_dLOSXbEA61LpWRuZcKHVfPe3A');

// result request body{}
$resultTelegram = $telegram->getData();

$chat_id = $telegram->ChatID();
$text    = $telegram->Text();

$myCommands = false;

if($text == "/start") {
    // true myCommands (bool)
    $myCommands = true;

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("افزودن آیتم جدید", '', '/addnew'), $telegram->buildInlineKeyBoardButton("نمایش لیست خرید من", '', '/mylist')),
        array($telegram->buildInlineKeyBoardButton("خالی کردن لیست خرید", '', '/reset'))
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    if(isset($resultTelegram['callback_query']['message']['message_id'])){
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "سلام خوبی؟ به ربات لیست خرید خوش اومدی", 'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
        $telegram->editMessageText($content);
    } else{
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "سلام خوبی؟ به ربات لیست خرید خوش اومدی");
        $telegram->sendMessage($content);
    }


}

if($text == "/addnew") {
    $myCommands = true;

    $content = array('chat_id' => $chat_id,'text' => "آیتم جدید لیست خریدت رو وارد کن:", 'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/mylist") {
    $myCommands = true;

    $message = "آیتم های شما به شرح زیر است:\n\n";

    $query = "SELECT * FROM items WHERE chat_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);

    if($result){
        foreach ($result as $item) $message .= ($item->id . ' . ' . $item->title . "\n");
    } else {
        $message .= "آیتمی وجود ندارد";
    }

    $message .= "\n\n برای حذف آیتم از لیست خرید آیدی اون آیتم رو وارد کنید (منظور از آیدی عددی است که قبل از اسم آیتم نوشته شده) ";

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("🔙 بازگشت", '', '/start')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $message, 'message_id'=> $resultTelegram['callback_query']['message']['message_id']);
    $telegram->editMessageText($content);
}

if($text == "/reset"){
    $myCommands = true;

    $query = "DELETE FROM items WHERE chat_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $chat_id);
    $stmt->execute();

    $content = array('chat_id' => $chat_id,'text' => "تمام آیتم ها با موفقیت حذف شد❌");
    $telegram->sendMessage($content);

    $option = array(
        //First row
        array($telegram->buildInlineKeyBoardButton("افزودن آیتم جدید", '', '/addnew'), $telegram->buildInlineKeyBoardButton("نمایش لیست خرید من", '', '/mylist')),
        array($telegram->buildInlineKeyBoardButton("خالی کردن لیست خرید", '', '/reset'))
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "سلام خوبی؟ به ربات لیست خرید خوش اومدی");
    $telegram->sendMessage($content);
}

if(!$myCommands) {

    if(intval($text)){
        // Create Record
        $query = "DELETE FROM items WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, ((int)$text));
        $stmt->execute();

        $content = array('chat_id' => $chat_id,'text' => "آیتم با موفقیت حذف شد❌");
        $telegram->sendMessage($content);

        $option = array(
            //First row
            array($telegram->buildInlineKeyBoardButton("افزودن آیتم جدید", '', '/addnew'), $telegram->buildInlineKeyBoardButton("نمایش لیست خرید من", '', '/mylist')),
            array($telegram->buildInlineKeyBoardButton("خالی کردن لیست خرید", '', '/reset'))
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "سلام خوبی؟ به ربات لیست خرید خوش اومدی");
        $telegram->sendMessage($content);
    } else {
        // Create Record
        $query = "INSERT INTO items SET chat_id=?, title=?, updated_at=?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $chat_id);
        $stmt->bindValue(2, $text);
        $stmt->bindValue(3, time());
        $stmt->execute();

        $content = array('chat_id' => $chat_id,'text' => "آیتم با موفقیت به لیست خرید اضافه شد✅");
        $telegram->sendMessage($content);

        $option = array(
            //First row
            array($telegram->buildInlineKeyBoardButton("افزودن آیتم جدید", '', '/addnew'), $telegram->buildInlineKeyBoardButton("نمایش لیست خرید من", '', '/mylist')),
            array($telegram->buildInlineKeyBoardButton("خالی کردن لیست خرید", '', '/reset'))
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "سلام خوبی؟ به ربات لیست خرید خوش اومدی");
        $telegram->sendMessage($content);
    }
}