<?php
// VERSIONE BASE PER TEST - auth-handler.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisci OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Per ora, risposte simulate per testare il frontend
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'check_status':
        // Simula utente non loggato per test
        echo json_encode([
            'logged_in' => false,
            'user' => null,
            'credits' => 0
        ]);
        break;
        
    case 'login':
        // Simula login di successo per test
        echo json_encode([
            'success' => true,
            'message' => 'Login simulato per test',
            'user' => [
                'id' => 999,
                'email' => $_POST['email'] ?? 'test@test.com',
                'name' => 'Utente Test',
                'credits' => 10
            ]
        ]);
        break;
        
    case 'register':
        // Simula registrazione di successo per test
        echo json_encode([
            'success' => true,
            'message' => 'Registrazione simulata per test',
            'user' => [
                'id' => 999,
                'email' => $_POST['email'] ?? 'test@test.com',
                'name' => $_POST['name'] ?? 'Utente Test',
                'credits' => 0
            ]
        ]);
        break;
        
    case 'logout':
        echo json_encode([
            'success' => true,
            'message' => 'Logout simulato'
        ]);
        break;
        
    case 'check_credits':
        echo json_encode([
            'logged_in' => false,
            'user_id' => null,
            'available' => 0
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Azione non valida'
        ]);
}
?>