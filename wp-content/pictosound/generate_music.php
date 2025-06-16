<?php
// ===== PASSO 1: INTEGRAZIONE CON WORDPRESS =====
// Carichiamo l'ambiente di WordPress per accedere alle sue funzioni (login, database, etc.).
// Il percorso potrebbe variare, ma se lo script è in 'wp-content/pictosound/', questo dovrebbe essere corretto.
require_once('../../../wp-load.php');


// Impostazioni degli header per le richieste cross-origin (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Funzione di logging per il debug
function write_log($message) {
    $log_dir = __DIR__ . '/logs/';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0775, true);
    }
    file_put_contents($log_dir . 'api_log.txt', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// Gestione richiesta OPTIONS per preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Rispondiamo solo a richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    write_log("Richiesta con metodo non supportato: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non supportato.']);
    exit;
}

// ===== PASSO 2: CONTROLLO AUTENTICAZIONE UTENTE =====
if (!is_user_logged_in()) {
    header('Content-Type: application/json');
    write_log("Tentativo di accesso non autorizzato.");
    http_response_code(401);
    echo json_encode(['error' => 'Devi essere loggato per generare un suono.']);
    exit;
}
// Otteniamo l'ID dell'utente corrente
$user_id = get_current_user_id();
write_log("Richiesta ricevuta dall'utente con ID: " . $user_id);


// Inizializziamo la risposta JSON
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== PASSO 3: GESTIONE UPLOAD IMMAGINE E PROMPT =====
// Ora ci aspettiamo dati 'multipart/form-data' invece di JSON
$prompt_text_from_frontend = trim($_POST['prompt'] ?? '');

if (empty($prompt_text_from_frontend)) {
    write_log("Errore: Prompt mancante.");
    http_response_code(400);
    echo json_encode(['error' => 'Il prompt è obbligatorio.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    write_log("Errore: Immagine mancante o errore di caricamento. Codice errore: " . ($_FILES['image']['error'] ?? 'N/D'));
    http_response_code(400);
    echo json_encode(['error' => 'L\'immagine è obbligatoria e deve essere caricata correttamente.']);
    exit;
}

// Creiamo la directory per gli upload se non esiste
$uploads_dir_absolute = __DIR__ . '/uploads/';
$uploads_dir_relative_base = 'wp-content/pictosound/uploads/'; // Percorso relativo da salvare nel DB

if (!file_exists($uploads_dir_absolute)) {
    if (!mkdir($uploads_dir_absolute, 0775, true) && !is_dir($uploads_dir_absolute)) {
        write_log("Impossibile creare la directory di upload.");
        http_response_code(500);
        echo json_encode(['error' => 'Errore server: impossibile creare la directory di upload.']);
        exit;
    }
}

// Salviamo l'immagine caricata con un nome unico
$image_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$image_filename = 'img_' . $user_id . '_' . time() . '.' . $image_extension;
$image_path_absolute = $uploads_dir_absolute . $image_filename;
$image_path_relative = $uploads_dir_relative_base . $image_filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path_absolute)) {
    write_log("Errore critico nel salvare l'immagine caricata in: " . $image_path_absolute);
    http_response_code(500);
    echo json_encode(['error' => 'Errore server: impossibile salvare l\'immagine.']);
    exit;
}

write_log("Immagine salvata con successo in: " . $image_path_absolute);
write_log("Prompt ricevuto: '" . $prompt_text_from_frontend . "'");

// ===== PASSO 4: CHIAMATA ALL'API DI STABILITY.AI (logica esistente adattata) =====
$api_key = 'sk-EQyuyCbTzRuI9InYbQZtsCVPLSNAy202c5veU8iXOoY9KcTA'; // SOSTITUISCI CON LA TUA CHIAVE API
if (empty($api_key)) {
    write_log("API Key mancante nello script.");
    http_response_code(500);
    echo json_encode(['error' => 'Configurazione API Key mancante (server).']);
    exit;
}

$api_url = 'https://api.stability.ai/v2beta/audio/stable-audio-2/text-to-audio';
$duration_seconds = isset($_POST['duration']) ? max(30, min(180, intval($_POST['duration']))) : 45;

$fields_to_send = [
    'prompt'        => $prompt_text_from_frontend,
    'output_format' => 'mp3',
    'duration'      => $duration_seconds,
    'steps'         => 30
];

write_log("Invio richiesta a Stability API. Dati: " . print_r($fields_to_send, true));

$boundary = "------------------------" . uniqid();
$request_body = "";
foreach ($fields_to_send as $name => $value) {
    $request_body .= "--" . $boundary . "\r\n";
    $request_body .= "Content-Disposition: form-data; name=\"" . $name . "\"\r\n\r\n";
    $request_body .= $value . "\r\n";
}
$request_body .= "--" . $boundary . "--\r\n";

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $request_body,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer $api_key",
        "Accept: audio/*",
        "Content-Type: multipart/form-data; boundary=" . $boundary,
        "Content-Length: " . strlen($request_body),
        "Expect:"
    ],
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_CONNECTTIMEOUT => 30,
]);

$response_body_raw = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

write_log("Risposta da Stability API - HTTP Code: $http_code");

if ($curl_error || $http_code !== 200 || empty($response_body_raw)) {
    // Se la chiamata API fallisce, cancelliamo l'immagine che avevamo salvato per non lasciare file orfani
    unlink($image_path_absolute);
    write_log("Errore API o cURL. L'immagine temporanea '$image_filename' è stata cancellata.");
    http_response_code(502); // Bad Gateway
    echo json_encode(['error' => "Errore durante la comunicazione con l'API di generazione audio (Code: $http_code). Curl Error: " . $curl_error]);
    exit;
}

// Salviamo il file audio ricevuto
$sound_filename = 'sound_' . $user_id . '_' . time() . '.mp3';
$sound_path_absolute = $uploads_dir_absolute . $sound_filename;
$sound_path_relative = $uploads_dir_relative_base . $sound_filename;

if (file_put_contents($sound_path_absolute, $response_body_raw) === false) {
    unlink($image_path_absolute); // Cancelliamo anche l'immagine se il salvataggio audio fallisce
    write_log("Errore nel salvare il file audio. L'immagine '$image_filename' è stata cancellata.");
    http_response_code(500);
    echo json_encode(['error' => 'Errore server: impossibile salvare il file audio generato.']);
    exit;
}

write_log("Audio salvato con successo: " . $sound_path_absolute);

// ===== PASSO 5: SALVATAGGIO NEL DATABASE =====
global $wpdb;
$table_name = 'user_gallery'; // Il nome della nostra nuova tabella

$result = $wpdb->insert(
    $table_name,
    [
        'user_id' => $user_id,
        'image_path' => $image_path_relative,
        'sound_path' => $sound_path_relative,
    ],
    [
        '%d', // user_id è un intero
        '%s', // image_path è una stringa
        '%s', // sound_path è una stringa
    ]
);

if ($result === false) {
    // Se il salvataggio nel DB fallisce, cancelliamo i file per non lasciare orfani
    unlink($image_path_absolute);
    unlink($sound_path_absolute);
    write_log("ERRORE CRITICO: Impossibile salvare nel database. File '$image_filename' e '$sound_filename' cancellati. Errore DB: " . $wpdb->last_error);
    http_response_code(500);
    echo json_encode(['error' => 'Errore server: impossibile salvare la creazione nella galleria.']);
    exit;
}

write_log("Creazione salvata nel database con successo per l'utente ID: " . $user_id);


// ===== PASSO 6: RISPOSTA DI SUCCESSO AL FRONTEND =====
// Costruiamo gli URL completi da restituire
$site_url = site_url();
$full_image_url = $site_url . '/' . $image_path_relative;
$full_sound_url = $site_url . '/' . $sound_path_relative;

echo json_encode([
    'success'    => true,
    'message'    => 'Creazione salvata nella galleria con successo!',
    'imageUrl'   => $full_image_url,
    'audioUrl'   => $full_sound_url
], JSON_UNESCAPED_SLASHES);

exit;

?>