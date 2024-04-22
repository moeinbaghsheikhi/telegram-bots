<?php
require_once 'config/db.php';

$query = "SELECT * FROM chats WHERE status=? AND (user_1=? OR user_2=?)";
$stmt = $conn->prepare($query);
$stmt->bindValue(1, "doing");
$stmt->bindValue(2, 5875296782);
$stmt->bindValue(3, 5875296782);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
var_dump($result);

//$curl = curl_init();
//
//curl_setopt_array($curl, array(
//    CURLOPT_URL => "https://one-api.ir/DigitalCurrency/?token=277542:65be30c3eb3ce",
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_ENCODING => '',
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 0,
//    CURLOPT_FOLLOWLOCATION => true,
//    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//    CURLOPT_CUSTOMREQUEST => 'GET',
//));
//
//$response = curl_exec($curl);
//
//curl_close($curl);
//$result = (json_decode($response));
//
//var_dump($result->result[0]); die();