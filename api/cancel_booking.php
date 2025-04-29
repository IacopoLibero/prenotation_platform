<?php
session_start();
require_once '../connessione.php';
require_once '../google_calendar/calendar_functions.php';

// Controllo se l'utente è loggato e sia studente
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dei dati
$lesson_id = $_POST['lesson_id'];
$student_email = $_SESSION['email'];

// Validazione
if (empty($lesson_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID lezione mancante']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Fetch lesson details including teacher email and Google Calendar event IDs
    $lesson_query = "SELECT l.id, l.teacher_email, l.teacher_event_id, l.student_event_id, p.username as teacher_name
                    FROM Lezioni l 
                    LEFT JOIN Professori p ON l.teacher_email = p.email
                    WHERE l.id = ? AND l.student_email = ? AND l.stato = 'prenotata'";
    
    $stmt = $conn->prepare($lesson_query);
    $stmt->bind_param("is", $lesson_id, $student_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Lezione non trovata o non prenotata da te');
    }
    
    $lesson = $result->fetch_assoc();
    $teacher_email = $lesson['teacher_email'];
    $teacher_event_id = $lesson['teacher_event_id'];
    $student_event_id = $lesson['student_event_id'];
    
    // Delete Google Calendar events if they exist
    $calendar_events_deleted = ['teacher' => false, 'student' => false];
    
    // Delete event from teacher's calendar
    if ($teacher_event_id && hasValidOAuthTokens($teacher_email, 'professore')) {
        $result = deleteCalendarEvent($teacher_email, 'professore', $teacher_event_id);
        $calendar_events_deleted['teacher'] = $result['success'];
    }
    
    // Delete event from student's calendar
    if ($student_event_id && hasValidOAuthTokens($student_email, 'studente')) {
        $result = deleteCalendarEvent($student_email, 'studente', $student_event_id);
        $calendar_events_deleted['student'] = $result['success'];
    }
    
    // Annulla la prenotazione e rimuove i riferimenti agli eventi di Google Calendar
    $update_query = "UPDATE Lezioni 
                    SET stato = 'disponibile', 
                        student_email = NULL, 
                        teacher_event_id = NULL, 
                        student_event_id = NULL 
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $lesson_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Errore nella cancellazione della prenotazione: ' . $update_stmt->error);
    }
    
    // Commit the transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Prenotazione cancellata con successo',
        'calendar_events_deleted' => $calendar_events_deleted
    ]);
    
} catch (Exception $e) {
    // Rollback in case of error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
