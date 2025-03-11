<?php
    session_start();
    if(!isset($_SESSION['user'])){
        header('Location: ../index.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
    ciao <?php echo $_SESSION['user']; ?>, benvenuto nella tua home
    <a href="login/logout.php">Logout</a>
</body>
</html>