<?php
// ⚡ API GALLERY PICTOSOUND COMPLETA - Tutto in un file
// ⚡ CARICA WORDPRESS 
require_once('../../../wp-load.php');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Logging
function write_log($message) {
    $log_dir = __DIR__ . '/logs/';
    if (!file_exists($log_dir)) mkdir($log_dir, 0775, true);
    file_put_contents($log_dir . 'gallery_log.txt', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// ================== CREAZIONE TABELLA SE NON ESISTE ==================
function ensure_gallery_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL DEFAULT '',
            description text DEFAULT NULL,
            original_image_path varchar(500) DEFAULT '',
            audio_file_path varchar(500) NOT NULL DEFAULT '',
            audio_file_url varchar(500) NOT NULL DEFAULT '',
            prompt_text text DEFAULT NULL,
            duration int(11) DEFAULT 40,
            audio_format varchar(10) DEFAULT 'mp3',
            file_size bigint(20) DEFAULT 0,
            image_analysis_data longtext DEFAULT NULL,
            detected_objects varchar(1000) DEFAULT '',
            detected_emotions varchar(500) DEFAULT '',
            musical_settings longtext DEFAULT NULL,
            share_token varchar(64) NOT NULL DEFAULT '',
            is_public tinyint(1) DEFAULT 0,
            is_favorite tinyint(1) DEFAULT 0,
            plays_count int(11) DEFAULT 0,
            downloads_count int(11) DEFAULT 0,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_played_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_share_token (share_token),
            KEY idx_user_id (user_id),
            KEY idx_user_created (user_id, created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        write_log("Tabella gallery creata automaticamente");
    }
}
ensure_gallery_table();

// ================== FUNZIONI GALLERY ==================
function get_user_creations($user_id, $limit = 20, $offset = 0, $search = '', $filter = 'all') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $where_conditions = ["user_id = %d"];
    $where_values = [$user_id];
    
    if (!empty($search)) {
        $where_conditions[] = "(title LIKE %s OR description LIKE %s OR prompt_text LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $where_values = array_merge($where_values, [$search_term, $search_term, $search_term]);
    }
    
    switch ($filter) {
        case 'favorites': $where_conditions[] = "is_favorite = 1"; break;
        case 'public': $where_conditions[] = "is_public = 1"; break;
        case 'recent': $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; break;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        array_merge($where_values, [$limit, $offset])
    ));
}

function update_play_count($creation_id, $user_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    if ($user_id) {
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET plays_count = plays_count + 1, last_played_at = NOW() WHERE id = %d AND user_id = %d",
            $creation_id, $user_id
        ));
    } else {
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET plays_count = plays_count + 1, last_played_at = NOW() WHERE id = %d",
            $creation_id
        ));
    }
}

function toggle_favorite($creation_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} SET is_favorite = NOT is_favorite WHERE id = %d AND user_id = %d",
        $creation_id, $user_id
    ));
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT is_favorite FROM {$table_name} WHERE id = %d AND user_id = %d",
        $creation_id, $user_id
    ));
}

function update_creation($creation_id, $user_id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $update_data = [];
    if (isset($data['title'])) $update_data['title'] = sanitize_text_field($data['title']);
    if (isset($data['description'])) $update_data['description'] = sanitize_textarea_field($data['description']);
    if (isset($data['is_public'])) $update_data['is_public'] = boolval($data['is_public']) ? 1 : 0;
    
    if (empty($update_data)) return false;
    
    return $wpdb->update($table_name, $update_data, ['id' => $creation_id, 'user_id' => $user_id]);
}

function delete_creation($creation_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $creation = $wpdb->get_row($wpdb->prepare(
        "SELECT audio_file_path FROM {$table_name} WHERE id = %d AND user_id = %d",
        $creation_id, $user_id
    ));
    
    if (!$creation) return false;
    
    $result = $wpdb->delete($table_name, ['id' => $creation_id, 'user_id' => $user_id], ['%d', '%d']);
    
    if ($result && !empty($creation->audio_file_path) && file_exists($creation->audio_file_path)) {
        @unlink($creation->audio_file_path);
        write_log("File eliminato: " . $creation->audio_file_path);
    }
    
    return $result;
}

function get_creation_by_token($share_token) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, u.display_name as creator_name FROM {$table_name} c 
         LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
         WHERE c.share_token = %s AND c.is_public = 1",
        $share_token
    ));
}

function get_user_statistics($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) as total_creations, 
         COUNT(CASE WHEN is_favorite = 1 THEN 1 END) as favorites_count,
         COUNT(CASE WHEN is_public = 1 THEN 1 END) as public_count,
         SUM(plays_count) as total_plays,
         SUM(downloads_count) as total_downloads,
         AVG(duration) as avg_duration
         FROM {$table_name} WHERE user_id = %d",
        $user_id
    ));
}

function pictosound_save_user_creation($user_id, $creation_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $share_token = wp_generate_password(32, false);
    
    $data = [
        'user_id' => $user_id,
        'title' => sanitize_text_field($creation_data['title'] ?? 'Creazione del ' . date('d/m/Y H:i')),
        'description' => sanitize_textarea_field($creation_data['description'] ?? ''),
        'original_image_path' => sanitize_text_field($creation_data['image_path'] ?? ''),
        'audio_file_path' => sanitize_text_field($creation_data['audio_path'] ?? ''),
        'audio_file_url' => esc_url_raw($creation_data['audio_url'] ?? ''),
        'prompt_text' => sanitize_textarea_field($creation_data['prompt'] ?? ''),
        'duration' => intval($creation_data['duration'] ?? 40),
        'audio_format' => sanitize_text_field($creation_data['format'] ?? 'mp3'),
        'file_size' => intval($creation_data['file_size'] ?? 0),
        'image_analysis_data' => wp_json_encode($creation_data['analysis_data'] ?? []),
        'detected_objects' => sanitize_text_field($creation_data['objects'] ?? ''),
        'detected_emotions' => sanitize_text_field($creation_data['emotions'] ?? ''),
        'musical_settings' => wp_json_encode($creation_data['musical_settings'] ?? []),
        'share_token' => $share_token,
        'is_public' => boolval($creation_data['is_public'] ?? false)
    ];
    
    $result = $wpdb->insert($table_name, $data);
    
    if ($result !== false) {
        write_log("Creazione salvata con ID: " . $wpdb->insert_id);
        return $wpdb->insert_id;
    }
    
    return false;
}

// ================== GESTIONE RICHIESTE ==================

// Verifica login (eccetto per visualizzazione pubblica)
$is_public_view = isset($_GET['token']) || (isset($_GET['action']) && $_GET['action'] === 'public_view');
if (!$is_public_view && !is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login richiesto']);
    exit;
}

$current_user = $is_public_view ? null : wp_get_current_user();

// ================== GET REQUESTS ==================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'public_view':
            $token = sanitize_text_field($_GET['token'] ?? '');
            if (empty($token)) {
                http_response_code(400);
                echo json_encode(['error' => 'Token mancante']);
                exit;
            }
            
            $creation = get_creation_by_token($token);
            if (!$creation) {
                http_response_code(404);
                echo json_encode(['error' => 'Creazione non trovata']);
                exit;
            }
            
            update_play_count($creation->id);
            echo json_encode(['success' => true, 'creation' => $creation]);
            break;
            
        case 'statistics':
            $stats = get_user_statistics($current_user->ID);
            echo json_encode(['success' => true, 'statistics' => $stats]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Azione non riconosciuta']);
    }
}

// ================== POST REQUESTS ==================
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON malformato']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'get_creations':
            $limit = max(1, min(100, intval($input['limit'] ?? 20)));
            $offset = max(0, intval($input['offset'] ?? 0));
            $search = sanitize_text_field($input['search'] ?? '');
            $filter = sanitize_text_field($input['filter'] ?? 'all');
            
            $creations = get_user_creations($current_user->ID, $limit, $offset, $search, $filter);
            echo json_encode(['success' => true, 'creations' => $creations]);
            break;
            
        case 'play':
            $creation_id = intval($input['creation_id'] ?? 0);
            if (!$creation_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID creazione mancante']);
                exit;
            }
            
            $result = update_play_count($creation_id, $current_user->ID);
            echo json_encode(['success' => (bool)$result]);
            break;
            
        case 'toggle_favorite':
            $creation_id = intval($input['creation_id'] ?? 0);
            if (!$creation_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID creazione mancante']);
                exit;
            }
            
            $new_state = toggle_favorite($creation_id, $current_user->ID);
            echo json_encode(['success' => true, 'is_favorite' => (bool)$new_state]);
            break;
            
        case 'update':
            $creation_id = intval($input['creation_id'] ?? 0);
            if (!$creation_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID creazione mancante']);
                exit;
            }
            
            $result = update_creation($creation_id, $current_user->ID, $input);
            echo json_encode(['success' => (bool)$result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Azione non riconosciuta']);
    }
}

// ================== DELETE REQUESTS ==================
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $creation_id = intval($input['creation_id'] ?? 0);
    
    if (!$creation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID creazione mancante']);
        exit;
    }
    
    $result = delete_creation($creation_id, $current_user->ID);
    echo json_encode(['success' => (bool)$result]);
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non supportato']);
}
?>