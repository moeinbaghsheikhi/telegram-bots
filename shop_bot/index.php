<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6817903574:AAEafofh8hFsEX5frtFPMHubAZu0dZ_Fk0M');
$zibal_merchant = "zibal";

// result request body{}
$resultTelegram = $telegram->getData();

$chat_id = $telegram->ChatID();
$text = $telegram->Text();
$mesasge_id = false;
if (isset($resultTelegram['callback_query'])) $mesasge_id = $resultTelegram['callback_query']['message']['message_id'];


// my Functions

function sendMessage($chat_id, $text, $keyboard = false, $editMessage = false)
{
    global $telegram;

    $content = array('chat_id' => $chat_id, 'text' => $text);

    if ($keyboard) $content["reply_markup"] = $keyboard;
    if ($editMessage) {
        $content['message_id'] = $editMessage;
        return $telegram->editMessageText($content);
    }

    return $telegram->sendMessage($content);
}

function sendMainKeyboardMenu($chat_id, $mesasge_id = false)
{
    global $telegram, $resultTelegram;

    $option = array(
        array($telegram->buildInlineKeyBoardButton("مشاهده محصولات 🛍️️", '', '/product_list')),
        array($telegram->buildInlineKeyBoardButton("سبد خرید ️🛒", '', '/cart'), $telegram->buildInlineKeyBoardButton("پشتیبانی 🗣️", '', '/support')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    $text = "سلام " . $telegram->FirstName();
    $text .= ("\n" . "به فروشگاه سبزلرن خوش اومدی ❤️");

    sendMessage($chat_id, $text, $keyb, $mesasge_id);
}

function query($action, $table, $fields = false, $wheres = false, $isfetchall = false, $order_by = false)
{
    global $conn, $chat_id;

    // CREATE
    if ($action == "CREATE") {
        $values = [];
        $sql = "INSERT INTO $table SET ";
        $item = 1;
        foreach ($fields as $key => $value) {
            $sql .= ($key . '=? ');
            array_push($values, $value);
            if ($item < count($fields)) $sql .= ', ';
            $item++;
        }

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($fields); $i++)
            $statment->bindValue($i, $values[$i - 1]);
        $statment->execute();
    }

    // UPDATE
    if ($action == "UPDATE") {
        $values = [];
        $sql = "UPDATE $table SET ";

        $item = 1;
        foreach ($fields as $key => $value) {
            $sql .= ($key . '=? ');
            array_push($values, $value);
            if ($item < count($fields)) $sql .= ', ';
            $item++;
        }

        if ($wheres) {
            $sql .= "WHERE ";
            $item = 1;
            foreach ($wheres as $where) {
                $sql .= $where["key"] . $where["condition"] . "?";
                array_push($values, $where["value"]);
                if ($item < count($wheres)) $sql .= ' AND ';
                $item++;
            }
        }

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($values); $i++)
            $statment->bindValue($i, $values[$i - 1]);
        $statment->execute();
    }

    // SELECT
    if ($action == "SELECT") {
        $values = [];
        $sql = "SELECT * FROM $table ";

        if ($wheres) {
            $sql .= "WHERE ";
            $item = 1;
            foreach ($wheres as $where) {
                $sql .= $where["key"] . $where["condition"] . "?";
                array_push($values, $where["value"]);
                if ($item < count($wheres)) $sql .= ' AND ';
                $item++;
            }
        }

        if ($order_by) $sql .= (" ORDER BY " . $order_by);

        $statment = $conn->prepare($sql);
        for ($i = 1; $i <= count($values); $i++)
            $statment->bindValue($i, $values[$i - 1]);
        $statment->execute();

        if ($isfetchall) return $statment->fetchAll(PDO::FETCH_OBJ);
        return $statment->fetch(PDO::FETCH_OBJ);
    }
}

// end

$commandsList = ["/start", "/product_list", "/cart", "/cancel", "/support", "/pay"];
$myCommands = false;
if (in_array($text, $commandsList)) $myCommands = true;

if ($text == "/start") {
    $getUser = query("SELECT", "users", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]]);
    if (!$getUser) {
        query("CREATE", "users", ["chat_id" => $chat_id, "name" => $telegram->FirstName(), "status" => "enable"]);
    }

    sendMainKeyboardMenu($chat_id);
}

if ($text == "/product_list") {
    $products = query("SELECT", "products", false, false, true);

    foreach ($products as $product) {
        $option = array(
            array($telegram->buildInlineKeyBoardButton("افزودن به سبد خرید +", '', '+' . $product->id)),
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $text = ("عنوان محصول: " . $product->title . "\n" . "قیمت محصول: " . $product->price . "\n" . "موجودی انبار: " . $product->inventory);

        sendMessage($chat_id, $text, $keyb);
    }

    $option = array(
        array($telegram->buildInlineKeyBoardButton("بازگشت به منوی اصلی 🏠", '', "/start")),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "محصول مد نظر خودت رو به سبد خرید اضافه کن✅", $keyb);
}

if ($text[0] == "+") {
    $myCommands = true;
    $product_id = trim($text, '+');

    // get user detail
    $user_detail = query("SELECT", "users", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]]);

    // get active order
    $active_order = query("SELECT", "orders", false, [["key" => "status", "condition" => "=", "value" => "pending"], ["key" => "user_id", "condition" => "=", "value" => $user_detail->id]]);

    if ($active_order) {
        query("CREATE", "orders_item", ["order_id" => $active_order->id, "product_id" => $product_id]);
    } else {
        query("CREATE", "orders", ["user_id" => $user_detail->id, "time" => time(), "status" => "pending"]);

        // get new active order
        $active_order = query("SELECT", "orders", false, [["key" => "status", "condition" => "=", "value" => "pending"], ["key" => "user_id", "condition" => "=", "value" => $user_detail->id]]);

        query("CREATE", "orders_item", ["order_id" => $active_order->id, "product_id" => $product_id]);
    }

    sendMessage($chat_id, "محصول مورد نظر با موفقیت به سبد خرید اضافه شد! ✅");
}

if ($text == "/cart") {
    // get user detail
    $user_detail = query("SELECT", "users", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]]);

    // get active order
    $active_order = query("SELECT", "orders", false, [["key" => "status", "condition" => "=", "value" => "pending"], ["key" => "user_id", "condition" => "=", "value" => $user_detail->id]]);

    if(!$active_order){
        $option = array(
            array($telegram->buildInlineKeyBoardButton("بازگشت به منوی اصلی 🏠", '', "/start")),
        );
        $keyb = $telegram->buildInlineKeyBoard($option);

        sendMessage($chat_id, "سبد خرید شما خالی هست 📪", $keyb);
    }else {
        $sql = "SELECT orders_item.id AS orderitem_id, products.title as product_title, products.price as product_price, products.inventory as product_inventory FROM `orders_item`
                LEFT JOIN products ON orders_item.product_id = products.id
                WHERE orders_item.order_id=".$active_order->id;

        $statment = $conn->query($sql);
        $statment->execute();
        $products = $statment->fetchAll(PDO::FETCH_OBJ);

        $option = array(
            array($telegram->buildInlineKeyBoardButton("پرداخت فاکتور 💰", '', "/pay"), $telegram->buildInlineKeyBoardButton("کنسل کردن ❌", '', "/cancel")),
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        $text = "";

        $total_price = 0;
        foreach ($products as $product){
            $total_price += $product->product_price;
            $text .= ("نام محصول: " . $product->product_title . "\n" . "قیمت محصول: " . number_format($product->product_price) . "\n" . "--------------------------------------" . "\n");
        }
        $text .= ("\n" . "مجموع مبلغ فاکتور: " . number_format($total_price));
        sendMessage($chat_id, $text, $keyb);
    }
}

if($text == "/pay"){
    // get user detail
    $user_detail = query("SELECT", "users", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]]);

    // get active order
    $active_order = query("SELECT", "orders", false, [["key" => "status", "condition" => "=", "value" => "pending"], ["key" => "user_id", "condition" => "=", "value" => $user_detail->id]]);

    $sql = "SELECT orders_item.id AS orderitem_id, products.title as product_title, products.price as product_price, products.inventory as product_inventory FROM `orders_item`
                LEFT JOIN products ON orders_item.product_id = products.id
                WHERE orders_item.order_id=".$active_order->id;

    $statment = $conn->query($sql);
    $statment->execute();
    $products = $statment->fetchAll(PDO::FETCH_OBJ);

    $total_price = 0;
    foreach ($products as $product) $total_price += $product->product_price;


$curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://gateway.zibal.ir/v1/request',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
    "merchant": "'.$zibal_merchant.'",
    "amount": '.($total_price*10).',
    "callbackUrl": "http://localhost/shop_bot/verify.php"
}',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);

    curl_close($curl);

    $trackId = $response->trackId;

    query("UPDATE", "orders", ["trackId" => $trackId], [["key" => "id", "condition" => "=" , "value" => $active_order->id]]);

    $option = array(
        array($telegram->buildInlineKeyBoardButton("رفتن به صفحه پرداخت 💳️", 'https://gateway.zibal.ir/start/'.$trackId)),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "برای تکمیل سفارش وارد لینک زیر شوید!", $keyb);
}

if($text == "/cancel"){
    // get user detail
    $user_detail = query("SELECT", "users", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]]);

    query("UPDATE", "orders", ["status" => "cancel"], [["key" => "status", "condition" => "=", "value" => "pending"], ["key" => "user_id", "condition" => "=", "value" => $user_detail->id]]);

    sendMessage($chat_id, "سبد خرید شما با موفقیت خالی شد ⛔📝");
    sendMainKeyboardMenu($chat_id);
}

if($text == "/support"){
    $setting = query("SELECT", "settings", false, [["key" => "setting_key", "condition"=> "=", "value" => "support"]]);
    sendMessage($chat_id, $setting->setting_value);

    sendMainKeyboardMenu($chat_id);
}

if (!$myCommands) sendMessage($chat_id, "دستور وارد شده معتبر نیست ⛔");