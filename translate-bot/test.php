<?php
require_once 'config/db.php';

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://one-api.ir/translate/?token=277542%3A65be30c3eb3ce&action=targoman&lang=fa2en&q=%D8%B3%D9%84%D8%A7%D9%85%20%D8%AE%D9%88%D8%A8%DB%8C%D8%9F',
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
//var_dump($result);
$translated_text = translateRequestApi("277542:65be30c3eb3ce", "faraazin", "fa_en", "سلام");
var_dump($translated_text->result->base[0][1]);
function translateRequestApi($token, $action, $lang, $query){
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://one-api.ir/translate/?token=$token&action=$action&lang=$lang&q=$query",
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

//    var_dump($result);
return $result;
//    if($result->status == 200) return $result->result;
//    else var_dump($result); die();
}
