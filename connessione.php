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
    
    mysqli_report(MYSQLI_REPORT_OFF);  // Serve a disabilitare le eccezioni nelle nuove versioni di PHP

    // Creazione della connessione al database
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Controllo della connessione
    if ($conn->connect_error) {
        echo("Connection failed: " . $conn->connect_error);
    }
?>