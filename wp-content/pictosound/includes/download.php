<?php
// --- Inizio Blocco di Caricamento Robusto di WordPress ---
// Cerca wp-load.php risalendo le directory, più affidabile di un percorso statico.
$wp_load_path = __DIR__;
while (strpos($wp_load_path, 'wp-content') !== false) {
    $wp_load_path = dirname($wp_load_path);
    if (file_exists($wp_load_path . '/wp-load.php')) {
        require_once($wp_load_path . '/wp-load.php');
        break;
    }
}
// --- Fine Blocco di Caricamento ---

// Log per verificare l'esecuzione e i parametri
error_log("DOWNLOAD.PHP: Script eseguito. Parametro 'file' ricevuto: " . ($_GET['file'] ?? 'NON PRESENTE'));

if (!isset($_GET['file'])) {
    wp_die('Nessun file specificato.');
}

$filename = basename(wp_unslash($_GET['file']));
$filename = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);

// Definiamo il percorso ESATTAMENTE come nello script di generazione
$audio_dir = WP_CONTENT_DIR . '/pictosound/audio/';
$filepath = $audio_dir . $filename;

// LOG FONDAMENTALE
error_log("DOWNLOAD.PHP: Cerco il file nel percorso assoluto -> " . $filepath);

if (file_exists($filepath)) {
    error_log("DOWNLOAD.PHP: File trovato! Invio in corso...");
    
    // Codice per servire il file...
    $content_type = 'audio/mpeg';
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('X-Content-Type-Options: nosniff');
    ob_clean();
    flush();
    readfile($filepath);
    exit;
} else {
    // Il file non è stato trovato, inviamo un log e poi l'errore 404
    error_log("DOWNLOAD.PHP: ERRORE - File non trovato al percorso specificato.");
    status_header(404);
    // Non usiamo wp_die per non mostrare una pagina di errore WordPress completa
    echo 'File non trovato.';
    exit;
}
?>