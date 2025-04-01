<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
    <!-- Add cache busting parameter to ensure latest CSS is loaded -->
    <link rel="stylesheet" href="./login/style_login.css?v=<?php echo time(); ?>" />
    <title>Login</title>
    <script type="text/javascript">
      var _iub = _iub || [];
      _iub.csConfiguration = {"askConsentAtCookiePolicyUpdate":true,"countryDetection":true,"enableFadp":true,"enableLgpd":true,"enableTcf":true,"enableUspr":true,"floatingPreferencesButtonDisplay":"anchored-bottom-right","googleAdditionalConsentMode":true,"lgpdAppliesGlobally":false,"perPurposeConsent":true,"preferenceCookie":{"expireAfter":180},"siteId":3962138,"storage":{"useSiteId":true},"tcfPurposes":{"2":"consent_only","7":"consent_only","8":"consent_only","9":"consent_only","10":"consent_only","11":"consent_only"},"usPreferencesWidgetDisplay":"bottom-left","whitelabel":false,"cookiePolicyId":56978183,"lang":"it","floatingPreferencesButtonCaption":true,"banner":{"acceptButtonCaptionColor":"#FFFFFF","acceptButtonColor":"#0073CE","acceptButtonDisplay":true,"backgroundColor":"#FFFFFF","closeButtonDisplay":false,"customizeButtonCaptionColor":"#4D4D4D","customizeButtonColor":"#DADADA","customizeButtonDisplay":true,"explicitWithdrawal":true,"listPurposes":true,"ownerName":"superipetizioni.altervista.org","position":"float-bottom-center","rejectButtonCaptionColor":"#FFFFFF","rejectButtonColor":"#0073CE","rejectButtonDisplay":true,"showPurposesToggles":true,"showTotalNumberOfProviders":true,"textColor":"#000000"}};
      </script>
      <script type="text/javascript" src="https://cs.iubenda.com/autoblocking/3962138.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/tcf/stub-v2.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/tcf/safe-tcf-v2.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/gpp/stub.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/iubenda_cs.js" charset="UTF-8" async>
    </script>
  </head>

  <body>
    <div class="container" id="container">
      <!-- Sign-up form and content -->
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
          
          <!-- Status messages -->
          <?php
            session_start();
            if(isset($_SESSION['status_reg'])) {
              echo "<br>";
              if($_SESSION['status_reg'] == "Registrazione effettuata"||$_SESSION['status']=="Logout effettuato con successo") {
                echo "<label style='color:green;'>".$_SESSION['status_reg']."</label>";
              }
              else if($_SESSION['status_reg']=="Errore nella registrazione"||$_SESSION['status_reg']=="Utente già registrato") {
                echo "<label style='color:red;'>".$_SESSION['status_reg']."</label>";
              }
            }          
          ?>
        </form>
        
        <!-- Text link instead of button -->
        <div class="mobile-switch">
          <a href="#" class="switch-link to-signin">Already registered? Sign in here</a>
        </div>
      </div>
      
      <!-- Sign-in form and content -->
      <div class="form-container sign-in">
        <form action="./login/login.php" method="POST">
          <h1>Sign In</h1>
          <input type="email" name="Email" placeholder="Email" required />
          <input type="password" name="Password" placeholder="Password" required />
          <a href="#">Forget Your Password?</a>
          <button type="submit">Sign in</button>
          
          <!-- Status messages -->
          <?php
            if(!isset($_SESSION)) { 
              session_start();
            }
            if(isset($_SESSION['status'])) {
              echo "<br>";
              echo "<label style='color:red;'>".$_SESSION['status']."</label>";
              session_unset();
            }          
          ?>
        </form>
        
        <!-- Text link instead of button -->
        <div class="mobile-switch">
          <a href="#" class="switch-link to-signup">Not registered yet? Create an account</a>
        </div>
      </div>
      
      <!-- Desktop toggle container -->
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

    <!-- Add cache busting parameter to ensure latest JS is loaded -->
    <script src="./login/script_login.js?v=<?php echo time(); ?>"></script>
    
    <!-- Remove reference to mobile_login.js since functionality is now in script_login.js -->
  </body>
</html>
