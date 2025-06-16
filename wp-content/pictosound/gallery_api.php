<?php
// Carichiamo l'ambiente di WordPress per poter gestire gli utenti
require_once('../../../wp-load.php');

// Header per la risposta JSON
header('Content-Type: application/json');

// Controlliamo se l'utente è loggato
if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Devi essere loggato per vedere la galleria.']);
    exit;
}

$user_id = get_current_user_id();

try {
    global $wpdb;
    // Il nome della tabella che abbiamo creato
    $table_name = 'user_gallery';

    // Prendiamo tutte le creazioni per l'utente corrente
    $gallery_items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT image_path, sound_path FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ),
        ARRAY_A 
    );
    
    // Costruiamo gli URL completi per ogni file
    $site_url = site_url();
    foreach ($gallery_items as &$item) {
        $item['image_path'] = $site_url . '/' . $item['image_path'];
        $item['sound_path'] = $site_url . '/' . $item['sound_path'];
    }

    // Inviamo i dati come risposta
    echo json_encode(['success' => true, 'gallery' => $gallery_items]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore del server.']);
}

exit;
?>