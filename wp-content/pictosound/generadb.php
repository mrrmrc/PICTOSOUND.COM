<?php
/**
 * Crea la tabella per le creazioni utente
 * Da aggiungere al plugin pictosound-credits-manager.php
 */

function pictosound_cm_create_user_creations_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(255) DEFAULT '',
        description text DEFAULT '',
        
        -- File originali
        original_image_path varchar(500) DEFAULT '',
        audio_file_path varchar(500) DEFAULT '',
        audio_file_url varchar(500) DEFAULT '',
        
        -- Metadati generazione
        prompt_text text DEFAULT '',
        duration int(11) DEFAULT 40,
        audio_format varchar(10) DEFAULT 'mp3',
        file_size int(11) DEFAULT 0,
        
        -- Dati analisi immagine (JSON)
        image_analysis_data longtext DEFAULT '',
        detected_objects text DEFAULT '',
        detected_emotions text DEFAULT '',
        
        -- Impostazioni musicali usate (JSON)
        musical_settings longtext DEFAULT '',
        
        -- Stats e interazioni
        plays_count int(11) DEFAULT 0,
        downloads_count int(11) DEFAULT 0,
        is_favorite boolean DEFAULT FALSE,
        is_public boolean DEFAULT FALSE,
        
        -- Condivisione
        share_token varchar(32) DEFAULT '',
        shared_count int(11) DEFAULT 0,
        
        -- Timestamp
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY share_token (share_token),
        KEY is_public (is_public),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Crea anche la tabella per i link di condivisione
    $shares_table = $wpdb->prefix . 'pictosound_creation_shares';
    
    $shares_sql = "CREATE TABLE $shares_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        creation_id mediumint(9) NOT NULL,
        share_token varchar(32) NOT NULL,
        shared_by bigint(20) NOT NULL,
        access_count int(11) DEFAULT 0,
        expires_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        
        PRIMARY KEY (id),
        UNIQUE KEY share_token (share_token),
        KEY creation_id (creation_id),
        KEY shared_by (shared_by)
    ) $charset_collate;";
    
    dbDelta($shares_sql);
    
    write_log_cm("Tabelle user creations e shares create/aggiornate");
}

// Aggiungi questa chiamata al hook di attivazione del plugin
register_activation_hook(__FILE__, 'pictosound_cm_create_user_creations_table');

/**
 * Funzioni helper per gestire le creazioni
 */

function pictosound_save_user_creation($user_id, $creation_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    // Genera token di condivisione unico
    $share_token = wp_generate_password(32, false);
    
    $data = [
        'user_id' => $user_id,
        'title' => sanitize_text_field($creation_data['title'] ?? ''),
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
        $creation_id = $wpdb->insert_id;
        write_log_cm("Creazione salvata con ID: $creation_id per user: $user_id");
        return $creation_id;
    }
    
    write_log_cm("Errore nel salvare creazione per user: $user_id - " . $wpdb->last_error);
    return false;
}

function pictosound_get_user_creations($user_id, $limit = 20, $offset = 0, $order_by = 'created_at DESC') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE user_id = %d 
         ORDER BY {$order_by}
         LIMIT %d OFFSET %d",
        $user_id, $limit, $offset
    ));
    
    return $results;
}

function pictosound_get_creation_by_token($share_token) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE share_token = %s",
        $share_token
    ));
}

function pictosound_update_creation_stats($creation_id, $stat_type) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    $valid_stats = ['plays_count', 'downloads_count', 'shared_count'];
    
    if (!in_array($stat_type, $valid_stats)) return false;
    
    return $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} SET {$stat_type} = {$stat_type} + 1 WHERE id = %d",
        $creation_id
    ));
}
?>