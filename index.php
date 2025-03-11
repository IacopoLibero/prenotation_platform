<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
    <link rel="stylesheet" href="./login/style_login.css" />
    <title>Login</title>
  </head>

  <body>
    <div class="container" id="container">
      <div class="form-container sign-up">
        <form action="./login/script_registrazione.php" method="POST">
          <h1>Create Account</h1>
          
          <input type="text" placeholder="Username" />
          <input type="email" placeholder="Email" />
          <input type="password" placeholder="Password" />
          <div class="checkbox-container">
            <input type="checkbox" id="professore"/>
            <label for="professore">I'm a professor</label>
          </div>
          
          <input class="belbottone" type="submit" value="Sign Up"/>
        </form>
        <?php
          session_start();
          if(isset($_SESSION['status_reg']))
          {
            echo "<br>";
            if($_SESSION['status_reg'] == "Registrazione effettuata"||$_SESSION['status']=="Logout effettuato con successo")
            {
              //verde
              echo "<label>".$_SESSION['status_reg']."</label>";
            }
            else if($_SESSION['status_reg']=="Errore nella registrazione"||$_SESSION['status_reg']=="Utente già registrato")
            {
              //rosso
              echo "<label>".$_SESSION['status_reg']."</label>";
            }
          }          
        ?>
      </div>
      <div class="form-container sign-in">
        <form action="./login/login.php" method="POST">
          <h1>Sign In</h1>
          <input type="email" placeholder="Email" />
          <input type="password" placeholder="Password" />
          <a href="#">Forget Your Password?</a>
          <input class="belbottone" type="submit" value="Sign In"/>
        </form>
        <?php
          session_start();
          if(isset($_SESSION['status']))
          {
            echo "<br>";
            echo "<label>".$_SESSION['status']."</label>";
            session_unset();
          }          
        ?>
      </div>
      <div class="toggle-container">
        <div class="toggle">
          <div class="toggle-panel toggle-left">
            <h1>Welcome Back!</h1>
            <p>To continue, please log in with your personal details.</p>
            <button class="hidden" id="login">Sign In</button>
          </div>
          <div class="toggle-panel toggle-right">
            <h1>Hello, Friend!</h1>
            <p>Create an account to access all the features of our platform.</p>
            <button class="hidden" id="register">Sign Up</button>
          </div>
        </div>
      </div>
    </div>

    <script src="./login/script_login.js"></script>
  </body>
</html>
