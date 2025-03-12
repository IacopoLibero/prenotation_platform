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
      <form action="/login/script_registrazione.php" method="POST">
          <h1>Create Account</h1>
          <input type="text" name="Username" placeholder="Username" required />
          <input type="email" name="Email" placeholder="Email" required />
          <input type="password" name="Password" placeholder="Password" required />
          
          <div class="checkbox-container">
            <input type="checkbox" name="professore" id="professore"/>
            <label for="professore">I'm a professor</label>
          </div>
          
          <button type="submit">Sign up</button>
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
        <!-- Mobile switch buttons for sign-up page -->
        <div class="mobile-switch">
          <button type="button" class="to-signin">Sign In</button>
          <button type="button" class="to-signup active">Sign Up</button>
        </div>
      </div>
      <div class="form-container sign-in">
        <form action="./login/login.php" method="POST">
          <h1>Sign In</h1>
          <input type="email" name="Email" placeholder="Email" />
          <input type="password" name="Password" placeholder="Password" />
          <a href="#">Forget Your Password?</a>
          <button type="submit">Sign in</button>
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
        <!-- Mobile switch buttons for sign-in page -->
        <div class="mobile-switch">
          <button type="button" class="to-signin active">Sign In</button>
          <button type="button" class="to-signup">Sign Up</button>
        </div>
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
