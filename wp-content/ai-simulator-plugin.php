<?php
/**
 * Plugin Name:       AI Compatibility Simulator
 * Description:       Aggiunge uno shortcode [ai_simulator] per analizzare la compatibilità di un sito con i sistemi AI e fornire suggerimenti per migliorare.
 * Version:           2.0
 * Author:            La Tua AI
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Registra lo shortcode [ai_simulator]
 */
function ai_simulator_v2_shortcode_html() {
    ai_simulator_v2_enqueue_assets();

    ob_start();
    ?>
    <div class="ai-simulator-container">
        <header>
            <h1>🤖 Simulatore di Compatibilità AI v2.0</h1>
            <p>Scopri come un'IA interpreta il tuo sito e ricevi consigli per migliorare.</p>
        </header>

        <main>
            <form id="ai-form-v2">
                <?php wp_nonce_field('ai_simulator_nonce', 'ai_simulator_nonce_field'); ?>
                <div class="form-group">
                    <label for="url">URL della pagina da analizzare</label>
                    <input type="url" id="url" name="url" placeholder="https://www.esempio.it/pagina" required>
                </div>
                <div class="form-group">
                    <label for="query">Qual è la tua domanda?</label>
                    <input type="text" id="query" name="query" placeholder="Es: Quali sono gli orari di apertura?" required>
                </div>
                <button type="submit" id="submit-button">Analizza Ora</button>
            </form>

            <div id="loader" class="hidden">
                <div class="spinner"></div>
                <p>Analisi in corso...</p>
            </div>

            <div id="results-container" class="hidden">
                <h2>Risultati dell'Analisi</h2>
                <div class="result-box score">
                    <h3>Punteggio di Compatibilità</h3>
                    <div id="ai-score">0%</div>
                </div>
                
                <div id="suggestions-box" class="result-box suggestions hidden">
                     <h3>💡 Come Migliorare</h3>
                     <ul id="suggestions-list"></ul>
                </div>
                
                <div class="result-box response">
                    <h3>Risposta Simulata dall'AI</h3>
                    <p id="simulated-response"></p>
                </div>
                
                <div class="result-box diagnostics">
                    <h3>Diagnosi Tecnica</h3>
                    <ul id="diagnostics-list"></ul>
                </div>
            </div>
             <div id="error-container" class="hidden">
                <h3>⚠️ Si è verificato un errore</h3>
                <p id="error-message"></p>
            </div>
        </main>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ai_simulator', 'ai_simulator_v2_shortcode_html');

/**
 * 2. Carica CSS e JavaScript
 */
function ai_simulator_v2_enqueue_assets() {
    ?>
    <style>
        .ai-simulator-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background-color: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); padding: 30px 40px; margin: 40px 0; max-width: 700px; }
        .ai-simulator-container header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #e0e0e0; padding-bottom: 20px; }
        .ai-simulator-container header h1 { font-size: 28px; margin: 0; color: #1e3a8a; }
        .ai-simulator-container header p { font-size: 16px; color: #555; margin-top: 8px; }
        .ai-simulator-container .form-group { margin-bottom: 20px; }
        .ai-simulator-container .form-group label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; }
        .ai-simulator-container .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        .ai-simulator-container button { width: 100%; padding: 15px; background-color: #1e3a8a; color: #fff; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: background-color 0.3s; }
        .ai-simulator-container button:hover { background-color: #1c3d9a; }
        .ai-simulator-container .hidden { display: none !important; }
        .ai-simulator-container #loader { text-align: center; margin-top: 30px; }
        .ai-simulator-container .spinner { border: 6px solid #f3f3f3; border-top: 6px solid #1e3a8a; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 15px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .ai-simulator-container #results-container, .ai-simulator-container #error-container { margin-top: 40px; }
        .ai-simulator-container .result-box { background-color: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .ai-simulator-container .result-box h3 { margin-top: 0; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 15px; color: #1e3a8a; }
        .ai-simulator-container #ai-score { font-size: 48px; font-weight: bold; text-align: center; color: #1e3a8a; }
        .ai-simulator-container #simulated-response { font-size: 16px; line-height: 1.6; }
        .ai-simulator-container ul { list-style-type: none; padding: 0; }
        .ai-simulator-container ul li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .ai-simulator-container ul li:last-child { border-bottom: none; }
        .ai-simulator-container .result-box.suggestions { background-color: #fffbeb; border-color: #fbbd23; }
        .ai-simulator-container .result-box.suggestions h3 { color: #b45309; }
        .ai-simulator-container #error-container { background-color: #ffebee; border: 1px solid #c62828; color: #c62828; border-radius: 8px; padding: 20px; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('ai-form-v2');
        if (!form) return;
        const loader = document.getElementById('loader');
        const resultsContainer = document.getElementById('results-container');
        const errorContainer = document.getElementById('error-container');
        const submitButton = document.getElementById('submit-button');

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(form);
            formData.append('action', 'analyze_ai_v2');
            formData.append('nonce', document.getElementById('ai_simulator_nonce_field').value);

            resultsContainer.classList.add('hidden');
            errorContainer.classList.add('hidden');
            loader.classList.remove('hidden');
            submitButton.disabled = true;
            submitButton.textContent = 'Analisi in corso...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.data);
                } else {
                    displayError(data.data.message || 'Errore sconosciuto.');
                }
            })
            .catch(error => displayError('Errore di comunicazione con il server.'))
            .finally(() => {
                loader.classList.add('hidden');
                submitButton.disabled = false;
                submitButton.textContent = 'Analizza Ora';
            });
        });

        function displayResults(data) {
            document.getElementById('ai-score').textContent = `${data.aiScore}%`;
            document.getElementById('simulated-response').textContent = data.simulatedResponse;
            
            const diagnosticsList = document.getElementById('diagnostics-list');
            diagnosticsList.innerHTML = '';
            data.diagnostics.forEach(item => {
                const li = document.createElement('li');
                li.innerHTML = item; // Use innerHTML to render emojis
                diagnosticsList.appendChild(li);
            });

            const suggestionsList = document.getElementById('suggestions-list');
            const suggestionsBox = document.getElementById('suggestions-box');
            suggestionsList.innerHTML = '';
            if (data.suggestions && data.suggestions.length > 0) {
                 data.suggestions.forEach(item => {
                    const li = document.createElement('li');
                    li.innerHTML = item;
                    suggestionsList.appendChild(li);
                });
                suggestionsBox.classList.remove('hidden');
            } else {
                suggestionsBox.classList.add('hidden');
            }
            resultsContainer.classList.remove('hidden');
        }

        function displayError(message) {
            document.getElementById('error-message').textContent = message;
            errorContainer.classList.remove('hidden');
        }
    });
    </script>
    <?php
}

/**
 * 3. Gestisce la chiamata AJAX (Logica di analisi migliorata)
 */
function ai_simulator_v2_ajax_handler() {
    check_ajax_referer('ai_simulator_nonce', 'nonce');

    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    $query = isset($_POST['query']) ? strtolower(sanitize_text_field($_POST['query'])) : '';

    if (!filter_var($url, FILTER_VALIDATE_URL) || empty($query)) {
        wp_send_json_error(['message' => 'URL non valido o query mancante.'], 400);
    }
    
    $response = wp_remote_get($url, ['timeout' => 15, 'user-agent' => 'AIOptimizerBot-WP/2.0']);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        wp_send_json_error(['message' => 'Impossibile recuperare il contenuto dall\'URL.'], 500);
    }

    $html = wp_remote_retrieve_body($response);
    
    // --- Inizio Analisi v2.0 ---
    $score = 0;
    $diagnostics = [];
    $suggestions = [];
    $simulatedResponse = "Non ho trovato una risposta chiara. Prova a riformulare la domanda o a migliorare il contenuto della pagina.";
    $answer_found = false;

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $query_keywords = array_filter(explode(' ', preg_replace('/[^a-z0-9\s]/i', '', $query)));

    // Funzione helper per la ricerca
    $containsKeywords = function($text, $keywords) {
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2 && stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    };

    // 1. Dati Strutturati (JSON-LD) - Massima priorità
    $jsonLdNodes = $xpath->query('//script[@type="application/ld+json"]');
    if ($jsonLdNodes->length > 0) {
        $diagnostics[] = "✅ La pagina utilizza dati strutturati (Schema.org). Ottimo!";
        foreach ($jsonLdNodes as $node) {
            if ($containsKeywords($node->textContent, $query_keywords)) {
                $score += 45;
                $diagnostics[] = "✅ I dati strutturati sono pertinenti alla domanda.";
                $simulatedResponse = "Ho trovato un'informazione rilevante nei dati strutturati della pagina. Per dettagli più precisi, consulta il sito.";
                $answer_found = true;
                break;
            }
        }
    } else {
        $diagnostics[] = "❌ Dati strutturati (JSON-LD) non trovati.";
        $suggestions[] = "<b>Aggiungi dati strutturati (Schema.org)</b>. È il modo più efficace per spiegare alle IA di cosa parla la tua pagina (orari, prodotti, ricette, ecc.).";
    }

    // 2. Headings (H1-H3)
    $headings = $xpath->query('//h1|//h2|//h3');
    if (!$answer_found) {
        foreach ($headings as $heading) {
            if ($containsKeywords($heading->textContent, $query_keywords)) {
                $score += 30;
                $diagnostics[] = "✅ Trovata corrispondenza in un'intestazione: \"{$heading->textContent}\".";
                $simulatedResponse = trim($heading->textContent);
                // Prova a prendere il paragrafo successivo come contesto aggiuntivo
                $next_element = $xpath->query('following-sibling::*[1]', $heading)->item(0);
                if ($next_element && $next_element->nodeName == 'p') {
                    $simulatedResponse .= " " . trim($next_element->textContent);
                }
                $answer_found = true;
                break;
            }
        }
    }
     if (!$answer_found && $headings->length == 0) {
        $diagnostics[] = "⚠️ La pagina non usa heading (H1, H2, H3) in modo efficace.";
        $suggestions[] = "<b>Struttura i tuoi contenuti con i titoli (H1, H2, H3)</b>. Usa i titoli per porre domande o definire argomenti, proprio come in un libro.";
    }

    // 3. Meta Description
    $metaDescNode = $xpath->query('//meta[@name="description"]/@content')->item(0);
    if ($metaDescNode) {
        if ($containsKeywords($metaDescNode->value, $query_keywords) && !$answer_found) {
            $score += 15;
            $diagnostics[] = "✅ La meta description è pertinente.";
            $simulatedResponse = trim($metaDescNode->value);
            $answer_found = true;
        }
    } else {
        $diagnostics[] = "❌ Manca la meta description.";
        $suggestions[] = "<b>Scrivi una meta description accattivante</b>. È un breve riassunto che le IA usano spesso per capire il contenuto di una pagina.";
    }

    // 4. Paragrafi
    if (!$answer_found) {
        $paragraphs = $xpath->query('//p');
        foreach ($paragraphs as $p) {
            if (strlen($p->textContent) > 50 && $containsKeywords($p->textContent, $query_keywords)) {
                $score += 10;
                $diagnostics[] = "✅ Trovata una potenziale risposta in un paragrafo del testo.";
                $simulatedResponse = trim($p->textContent);
                $answer_found = true;
                break;
            }
        }
    }

    if (!$answer_found) {
         $diagnostics[] = "⚠️ Non sono state trovate risposte dirette nel testo.";
         $suggestions[] = "<b>Crea una sezione F.A.Q. (Domande Frequenti)</b>. Rispondere direttamente alle domande più comuni è un modo eccellente per essere utili sia agli utenti che alle IA.";
    }


    $output = [
        'aiScore'           => min(100, $score),
        'simulatedResponse' => $simulatedResponse,
        'diagnostics'       => $diagnostics,
        'suggestions'       => $suggestions,
    ];

    wp_send_json_success($output);
}

add_action('wp_ajax_analyze_ai_v2', 'ai_simulator_v2_ajax_handler');
add_action('wp_ajax_nopriv_analyze_ai_v2', 'ai_simulator_v2_ajax_handler');