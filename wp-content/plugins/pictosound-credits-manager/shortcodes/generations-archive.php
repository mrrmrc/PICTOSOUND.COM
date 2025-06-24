<?php
/**
 * Shortcode per l'archivio delle generazioni.
 * VERSIONE CON LAYOUT OTTIMIZZATO E PAGINAZIONE - IMMAGINE A SINISTRA, PLAYER A DESTRA
 */
function pictosound_cm_generations_archive_shortcode() {
    if (!is_user_logged_in()) {
        return '<div id="pictosound-archive-container" class="ps-archive-login-prompt"><p>Devi effettuare il <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> per vedere le tue creazioni musicali.</p></div>';
    }

    ob_start();

    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'ps_generations';
    
    // Configurazione paginazione
    $items_per_page = 4;
    $current_page = isset($_GET['ps_page']) ? max(1, intval($_GET['ps_page'])) : 1;
    $offset = ($current_page - 1) * $items_per_page;
    
    // Query per contare il totale degli elementi
    $count_query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id);
    $total_items = $wpdb->get_var($count_query);
    $total_pages = ceil($total_items / $items_per_page);
    
    // Query con paginazione
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", $user_id, $items_per_page, $offset);
    $generations = $wpdb->get_results($query);

    // Contenitore principale con ID unico per isolare gli stili
    echo '<div id="pictosound-archive-container">';
    echo '<div class="pictosound-generations-archive-wrapper">';
    
    if ($total_items > 0) {
        echo '<h3>Le Tue Creazioni Musicali</h3>';
        
        // Informazioni sulla paginazione
        $start_item = $offset + 1;
        $end_item = min($offset + $items_per_page, $total_items);
        echo '<div class="ps-pagination-info">Mostrando ' . $start_item . '-' . $end_item . ' di ' . $total_items . ' creazioni</div>';
        
        echo '<ul class="ps-generation-list">';

        foreach ($generations as $generation) {
            // Stili inline per garantire priorità assoluta
            $item_style = 'display: flex !important; flex-direction: row !important; align-items: flex-start !important; gap: 20px !important; padding: 20px !important; margin-bottom: 15px !important; background: #ffffff !important; border: 1px solid #eef0f3 !important; border-radius: 12px !important; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04) !important; list-style: none !important; width: 100% !important; box-sizing: border-box !important;';
            $thumbnail_style = 'width: 120px !important; height: 120px !important; flex-shrink: 0 !important; border-radius: 10px !important; overflow: hidden !important; display: block !important;';
            $content_style = 'flex: 1 !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; min-height: 120px !important; min-width: 0 !important;';
            $player_style = 'display: flex !important; align-items: center !important; gap: 15px !important; flex-wrap: wrap !important; margin: 0 !important; padding: 0 !important;';
            
            echo '<li class="generation-item" style="' . $item_style . '">';
        
            // --- Blocco 1: Miniatura (a sinistra) ---
            echo '<div class="generation-thumbnail" style="' . $thumbnail_style . '">';
                $valid_image_url = '';
                if (!empty($generation->image_url) && filter_var($generation->image_url, FILTER_VALIDATE_URL)) {
                    $valid_image_url = $generation->image_url;
                }
            
                // Se c'è un'immagine, crea un link diretto. Altrimenti, solo il placeholder.
                if ($valid_image_url) {
                    echo '<a href="' . esc_url($valid_image_url) . '" target="_blank" rel="noopener noreferrer" title="Visualizza immagine ingrandita">';
                    echo '<img src="' . esc_url($valid_image_url) . '" loading="lazy" alt="Miniatura per ' . esc_attr($generation->prompt) . '" style="display: block !important; width: 100% !important; height: 100% !important; object-fit: cover !important; border: none !important;" />';
                    echo '</a>';
                } else {
                    echo '<div class="ps-no-image-placeholder" style="background: linear-gradient(45deg, #667eea, #764ba2) !important; display: flex !important; align-items: center !important; justify-content: center !important; color: white !important; font-size: 2.5rem !important; width: 100% !important; height: 100% !important;">🎵</div>';
                }
            echo '</div>';
        
            // --- Blocco 2: Contenuto e Player (a destra) ---
            echo '<div class="generation-content" style="' . $content_style . '">';
                echo '<div class="generation-info">';
                    echo '<strong class="generation-prompt" style="font-size: 1.1rem !important; font-weight: 600 !important; color: #1a202c !important; line-height: 1.4 !important; margin: 0 0 8px 0 !important; display: block !important;">' . esc_html($generation->prompt) . '</strong>';
                    echo '<div class="generation-meta" style="font-size: 0.85rem !important; color: #64748b !important; display: flex !important; gap: 20px !important; flex-wrap: wrap !important;">';
                        echo '<span><strong>Data:</strong> ' . date_i18n(get_option('date_format'), strtotime($generation->created_at)) . '</span>';
                        echo '<span><strong>Durata:</strong> ' . esc_html($generation->duration) . 's</span>';
                    echo '</div>';
                echo '</div>';
                
                echo '<div class="generation-player-section" style="' . $player_style . '">';
                    echo '<audio class="original-audio-player" controls preload="none" src="' . esc_url($generation->audio_url) . '" style="flex: 1 !important; min-width: 250px !important; max-width: 350px !important; height: 45px !important; margin: 0 !important;"></audio>';
                    
                    // Container per i pulsanti
                    echo '<div class="download-buttons-container" style="display: flex !important; gap: 8px !important; flex-wrap: wrap !important;">';
                        echo '<a href="' . esc_url($generation->audio_url) . '" class="download-button-archive download-mp3" download style="background: #667eea !important; color: white !important; padding: 8px 12px !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 0.8rem !important; white-space: nowrap !important; border: none !important;">🎵 MP3</a>';
                        
                        echo '<button class="download-button-archive download-qr" onclick="downloadQRCode(\'' . esc_js($generation->audio_url) . '\', \'qr_' . $generation->id . '\')" style="background: #28a745 !important; color: white !important; padding: 8px 12px !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 0.8rem !important; white-space: nowrap !important; border: none !important; cursor: pointer !important;">📱 QR</button>';
                        
                        if ($valid_image_url) {
                            echo '<button class="download-button-archive download-combo" onclick="downloadImageWithQR(\'' . esc_js($generation->audio_url) . '\', \'' . esc_js($valid_image_url) . '\', \'' . esc_js($generation->prompt) . '\', \'' . esc_js(date_i18n(get_option('date_format'), strtotime($generation->created_at))) . '\', \'combo_' . $generation->id . '\')" style="background: #fd7e14 !important; color: white !important; padding: 8px 12px !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 0.8rem !important; white-space: nowrap !important; border: none !important; cursor: pointer !important;">🖼️ IMG+QR</button>';
                        }
                    echo '</div>';
                echo '</div>';
            echo '</div>';
            
            echo '</li>';
        }

        echo '</ul>';
        
        // Controlli di paginazione
        if ($total_pages > 1) {
            echo '<div class="ps-pagination-controls">';
            
            $current_url = remove_query_arg('ps_page');
            
            // Pulsante Precedente
            if ($current_page > 1) {
                $prev_url = add_query_arg('ps_page', $current_page - 1, $current_url);
                echo '<a href="' . esc_url($prev_url) . '" class="ps-pagination-btn ps-prev-btn">« Precedente</a>';
            }
            
            // Numeri delle pagine
            echo '<div class="ps-pagination-numbers">';
            
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1) {
                $page_url = add_query_arg('ps_page', 1, $current_url);
                echo '<a href="' . esc_url($page_url) . '" class="ps-pagination-number">1</a>';
                if ($start_page > 2) {
                    echo '<span class="ps-pagination-dots">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    echo '<span class="ps-pagination-number ps-current-page">' . $i . '</span>';
                } else {
                    $page_url = add_query_arg('ps_page', $i, $current_url);
                    echo '<a href="' . esc_url($page_url) . '" class="ps-pagination-number">' . $i . '</a>';
                }
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="ps-pagination-dots">...</span>';
                }
                $page_url = add_query_arg('ps_page', $total_pages, $current_url);
                echo '<a href="' . esc_url($page_url) . '" class="ps-pagination-number">' . $total_pages . '</a>';
            }
            
            echo '</div>';
            
            // Pulsante Successivo
            if ($current_page < $total_pages) {
                $next_url = add_query_arg('ps_page', $current_page + 1, $current_url);
                echo '<a href="' . esc_url($next_url) . '" class="ps-pagination-btn ps-next-btn">Successivo »</a>';
            }
            
            echo '</div>';
        }
        
    } else {
        echo '<div class="pictosound-no-generations"><h3>Nessuna Creazione Trovata</h3><p>Non hai ancora creato nessuna traccia musicale. <a href="/">Inizia ora!</a></p></div>';
    }
    echo '</div>';
    echo '</div>'; // Chiusura del contenitore principale
    
    // JavaScript inline direttamente nello shortcode
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>
    <script>
    // Funzione per scaricare solo il QR code
    function downloadQRCode(audioUrl, filename) {
        console.log('🔵 Avvio downloadQRCode:', audioUrl, filename);
        
        // Verifica che QRCode sia caricato
        if (typeof QRCode === 'undefined') {
            alert('Errore: Libreria QR Code non caricata. Ricarica la pagina.');
            return;
        }
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Imposta dimensioni del canvas per il QR
        canvas.width = 300;
        canvas.height = 300;
        
        // Sfondo bianco
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        console.log('🔵 Generando QR per:', audioUrl);
        
        // Genera QR code
        QRCode.toCanvas(canvas, audioUrl, {
            width: 300,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#ffffff'
            }
        }, function (error) {
            if (error) {
                console.error('❌ Errore generazione QR:', error);
                alert('Errore nella generazione del QR code: ' + error.message);
                return;
            }
            
            console.log('✅ QR generato, avvio download...');
            
            // Scarica l'immagine
            try {
                const link = document.createElement('a');
                link.download = filename + '_qr.png';
                link.href = canvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                console.log('✅ Download QR completato');
            } catch (downloadError) {
                console.error('❌ Errore download:', downloadError);
                alert('Errore durante il download: ' + downloadError.message);
            }
        });
    }

    // Funzione per scaricare immagine + QR + watermark
    function downloadImageWithQR(audioUrl, imageUrl, prompt, date, filename) {
        console.log('🟠 Avvio downloadImageWithQR:', {audioUrl, imageUrl, prompt, date, filename});
        
        // Verifica che QRCode sia caricato
        if (typeof QRCode === 'undefined') {
            alert('Errore: Libreria QR Code non caricata. Ricarica la pagina.');
            return;
        }
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Carica l'immagine originale
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = function() {
            console.log('🟠 Immagine caricata:', img.width, 'x', img.height);
            
            // Dimensioni canvas basate sull'immagine
            const imgWidth = img.width;
            const imgHeight = img.height;
            const qrSize = Math.min(150, imgWidth * 0.2);
            const bottomSpace = 80;
            
            canvas.width = imgWidth;
            canvas.height = imgHeight + bottomSpace;
            
            // Disegna l'immagine originale
            ctx.drawImage(img, 0, 0, imgWidth, imgHeight);
            
            // Sfondo per la zona del QR e testo
            ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
            ctx.fillRect(0, imgHeight, imgWidth, bottomSpace);
            
            console.log('🟠 Generando QR per immagine combinata...');
            
            // Genera QR code in un canvas temporaneo
            const qrCanvas = document.createElement('canvas');
            QRCode.toCanvas(qrCanvas, audioUrl, {
                width: qrSize,
                margin: 1,
                color: {
                    dark: '#000000',
                    light: '#ffffff'
                }
            }, function (error) {
                if (error) {
                    console.error('❌ Errore generazione QR per immagine:', error);
                    alert('Errore nella generazione del QR code: ' + error.message);
                    return;
                }
                
                console.log('🟠 QR generato, componendo immagine finale...');
                
                try {
                    // Posiziona il QR in basso a sinistra
                    const qrX = 10;
                    const qrY = imgHeight + 10;
                    ctx.drawImage(qrCanvas, qrX, qrY, qrSize, qrSize);
                    
                    // Aggiungi timestamp e watermark accanto al QR
                    const textX = qrX + qrSize + 15;
                    const textY = imgHeight + 25;
                    
                    // Stile del testo
                    ctx.fillStyle = '#333333';
                    ctx.font = 'bold 14px Arial, sans-serif';
                    ctx.fillText(date, textX, textY);
                    
                    ctx.font = '12px Arial, sans-serif';
                    ctx.fillStyle = '#667eea';
                    ctx.fillText('by pictosound.com', textX, textY + 25);
                    
                    // Titolo del prompt se c'è spazio
                    if (prompt && prompt.length > 0) {
                        ctx.font = '11px Arial, sans-serif';
                        ctx.fillStyle = '#666666';
                        const maxWidth = imgWidth - textX - 10;
                        const truncatedPrompt = prompt.length > 50 ? prompt.substring(0, 47) + '...' : prompt;
                        ctx.fillText(truncatedPrompt, textX, textY + 45, maxWidth);
                    }
                    
                    console.log('🟠 Avvio download immagine combinata...');
                    
                    // Scarica l'immagine combinata
                    const link = document.createElement('a');
                    link.download = filename + '_with_qr.png';
                    link.href = canvas.toDataURL('image/png', 0.9);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    console.log('✅ Download immagine combinata completato');
                    
                } catch (processError) {
                    console.error('❌ Errore processing immagine:', processError);
                    alert('Errore durante la creazione dell\'immagine: ' + processError.message);
                }
            });
        };
        
        img.onerror = function() {
            console.error('❌ Errore caricamento immagine:', imageUrl);
            alert('Errore nel caricamento dell\'immagine. URL: ' + imageUrl);
        };
        
        console.log('🟠 Caricamento immagine da:', imageUrl);
        img.src = imageUrl;
    }

    // Test della libreria
    console.log('📋 Stato libreria QRCode:', typeof QRCode);
    if (typeof QRCode === 'undefined') {
        console.warn('⚠️ Libreria QRCode non caricata!');
    } else {
        console.log('✅ Libreria QRCode caricata correttamente');
    }
    </script>
    <?php
    
    return ob_get_clean();
}
add_shortcode('pictosound_generations_archive', 'pictosound_cm_generations_archive_shortcode');

/**
 * CSS per l'archivio - Layout ottimizzato con immagine a sinistra e player a destra + Paginazione.
 */
function pictosound_cm_archive_styles_and_scripts() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pictosound_generations_archive')) {
        ?>
        <style>
        /* =================================
           STILI ARCHIVIO - LAYOUT SIDE BY SIDE + PAGINAZIONE
           PRIORITÀ MASSIMA
           ================================= */

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            display: block !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            gap: 20px !important;
            padding: 20px !important;
            margin: 0 0 15px 0 !important;
            background: #ffffff !important;
            border: 1px solid #eef0f3 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04) !important;
            transition: box-shadow 0.3s ease !important;
            width: 100% !important;
            box-sizing: border-box !important;
            list-style: none !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08) !important;
        }

        /* MINIATURA A SINISTRA - SELETTORI ULTRA SPECIFICI */
        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail {
            width: 120px !important;
            height: 120px !important;
            flex-shrink: 0 !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            display: block !important;
            float: none !important;
            position: relative !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail a,
        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail img,
        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail div.ps-no-image-placeholder {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail img {
            object-fit: cover !important;
            transition: transform 0.3s ease, opacity 0.3s ease !important;
            border: none !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail a:hover img {
            transform: scale(1.05) !important;
            opacity: 0.9 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail div.ps-no-image-placeholder {
            background: linear-gradient(45deg, #667eea, #764ba2) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: white !important;
            font-size: 2.5rem !important;
        }

        /* CONTENUTO A DESTRA - SELETTORI ULTRA SPECIFICI */
        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            min-height: 120px !important;
            min-width: 0 !important;
            width: auto !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-info {
            margin-bottom: 15px !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-info strong.generation-prompt {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            color: #1a202c !important;
            line-height: 1.4 !important;
            margin: 0 0 8px 0 !important;
            display: block !important;
            padding: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-info div.generation-meta {
            font-size: 0.85rem !important;
            color: #64748b !important;
            display: flex !important;
            gap: 20px !important;
            flex-wrap: wrap !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-info div.generation-meta span {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* SEZIONE PLAYER - SELETTORI ULTRA SPECIFICI */
        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-player-section {
            display: flex !important;
            align-items: center !important;
            gap: 15px !important;
            flex-wrap: wrap !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-player-section audio.original-audio-player {
            flex: 1 !important;
            min-width: 250px !important;
            max-width: 350px !important;
            height: 45px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-player-section a.download-button-archive {
            background: #667eea !important;
            color: white !important;
            padding: 10px 18px !important;
            text-decoration: none !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
            font-size: 0.9rem !important;
            transition: background-color 0.3s ease !important;
            white-space: nowrap !important;
            border: none !important;
            outline: none !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-player-section a.download-button-archive:hover {
            background: #5a67d8 !important;
            color: white !important;
            text-decoration: none !important;
        }

        /* STILI PAGINAZIONE */
        div#pictosound-archive-container .ps-pagination-info {
            font-size: 0.9rem !important;
            color: #64748b !important;
            margin: 0 0 20px 0 !important;
            text-align: center !important;
        }

        div#pictosound-archive-container .ps-pagination-controls {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 10px !important;
            margin: 30px 0 0 0 !important;
            flex-wrap: wrap !important;
        }

        div#pictosound-archive-container .ps-pagination-btn {
            background: #667eea !important;
            color: white !important;
            padding: 10px 16px !important;
            text-decoration: none !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            font-size: 0.9rem !important;
            transition: background-color 0.3s ease !important;
            border: none !important;
        }

        div#pictosound-archive-container .ps-pagination-btn:hover {
            background: #5a67d8 !important;
            color: white !important;
            text-decoration: none !important;
        }

        div#pictosound-archive-container .ps-pagination-numbers {
            display: flex !important;
            gap: 5px !important;
            align-items: center !important;
        }

        div#pictosound-archive-container .ps-pagination-number {
            display: inline-block !important;
            padding: 8px 12px !important;
            text-decoration: none !important;
            border-radius: 4px !important;
            font-weight: 500 !important;
            font-size: 0.9rem !important;
            color: #64748b !important;
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            transition: all 0.3s ease !important;
        }

        div#pictosound-archive-container .ps-pagination-number:hover {
            background: #667eea !important;
            color: white !important;
            text-decoration: none !important;
            border-color: #667eea !important;
        }

        div#pictosound-archive-container .ps-pagination-number.ps-current-page {
            background: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
        }

        div#pictosound-archive-container .ps-pagination-dots {
            color: #64748b !important;
            font-weight: bold !important;
            padding: 8px 4px !important;
        }

        /* RESPONSIVE DESIGN - SELETTORI ULTRA SPECIFICI */
        @media (max-width: 768px) {
            div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 15px !important;
                padding: 15px !important;
            }
            
            div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-thumbnail {
                width: 100% !important;
                height: 180px !important;
                align-self: center !important;
                max-width: 180px !important;
            }
            
            div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content {
                min-height: auto !important;
            }
            
            div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-player-section {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
            }
            
            div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-player-section audio.original-audio-player {
                min-width: auto !important;
                max-width: none !important;
            }

            div#pictosound-archive-container .ps-pagination-controls {
                flex-direction: column !important;
                gap: 15px !important;
            }

            div#pictosound-archive-container .ps-pagination-numbers {
                flex-wrap: wrap !important;
                justify-content: center !important;
            }
        }

        @media (max-width: 480px) {
            div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item div.generation-content div.generation-info div.generation-meta {
                display: flex !important;
                flex-direction: column !important;
                gap: 5px !important;
            }
        }

        /* STILI PER MESSAGGIO NESSUNA CREAZIONE - SELETTORI ULTRA SPECIFICI */
        div#pictosound-archive-container div.pictosound-generations-archive-wrapper div.pictosound-no-generations {
            text-align: center !important;
            padding: 40px 20px !important;
            background: #f8fafc !important;
            border-radius: 12px !important;
            border: 1px solid #e2e8f0 !important;
            margin: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper div.pictosound-no-generations h3 {
            color: #2d3748 !important;
            margin: 0 0 10px 0 !important;
            padding: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper div.pictosound-no-generations p {
            color: #64748b !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper div.pictosound-no-generations a {
            color: #667eea !important;
            text-decoration: none !important;
            font-weight: 500 !important;
        }

        div#pictosound-archive-container div.pictosound-generations-archive-wrapper div.pictosound-no-generations a:hover {
            text-decoration: underline !important;
        }

        /* FORCE FLEXBOX LAYOUT - AGGIUNTA FINALE */
        div#pictosound-archive-container div.pictosound-generations-archive-wrapper ul.ps-generation-list li.generation-item {
            display: -webkit-box !important;
            display: -webkit-flex !important;
            display: -moz-box !important;
            display: -ms-flexbox !important;
            display: flex !important;
            -webkit-flex-direction: row !important;
            -moz-flex-direction: row !important;
            -ms-flex-direction: row !important;
            flex-direction: row !important;
            -webkit-align-items: flex-start !important;
            -moz-align-items: flex-start !important;
            -ms-align-items: flex-start !important;
            align-items: flex-start !important;
        }
        </style>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>
        <script>
        // Le stesse funzioni JavaScript del codice originale...
        // (mantenute identiche per brevità)
        </script>
        <?php
    }
}
add_action('wp_footer', 'pictosound_cm_archive_styles_and_scripts');
?>