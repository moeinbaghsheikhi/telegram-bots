<?php

// تنظیمات
$token = "7072536960:AAFN3wiOxA2yPdPSQfGBOi9BGF5s7vt5yZw";
$wateemark_image_path = "watermark.png"; // مسیر تصویر واترمارک

// تابع ارسال پیام
function sendMessage($chat_id, $message) {
    global $token;
    $apiUrl = "https://api.telegram.org/bot$token/sendMessage";
    $postData = array('chat_id' => $chat_id, 'text' => $message);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
}

// تابع دریافت عکس و اعمال واترمارک
function applyWatermarkAndSend($chat_id, $photo_url) {
    global $wateemark_image_path, $token;
    $photo_path = "watermark" . time() . "-downloaded_photo.jpg";
    $marked_photo_path = "watermark" . time() . "-marked_photo.jpg";

    // دانلود عکس
    file_put_contents($photo_path, file_get_contents($photo_url));

    // اعمال واترمارک
    $watermark = imagecreatefrompng($wateemark_image_path);
    $source = imagecreatefromjpeg($photo_path);

    // محاسبه موقعیت واترمارک
    $watermark_width = imagesx($watermark);
    $watermark_height = imagesy($watermark);
    $source_width = imagesx($source);
    $source_height = imagesy($source);
    $offset_x = $source_width - $watermark_width - 10;
    $offset_y = $source_height - $watermark_height - 10;

    // افزودن واترمارک به عکس
    imagecopy($source, $watermark, $offset_x, $offset_y, 0, 0, $watermark_width, $watermark_height);

    // ذخیره عکس با واترمارک
    imagejpeg($source, $marked_photo_path);

    // ارسال عکس با واترمارک
    $apiUrl = "https://api.telegram.org/bot$token/sendPhoto";
    $postData = array('chat_id' => $chat_id, 'photo' => new CURLFile(realpath($marked_photo_path)));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    // حذف فایل‌های موقت
    unlink($photo_path);
    unlink($marked_photo_path);
}

// تابع پردازش دستور
function processCommand($chat_id, $command) {
    switch ($command) {
        case '/start':
            sendMessage($chat_id, "سلام! لطفا عکس خود را ارسال کنید.");
            break;
        default:
            sendMessage($chat_id, "دستور نامعتبر. لطفا /start را ارسال کنید.");
    }
}

// تابع پردازش عکس
function processPhoto($chat_id, $photo) {
    global $token;
    $photo_id = end($photo)["file_id"];

    // دریافت اطلاعات فایل
    $getFileUrl = "https://api.telegram.org/bot$token/getFile?file_id=$photo_id";
    $fileInfo = json_decode(file_get_contents($getFileUrl), true);

    // استخراج آدرس فایل
    $file_path = $fileInfo["result"]["file_path"];

    // ساخت آدرس دانلود
    $photo_url = "https://api.telegram.org/file/bot$token/$file_path";

    // اعمال واترمارک و ارسال
    applyWatermarkAndSend($chat_id, $photo_url);
}

// اجرای عملیات
$update = json_decode(file_get_contents('php://input'), TRUE);
$chat_id = $update["message"]["chat"]["id"];
$text = "";
if($update["message"]["text"]) $text = $update["message"]["text"];
$photo = "";
if($update["message"]["photo"]) $photo = $update["message"]["photo"];

if (!empty($text)) {
    processCommand($chat_id, $text);
} elseif (!empty($photo)) {
    processPhoto($chat_id, $photo);
}

?>
