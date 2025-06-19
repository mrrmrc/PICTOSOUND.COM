<?php
/**
 * Shortcode per l'archivio delle generazioni - VERSIONE MIGLIORATA.
 */
function pictosound_cm_generations_archive_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ps-archive-login-prompt">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 20px 0;">
                <h3 style="margin: 0 0 15px 0;">🔒 Accesso Richiesto</h3>
                <p style="margin: 0 0 20px 0;">Effettua il login per vedere le tue creazioni musicali</p>
                <a href="' . esc_url(wp_login_url(get_permalink())) . '" style="background: white; color: #667eea; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">🚀 ACCEDI ORA</a>
            </div>
        </div>';
    }

    ob_start();

    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'ps_generations';
    
    // ⚡ DEBUG: Verifica che la tabella esista
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        echo '<div class="pictosound-no-table-error" style="background: #f8d7da; padding: 20px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
            <h3>⚠️ Tabella Generazioni Non Trovata</h3>
            <p><strong>ERRORE TECNICO:</strong> La tabella del database <code>' . esc_html($table_name) . '</code> non esiste.</p>
            <p><strong>SOLUZIONE:</strong> Disattiva e riattiva il plugin Pictosound Credits Manager per creare le tabelle necessarie.</p>
            <div style="margin-top: 15px; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <small><strong>Per sviluppatori:</strong> Esegui <code>pictosound_cm_create_generations_table()</code> o riattiva il plugin.</small>
            </div>
        </div>';
        return ob_get_clean();
    }

    // Query per recuperare le generazioni
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id);
    $generations = $wpdb->get_results($query);
    
    // ⚡ DEBUG: Log della query
    if (defined('WP_DEBUG') && WP_DEBUG) {
        write_log_cm("DEBUG Archive: Query eseguita per user $user_id, risultati trovati: " . count($generations));
    }

    echo '<div class="pictosound-generations-archive-wrapper">';
    
    if ($generations && count($generations) > 0) {
        echo '<div style="text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 40px; border-radius: 15px;">
            <div style="font-size: 3rem; margin-bottom: 15px;">🎵</div>
            <h2 style="margin: 0 0 10px 0; font-size: 2rem;">Le Tue Creazioni Musicali</h2>
            <p style="margin: 0; opacity: 0.9;">Hai creato ' . count($generations) . ' tracce musicali uniche</p>
        </div>';
        
        echo '<div class="ps-generation-grid">';
        
        foreach ($generations as $generation) {
            echo '<div class="generation-card">';
            
            // ⚡ GESTIONE AVANZATA DELLE IMMAGINI
            $image_url = $generation->image_url ?? '';
            $valid_image_url = '';
            $debug_info = '';
            $image_status = 'missing';
            
            // Debug dettagliato per sviluppatori
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $debug_info = '<div class="debug-info" style="font-size: 11px; color: #999; margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px;">';
                $debug_info .= '<strong>DEBUG:</strong><br>';
                $debug_info .= 'ID Generazione: ' . $generation->id . '<br>';
                $debug_info .= 'Image URL DB: "' . esc_html($image_url) . '"<br>';
            }
            
            // ⚡ LOGICA MIGLIORATA PER TROVARE L'IMMAGINE
            if (!empty($image_url)) {
                // Caso 1: URL completo e valido
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $valid_image_url = $image_url;
                    $image_status = 'url_complete';
                } 
                // Caso 2: Percorso relativo che inizia con /
                elseif (strpos($image_url, '/') === 0) {
                    $valid_image_url = home_url($image_url);
                    $image_status = 'path_relative';
                }
                // Caso 3: Solo nome file
                elseif (!empty($image_url) && !strpos($image_url, '/')) {
                    // Prova diverse directory possibili
                    $possible_paths = [
                        content_url('/uploads/pictosound_images/' . basename($image_url)),
                        wp_upload_dir()['baseurl'] . '/pictosound_images/' . basename($image_url),
                        wp_upload_dir()['baseurl'] . '/' . basename($image_url)
                    ];
                    
                    foreach ($possible_paths as $path) {
                        // Verifica se il file esiste (test HTTP per URL remoti)
                        $headers = @get_headers($path);
                        if ($headers && strpos($headers[0], '200') !== false) {
                            $valid_image_url = $path;
                            $image_status = 'found_in_' . basename(dirname($path));
                            break;
                        }
                    }
                    
                    if (!$valid_image_url) {
                        $valid_image_url = $possible_paths[0]; // Usa il primo come fallback
                        $image_status = 'fallback_first';
                    }
                }
                // Caso 4: Percorso locale completo
                else {
                    $valid_image_url = $image_url;
                    $image_status = 'as_is';
                }
                
                // Verifica finale dell'esistenza del file per percorsi locali
                if ($valid_image_url && strpos($valid_image_url, home_url()) === 0) {
                    $local_path = str_replace(home_url(), ABSPATH, $valid_image_url);
                    $file_exists = file_exists($local_path);
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $debug_info .= 'Percorso locale: ' . $local_path . '<br>';
                        $debug_info .= 'File esiste: ' . ($file_exists ? 'SÌ' : 'NO') . '<br>';
                        $debug_info .= 'Status: ' . $image_status . '<br>';
                    }
                    
                    if (!$file_exists) {
                        $image_status = 'file_not_found';
                    }
                }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $debug_info .= 'URL finale: "' . esc_html($valid_image_url) . '"<br>';
                $debug_info .= 'Status finale: ' . $image_status;
                $debug_info .= '</div>';
            }
            
            // ⚡ RENDERING DELL'IMMAGINE O PLACEHOLDER
            echo '<div class="generation-thumbnail">';
            echo '<a href="#" class="open-generation-modal" 
                     data-full-image-url="' . esc_url($valid_image_url) . '" 
                     data-audio-url="' . esc_url($generation->audio_url) . '" 
                     data-prompt="' . esc_attr($generation->prompt) . '"
                     data-generation-id="' . esc_attr($generation->id) . '">';
            
            if ($valid_image_url && in_array($image_status, ['url_complete', 'path_relative', 'found_in_pictosound_images', 'found_in_uploads'])) {
                echo '<img src="' . esc_url($valid_image_url) . '" 
                          alt="Immagine per: ' . esc_attr($generation->prompt) . '"
                          loading="lazy" 
                          onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';" 
                          style="width: 100%; height: 200px; object-fit: cover; border-radius: 12px;" />';
                echo '<div class="image-fallback" style="display: none; width: 100%; height: 200px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 12px; color: white; font-size: 3rem; align-items: center; justify-content: center; flex-direction: column;">
                        <div>🎵</div>
                        <div style="font-size: 0.8rem; margin-top: 8px;">Immagine non disponibile</div>
                      </div>';
            } else {
                // Placeholder predefinito con stile migliorato
                echo '<div class="image-placeholder" style="width: 100%; height: 200px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-direction: column;">
                        <div style="font-size: 3rem; margin-bottom: 8px;">🎵</div>
                        <div style="font-size: 0.9rem; font-weight: 500;">Creazione Musicale</div>
                        <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 4px;">ID: ' . $generation->id . '</div>
                      </div>';
            }
            
            echo '</a>';
            echo '</div>';
            
            // ⚡ DETTAGLI DELLA GENERAZIONE
            echo '<div class="generation-details">';
            echo '<h4 class="generation-prompt" style="margin: 15px 0 10px 0; font-size: 1.1rem; color: #333; line-height: 1.4;">' . esc_html($generation->prompt) . '</h4>';
            
            echo '<div class="generation-meta" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; font-size: 0.9rem; color: #666;">';
            echo '<span><strong>📅 Data:</strong> ' . date_i18n('d/m/Y H:i', strtotime($generation->created_at)) . '</span>';
            echo '<span><strong>⏱️ Durata:</strong> ' . esc_html($generation->duration) . 's</span>';
            echo '<span><strong>💎 Crediti:</strong> ' . esc_html($generation->credits_used ?? 0) . '</span>';
            echo '<span><strong>📊 Status:</strong> ' . esc_html($generation->generation_status ?? 'completed') . '</span>';
            echo '</div>';
            
            // Debug info per sviluppatori
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo $debug_info;
            }
            
            echo '</div>';
            
            // ⚡ PLAYER E AZIONI
            echo '<div class="generation-actions" style="margin-top: 15px;">';
            echo '<audio class="generation-audio-player" controls preload="none" style="width: 100%; margin-bottom: 10px;">
                    <source src="' . esc_url($generation->audio_url) . '" type="audio/mpeg">
                    Il tuo browser non supporta l\'audio HTML5.
                  </audio>';
            
            echo '<div class="action-buttons" style="display: flex; gap: 8px;">';
            echo '<a href="' . esc_url($generation->audio_url) . '" 
                     class="download-button-archive" 
                     download 
                     style="background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; flex: 1; text-align: center;">
                     📥 Scarica Audio
                  </a>';
            
            if ($valid_image_url) {
                echo '<a href="' . esc_url($valid_image_url) . '" 
                         class="download-image-button" 
                         download 
                         style="background: linear-gradient(45deg, #007cba, #0099d4); color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; flex: 1; text-align: center;">
                         🖼️ Scarica Img
                      </a>';
            }
            
            echo '</div>';
            echo '</div>';
            
            echo '</div>'; // Fine generation-card
        }
        
        echo '</div>'; // Fine ps-generation-grid
        
    } else {
        echo '<div class="pictosound-no-generations" style="text-align: center; background: linear-gradient(135deg, #ffc107, #ff8c00); color: white; padding: 50px 30px; border-radius: 15px;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🎼</div>
            <h3 style="margin: 0 0 15px 0; font-size: 2rem;">Nessuna Creazione Trovata</h3>
            <p style="margin: 0 0 25px 0; font-size: 1.1rem; opacity: 0.9;">Non hai ancora creato nessuna traccia musicale AI.</p>
            <a href="/" style="background: white; color: #ff8c00; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 1.1rem; display: inline-block;">🚀 INIZIA ORA!</a>
        </div>';
    }
    
    echo '</div>'; // Fine pictosound-generations-archive-wrapper
    
    // ⚡ MODAL PER VISUALIZZAZIONE COMPLETA
    ?>
    <div id="ps-generation-modal" class="ps-modal-overlay" style="display: none;">
        <div class="ps-modal-content">
            <span class="ps-modal-close" style="position: absolute; top: 15px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; z-index: 1001;">&times;</span>
            
            <div id="ps-modal-image-container" style="text-align: center; margin-bottom: 20px;">
                <img id="ps-modal-image" src="" style="max-width: 100%; max-height: 60vh; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);" />
            </div>
            
            <div id="ps-modal-audio-container" style="text-align: center;">
                <h4 id="ps-modal-prompt" style="margin: 0 0 15px 0; color: #333; font-size: 1.2rem;"></h4>
                <div id="ps-modal-meta" style="margin-bottom: 20px; color: #666; font-size: 0.9rem;"></div>
                <!-- Audio player verrà inserito dinamicamente qui -->
            </div>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('pictosound_generations_archive', 'pictosound_cm_generations_archive_shortcode');

/**
 * ⚡ CSS e JS MIGLIORATI per l'archivio.
 */
function pictosound_cm_archive_styles_and_scripts() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pictosound_generations_archive')) {
        ?>
        <style>
        /* ⚡ STILI MODERNI PER L'ARCHIVIO GENERAZIONI */
        .pictosound-generations-archive-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .ps-generation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .generation-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .generation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .generation-thumbnail {
            margin-bottom: 15px;
            cursor: pointer;
            overflow: hidden;
            border-radius: 12px;
        }
        
        .generation-thumbnail a {
            display: block;
            text-decoration: none;
        }
        
        .generation-thumbnail img {
            transition: transform 0.3s ease;
        }
        
        .generation-thumbnail:hover img {
            transform: scale(1.05);
        }
        
        .generation-prompt {
            font-weight: 600;
            color: #333;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        
        .generation-meta {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 15px;
        }
        
        .generation-audio-player {
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .download-button-archive,
        .download-image-button {
            transition: all 0.2s ease;
            text-decoration: none !important;
        }
        
        .download-button-archive:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.3);
        }
        
        .download-image-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,124,186,0.3);
        }
        
        /* ⚡ MODAL MIGLIORATO */
        .ps-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .ps-modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 90vw;
            max-height: 90vh;
            position: relative;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow-y: auto;
        }
        
        .ps-modal-close {
            transition: color 0.2s ease;
        }
        
        .ps-modal-close:hover {
            color: #dc3545 !important;
        }
        
        /* ⚡ DEBUG INFO */
        .debug-info {
            border-left: 3px solid #ffc107;
            font-family: 'Courier New', monospace;
        }
        
        /* ⚡ RESPONSIVE */
        @media (max-width: 768px) {
            .ps-generation-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .generation-card {
                padding: 15px;
            }
            
            .ps-modal-content {
                padding: 25px;
                margin: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .pictosound-generations-archive-wrapper {
                padding: 15px;
            }
            
            .generation-meta {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Pictosound Archive: Script inizializzato');
            
            // ⚡ GESTIONE MODAL MIGLIORATA
            document.body.addEventListener('click', function(e) {
                const trigger = e.target.closest('.open-generation-modal');
                if (!trigger) return;
                
                e.preventDefault();
                
                const modal = document.getElementById('ps-generation-modal');
                if (!modal) {
                    console.error('Pictosound Archive: Modal non trovato');
                    return;
                }
                
                // Estrae i dati dal trigger
                const imageUrl = trigger.dataset.fullImageUrl;
                const audioUrl = trigger.dataset.audioUrl;
                const prompt = trigger.dataset.prompt;
                const generationId = trigger.dataset.generationId;
                
                console.log('Pictosound Archive: Apertura modal per generazione', generationId);
                
                // Aggiorna l'immagine del modal
                const modalImage = modal.querySelector('#ps-modal-image');
                const imageContainer = modal.querySelector('#ps-modal-image-container');
                
                if (imageUrl && imageUrl !== '') {
                    modalImage.src = imageUrl;
                    modalImage.style.display = 'block';
                    modalImage.onerror = function() {
                        console.warn('Pictosound Archive: Errore caricamento immagine:', imageUrl);
                        imageContainer.innerHTML = '<div style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 60px; border-radius: 12px; font-size: 3rem;">🎵<br><span style="font-size: 1rem;">Immagine non disponibile</span></div>';
                    };
                } else {
                    imageContainer.innerHTML = '<div style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 60px; border-radius: 12px; font-size: 3rem;">🎵<br><span style="font-size: 1rem;">Creazione Musicale</span></div>';
                }
                
                // Aggiorna il contenuto testuale
                modal.querySelector('#ps-modal-prompt').textContent = prompt;
                
                // Crea il player audio
                const audioContainer = modal.querySelector('#ps-modal-audio-container');
                const existingAudio = audioContainer.querySelector('audio');
                if (existingAudio) {
                    existingAudio.remove();
                }
                
                if (audioUrl) {
                    const audioPlayer = document.createElement('audio');
                    audioPlayer.controls = true;
                    audioPlayer.autoplay = false; // Cambiato per evitare autoplay indesiderato
                    audioPlayer.style.width = '100%';
                    audioPlayer.style.maxWidth = '400px';
                    audioPlayer.style.marginTop = '15px';
                    
                    const source = document.createElement('source');
                    source.src = audioUrl;
                    source.type = 'audio/mpeg';
                    audioPlayer.appendChild(source);
                    
                    audioPlayer.onerror = function() {
                        console.error('Pictosound Archive: Errore caricamento audio:', audioUrl);
                        audioContainer.insertAdjacentHTML('beforeend', '<p style="color: #dc3545; margin-top: 15px;">⚠️ Errore nel caricamento dell\'audio</p>');
                    };
                    
                    audioContainer.appendChild(audioPlayer);
                }
                
                // Mostra il modal
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Previene lo scroll della pagina
            });
            
            // ⚡ GESTIONE CHIUSURA MODAL
            const modal = document.getElementById('ps-generation-modal');
            if (modal) {
                const closeModal = () => {
                    modal.style.display = 'none';
                    document.body.style.overflow = ''; // Ripristina lo scroll
                    
                    // Ferma l'audio se in riproduzione
                    const audio = modal.querySelector('audio');
                    if (audio) {
                        audio.pause();
                        audio.currentTime = 0;
                    }
                    
                    console.log('Pictosound Archive: Modal chiuso');
                };
                
                // Chiusura con X
                const closeButton = modal.querySelector('.ps-modal-close');
                if (closeButton) {
                    closeButton.addEventListener('click', closeModal);
                }
                
                // Chiusura cliccando fuori
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
                
                // Chiusura con ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && modal.style.display === 'flex') {
                        closeModal();
                    }
                });
            }
            
            console.log('Pictosound Archive: Event listeners configurati');
        });
        </script>
        <?php
    }
}

add_action('wp_footer', 'pictosound_cm_archive_styles_and_scripts');
?>