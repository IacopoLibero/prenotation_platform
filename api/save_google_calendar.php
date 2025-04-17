<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dei dati
$teacher_email = $_SESSION['email'];

// Recupero degli array di calendari
$calendar_links = $_POST['calendar_links'] ?? [];
$calendar_ids = $_POST['calendar_ids'] ?? [];
$calendar_names = $_POST['calendar_names'] ?? [];
$ore_prima_evento = $_POST['ore_prima_evento'] ?? [];
$ore_dopo_evento = $_POST['ore_dopo_evento'] ?? [];

// Recupero delle preferenze
$weekend = isset($_POST['weekend']) ? intval($_POST['weekend']) : 0;
$mattina = isset($_POST['mattina']) ? intval($_POST['mattina']) : 1;
$pomeriggio = isset($_POST['pomeriggio']) ? intval($_POST['pomeriggio']) : 1;
$ora_inizio_mattina = $_POST['ora_inizio_mattina'] ?? '08:00';
$ora_fine_mattina = $_POST['ora_fine_mattina'] ?? '13:00';
$ora_inizio_pomeriggio = $_POST['ora_inizio_pomeriggio'] ?? '14:00';
$ora_fine_pomeriggio = $_POST['ora_fine_pomeriggio'] ?? '19:00';

// Validazione base
if (empty($calendar_links)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nessun calendario fornito']);
    exit;
}

// Array per tenere traccia dei calendari inseriti con successo
$successful_calendars = [];
$failed_calendars = [];

// Iniziamo una transazione per garantire che tutte le operazioni database vengano eseguite insieme
$conn->begin_transaction();

try {
    // Prima rimuoviamo tutti i calendari esistenti che non sono più presenti
    $existing_calendar_ids = [];
    foreach ($calendar_ids as $index => $cal_id) {
        if ($cal_id > 0) {
            $existing_calendar_ids[] = intval($cal_id);
        }
    }
    
    // Se ci sono ID esistenti, li escludiamo dalla cancellazione
    $delete_condition = empty($existing_calendar_ids) ? "" : " AND id NOT IN (" . implode(',', $existing_calendar_ids) . ")";
    $delete_query = "DELETE FROM Calendari_Professori WHERE teacher_email = ?" . $delete_condition;
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();

    // Per ogni calendario fornito
    foreach ($calendar_links as $index => $calendar_link) {
        if (empty($calendar_link)) continue;
        
        $calendar_id_value = isset($calendar_ids[$index]) ? intval($calendar_ids[$index]) : 0;
        $calendar_name = isset($calendar_names[$index]) ? $calendar_names[$index] : 'Calendario';
        $ore_prima = isset($ore_prima_evento[$index]) ? floatval($ore_prima_evento[$index]) : 0;
        $ore_dopo = isset($ore_dopo_evento[$index]) ? floatval($ore_dopo_evento[$index]) : 0;
        
        // Validazione del link
        if (!filter_var($calendar_link, FILTER_VALIDATE_URL)) {
            $failed_calendars[] = ["link" => $calendar_link, "reason" => "URL non valido"];
            continue;
        }
        
        // Verifica che il link sia accessibile prima di salvarlo
        $accessible = false;

        // Prova con file_get_contents
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $test_content = @file_get_contents($calendar_link, false, $ctx);

        // Se fallisce, prova con cURL
        if ($test_content === false && function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $calendar_link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $test_content = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($test_content !== false && $http_code >= 200 && $http_code < 300) {
                $accessible = true;
            }
        } else if ($test_content !== false) {
            $accessible = true;
        }

        // Verifica che il contenuto sembri essere un calendario iCal
        if ($accessible && strpos($test_content, 'BEGIN:VCALENDAR') === false) {
            $accessible = false;
        }

        if (!$accessible) {
            $failed_calendars[] = ["link" => $calendar_link, "reason" => "Calendario non accessibile o non nel formato iCal"];
            continue;
        }

        // Estrazione dell'ID del calendario dal link
        $google_calendar_id = null;
        if (preg_match('/\/([a-zA-Z0-9%@._-]+)\/public\/basic\.ics$/', $calendar_link, $matches)) {
            $google_calendar_id = urldecode($matches[1]);
        } else {
            // Formato più permissivo per supportare più varianti di URL iCal
            $parts = parse_url($calendar_link);
            $path_parts = explode('/', $parts['path']);
            foreach ($path_parts as $part) {
                if (strpos($part, '@') !== false || (strlen($part) > 10 && preg_match('/[a-z0-9]/i', $part))) {
                    $google_calendar_id = $part;
                    break;
                }
            }
            
            if (!$google_calendar_id) {
                $failed_calendars[] = ["link" => $calendar_link, "reason" => "Formato del link calendario non valido"];
                continue;
            }
        }
        
        // Inserisci o aggiorna il calendario
        if ($calendar_id_value > 0) {
            // Aggiorna calendario esistente
            $update_query = "UPDATE Calendari_Professori 
                            SET google_calendar_link = ?, google_calendar_id = ?, nome_calendario = ?, ore_prima_evento = ?, ore_dopo_evento = ? 
                            WHERE id = ? AND teacher_email = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssddis", $calendar_link, $google_calendar_id, $calendar_name, $ore_prima, $ore_dopo, $calendar_id_value, $teacher_email);
        } else {
            // Inserisci nuovo calendario
            $insert_query = "INSERT INTO Calendari_Professori (teacher_email, google_calendar_link, google_calendar_id, nome_calendario, ore_prima_evento, ore_dopo_evento) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssssdd", $teacher_email, $calendar_link, $google_calendar_id, $calendar_name, $ore_prima, $ore_dopo);
        }
        
        if ($stmt->execute()) {
            $successful_calendars[] = $calendar_link;
        } else {
            $failed_calendars[] = ["link" => $calendar_link, "reason" => "Errore database: " . $stmt->error];
        }
    }

    // Salvataggio delle preferenze di disponibilità
    $check_query = "SELECT id FROM Preferenze_Disponibilita WHERE teacher_email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Aggiorna le preferenze esistenti
        $update_query = "UPDATE Preferenze_Disponibilita 
                        SET weekend = ?, mattina = ?, pomeriggio = ?, 
                            ora_inizio_mattina = ?, ora_fine_mattina = ?,
                            ora_inizio_pomeriggio = ?, ora_fine_pomeriggio = ?
                        WHERE teacher_email = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("iiisssss", $weekend, $mattina, $pomeriggio, 
                        $ora_inizio_mattina, $ora_fine_mattina, 
                        $ora_inizio_pomeriggio, $ora_fine_pomeriggio,
                        $teacher_email);
    } else {
        // Inserisce nuove preferenze
        $insert_query = "INSERT INTO Preferenze_Disponibilita 
                        (teacher_email, weekend, mattina, pomeriggio, 
                        ora_inizio_mattina, ora_fine_mattina, 
                        ora_inizio_pomeriggio, ora_fine_pomeriggio)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("siiissss", $teacher_email, $weekend, $mattina, $pomeriggio, 
                        $ora_inizio_mattina, $ora_fine_mattina, 
                        $ora_inizio_pomeriggio, $ora_fine_pomeriggio);
    }

    if (!$stmt->execute()) {
        throw new Exception('Errore nel salvare le preferenze: ' . $stmt->error);
    }

    // Commit della transazione
    $conn->commit();

    // Preparazione della risposta
    $response = ['success' => true];
    
    if (!empty($failed_calendars)) {
        $response['message'] = 'Alcuni calendari non sono stati salvati. Verificali e riprova.';
        $response['failed_calendars'] = $failed_calendars;
    } else {
        $response['message'] = 'Preferenze salvate con successo';
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    // Se c'è un errore, facciamo rollback della transazione
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}

$conn->close();
?>
