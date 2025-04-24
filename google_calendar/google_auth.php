<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/token_storage.php';

// Verifica se l'utente è autenticato nel sito
if (!isset($_SESSION['user_email']) || !isset($_SESSION['user_type'])) {
    header('Location: /login/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userEmail = $_SESSION['user_email'];
$userType = $_SESSION['user_type'];

// Converti il tipo utente per il nostro sistema (se necessario)
if ($userType === 'teacher') {
    $userType = 'professore';
} elseif ($userType === 'student') {
    $userType = 'studente';
}

// Verifica se l'utente ha già autorizzato Google Calendar
if (hasValidOAuthTokens($userEmail, $userType)) {
    // L'utente ha già autorizzato, reindirizza alla pagina di gestione
    header('Location: /front-end/google_calendar_setup.php?status=already_authorized');
    exit;
}

// Crea il client Google
$client = createGoogleClient();

// Genera un token anti-CSRF per la sicurezza
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Memorizza informazioni sulla sessione per l'OAuth callback
$_SESSION['oauth_user_email'] = $userEmail;
$_SESSION['oauth_user_type'] = $userType;

// Genera l'URL di autorizzazione e reindirizza
$client->setState($state);
$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
?>