<?php
// Carica l'ambiente WordPress per controlli di sicurezza
require_once realpath('../../../../wp-load.php');

if (!isset($_GET['file'])) {
    wp_die('Nessun file specificato.');
}

// Sanitizza il nome del file per prevenire attacchi "Directory Traversal"
$filename = basename($_GET['file']);
$filename = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);

$audio_dir = WP_CONTENT_DIR . '/pictosound/audio/';
$filepath = $audio_dir . $filename;

if (file_exists($filepath)) {
    // QUI POTRESTI AGGIUNGERE CONTROLLI
    // Esempio: if ( !is_user_logged_in() ) { wp_die('Accesso negato'); }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $content_type = 'application/octet-stream';
    if ($extension === 'wav') {
        $content_type = 'audio/wav';
    } elseif ($extension === 'mp3') {
        $content_type = 'audio/mpeg';
    }
    
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('X-Content-Type-Options: nosniff');
    ob_clean();
    flush();
    readfile($filepath);
    exit;
} else {
    status_header(404);
    wp_die('File non trovato.');
}
?>