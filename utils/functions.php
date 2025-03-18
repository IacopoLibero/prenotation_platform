<?php
/**
 * Funzioni di utilità per il sistema di prenotazione lezioni
 */

/**
 * Formatta il tempo in ore e minuti
 * 
 * @param int $minutes Il numero totale di minuti
 * @return string Il tempo formattato come "Xh Ym"
 */
function formatTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . $mins . 'm';
}

/**
 * Verifica se l'utente è autenticato e reindirizza se necessario
 * 
 * @param string|null $tipo Il tipo di utente richiesto (studente/professore)
 * @return bool True se l'utente è autenticato, altrimenti reindirizza
 */
function check_auth($tipo = null) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['user'])) {
        header('Location: ../index.php');
        exit;
    }
    
    if ($tipo !== null && $_SESSION['tipo'] !== $tipo) {
        header('Location: ../front-end/home.php');
        exit;
    }
    
    return true;
}

/**
 * Sanitizza l'input dell'utente
 * 
 * @param string $input L'input da sanitizzare
 * @return string L'input sanitizzato
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un messaggio di alert
 * 
 * @param string $message Il messaggio da mostrare
 * @param string $type Il tipo di messaggio (success, error, warning, info)
 * @return string Il codice HTML dell'alert
 */
function generateAlert($message, $type = 'info') {
    $class = '';
    switch ($type) {
        case 'success':
            $class = 'alert-success';
            break;
        case 'error':
            $class = 'alert-error';
            break;
        case 'warning':
            $class = 'alert-warning';
            break;
        default:
            $class = 'alert-info';
    }
    
    return '<div class="alert ' . $class . '">' . $message . '</div>';
}

/**
 * Traduce i giorni della settimana dal formato database al formato italiano
 * 
 * @param string $day Il giorno della settimana in formato database
 * @return string Il giorno tradotto
 */
function translateDay($day) {
    $days = [
        'lunedi' => 'Lunedì',
        'martedi' => 'Martedì',
        'mercoledi' => 'Mercoledì',
        'giovedi' => 'Giovedì',
        'venerdi' => 'Venerdì',
        'sabato' => 'Sabato',
        'domenica' => 'Domenica'
    ];
    
    return isset($days[$day]) ? $days[$day] : $day;
}

/**
 * Traduce lo stato di una lezione
 * 
 * @param string $status Lo stato della lezione
 * @return string Lo stato tradotto
 */
function translateStatus($status) {
    $statuses = [
        'disponibile' => 'Disponibile',
        'prenotata' => 'Prenotata',
        'completata' => 'Completata',
        'cancellata' => 'Cancellata'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}
?>
