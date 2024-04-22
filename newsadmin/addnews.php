<?php
include_once "config/db.php";
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


// get All users
$sql = "SELECT * FROM users";
$stmt = $conn->query($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_OBJ);

if(isset($_POST["add_new"])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // send for all bot users
    foreach ($rows as $row){
        $text = $title . "\n\n" . $description;
        sendMessage($row->chat_id, $text);
    }

    $sql = "INSERT INTO news SET title=?, description=?";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $title);
    $stmt->bindValue(2, $description);
    $stmt->execute();
    echo "<div class='alert alert-info'> Add new post successfully! </div>";
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>News Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>

    <style>
        .my-form{
            margin: 0 auto;
            width: 1150px;
        }
        .form-item{
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="row">
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container">
                <a class="navbar-brand" href="#">Navbar</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
                        aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="index.php">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="addnews.php">Add News</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
    <br>
    <div class="row">
        <form method="post" class="my-form">
            <div class="form-item">
                <input type="text" name="title" placeholder="Title:" class="form-control">
            </div>

            <div class="form-item">
                <textarea name="description" rows="10" class="form-control"></textarea>
            </div>

            <div class="form-item">
                <input type="submit" class="btn btn-success" value="Add" name="add_new">
            </div>
        </form>
    </div>
</body>
</html>
