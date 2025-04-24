<?php
// Abilito la visualizzazione degli errori per il debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Debug: verifica che la libreria sia caricata correttamente
if (!class_exists('Google_Client')) {
    die('Google_Client class non trovata. Controlla l\'installazione della libreria Google API.');
}

require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/token_storage.php';

// Debug: stampa le variabili di sessione
echo "<pre>DEBUG - Variabili di sessione:\n";
print_r($_SESSION);
echo "</pre>";

// Verifica se l'utente è autenticato nel sito
if (!isset($_SESSION['user'])) {
        header('Location: ../index.php');
    exit;
}

// Utilizza le chiavi di sessione corrette
$userEmail = $_SESSION['email'];
$userType = $_SESSION['tipo'];

echo "<p>DEBUG - Email: $userEmail, Tipo: $userType</p>";

// Converti il tipo utente per il nostro sistema (se necessario)
if ($userType === 'teacher') {
    $userType = 'professore';
} elseif ($userType === 'student') {
    $userType = 'studente';
}

echo "<p>DEBUG - Tipo convertito: $userType</p>";

// Verifica se l'utente ha già autorizzato Google Calendar
$hasTokens = hasValidOAuthTokens($userEmail, $userType);
echo "<p>DEBUG - hasValidOAuthTokens result: " . ($hasTokens ? "true" : "false") . "</p>";

if ($hasTokens) {
    // L'utente ha già autorizzato, reindirizza alla pagina di gestione
    echo "<p>DEBUG - Utente già autorizzato, redirect a setup</p>";
    header('Location: ../front-end/google_calendar_setup.php?status=already_authorized');
    exit;
}

// Crea il client Google
try {
    $client = createGoogleClient();
    echo "<p>DEBUG - Client Google creato</p>";
    
    // Debug delle credenziali
    echo "<p>DEBUG - Client ID: " . substr(getenv('GOOGLE_CLIENT_ID'), 0, 10) . "...</p>";
    echo "<p>DEBUG - Redirect URI: " . getenv('GOOGLE_REDIRECT_URI') . "</p>";
    
    // Genera un token anti-CSRF per la sicurezza
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    // Memorizza informazioni sulla sessione per l'OAuth callback
    $_SESSION['oauth_user_email'] = $userEmail;
    $_SESSION['oauth_user_type'] = $userType;
    
    // Genera l'URL di autorizzazione e reindirizza
    $client->setState($state);
    $authUrl = $client->createAuthUrl();
    echo "<p>DEBUG - Auth URL generato: " . htmlspecialchars($authUrl) . "</p>";
    echo "<p>DEBUG - Redirect in corso...</p>";
    
    header('Location: ' . $authUrl);
    exit;
} catch (Exception $e) {
    die("DEBUG - Errore durante la creazione del client Google: " . $e->getMessage());
}
?>