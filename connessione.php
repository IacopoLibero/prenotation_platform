<?php
    // Definizione delle variabili per la connessione al database
    $servername = "localhost";
    $username = "superipetizioni";
    $password = "";
    $dbname = "my_superipetizioni";

    /*
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "mercatino";
    */
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);  // Abilita le eccezioni per il debug

    // Creazione della connessione al database
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
    } catch (mysqli_sql_exception $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Controllo della connessione
    if ($conn->connect_error) {
        echo("Connection failed: " . $conn->connect_error);
    }
?>