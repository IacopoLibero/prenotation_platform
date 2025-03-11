<?php
    session_start();
    include('../connessione.php');

    $username =htmlspecialchars( $_POST['Username']);
    $email = htmlspecialchars($_POST['Email']);
    $password = htmlspecialchars($_POST['Password']);
    
    $professore= $_POST['professore'];
    if(isset($professore) && $professore == "on")
    {
        $professore=TRUE;
    }
    else
    {
        $professore=FALSE;
    }
    
    $password = hash("sha256", $password);

    // Controllo se l'Utente è già registrato
    $checkQuery = "SELECT * FROM `Users` WHERE email = '$email'";
    $result = $conn->query($checkQuery);

    // Se non è registrato lo inserisco nel database, altrimenti mostro un errore
    if($result->num_rows == 0)
    {
        $query = "INSERT INTO `Users`(`username`, `email`, `password`, `is_teacher`) VALUES ('$username','$email','$password',$professore)";
        try {
            if ($conn->query($query)) 
            {
                $_SESSION['status_reg'] = "Registrazione effettuata";
                header("Location: ..\index.php");
            } 
            else 
            {
                $_SESSION['status_reg'] = "Errore nella registrazione";
                header("Location: ..\index.php");
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION['status_reg'] = "Errore nella registrazione: " . $e->getMessage();
            header("Location: ..\index.php");
        }
    }
    else 
    {
        $_SESSION['status_reg'] = "Utente già registrato";
        header("Location: ../index.php");
    }
?>