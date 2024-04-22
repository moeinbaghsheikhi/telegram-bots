<?php
require_once 'config/db.php';
include 'Telegram.php';

$telegram = new Telegram('6776376544:AAH3NZgudGayMWVntJPmHs1n_xtrpWkLbqY');

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
        array($telegram->buildInlineKeyBoardButton("دانلود از یوتیوب 🧩", '', '/getlink')),
        array($telegram->buildInlineKeyBoardButton("نمایش 5 تا لینک آخر 5️⃣", '', '/thelastfive')),
    );
    $keyb = $telegram->buildInlineKeyBoard($option);

    sendMessage($chat_id, "برای پیدا کردن کاربر تصادفی روی دکمه زیر کلیک کن!", $keyb, $mesasge_id);
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

$commandsList = ["/start", "/getlink", "/getlink_download", "/direct_download", "/thelastfive"];
$myCommands = false;
if(in_array($text, $commandsList)) $myCommands = true;


if($text == "/start") sendMainKeyboardMenu($chat_id);

if($text == "/getlink") {
    sendMessage($chat_id, "لطفا لینک ویدیو خود را ارسال کنید:");
}

if($text == "/getlink_download"){
    $last_of_link = query("SELECT", "links", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]], false, "ORDER BY id DESC LIMIT 1");

    $option = array(
        array($telegram->buildInlineKeyBoardButton("دانلود ویدیو 🌎", $last_of_link->download_url))
    );
    $keyb = $telegram->buildInlineKeyBoard($option);
    sendMessage($chat_id, "دانلود ویدیو از طریق لینک زیر:", $keyb);

    sendMainKeyboardMenu($chat_id);
}

if($text == "/direct_download"){
    $last_of_link = query("SELECT", "links", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id]], false, "ORDER BY id DESC LIMIT 1");

    //set loading
    sendMessage($chat_id, "درحال بارگذاری ویدیو...");

    $content = array('chat_id' => $chat_id, 'video' => curl_file_create($last_of_link->download_url));
    $telegram->sendVideo($content);

    sendMessage($chat_id,  "ویدیو با موفقیت دانلود شد✅");

    sendMainKeyboardMenu($chat_id);
}

if($text == "/thelastfive"){
    $last_five = query("SELECT", "links", false, [["key" => "chat_id", "condition" => "=", "value" => $chat_id], ["key" => "download_url", "condition" => "!=", "value" => "NULL"]], true, "ORDER BY id DESC LIMIT 5");
    $text = "لینک های دانلود اخیر🌎 :" . "\n";

    foreach ($last_five as $item){
        $text .= ("\n" . $item->title . ":" . "\n" . $item->download_url . "\n");
    }

    sendMessage($chat_id, $text);
    sendMainKeyboardMenu($chat_id);
}

if(!$myCommands){

    if($text[0] == '/'){
        $download_id = trim($text, '/');

        $response = request_curl("https://youtube.one-api.ir/?token=$api_token&action=download&id=$download_id");
        $url = $response->result->link;

        query("UPDATE", "links", ["download_id" => $download_id, "download_url" => $url], [["key" => "chat_id", "condition" => "=", "value" => $chat_id]], false, "ORDER BY id DESC LIMIT 1");


        $option = array(
            array($telegram->buildInlineKeyBoardButton("آپلود به صورت مستقیم", '', "/direct_download"), $telegram->buildInlineKeyBoardButton("دریافت لینک دانلود", '', "/getlink_download"))
        );
        $keyb = $telegram->buildInlineKeyBoard($option);
        sendMessage($chat_id, "روش دانلود رو انتخاب کن", $keyb);
    }
    else
    {
        $response = request_curl("https://one-api.ir/youtube/?token=$api_token&action=getvideoid&link=" . $text);

        if($response->status == 500) sendMessage($chat_id, "دستور وارد شده نامعتبر است!");
        else if($response->status == 200) {
            $video_id = $response->result;

            $full_video = request_curl("https://youtube.one-api.ir/?token=$api_token&action=fullvideo&id=$video_id&filter=video");

            if($full_video->status == 500) sendMessage($chat_id, "مشکلی رخ داده!");
            else if($full_video->status == 200){
                // create record
                query("CREATE", "links", ["chat_id" => $chat_id, "url" => $text, "video_id" => $video_id, "title" => $full_video->result->title]);
//                sendMessage($chat_id, $full_video->result->title);
                $formats = $full_video->result->formats;
                $formats_mp4 = [];
                $option = array();
                $formatLists = [];
                foreach ($formats as $format){
                    if($format->ext == "mp4"){
                        if(!in_array($format->format_note, $formatLists)) {
                            array_push($formats_mp4, $format);
                            array_push($formatLists, $format->format_note);
                        }
                    }
                }

//            sendMessage($chat_id, json_encode($formats_mp4));

                for ($i = 0; $i < count($formats_mp4); $i = ($i+2)){
                    if(($i+1) < count($formats_mp4)){
                        array_push($option, array($telegram->buildInlineKeyBoardButton($formats_mp4[$i]->format_note, '', '/' . $formats_mp4[$i]->id),$telegram->buildInlineKeyBoardButton($formats_mp4[$i+1]->format_note, '', '/' . $formats_mp4[$i+1]->id)));
                    } else {
                        array_push($option, array($telegram->buildInlineKeyBoardButton($formats_mp4[$i]->format_note, '', '/' . $formats_mp4[$i]->id)));
                    }
                }


                $keyb = $telegram->buildInlineKeyBoard($option);
                sendMessage($chat_id, "لینک های دانلود شما:", $keyb);
            }
        }
    }
}


function request_curl($url){
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
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

    return $result;
}