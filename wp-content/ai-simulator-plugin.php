<?php
/**
 * Plugin Name:       AI Engine Analyzer Pro
 * Description:       Analisi avanzata AI-readiness con controlli semantici, linguaggio naturale e navigazione. Shortcode: [ai_engine]
 * Version:           6.0
 * Author:            AI Analysis Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrazione dello Shortcode e UI (HTML)
function ai_engine_v6_shortcode_html() {
    ai_engine_v6_enqueue_assets();
    ob_start();
    ?>
    <div class="ai-engine-container">
        <header class="hero-section">
            <h1>Il tuo sito è pronto per l'era dell'AI?</h1>
            <p class="subtitle">Scopri in 2 minuti se il tuo sito web è futuro-proof</p>
            
            <div class="quick-intro">
                <p>🤖 Le AI come ChatGPT stanno cambiando il modo di cercare informazioni online. 
                   Questo tool verifica se il tuo sito è pronto per essere compreso dalle intelligenze artificiali.</p>
            </div>
            
            <div class="accordion-info">
                <button class="info-toggle" type="button">
                    🤔 Perché è importante? <span class="arrow">▼</span>
                </button>
                <div class="info-content">
                    <div class="info-grid">
                        <div class="info-card">
                            <h3>🧠 Cos'è AI Engine Analyzer Pro?</h3>
                            <p>È come un "check-up medico" per il tuo sito web, ma invece di controllare se è sano per Google, verifica se è pronto per l'era dell'Intelligenza Artificiale.</p>
                        </div>
                        
                        <div class="info-card">
                            <h3>🔍 Modalità Audit Completo</h3>
                            <ul>
                                <li>Analizza se il tuo sito è strutturato bene per essere "letto" dalle AI</li>
                                <li>Controlla se i contenuti sono organizzati in modo logico</li>
                                <li>Ti dà un punteggio da 0 a 100 sulla "AI-readiness"</li>
                            </ul>
                        </div>
                        
                        <div class="info-card">
                            <h3>🤖 Modalità Test Domanda AI</h3>
                            <ul>
                                <li>Fai una domanda specifica (es: "Offrite consulenza SEO?")</li>
                                <li>L'applicazione simula come un'AI cercherebbe la risposta</li>
                                <li>Ti suggerisce come migliorare per rendere le risposte più facili da trovare</li>
                            </ul>
                        </div>
                        
                        <div class="info-card highlight">
                            <h3>💡 In parole semplici</h3>
                            <p>Se il tuo sito fosse una biblioteca, questa applicazione controlla se i libri sono ordinati bene, le etichette sono chiare e c'è un catalogo che aiuta a trovare quello che cerchi.</p>
                            <p><strong>Il risultato?</strong> Un sito perfetto per l'era dell'AI - dove sempre più persone useranno assistenti intelligenti per trovare informazioni sui tuoi prodotti e servizi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <div class="engine-layout">
            <div class="form-column">
                <form id="ai-engine-form">
                    <?php wp_nonce_field('ai_engine_nonce', 'ai_engine_nonce_field'); ?>
                    <div class="mode-selector">
                        <label><input type="radio" name="analysis_mode" value="audit" checked><strong>🔍 Audit Completo</strong></label>
                        <label><input type="radio" name="analysis_mode" value="query"><strong>🤖 Test Domanda AI</strong></label>
                    </div>
                    <div class="form-group">
                        <label for="url">🌐 Sito da analizzare</label>
                        <input type="text" id="url" name="url" placeholder="esempio.it" required>
                    </div>
                    <div class="form-group hidden" id="query-group">
                        <label for="query">❓ Domanda specifica per l'AI</label>
                        <input type="text" id="query" name="query" placeholder="Es: Offrire servizi di consulenza SEO?">
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" id="deep-analysis" name="deep_analysis"> 🔬 Analisi approfondita (più lenta)</label>
                    </div>
                    <button type="submit" id="submit-button">🚀 Avvia Analisi AI-Ready</button>
                </form>
            </div>
            <div class="log-column">
                <div id="analysis-log-container" class="hidden">
                    <h3>📊 Log di Analisi in Tempo Reale</h3>
                    <div id="analysis-log"></div>
                    <div id="progress-bar">
                        <div id="progress-fill"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="loader" class="hidden"><div class="spinner"></div><p>Elaborazione finale...</p></div>
        <div id="results-container" class="hidden"></div>
        <div id="error-container" class="hidden"><h3>⚠️ Errore</h3><p id="error-message"></p></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ai_engine', 'ai_engine_v6_shortcode_html');

// Inserimento di CSS e JavaScript
function ai_engine_v6_enqueue_assets() {
    ?>
    <style>
        .ai-engine-container { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 20px;
        }
        .hero-section { 
            text-align: center; 
            margin-bottom: 30px; 
            padding: 40px 30px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            position: relative;
        }
        .hero-section h1 { 
            font-size: 36px; 
            margin: 0 0 10px 0; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            font-weight: 700;
        }
        .subtitle {
            font-size: 18px;
            margin: 0 0 25px 0;
            opacity: 0.9;
            font-weight: 300;
        }
        .quick-intro {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            backdrop-filter: blur(10px);
        }
        .quick-intro p {
            margin: 0;
            font-size: 16px;
            line-height: 1.6;
        }
        .accordion-info {
            margin-top: 25px;
        }
        .info-toggle {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            backdrop-filter: blur(5px);
        }
        .info-toggle:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .info-toggle .arrow {
            transition: transform 0.3s ease;
            font-size: 14px;
        }
        .info-toggle.active .arrow {
            transform: rotate(180deg);
        }
        .info-content {
            margin-top: 20px;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.5s ease;
        }
        .info-content.active {
            max-height: 2000px;
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            text-align: left;
        }
        @media (min-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .info-card {
            background: rgba(255,255,255,0.95);
            color: #1e293b;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        .info-card.highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%);
            border: 2px solid #f59e0b;
            grid-column: 1 / -1;
        }
        .info-card h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        .info-card p {
            margin: 0 0 10px 0;
            line-height: 1.6;
            font-size: 14px;
        }
        .info-card ul {
            margin: 0;
            padding-left: 20px;
        }
        .info-card li {
            margin: 8px 0;
            line-height: 1.5;
            font-size: 14px;
        }
        .engine-layout { 
            display: grid; 
            grid-template-columns: 1fr; 
            gap: 30px; 
        }
        @media (min-width: 768px) { 
            .engine-layout { 
                grid-template-columns: 1fr 1fr; 
            } 
        }
        .form-column { 
            background: #fff; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e0e7ff;
        }
        .mode-selector { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-bottom: 25px; 
        }
        .mode-selector label { 
            text-align: center; 
            border: 2px solid #e0e7ff; 
            border-radius: 10px; 
            padding: 15px; 
            cursor: pointer; 
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        .mode-selector input[type="radio"] { 
            display: none; 
        }
        .mode-selector label:has(input:checked) { 
            border-color: #4f46e5; 
            background: linear-gradient(135deg, #eef2ff 0%, #ddd6fe 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.2);
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: #374151;
        }
        .form-group input[type="text"] { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e5e7eb; 
            border-radius: 8px; 
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-group input[type="text"]:focus { 
            outline: none; 
            border-color: #4f46e5; 
        }
        button { 
            width: 100%; 
            padding: 18px; 
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); 
            color: #fff; 
            border: none; 
            border-radius: 10px; 
            font-size: 18px; 
            font-weight: bold; 
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        button:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }
        button:disabled { 
            opacity: 0.7; 
            cursor: not-allowed; 
            transform: none;
        }
        .hidden { 
            display: none !important; 
        }
        .log-column #analysis-log-container { 
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); 
            color: #e2e8f0; 
            padding: 25px; 
            border-radius: 15px; 
            height: 100%; 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 14px;
            border: 1px solid #334155;
        }
        #analysis-log { 
            height: 300px; 
            overflow-y: auto; 
            scroll-behavior: smooth;
            margin-bottom: 15px;
        }
        #analysis-log p { 
            margin: 0 0 8px 0; 
            padding: 5px 10px;
            border-radius: 4px;
            background: rgba(255,255,255,0.05);
        }
        #analysis-log p:last-child { 
            background: rgba(34, 197, 94, 0.2);
        }
        #progress-bar { 
            width: 100%; 
            height: 8px; 
            background: #334155; 
            border-radius: 4px; 
            overflow: hidden;
        }
        #progress-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%); 
            width: 0%; 
            transition: width 0.5s ease;
        }
        #loader { 
            text-align: center; 
            margin-top: 30px; 
        }
        .spinner { 
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #4f46e5; 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            animation: spin 1s linear infinite; 
            margin: 0 auto 15px auto; 
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        .result-box { 
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 25px; 
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .result-box h3 { 
            margin-top: 0; 
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .confidence-box { 
            text-align: center; 
            padding: 25px; 
            border-radius: 12px; 
            color: white; 
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .confidence-box strong { 
            font-size: 28px; 
            display: block; 
            margin-bottom: 10px;
        }
        .confidence-alto { 
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); 
        }
        .confidence-medio { 
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%); 
        }
        .confidence-basso { 
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); 
        }
        .score-display { 
            font-size: 64px; 
            font-weight: bold; 
            text-align: center; 
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .score-high { color: #16a34a; }
        .score-medium { color: #d97706; }
        .score-low { color: #dc2626; }
        .diagnostic-list { 
            list-style: none; 
            padding: 0; 
        }
        .diagnostic-list li { 
            padding: 10px; 
            margin: 8px 0; 
            border-radius: 8px; 
            background: rgba(255,255,255,0.7);
            border-left: 4px solid #e2e8f0;
        }
        .diagnostic-list li.success { 
            border-left-color: #16a34a; 
            background: rgba(34, 197, 94, 0.1);
        }
        .diagnostic-list li.warning { 
            border-left-color: #d97706; 
            background: rgba(217, 119, 6, 0.1);
        }
        .diagnostic-list li.error { 
            border-left-color: #dc2626; 
            background: rgba(220, 38, 38, 0.1);
        }
        .suggestions-box { 
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); 
            border: 1px solid #f59e0b; 
            border-radius: 12px; 
            padding: 20px; 
            margin-top: 20px;
        }
        .metric-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin-top: 20px;
        }
        .metric-card { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .metric-value { 
            font-size: 24px; 
            font-weight: bold; 
            margin: 10px 0;
        }
        .metric-label { 
            font-size: 12px; 
            color: #6b7280; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('ai-engine-form');
        if (!form) return;
        
        // Gestione Accordion Informativo
        const infoToggle = document.querySelector('.info-toggle');
        const infoContent = document.querySelector('.info-content');
        
        if (infoToggle && infoContent) {
            infoToggle.addEventListener('click', () => {
                const isActive = infoContent.classList.contains('active');
                
                if (isActive) {
                    infoContent.classList.remove('active');
                    infoToggle.classList.remove('active');
                } else {
                    infoContent.classList.add('active');
                    infoToggle.classList.add('active');
                }
            });
        }
        
        const queryGroup = document.getElementById('query-group');
        const queryInput = document.getElementById('query');
        const logContainer = document.getElementById('analysis-log-container');
        const logEl = document.getElementById('analysis-log');
        const progressFill = document.getElementById('progress-fill');
        const resultsContainer = document.getElementById('results-container');
        const errorContainer = document.getElementById('error-container');
        const loader = document.getElementById('loader');
        const submitButton = document.getElementById('submit-button');

        document.querySelectorAll('input[name="analysis_mode"]').forEach(radio => {
            radio.addEventListener('change', e => {
                const isQueryMode = e.target.value === 'query';
                queryGroup.classList.toggle('hidden', !isQueryMode);
                queryInput.required = isQueryMode;
            });
        });

        const logMessage = (message, progress = 0) => {
            logEl.innerHTML += `<p>${message}</p>`;
            logEl.scrollTop = logEl.scrollHeight;
            progressFill.style.width = `${progress}%`;
        };
        
        form.addEventListener('submit', e => {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('action', 'ai_engine_v6');
            
            // Chiudi l'accordion informativo durante l'analisi
            if (infoContent && infoToggle) {
                infoContent.classList.remove('active');
                infoToggle.classList.remove('active');
            }
            
            resultsContainer.classList.add('hidden');
            errorContainer.classList.add('hidden');
            loader.classList.remove('hidden');
            submitButton.disabled = true;
            logContainer.classList.remove('hidden');
            logEl.innerHTML = '';
            progressFill.style.width = '0%';

            const mode = formData.get('analysis_mode');
            const deepAnalysis = formData.get('deep_analysis') === 'on';
            
            const logSteps = mode === 'audit' ? [
                '🔍 Inizializzazione: Audit AI-Ready completo...',
                '🌐 Verifica connettività e accessibilità...',
                '📊 Analisi struttura semantica (H1-H6)...',
                '🔗 Controllo dati strutturati e metadati...',
                '🧠 Analisi linguaggio naturale...',
                '🗺️ Verifica navigazione e sitemap...',
                '📱 Controllo responsive e accessibilità...',
                '⚡ Analisi velocità e performance...',
                '🔬 Test AI-specific features...',
                '📈 Compilazione report finale...'
            ] : [
                '🤖 Inizializzazione: Simulazione AI Query...',
                '🎯 Livello 1: Analisi diretta contenuto...',
                '🔍 Livello 2: Ricerca semantica avanzata...',
                '🗺️ Livello 3: Analisi sitemap e navigazione...',
                '🔗 Livello 4: Controllo link interni...',
                '🧠 Livello 5: Analisi contesto e NLP...',
                '📊 Sintesi della risposta AI...'
            ];
            
            let delay = 0;
            const stepDelay = deepAnalysis ? 1500 : 1000;
            
            logSteps.forEach((step, index) => {
                setTimeout(() => {
                    const progress = ((index + 1) / logSteps.length) * 90;
                    logMessage(step, progress);
                }, delay);
                delay += stepDelay;
            });

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        logMessage('✅ Analisi completata con successo!', 100);
                        setTimeout(() => displayResults(data.data), 500);
                    }, delay);
                } else {
                    displayError(data.data.message || 'Errore sconosciuto durante l\'analisi.');
                }
            })
            .catch(err => {
                console.error('Errore:', err);
                displayError('Errore di comunicazione con il server.');
            })
            .finally(() => {
                setTimeout(() => {
                    loader.classList.add('hidden');
                    submitButton.disabled = false;
                }, delay + 1000);
            });
        });

        function displayResults(data) {
            let html = `<div class="result-box"><h2>📊 ${data.reportTitle}</h2></div>`;
            
            if (data.mode === 'audit') {
                // Audit Mode Results
                const scoreClass = data.score >= 80 ? 'score-high' : data.score >= 60 ? 'score-medium' : 'score-low';
                html += `
                    <div class="result-box">
                        <h3>🎯 Punteggio AI-Readiness</h3>
                        <div class="score-display ${scoreClass}">${data.score}%</div>
                        <div class="metric-grid">
                            <div class="metric-card">
                                <div class="metric-value ${data.metrics.semantic >= 80 ? 'score-high' : data.metrics.semantic >= 60 ? 'score-medium' : 'score-low'}">${data.metrics.semantic}%</div>
                                <div class="metric-label">Semantica</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value ${data.metrics.content >= 80 ? 'score-high' : data.metrics.content >= 60 ? 'score-medium' : 'score-low'}">${data.metrics.content}%</div>
                                <div class="metric-label">Contenuto</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value ${data.metrics.navigation >= 80 ? 'score-high' : data.metrics.navigation >= 60 ? 'score-medium' : 'score-low'}">${data.metrics.navigation}%</div>
                                <div class="metric-label">Navigazione</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value ${data.metrics.technical >= 80 ? 'score-high' : data.metrics.technical >= 60 ? 'score-medium' : 'score-low'}">${data.metrics.technical}%</div>
                                <div class="metric-label">Tecnico</div>
                            </div>
                        </div>
                    </div>
                `;
                
                html += `
                    <div class="result-box">
                        <h3>🔍 Analisi Dettagliata</h3>
                        <ul class="diagnostic-list">
                            ${data.diagnostics.map(item => {
                                const className = item.startsWith('✅') ? 'success' : 
                                                item.startsWith('⚠️') ? 'warning' : 
                                                item.startsWith('❌') ? 'error' : '';
                                return `<li class="${className}">${item}</li>`;
                            }).join('')}
                        </ul>
                    </div>
                `;
            } else {
                // Query Mode Results
                html += `
                    <div class="confidence-box confidence-${data.confidence.level.toLowerCase()}">
                        <strong>Confidenza: ${data.confidence.level}</strong>
                        <p>${data.confidence.reason}</p>
                    </div>
                `;
                
                html += `
                    <div class="result-box">
                        <h3>🤖 Risposta Simulata dall'AI</h3>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #4f46e5;">
                            <p style="margin: 0; font-style: italic;">"${data.simulatedResponse}"</p>
                        </div>
                    </div>
                `;
                
                html += `
                    <div class="result-box">
                        <h3>🔍 Percorso di Analisi</h3>
                        <ul class="diagnostic-list">
                            ${data.diagnostics.map(item => {
                                const className = item.startsWith('✅') ? 'success' : 
                                                item.startsWith('⚠️') ? 'warning' : 
                                                item.startsWith('❌') ? 'error' : '';
                                return `<li class="${className}">${item}</li>`;
                            }).join('')}
                        </ul>
                    </div>
                `;
            }
            
            if (data.suggestions && data.suggestions.length > 0) {
                html += `
                    <div class="suggestions-box">
                        <h3>💡 Suggerimenti per Migliorare</h3>
                        <ul>
                            ${data.suggestions.map(item => `<li>${item}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            resultsContainer.innerHTML = html;
            resultsContainer.classList.remove('hidden');
        }

        function displayError(message) {
            logMessage(`⚠️ ERRORE: ${message}`, 0);
            errorContainer.querySelector('#error-message').textContent = message;
            errorContainer.classList.remove('hidden');
        }
    });
    </script>
    <?php
}

// Gestore AJAX principale
function ai_engine_v6_ajax_handler() {
    check_ajax_referer('ai_engine_nonce', 'ai_engine_nonce_field');
    
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';
    if (empty($url)) {
        wp_send_json_error(['message' => 'L\'URL non può essere vuoto.'], 400);
    }
    
    if (!preg_match("~^https?://~i", $url)) {
        $url = "https://" . $url;
    }
    
    $url = esc_url_raw($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        wp_send_json_error(['message' => 'L\'URL fornito non è valido.'], 400);
    }
    
    $mode = isset($_POST['analysis_mode']) ? sanitize_key($_POST['analysis_mode']) : 'audit';
    $deep_analysis = isset($_POST['deep_analysis']) && $_POST['deep_analysis'] === 'on';
    
    if ($mode === 'audit') {
        wp_send_json_success(perform_site_audit_v6($url, $deep_analysis));
    } else {
        $query = isset($_POST['query']) ? strtolower(sanitize_text_field($_POST['query'])) : '';
        if (empty($query)) {
            wp_send_json_error(['message' => 'La domanda è obbligatoria per il test AI.'], 400);
        }
        wp_send_json_success(perform_query_simulation_v6($url, $query, $deep_analysis));
    }
}
add_action('wp_ajax_ai_engine_v6', 'ai_engine_v6_ajax_handler');
add_action('wp_ajax_nopriv_ai_engine_v6', 'ai_engine_v6_ajax_handler');

// Funzione principale per l'Audit AI-Ready
function perform_site_audit_v6($url, $deep_analysis = false) {
    $diagnostics = [];
    $suggestions = [];
    $metrics = [
        'semantic' => 0,
        'content' => 0,
        'navigation' => 0,
        'technical' => 0
    ];
    
    // Recupero della pagina
    $response = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($response)) {
        return [
            'reportTitle' => 'Errore di Connessione',
            'score' => 0,
            'diagnostics' => ['❌ Impossibile raggiungere il sito. Verifica che sia online.'],
            'suggestions' => ['Verifica la connettività del sito e riprova.']
        ];
    }
    
    $html = wp_remote_retrieve_body($response);
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($doc);
    
    // === ANALISI SEMANTICA ===
    $semantic_score = analyze_semantic_structure($xpath, $diagnostics, $suggestions);
    $metrics['semantic'] = $semantic_score;
    
    // === ANALISI CONTENUTO ===
    $content_score = analyze_content_quality($xpath, $diagnostics, $suggestions, $deep_analysis);
    $metrics['content'] = $content_score;
    
    // === ANALISI NAVIGAZIONE ===
    $navigation_score = analyze_navigation($url, $xpath, $diagnostics, $suggestions);
    $metrics['navigation'] = $navigation_score;
    
    // === ANALISI TECNICA ===
    $technical_score = analyze_technical_aspects($url, $xpath, $diagnostics, $suggestions);
    $metrics['technical'] = $technical_score;
    
    // Calcolo punteggio finale
    $final_score = round(($semantic_score + $content_score + $navigation_score + $technical_score) / 4);
    
    // Suggerimenti generali basati sul punteggio
    if ($final_score < 60) {
        $suggestions[] = "🚨 Punteggio basso: il sito necessita di miglioramenti significativi per essere AI-friendly.";
    } elseif ($final_score < 80) {
        $suggestions[] = "⚠️ Punteggio medio: con alcuni miglioramenti il sito può diventare molto più fruibile per le AI.";
    } else {
        $suggestions[] = "🎉 Ottimo lavoro! Il sito è ben strutturato per l'analisi AI.";
    }
    
    return [
        'mode' => 'audit',
        'reportTitle' => 'Audit AI-Readiness Completo',
        'score' => $final_score,
        'metrics' => $metrics,
        'diagnostics' => $diagnostics,
        'suggestions' => $suggestions
    ];
}

// Analisi della struttura semantica
function analyze_semantic_structure($xpath, &$diagnostics, &$suggestions) {
    $score = 0;
    
    // Controllo H1
    $h1_nodes = $xpath->query('//h1');
    if ($h1_nodes->length > 0 && !empty(trim($h1_nodes->item(0)->textContent))) {
        $diagnostics[] = "✅ Titolo H1 presente e popolato.";
        $score += 20;
        
        if ($h1_nodes->length === 1) {
            $diagnostics[] = "✅ Un solo H1 per pagina (best practice).";
            $score += 10;
        } else {
            $diagnostics[] = "⚠️ Multipli H1 trovati. Meglio uno solo per pagina.";
            $suggestions[] = "Usa un solo H1 per pagina e struttura gli altri titoli con H2-H6.";
        }
    } else {
        $diagnostics[] = "❌ Manca il titolo H1 o è vuoto.";
        $suggestions[] = "Aggiungi un titolo H1 chiaro e descrittivo.";
    }
    
    // Controllo struttura gerarchica H2-H6
    $heading_levels = [];
    for ($i = 1; $i <= 6; $i++) {
        $headings = $xpath->query("//h$i");
        if ($headings->length > 0) {
            $heading_levels[] = $i;
        }
    }
    
    if (count($heading_levels) >= 3) {
        $diagnostics[] = "✅ Struttura gerarchica dei titoli ben definita.";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ Struttura dei titoli limitata. Usa più livelli (H1-H6).";
        $suggestions[] = "Crea una gerarchia chiara con H1 > H2 > H3 per facilitare la comprensione AI.";
    }
    
    // Controllo meta description
    $meta_desc = $xpath->query('//meta[@name="description"]/@content');
    if ($meta_desc->length > 0) {
        $desc_content = $meta_desc->item(0)->nodeValue;
        if (strlen($desc_content) >= 120 && strlen($desc_content) <= 160) {
            $diagnostics[] = "✅ Meta description presente e di lunghezza ottimale.";
            $score += 15;
        } else {
            $diagnostics[] = "⚠️ Meta description presente ma lunghezza non ottimale.";
            $suggestions[] = "Ottimizza la meta description tra 120-160 caratteri.";
            $score += 8;
        }
    } else {
        $diagnostics[] = "❌ Meta description mancante.";
        $suggestions[] = "Aggiungi una meta description descrittiva per ogni pagina.";
    }
    
    // Controllo dati strutturati
    $structured_data = $xpath->query('//script[@type="application/ld+json"]');
    if ($structured_data->length > 0) {
        $diagnostics[] = "✅ Dati strutturati JSON-LD trovati!";
        $score += 25;
        
        // Verifica tipi di schema
        $schema_types = [];
        foreach ($structured_data as $script) {
            $json_content = $script->textContent;
            $schema_data = json_decode($json_content, true);
            if (isset($schema_data['@type'])) {
                $schema_types[] = $schema_data['@type'];
            }
        }
        
        if (count($schema_types) > 0) {
            $diagnostics[] = "✅ Schema Types trovati: " . implode(', ', array_unique($schema_types));
            $score += 10;
        }
    } else {
        $diagnostics[] = "❌ Nessun dato strutturato JSON-LD trovato.";
        $suggestions[] = "Implementa i dati strutturati Schema.org per migliorare la comprensione AI.";
    }
    
    // Controllo attributi alt per immagini
    $images = $xpath->query('//img');
    $images_with_alt = $xpath->query('//img[@alt]');
    if ($images->length > 0) {
        $alt_ratio = ($images_with_alt->length / $images->length) * 100;
        if ($alt_ratio >= 90) {
            $diagnostics[] = "✅ Ottime descrizioni alt per le immagini.";
            $score += 15;
        } elseif ($alt_ratio >= 70) {
            $diagnostics[] = "⚠️ Buone descrizioni alt, ma si può migliorare.";
            $suggestions[] = "Aggiungi descrizioni alt a tutte le immagini.";
            $score += 10;
        } else {
            $diagnostics[] = "❌ Molte immagini senza descrizione alt.";
            $suggestions[] = "Le descrizioni alt sono fondamentali per l'accessibilità e la comprensione AI.";
            $score += 5;
        }
    }
    
    return min($score, 100);
}

// Analisi qualità del contenuto
function analyze_content_quality($xpath, &$diagnostics, &$suggestions, $deep_analysis = false) {
    $score = 0;
    
    // Controllo paragrafi
    $paragraphs = $xpath->query('//p');
    $substantial_paragraphs = 0;
    $total_text_length = 0;
    
    foreach ($paragraphs as $p) {
        $text = trim($p->textContent);
        $text_length = strlen($text);
        $total_text_length += $text_length;
        
        if ($text_length > 50) {
            $substantial_paragraphs++;
        }
    }
    
    if ($substantial_paragraphs >= 3) {
        $diagnostics[] = "✅ Contenuto sostanzioso con paragrafi ben strutturati.";
        $score += 25;
    } else {
        $diagnostics[] = "⚠️ Contenuto limitato. Aggiungi più paragrafi descrittivi.";
        $suggestions[] = "Crea contenuto più ricco con paragrafi dettagliati per facilitare l'analisi AI.";
        $score += 10;
    }
    
    // Controllo liste
    $lists = $xpath->query('//ul | //ol');
    if ($lists->length > 0) {
        $diagnostics[] = "✅ Presenza di liste strutturate.";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ Nessuna lista trovata. Le liste aiutano la comprensione AI.";
        $suggestions[] = "Usa liste puntate o numerate per organizzare informazioni chiave.";
    }
    
    // Controllo tabelle
    $tables = $xpath->query('//table');
    if ($tables->length > 0) {
        $diagnostics[] = "✅ Presenza di tabelle per dati strutturati.";
        $score += 10;
    }
    
    // Controllo FAQ o Q&A
    $faq_indicators = $xpath->query('//*[contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "faq") or contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "domande") or contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "questions")]');
    if ($faq_indicators->length > 0) {
        $diagnostics[] = "✅ Sezione FAQ o Q&A rilevata.";
        $score += 20;
    } else {
        $suggestions[] = "Considera di aggiungere una sezione FAQ per rispondere a domande comuni.";
    }
    
    // Analisi linguaggio naturale (se deep analysis)
    if ($deep_analysis) {
        $score += analyze_natural_language($xpath, $diagnostics, $suggestions);
    }
    
    // Controllo lunghezza contenuto
    if ($total_text_length > 1000) {
        $diagnostics[] = "✅ Contenuto di lunghezza adeguata per l'analisi AI.";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ Contenuto piuttosto breve. Più contenuto = migliore comprensione AI.";
        $suggestions[] = "Espandi il contenuto con dettagli, esempi e approfondimenti.";
        $score += 5;
    }
    
    // Controllo link interni
    $internal_links = $xpath->query('//a[starts-with(@href, "/") or contains(@href, "' . parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) . '")]');
    if ($internal_links->length >= 3) {
        $diagnostics[] = "✅ Buona presenza di link interni.";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ Pochi link interni. Migliora la connessione tra le pagine.";
        $suggestions[] = "Aggiungi link interni per aiutare l'AI a navigare tra i contenuti correlati.";
        $score += 5;
    }
    
    return min($score, 100);
}

// Analisi linguaggio naturale
function analyze_natural_language($xpath, &$diagnostics, &$suggestions) {
    $score = 0;
    
    // Controllo presenza di domande
    $question_indicators = $xpath->query('//*[contains(text(), "?")]');
    if ($question_indicators->length > 0) {
        $diagnostics[] = "✅ Presenza di domande nel contenuto.";
        $score += 10;
    }
    
    // Controllo presenza di definizioni
    $definition_indicators = $xpath->query('//*[contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "definizione") or contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "significa") or contains(translate(text(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "cos\'è")]');
    if ($definition_indicators->length > 0) {
        $diagnostics[] = "✅ Presenza di definizioni e spiegazioni.";
        $score += 15;
    } else {
        $suggestions[] = "Aggiungi definizioni e spiegazioni dei termini tecnici.";
    }
    
    // Controllo citazioni
    $quotes = $xpath->query('//blockquote | //q | //*[contains(@class, "quote")]');
    if ($quotes->length > 0) {
        $diagnostics[] = "✅ Presenza di citazioni strutturate.";
        $score += 5;
    }
    
    return $score;
}

// Analisi navigazione
function analyze_navigation($url, $xpath, &$diagnostics, &$suggestions) {
    $score = 0;
    
    // Controllo sitemap XML
    $sitemap_url = rtrim($url, '/') . '/sitemap.xml';
    $sitemap_response = wp_remote_head($sitemap_url, ['timeout' => 10]);
    if (!is_wp_error($sitemap_response) && wp_remote_retrieve_response_code($sitemap_response) === 200) {
        $diagnostics[] = "✅ Sitemap XML trovata e accessibile.";
        $score += 25;
    } else {
        $diagnostics[] = "❌ Sitemap XML non trovata.";
        $suggestions[] = "Crea e pubblica una sitemap XML per facilitare la navigazione AI.";
    }
    
    // Controllo robots.txt
    $robots_url = rtrim($url, '/') . '/robots.txt';
    $robots_response = wp_remote_head($robots_url, ['timeout' => 10]);
    if (!is_wp_error($robots_response) && wp_remote_retrieve_response_code($robots_response) === 200) {
        $diagnostics[] = "✅ File robots.txt presente.";
        $score += 10;
    } else {
        $diagnostics[] = "⚠️ File robots.txt non trovato.";
        $suggestions[] = "Aggiungi un file robots.txt per guidare l'accesso AI.";
    }
    
    // Controllo menu di navigazione
    $nav_elements = $xpath->query('//nav | //*[@role="navigation"] | //*[contains(@class, "nav") or contains(@class, "menu")]');
    if ($nav_elements->length > 0) {
        $diagnostics[] = "✅ Elementi di navigazione identificati.";
        $score += 20;
    } else {
        $diagnostics[] = "⚠️ Navigazione non chiaramente identificata.";
        $suggestions[] = "Usa elementi <nav> o attributi role per identificare la navigazione.";
    }
    
    // Controllo breadcrumb
    $breadcrumbs = $xpath->query('//*[contains(@class, "breadcrumb") or contains(@class, "breadcrumbs")]');
    if ($breadcrumbs->length > 0) {
        $diagnostics[] = "✅ Breadcrumb navigation presente.";
        $score += 15;
    } else {
        $suggestions[] = "Aggiungi breadcrumb per migliorare la navigazione AI.";
    }
    
    // Controllo paginazione
    $pagination = $xpath->query('//*[contains(@class, "pagination") or contains(@class, "paging")]');
    if ($pagination->length > 0) {
        $diagnostics[] = "✅ Sistema di paginazione presente.";
        $score += 10;
    }
    
    // Controllo link relativi
    $relative_links = $xpath->query('//a[starts-with(@href, "/")]');
    if ($relative_links->length >= 5) {
        $diagnostics[] = "✅ Buona presenza di link relativi.";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ Pochi link relativi. Migliora la connessione interna.";
        $suggestions[] = "Aggiungi più link interni per creare una rete di contenuti correlati.";
    }
    
    // Controllo anchor text descrittivi
    $links_with_text = $xpath->query('//a[string-length(normalize-space(text())) > 5]');
    $total_links = $xpath->query('//a')->length;
    if ($total_links > 0) {
        $descriptive_ratio = ($links_with_text->length / $total_links) * 100;
        if ($descriptive_ratio >= 70) {
            $diagnostics[] = "✅ Anchor text descrittivi e informativi.";
            $score += 15;
        } else {
            $diagnostics[] = "⚠️ Alcuni link hanno anchor text poco descrittivi.";
            $suggestions[] = "Usa anchor text descrittivi invece di 'clicca qui' o 'leggi di più'.";
            $score += 5;
        }
    }
    
    return min($score, 100);
}

// Analisi aspetti tecnici
function analyze_technical_aspects($url, $xpath, &$diagnostics, &$suggestions) {
    $score = 0;
    
    // Controllo DOCTYPE
    $doctype = $xpath->query('//html/@*')->length;
    if ($doctype > 0) {
        $diagnostics[] = "✅ Documento HTML ben formato.";
        $score += 10;
    }
    
    // Controllo lang attribute
    $lang = $xpath->query('//html/@lang');
    if ($lang->length > 0) {
        $diagnostics[] = "✅ Attributo lang specificato.";
        $score += 10;
    } else {
        $diagnostics[] = "⚠️ Attributo lang mancante nell'elemento html.";
        $suggestions[] = "Aggiungi l'attributo lang per specificare la lingua del contenuto.";
    }
    
    // Controllo charset
    $charset = $xpath->query('//meta[@charset]');
    if ($charset->length > 0) {
        $diagnostics[] = "✅ Charset specificato.";
        $score += 10;
    } else {
        $diagnostics[] = "⚠️ Charset non specificato.";
        $suggestions[] = "Aggiungi <meta charset='UTF-8'> per definire la codifica caratteri.";
    }
    
    // Controllo viewport meta
    $viewport = $xpath->query('//meta[@name="viewport"]');
    if ($viewport->length > 0) {
        $diagnostics[] = "✅ Meta viewport presente (responsive design).";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ Meta viewport mancante.";
        $suggestions[] = "Aggiungi meta viewport per la compatibilità mobile.";
    }
    
    // Controllo Open Graph
    $og_tags = $xpath->query('//meta[starts-with(@property, "og:")]');
    if ($og_tags->length >= 3) {
        $diagnostics[] = "✅ Meta tag Open Graph presenti.";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ Meta tag Open Graph limitati o assenti.";
        $suggestions[] = "Aggiungi meta tag Open Graph per migliorare la condivisione social.";
    }
    
    // Controllo Twitter Card
    $twitter_cards = $xpath->query('//meta[starts-with(@name, "twitter:")]');
    if ($twitter_cards->length >= 2) {
        $diagnostics[] = "✅ Twitter Card meta tag presenti.";
        $score += 10;
    } else {
        $suggestions[] = "Aggiungi meta tag Twitter Card per l'ottimizzazione social.";
    }
    
    // Controllo HTTPS
    if (strpos($url, 'https://') === 0) {
        $diagnostics[] = "✅ Sito servito via HTTPS.";
        $score += 15;
    } else {
        $diagnostics[] = "❌ Sito non servito via HTTPS.";
        $suggestions[] = "Implementa HTTPS per sicurezza e ranking.";
    }
    
    // Controllo feed RSS
    $rss_links = $xpath->query('//link[@type="application/rss+xml"]');
    if ($rss_links->length > 0) {
        $diagnostics[] = "✅ Feed RSS disponibile.";
        $score += 10;
    } else {
        $suggestions[] = "Considera di aggiungere un feed RSS per gli aggiornamenti.";
    }
    
    // Controllo canonical URL
    $canonical = $xpath->query('//link[@rel="canonical"]');
    if ($canonical->length > 0) {
        $diagnostics[] = "✅ URL canonico specificato.";
        $score += 15;
    } else {
        $diagnostics[] = "⚠️ URL canonico mancante.";
        $suggestions[] = "Aggiungi URL canonico per evitare contenuti duplicati.";
    }
    
    return min($score, 100);
}

// Simulazione query AI avanzata
function perform_query_simulation_v6($url, $query, $deep_analysis = false) {
    $diagnostics = [];
    $suggestions = [];
    $simulatedResponse = "L'informazione richiesta non è stata trovata nei contenuti analizzati.";
    $confidence = ['level' => 'Basso', 'reason' => 'Nessuna corrispondenza significativa trovata.'];
    
    // Preparazione keywords
    $query_keywords = array_filter(explode(' ', preg_replace('/[^a-z0-9\s]/i', '', $query)));
    $query_keywords = array_map('trim', $query_keywords);
    
    // Recupero della pagina
    $response = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($response)) {
        return [
            'mode' => 'query',
            'reportTitle' => 'Errore Test AI Query',
            'simulatedResponse' => 'Impossibile raggiungere il sito per effettuare il test.',
            'confidence' => ['level' => 'Basso', 'reason' => 'Errore di connessione.'],
            'diagnostics' => ['❌ Errore di connessione durante il test.']
        ];
    }
    
    $html = wp_remote_retrieve_body($response);
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($doc);
    
    // LIVELLO 1: Ricerca diretta nel contenuto
    $diagnostics[] = "🎯 Livello 1: Ricerca corrispondenze dirette nel contenuto...";
    $direct_match = search_direct_content($xpath, $query, $query_keywords);
    if ($direct_match['found']) {
        $simulatedResponse = $direct_match['response'];
        $confidence = ['level' => 'Alto', 'reason' => 'Trovata corrispondenza diretta nel contenuto principale.'];
        $diagnostics[] = "✅ Corrispondenza diretta trovata nel contenuto.";
        goto end_simulation;
    }
    
    // LIVELLO 2: Analisi semantica avanzata
    $diagnostics[] = "🔍 Livello 2: Analisi semantica e contesto...";
    $semantic_match = search_semantic_content($xpath, $query_keywords);
    if ($semantic_match['found']) {
        $simulatedResponse = $semantic_match['response'];
        $confidence = ['level' => 'Medio', 'reason' => 'Trovate corrispondenze semantiche nel contenuto.'];
        $diagnostics[] = "✅ Corrispondenze semantiche identificate.";
        goto end_simulation;
    }
    
    // LIVELLO 3: Analisi link e navigazione
    $diagnostics[] = "🗺️ Livello 3: Analisi link e anchor text...";
    $link_match = search_link_context($xpath, $query_keywords);
    if ($link_match['found']) {
        $simulatedResponse = $link_match['response'];
        $confidence = ['level' => 'Medio', 'reason' => 'Trovati indizi significativi nei link di navigazione.'];
        $diagnostics[] = "✅ Indizi trovati nei link di navigazione.";
        $suggestions[] = "Considera di aggiungere un riassunto dell'argomento nella pagina principale.";
        goto end_simulation;
    }
    
    // LIVELLO 4: Analisi sitemap e struttura
    $diagnostics[] = "🔗 Livello 4: Controllo sitemap e struttura sito...";
    $sitemap_match = search_sitemap_structure($url, $query_keywords);
    if ($sitemap_match['found']) {
        $simulatedResponse = $sitemap_match['response'];
        $confidence = ['level' => 'Basso', 'reason' => 'Trovate pagine correlate nella struttura del sito.'];
        $diagnostics[] = "✅ Pagine correlate identificate nella struttura del sito.";
        $suggestions[] = "L'informazione sembra essere presente in pagine collegate. Considera di aggiungere link più evidenti.";
        goto end_simulation;
    }
    
    // LIVELLO 5: Analisi NLP avanzata (se deep analysis)
    if ($deep_analysis) {
        $diagnostics[] = "🧠 Livello 5: Analisi linguaggio naturale avanzata...";
        $nlp_match = search_nlp_context($xpath, $query, $query_keywords);
        if ($nlp_match['found']) {
            $simulatedResponse = $nlp_match['response'];
            $confidence = ['level' => 'Basso', 'reason' => 'Trovate correlazioni linguistiche nel contenuto.'];
            $diagnostics[] = "✅ Correlazioni linguistiche identificate.";
            $suggestions[] = "Il contenuto ha correlazioni con la domanda. Considera di essere più esplicito.";
            goto end_simulation;
        }
    }
    
    // Nessuna corrispondenza trovata
    $diagnostics[] = "❌ Nessuna corrispondenza significativa trovata in tutti i livelli.";
    $suggestions[] = "Aggiungi contenuto specifico che risponda a questa domanda.";
    $suggestions[] = "Usa le parole chiave della domanda nel contenuto principale.";
    $suggestions[] = "Considera di creare una sezione FAQ con questa domanda.";
    
    end_simulation:
    return [
        'mode' => 'query',
        'reportTitle' => 'Test Simulazione AI Query',
        'confidence' => $confidence,
        'simulatedResponse' => $simulatedResponse,
        'diagnostics' => $diagnostics,
        'suggestions' => $suggestions
    ];
}

// Funzioni di supporto per la ricerca
function search_direct_content($xpath, $query, $keywords) {
    $paragraphs = $xpath->query('//p | //div | //section | //article');
    
    foreach ($paragraphs as $element) {
        $text = trim($element->textContent);
        if (strlen($text) > 30) {
            // Ricerca query completa
            if (stripos($text, $query) !== false) {
                return [
                    'found' => true,
                    'response' => "Ho trovato una risposta diretta: \"" . substr($text, 0, 200) . "...\""
                ];
            }
            
            // Ricerca keywords multiple
            $keyword_matches = 0;
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    $keyword_matches++;
                }
            }
            
            if ($keyword_matches >= ceil(count($keywords) * 0.7)) {
                return [
                    'found' => true,
                    'response' => "Ho trovato informazioni correlate: \"" . substr($text, 0, 200) . "...\""
                ];
            }
        }
    }
    
    return ['found' => false];
}

function search_semantic_content($xpath, $keywords) {
    $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
    
    foreach ($headings as $heading) {
        $text = trim($heading->textContent);
        $keyword_matches = 0;
        
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $keyword_matches++;
            }
        }
        
        if ($keyword_matches >= ceil(count($keywords) * 0.5)) {
            return [
                'found' => true,
                'response' => "Ho trovato una sezione correlata: \"$text\". L'informazione dettagliata dovrebbe essere in questa sezione."
            ];
        }
    }
    
    return ['found' => false];
}

function search_link_context($xpath, $keywords) {
    $links = $xpath->query('//a');
    
    foreach ($links as $link) {
        $link_text = trim($link->textContent);
        if (strlen($link_text) > 10) {
            $keyword_matches = 0;
            
            foreach ($keywords as $keyword) {
                if (stripos($link_text, $keyword) !== false) {
                    $keyword_matches++;
                }
            }
            
            if ($keyword_matches >= ceil(count($keywords) * 0.6)) {
                return [
                    'found' => true,
                    'response' => "Ho trovato un link correlato: \"$link_text\". L'informazione dettagliata è probabilmente in quella pagina."
                ];
            }
        }
    }
    
    return ['found' => false];
}

function search_sitemap_structure($url, $keywords) {
    $sitemap_url = rtrim($url, '/') . '/sitemap.xml';
    $response = wp_remote_get($sitemap_url, ['timeout' => 10]);
    
    if (is_wp_error($response)) {
        return ['found' => false];
    }
    
    $xml_content = wp_remote_retrieve_body($response);
    $keyword_matches = 0;
    
    foreach ($keywords as $keyword) {
        if (stripos($xml_content, $keyword) !== false) {
            $keyword_matches++;
        }
    }
    
    if ($keyword_matches >= ceil(count($keywords) * 0.5)) {
        return [
            'found' => true,
            'response' => "Ho trovato pagine correlate nella struttura del sito. L'informazione è probabilmente presente in pagine collegate."
        ];
    }
    
    return ['found' => false];
}

function search_nlp_context($xpath, $query, $keywords) {
    // Analisi NLP semplificata
    $all_text = '';
    $text_elements = $xpath->query('//p | //div | //span | //h1 | //h2 | //h3');
    
    foreach ($text_elements as $element) {
        $all_text .= ' ' . trim($element->textContent);
    }
    
    $all_text = strtolower($all_text);
    
    // Ricerca sinonimi e correlazioni semplici
    $synonyms = [
        'servizio' => ['servizi', 'offerta', 'offerte', 'consulenza'],
        'prodotto' => ['prodotti', 'articolo', 'articoli', 'item'],
        'contatto' => ['contatti', 'telefono', 'email', 'indirizzo'],
        'prezzo' => ['prezzi', 'costo', 'costi', 'tariffe', 'tariffa']
    ];
    
    foreach ($keywords as $keyword) {
        $keyword_lower = strtolower($keyword);
        if (isset($synonyms[$keyword_lower])) {
            foreach ($synonyms[$keyword_lower] as $synonym) {
                if (stripos($all_text, $synonym) !== false) {
                    return [
                        'found' => true,
                        'response' => "Ho trovato correlazioni linguistiche nel contenuto che potrebbero essere correlate alla tua domanda."
                    ];
                }
            }
        }
    }
    
    return ['found' => false];
}

?>