<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// Non impostare Content-Type: application/json qui di default, lo faremo dopo se necessario

// Logging per debugging
function write_log($message) {
    $log_dir = __DIR__ . '/logs/';
    if (!file_exists($log_dir)) {
        if (!mkdir($log_dir, 0775, true) && !is_dir($log_dir)) {
            error_log("Impossibile creare la directory di log: " . $log_dir);
            return;
        }
    }
    file_put_contents($log_dir . 'api_log.txt', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// Download audio
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);
    $audio_dir = __DIR__ . '/audio/'; // Assicurati che audio_dir sia definito
    $filepath = $audio_dir . $filename;

    if (file_exists($filepath)) {
        // Determina il content type in base all'estensione per sicurezza
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $content_type = 'application/octet-stream'; // Default
        if ($extension === 'wav') {
            $content_type = 'audio/wav';
        } elseif ($extension === 'mp3') {
            $content_type = 'audio/mpeg';
        }
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('X-Content-Type-Options: nosniff');
        readfile($filepath);
        exit;
    }
    // Se il file non esiste, invia una risposta JSON di errore
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'File non trovato']);
    exit;
}

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// Generazione audio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $raw_input = file_get_contents('php://input');
    write_log("Raw input ricevuto: " . $raw_input);
    $input = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) { 
        header('Content-Type: application/json');
        write_log("Errore decodifica JSON: " . json_last_error_msg());
        http_response_code(400); echo json_encode(['error' => 'JSON malformato.']); exit;
    }
    if (!is_array($input)) { 
        header('Content-Type: application/json');
        write_log("Input non è array.");
        http_response_code(400); echo json_encode(['error' => 'JSON non valido.']); exit;
    }

    $prompt_text_from_frontend = trim($input['prompt'] ?? '');
    write_log("Prompt ricevuto dal frontend: '" . $prompt_text_from_frontend . "' (Lunghezza UTF-8: " . mb_strlen($prompt_text_from_frontend, 'UTF-8') . ")");
    
    // DISATTIVIAMO IL TEST HARDCODED PER USARE IL PROMPT DAL FRONTEND
    // $USE_HARDCODED_PROMPT_FOR_TESTING = true; 

    if ($prompt_text_from_frontend === '') { 
        header('Content-Type: application/json');
        write_log("Prompt dal frontend è vuoto.");
        http_response_code(400); echo json_encode(['error' => 'Prompt mancante inviato dal frontend.']); exit;
    }

    $audio_dir = __DIR__ . '/audio/';
    if (!file_exists($audio_dir)) { 
        if(!mkdir($audio_dir, 0775, true) && !is_dir($audio_dir)){
            header('Content-Type: application/json');
            write_log("Impossibile creare dir audio.");
            http_response_code(500); echo json_encode(['error' => 'Errore server creazione directory.']); exit;
        }
    }

    $api_key = 'sk-EQyuyCbTzRuI9InYbQZtsCVPLSNAy202c5veU8iXOoY9KcTA'; // USA LA TUA CHIAVE API VALIDA
    if (empty($api_key)) { 
        header('Content-Type: application/json');
        write_log("API Key mancante nello script.");
        http_response_code(500); echo json_encode(['error' => 'Config API Key mancante (server).']); exit;
    }

    $api_url = 'https://api.stability.ai/v2beta/audio/stable-audio-2/text-to-audio';
    $fields_to_send = [];

    // Usiamo il prompt dal frontend
    $prompt_to_send_to_api = $prompt_text_from_frontend;
    
    // Parametri come da esempio curl di Stability AI
    $output_format = 'mp3'; // o 'wav'
    $duration_seconds = isset($input['duration']) ? max(30, min(180, intval($input['duration']))) : 45; // Default 45s, min 30s, max 180s // Default a 20s, o prendi da input
    if ($duration_seconds <=0) $duration_seconds = 20; // Fallback
    $steps = 30; // Valore di esempio, puoi renderlo configurabile

    $fields_to_send = [
        'prompt'         => $prompt_to_send_to_api, 
        'output_format'  => $output_format,
        'duration'       => $duration_seconds, // La documentazione a volte usa 'duration', a volte 'length_seconds'
                                              // L'esempio curl usa 'duration'
        'steps'          => $steps
        // Altri parametri come cfg_scale, sample_rate potrebbero essere aggiunti se supportati con questo stile di chiamata
        // Per ora, ci atteniamo all'esempio curl che ha funzionato
    ];
    write_log("Usando prompt da frontend con formato API stile esempio: '" . substr($prompt_to_send_to_api,0,100) . "...'");


    write_log("Invio richiesta multipart a Stability API: $api_url");
    write_log("Struttura dei campi da inviare: " . print_r($fields_to_send, true));

    $boundary = "------------------------" . uniqid();
    $request_body = "";
    foreach ($fields_to_send as $name => $value) {
        $request_body .= "--" . $boundary . "\r\n";
        $request_body .= "Content-Disposition: form-data; name=\"" . $name . "\"\r\n\r\n";
        $request_body .= $value . "\r\n";
    }
    $request_body .= "--" . $boundary . "--\r\n";

    write_log("Corpo della richiesta multipart costruito manualmente (prime 500 char): " . substr($request_body, 0, 500) . "...");
    write_log("Lunghezza corpo richiesta: " . strlen($request_body));

    $ch = curl_init($api_url);
    
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose_log_stream = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose_log_stream);

    $response_headers = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION,
      function($curl, $header) use (&$response_headers) {
        $len = strlen($header);
        $header_parts = explode(':', $header, 2);
        if (count($header_parts) < 2) { return $len; }
        $response_headers[strtolower(trim($header_parts[0]))][] = trim($header_parts[1]);
        return $len;
      }
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $request_body,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $api_key",
            "Accept: audio/*", // Richiediamo audio direttamente
            "Content-Type: multipart/form-data; boundary=" . $boundary,
            "Content-Length: " . strlen($request_body),
            "Expect:" 
        ],
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_CONNECTTIMEOUT => 30,
    ]);

    $response_body_raw = curl_exec($ch);
    $http_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error    = curl_error($ch);
    
    rewind($verbose_log_stream);
    $verbose_output = stream_get_contents($verbose_log_stream);
    fclose($verbose_log_stream);
    write_log("===== INIZIO OUTPUT VERBOSO cURL =====" . "\n" . trim($verbose_output) . "\n" . "===== FINE OUTPUT VERBOSO cURL =====");

    curl_close($ch);

    write_log("Risposta da Stability API - HTTP Code: $http_code, Curl Error: " . ($curl_error ?: 'None'));
    write_log("Header della risposta ricevuti: " . print_r($response_headers, true));
    // Log più esteso del corpo se non è chiaramente audio lungo
    $response_content_type_log = isset($response_headers['content-type'][0]) ? $response_headers['content-type'][0] : 'N/D';
    if (strlen($response_body_raw) < 5000 || stripos($response_content_type_log, 'text') !== false || stripos($response_content_type_log, 'json') !== false) {
        write_log("Corpo della risposta grezza (lunghezza: " . strlen($response_body_raw) . "):\n" . $response_body_raw);
    } else {
        write_log("Corpo della risposta grezza (lunghezza: " . strlen($response_body_raw) . ", Content-Type: " . $response_content_type_log . "): [DATI BINARI LUNGHI, NON MOSTRATI COMPLETAMENTE NEL LOG STANDARD]");
    }


    if ($curl_error) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => "Errore cURL: $curl_error"]);
        exit;
    }
    
    $response_content_type = isset($response_headers['content-type'][0]) ? $response_headers['content-type'][0] : '';

    if ($http_code >= 200 && $http_code < 300) {
        if (stripos($response_content_type, 'audio/') !== false && strlen($response_body_raw) > 0) {
            write_log("Content-Type della risposta è audio ($response_content_type). Tento salvataggio diretto.");
            $file_extension = 'tmp';
            if (stripos($response_content_type, 'mpeg') !== false) $file_extension = 'mp3';
            elseif (stripos($response_content_type, 'wav') !== false) $file_extension = 'wav';
            elseif (stripos($response_content_type, 'ogg') !== false) $file_extension = 'ogg';
            
            $filename = 'audio_gen_' . time() . '.' . $file_extension;
            $filepath = $audio_dir . $filename;

            if (file_put_contents($filepath, $response_body_raw) !== false) {
                write_log("Audio salvato con successo: $filename");
                $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
                $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $script_download_url = $_SERVER['PHP_SELF'] . '?download=' . urlencode($filename);
                $full_download_url = "$scheme://$host" . $script_download_url;
                
                header('Content-Type: application/json'); // Assicurati che la risposta al client sia JSON
                echo json_encode([
                    'success'     => true,
                    'audioUrl'    => $script_download_url, 
                    'downloadUrl' => $full_download_url,
                    'fileName'    => $filename,
                    'message'     => 'Audio generato e salvato con successo.'
                ], JSON_UNESCAPED_SLASHES);
                exit;
            } else {
                header('Content-Type: application/json');
                write_log("Errore salvataggio file audio diretto: " . $filepath);
                http_response_code(500);
                echo json_encode(['error' => "Errore interno del server: impossibile salvare il file audio."]);
                exit;
            }
        } else {
            // HTTP 200 ma non è audio e non è il JSON che ci aspettavamo prima
            header('Content-Type: application/json');
            $error_msg = "Risposta API OK (HTTP 200) ma il Content-Type non è audio come atteso, oppure il corpo è vuoto. Content-Type: $response_content_type";
            write_log($error_msg . ". Corpo risposta: " . $response_body_raw);
            http_response_code(502); // Bad Gateway - risposta inattesa dal server upstream
            echo json_encode(['error' => "API Error (502): $error_msg"]);
            exit;
        }

    } else { // Gestione errori HTTP non 2xx
        header('Content-Type: application/json');
        $error_msg = 'Errore API sconosciuto.';
        $resp_json = null;
        if (stripos($response_content_type, 'application/json') !== false) {
            $resp_json = json_decode($response_body_raw, true);
        }

        if ($resp_json !== null && isset($resp_json['errors']) && is_array($resp_json['errors'])) {
            $error_msg = implode('; ', $resp_json['errors']);
        } elseif ($resp_json !== null && isset($resp_json['message'])) {
            $error_msg = $resp_json['message'];
        } elseif (!empty($response_body_raw) && $resp_json === null && stripos($response_content_type, 'application/json') !== false ) {
             $error_msg = "L'API ha restituito 'application/json' ma il corpo non era JSON valido (HTTP $http_code). Errore JSON: " . json_last_error_msg();
        } elseif (!empty($response_body_raw)) {
             $error_msg = "L'API ha restituito una risposta non JSON (HTTP $http_code).";
        } else if (empty($response_body_raw)) {
            $error_msg = "L'API ha restituito HTTP $http_code senza corpo.";
        }
        
        // Specifico per l'errore "prompt: required" se dovesse riapparire
        if ($resp_json !== null && isset($resp_json['errors']) && in_array("prompt: required", $resp_json['errors'])) {
            $error_msg = "prompt: required (da API)";
        }


        write_log("Errore API Stability (HTTP $http_code): $error_msg. Risposta grezza: " . $response_body_raw);
        http_response_code($http_code); // Usa il codice HTTP originale dell'API
        echo json_encode(['error' => "API Error ($http_code): $error_msg"]);
        exit;
    }
}

// Se non è POST, GET con download, o OPTIONS
header('Content-Type: application/json');
write_log("Richiesta con metodo non supportato: " . $_SERVER['REQUEST_METHOD']);
http_response_code(405);
echo json_encode(['error' => 'Metodo non supportato.']);
// NOTE: Removed closing PHP tag to prevent extra output