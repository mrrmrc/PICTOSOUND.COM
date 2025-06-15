<?php
require_once('../../../wp-load.php');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function get_user_creations($user_id, $limit = 20, $offset = 0) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE user_id = %d 
         ORDER BY created_at DESC 
         LIMIT %d OFFSET %d",
        $user_id, $limit, $offset
    ));
    
    return $results;
}

function update_play_count($creation_id, $user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    return $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} 
         SET plays_count = plays_count + 1 
         WHERE id = %d AND user_id = %d",
        $creation_id, $user_id
    ));
}

function toggle_favorite($creation_id, $user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    return $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} 
         SET is_favorite = NOT is_favorite 
         WHERE id = %d AND user_id = %d",
        $creation_id, $user_id
    ));
}

// Verifica login
if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login richiesto']);
    exit;
}

$current_user = wp_get_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'get_creations':
            $limit = intval($input['limit'] ?? 20);
            $offset = intval($input['offset'] ?? 0);
            $creations = get_user_creations($current_user->ID, $limit, $offset);
            echo json_encode(['success' => true, 'creations' => $creations]);
            break;
            
        case 'play':
            $creation_id = intval($input['creation_id']);
            update_play_count($creation_id, $current_user->ID);
            echo json_encode(['success' => true]);
            break;
            
        case 'toggle_favorite':
            $creation_id = intval($input['creation_id']);
            toggle_favorite($creation_id, $current_user->ID);
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Azione non riconosciuta']);
    }
}
?>