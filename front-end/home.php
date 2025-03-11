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
    <link rel="stylesheet" href="../styles/main.css">
    <title>Home</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="welcome">
            <h1>Benvenuto, <?php echo $_SESSION['user']; ?>!</h1>
            <p>Gestisci le tue lezioni in modo semplice e veloce.</p>
        </section>
    </main>
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>