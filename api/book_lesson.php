<?php
session_start();
require_once '../connessione.php';
require_once '../google_calendar/calendar_functions.php';

// Verify user is logged in as a student
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato. Devi essere uno studente per prenotare lezioni.']);
    exit;
}

// Check if all required data is provided
$teacher_email = $_POST['teacher_email'] ?? null;
$date = $_POST['date'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

if (!$teacher_email || !$date || !$start_time || !$end_time) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dati mancanti per la prenotazione.']);
    exit;
}

// Format the full datetime values for database
$start_datetime = $date . ' ' . $start_time . ':00';
$end_datetime = $date . ' ' . $end_time . ':00';
$student_email = $_SESSION['email'];

try {
    // Start transaction to ensure data consistency
    $conn->begin_transaction();
    
    // Step 1: Check if the slot is available (not already booked)
    $check_query = "SELECT id, stato, student_email FROM Lezioni 
                   WHERE teacher_email = ? 
                   AND start_time = ? 
                   AND end_time = ?";
                   
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("sss", $teacher_email, $start_datetime, $end_datetime);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // Get student name for calendar event
    $student_query = "SELECT username FROM Studenti WHERE email = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("s", $student_email);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student_name = $student_result->fetch_assoc()['username'] ?? 'Studente';
    
    // Get teacher name for calendar event
    $teacher_query = "SELECT username FROM Professori WHERE email = ?";
    $teacher_stmt = $conn->prepare($teacher_query);
    $teacher_stmt->bind_param("s", $teacher_email);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher_name = $teacher_result->fetch_assoc()['username'] ?? 'Insegnante';
    
    $lesson_id = 0;
    
    if ($check_result->num_rows === 0) {
        // Lesson doesn't exist, check if there's a availability slot
        $avail_query = "SELECT * FROM Disponibilita 
                       WHERE teacher_email = ? 
                       AND giorno_settimana = LOWER(DATE_FORMAT(?, '%W'))
                       AND ora_inizio = TIME_FORMAT(?, '%H:%i')
                       AND ora_fine = TIME_FORMAT(?, '%H:%i')";
                       
        $avail_stmt = $conn->prepare($avail_query);
        $day_of_week = date('l', strtotime($date)); // Get day of week in English
        
        // Convert English day to Italian
        $days_map = [
            'Monday' => 'lunedi',
            'Tuesday' => 'martedi',
            'Wednesday' => 'mercoledi',
            'Thursday' => 'giovedi',
            'Friday' => 'venerdi',
            'Saturday' => 'sabato',
            'Sunday' => 'domenica'
        ];
        
        $italian_day = $days_map[$day_of_week];
        $avail_stmt->bind_param("ssss", $teacher_email, $italian_day, $start_time, $end_time);
        $avail_stmt->execute();
        $avail_result = $avail_stmt->get_result();
        
        if ($avail_result->num_rows === 0) {
            // No availability slot exists
            throw new Exception('Questo slot orario non è disponibile per la prenotazione.');
        }
        
        // Create a new lesson entry
        $title = "Prenotazione del " . date('d/m/Y', strtotime($date)) . " dalle $start_time alle $end_time";
        $insert_query = "INSERT INTO Lezioni (teacher_email, student_email, titolo, start_time, end_time, stato)
                        VALUES (?, ?, ?, ?, ?, 'prenotata')";
                        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sssss", $teacher_email, $student_email, $title, $start_datetime, $end_datetime);
        
        if (!$insert_stmt->execute()) {
            throw new Exception('Errore durante la prenotazione della lezione: ' . $insert_stmt->error);
        }
        
        $lesson_id = $conn->insert_id;
        
    } else {
        // Lesson exists, check if it's available
        $lesson = $check_result->fetch_assoc();
        
        if ($lesson['stato'] !== 'disponibile') {
            // Already booked - check if booked by this student
            if ($lesson['student_email'] === $student_email) {
                throw new Exception('Hai già prenotato questa lezione.');
            } else {
                throw new Exception('Questa lezione è già stata prenotata da un altro studente.');
            }
        }
        
        // Update the existing lesson
        $update_query = "UPDATE Lezioni 
                        SET student_email = ?, 
                            stato = 'prenotata' 
                        WHERE id = ?";
                        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $student_email, $lesson['id']);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Errore durante la prenotazione della lezione: ' . $update_stmt->error);
        }
        
        $lesson_id = $lesson['id'];
    }
    
    // Step 2: Create Google Calendar events if both users have connected their calendars
    $teacherEventId = null;
    $studentEventId = null;
    
    // Check if the teacher has Google Calendar integration
    if (hasValidOAuthTokens($teacher_email, 'professore')) {
        // Create event in teacher's calendar
        $eventTitle = "Lezione con $student_name";
        $eventDescription = "Lezione prenotata tramite la piattaforma.\n\n";
        $eventDescription .= "Studente: $student_name ($student_email)\n";
        $eventDescription .= "Data: " . date('d/m/Y', strtotime($date)) . "\n";
        $eventDescription .= "Orario: $start_time - $end_time";
        
        $teacherEventResult = createCalendarEvent(
            $teacher_email,
            'professore',
            $eventTitle,
            $eventDescription,
            $start_datetime,
            $end_datetime,
            [$student_email]
        );
        
        if ($teacherEventResult['success']) {
            $teacherEventId = $teacherEventResult['event_id'];
        }
    }
    
    // Check if the student has Google Calendar integration
    if (hasValidOAuthTokens($student_email, 'studente')) {
        // Create event in student's calendar
        $eventTitle = "Lezione con $teacher_name";
        $eventDescription = "Lezione prenotata tramite la piattaforma.\n\n";
        $eventDescription .= "Insegnante: $teacher_name ($teacher_email)\n";
        $eventDescription .= "Data: " . date('d/m/Y', strtotime($date)) . "\n";
        $eventDescription .= "Orario: $start_time - $end_time";
        
        $studentEventResult = createCalendarEvent(
            $student_email,
            'studente',
            $eventTitle,
            $eventDescription,
            $start_datetime,
            $end_datetime,
            [$teacher_email]
        );
        
        if ($studentEventResult['success']) {
            $studentEventId = $studentEventResult['event_id'];
        }
    }
    
    // Step 3: Save the event IDs if any were created
    if ($teacherEventId || $studentEventId) {
        saveCalendarEventIds($lesson_id, $teacherEventId, $studentEventId, $conn);
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Send success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Lezione prenotata con successo!',
        'calendar_events' => [
            'teacher' => $teacherEventId ? true : false,
            'student' => $studentEventId ? true : false
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    
    // Send error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close the connection
$conn->close();
?>
