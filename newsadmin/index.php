<?php
include_once "config/db.php";

$sql = "SELECT * FROM users";
$stmt = $conn->query($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>News Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .my-table{
            margin: 0 auto;
            width: 1150px;
        }
    </style>
</head>
<body>
    <div class="row">
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container">
                <a class="navbar-brand" href="#">Navbar</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="btn btn-primary" aria-current="page" href="index.php">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="addnews.php">Add News</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
    <br>
    <div class="row">
       <div class="my-table">
           <table class="table">
               <thead>
               <tr>
                   <th scope="col">#</th>
                   <th scope="col">Chat_id</th>
                   <th scope="col">Name</th>
               </tr>
               </thead>
               <tbody>
                <?php $item = 1; foreach ($rows as $row): ?>

               <tr>
                   <th scope="row"><?= $item++ ?></th>
                   <td><?= $row->chat_id ?></td>
                   <td><?= $row->name ?></td>
               </tr>

               <?php endforeach; ?>
               </tbody>
           </table>
       </div>
    </div>
</body>
</html>
