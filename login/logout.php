<?php
    session_start();
    $_SESSION['status']="Logout effettuato con successo";
    $_SESSION['log'] = false;
    header("Location: ..\index.php")
?>