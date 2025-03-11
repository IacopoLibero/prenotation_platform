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
    <title>Account Utente</title>
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
        <section class="account">
            <h1>Il Mio Account</h1>
            <form action="../update_account.php" method="POST">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo $_SESSION['user']; ?>" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>
                
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
                
                <button type="submit">Aggiorna</button>
            </form>
        </section>
    </main>
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>
