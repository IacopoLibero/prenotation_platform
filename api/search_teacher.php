<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dei dati di ricerca
$search = '%' . trim($_GET['search']) . '%';

// Cerca professori per nome utente o email
$query = "SELECT username, email, materie, bio FROM Professori 
          WHERE username LIKE ? OR email LIKE ? OR materie LIKE ?
          ORDER BY username ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'teachers' => $teachers]);

$stmt->close();
$conn->close();
?>
