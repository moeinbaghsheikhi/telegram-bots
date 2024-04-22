<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verify Transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body{
            text-align: right;
            direction: rtl;
        }
        .alert{
            width: 800px;
            margin: 0 auto;
            margin-top: 150px;
        }
    </style>
</head>
<body>
    <?php
    require_once 'config/db.php';
    include 'Telegram.php';

    $telegram = new Telegram('6817903574:AAEafofh8hFsEX5frtFPMHubAZu0dZ_Fk0M');

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


    $success = $_GET['success'];
        $trackId = $_GET['trackId'];
        $status  = $_GET['status'];
        $zibal_merchant = "zibal";


        if(isset($_GET["trackId"])){
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://gateway.zibal.ir/v1/verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
    "merchant": "'.$zibal_merchant.'",
    "trackId": "'.$trackId.'"
}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response);

            curl_close($curl);

            $order = query("SELECT" , "orders", false, [["key" => "trackId", "condition" => "=", "value" => $trackId]]);
            $user = query("SELECT" , "users", false, [["key" => "id", "condition" => "=", "value" => $order->user_id]]);
            $chat_id = $user->chat_id;

            if($response->result == 100){
                query("UPDATE", "orders", ["status" => "payed"], [["key" => "trackId", "condition" => "=" , "value" => $trackId]]);
                echo "<p class='alert alert-success'> پرداخت شما با موفقیت انجام شد </p>";
                sendMessage($chat_id, "پرداخت شما با موفقیت انجام شد ✅");
            }
            else if ($response->result == 201 ) {
                echo "<p class='alert alert-warning'> پرداخت تکراری هست! </p>";
                sendMessage($chat_id, "پرداخت تکراری هست ❌");
            }
            else {
                echo "<p class='alert alert-danger'> پرداخت ناموفق! </p>";
                sendMessage($chat_id, "پرداخت ناموفق ❌");
            }
            sendMainKeyboardMenu($chat_id);
        }
    ?>
</body>
</html>