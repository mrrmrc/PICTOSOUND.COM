<?php
/**
 * Shortcode parametrizzato per l'archivio delle generazioni.
 * VERSIONE CON PARAMETRI CONFIGURABILI E CSS ESTERNO
 */
function pictosound_cm_generations_archive_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div id="pictosound-archive-container" class="ps-archive-login-prompt"><p>Devi effettuare il <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> per vedere le tue creazioni musicali.</p></div>';
    }

    // Parametri dello shortcode con valori di default
    $atts = shortcode_atts(array(
        'layout' => 'scheda',              // 'scheda' o 'griglia'
        'colonne' => '2',                  // numero colonne per layout griglia
        'elementi_per_pagina' => '4',      // elementi per pagina
        'classe_css' => '',                // classe CSS aggiuntiva per il contenitore
        'mostra_info_paginazione' => 'no', // 'si' o 'no'
        'titolo' => ''                     // titolo personalizzabile
    ), $atts, 'pictosound_generations_archive');

    // Sanitizzazione parametri
    $layout = in_array($atts['layout'], ['scheda', 'griglia']) ? $atts['layout'] : 'scheda';
    $colonne = max(1, min(6, intval($atts['colonne'])));
    $elementi_per_pagina = max(1, intval($atts['elementi_per_pagina']));
    $classe_css = sanitize_html_class($atts['classe_css']);
    $mostra_info = $atts['mostra_info_paginazione'] === 'si';
    $titolo = esc_html($atts['titolo']);

    ob_start();

    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'ps_generations';
    
    // Configurazione paginazione
    $current_page = isset($_GET['ps_page']) ? max(1, intval($_GET['ps_page'])) : 1;
    $offset = ($current_page - 1) * $elementi_per_pagina;
    
    // Query per contare il totale degli elementi
    $count_query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id);
    $total_items = $wpdb->get_var($count_query);
    $total_pages = ceil($total_items / $elementi_per_pagina);
    
    // Query con paginazione
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", $user_id, $elementi_per_pagina, $offset);
    $generations = $wpdb->get_results($query);

    // Contenitore principale con classi dinamiche
    $container_classes = [
        'pictosound-archive-container',
        'ps-layout-' . $layout
    ];
    
    if (!empty($classe_css)) {
        $container_classes[] = $classe_css;
    }
    
    if ($layout === 'griglia') {
        $container_classes[] = 'ps-colonne-' . $colonne;
    }

    echo '<div id="pictosound-archive-container" class="' . esc_attr(implode(' ', $container_classes)) . '" data-layout="' . esc_attr($layout) . '" data-colonne="' . esc_attr($colonne) . '">';
    echo '<div class="pictosound-generations-archive-wrapper">';
    
    // Debug: mostra le classi applicate
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<!-- DEBUG ARCHIVIO: Classi = ' . esc_html(implode(' ', $container_classes)) . ' -->';
    }
    
    if ($total_items > 0) {
        if (!empty($titolo)) {
            echo '<h3>' . $titolo . '</h3>';
        }
        
        // Informazioni sulla paginazione
        if ($mostra_info) {
            $start_item = $offset + 1;
            $end_item = min($offset + $elementi_per_pagina, $total_items);
            echo '<div class="ps-pagination-info">Mostrando ' . $start_item . '-' . $end_item . ' di ' . $total_items . ' creazioni</div>';
        }
        
        // Container con classe layout specifica
        $list_classes = ['ps-generation-list'];
        
        echo '<ul class="' . esc_attr(implode(' ', $list_classes)) . '">';

        foreach ($generations as $generation) {
            // Stili inline FORZATI per layout scheda
            if ($layout === 'scheda') {
                $li_style = 'display: flex !important; flex-direction: row !important; align-items: flex-start !important; gap: 20px !important; padding: 20px !important; margin-bottom: 15px !important; background: #ffffff !important; border: 1px solid #eef0f3 !important; border-radius: 12px !important; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04) !important; list-style: none !important; width: 100% !important; box-sizing: border-box !important;';
                echo '<li class="generation-item" style="' . $li_style . '">';
            } else {
                echo '<li class="generation-item">';
            }
            
            if ($layout === 'scheda') {
                renderSchedaLayout($generation);
            } else {
                renderGrigliaLayout($generation);
            }
            
            echo '</li>';
        }

        echo '</ul>';
        
        // Controlli di paginazione
        if ($total_pages > 1) {
            renderPaginazione($current_page, $total_pages);
        }
        
    } else {
        echo '<div class="pictosound-no-generations">';
        echo '<h3>Nessuna Creazione Trovata</h3>';
        echo '<p>Non hai ancora creato nessuna traccia musicale. <a href="/">Inizia ora!</a></p>';
        echo '</div>';
    }
    
    echo '</div>'; // chiusura wrapper
    echo '</div>'; // chiusura container principale
    
    // Localizzazione AJAX direttamente nel shortcode per garantire che sia sempre presente
    ?>
    <script>
    // Definisci pictosoundAjax se non esiste
    if (typeof pictosoundAjax === 'undefined') {
        window.pictosoundAjax = {
            ajaxurl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('pictosound_delete_nonce')); ?>',
            debug: <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false'; ?>
        };
        console.log('✅ PictosoundAjax creato direttamente nel shortcode:', window.pictosoundAjax);
    }
    </script>
    <?php
    
    // JavaScript inline
    renderJavaScript();
    
    return ob_get_clean();
}

/**
 * Renderizza il layout scheda (immagine a sinistra, contenuto a destra)
 */
function renderSchedaLayout($generation) {
    // Stili inline ULTRA-FORZATI per garantire il layout corretto
    $thumbnail_style = 'width: 120px !important; height: 120px !important; flex-shrink: 0 !important; border-radius: 10px !important; overflow: hidden !important; display: block !important; float: none !important; position: relative !important; margin: 0 !important; padding: 0 !important;';
    $content_style = 'flex: 1 !important; display: flex !important; flex-direction: column !important; justify-content: flex-start !important; min-height: 120px !important; min-width: 0 !important; width: auto !important; margin: 0 !important; padding: 0 !important; float: none !important; position: relative !important;';
    
    echo '<div class="generation-thumbnail" style="' . $thumbnail_style . '">';
    renderThumbnail($generation);
    echo '</div>';
    
    echo '<div class="generation-content" style="' . $content_style . '">';
    renderGenerationInfo($generation);
    renderPlayerSection($generation);
    echo '</div>';
}

/**
 * Renderizza il layout griglia (elemento verticale)
 */
function renderGrigliaLayout($generation) {
    // Stili inline per garantire il layout griglia anche se il CSS non funziona
    echo '<div class="generation-thumbnail" style="width: 100% !important; height: 200px !important; overflow: hidden !important; display: block !important;">';
    renderThumbnail($generation);
    echo '</div>';
    
    echo '<div class="generation-content" style="padding: 15px !important; display: flex !important; flex-direction: column !important; gap: 10px !important; flex: 1 !important;">';
    renderGenerationInfo($generation);
    renderPlayerSection($generation);
    echo '</div>';
}

/**
 * Renderizza la miniatura
 */
function renderThumbnail($generation) {
    $valid_image_url = '';
    if (!empty($generation->image_url) && filter_var($generation->image_url, FILTER_VALIDATE_URL)) {
        $valid_image_url = $generation->image_url;
    }

    if ($valid_image_url) {
        echo '<a href="' . esc_url($valid_image_url) . '" target="_blank" rel="noopener noreferrer" title="Visualizza immagine ingrandita">';
        echo '<img src="' . esc_url($valid_image_url) . '" loading="lazy" alt="Miniatura per ' . esc_attr($generation->prompt) . '" style="display: block !important; width: 100% !important; height: 100% !important; object-fit: cover !important; border: none !important;" />';
        echo '</a>';
    } else {
        echo '<div class="ps-no-image-placeholder" style="background: linear-gradient(45deg, #667eea, #764ba2) !important; display: flex !important; align-items: center !important; justify-content: center !important; color: white !important; font-size: 2.5rem !important; width: 100% !important; height: 100% !important;">🎵</div>';
    }
}

/**
 * Renderizza le informazioni della generazione
 */
function renderGenerationInfo($generation) {
    echo '<div class="generation-info" style="text-align: left !important;">';
    
    // Prima riga: Titolo principale e Data/Ora affiancati
    echo '<div class="title-date-row" style="display: flex !important; justify-content: space-between !important; align-items: baseline !important; margin: 0 0 6px 0 !important; gap: 15px !important;">';
    
    // Titolo principale (dal campo title del database)
    if (!empty($generation->title)) {
        echo '<h4 class="generation-title" style="font-size: 1.2rem !important; font-weight: 700 !important; color: #1a202c !important; line-height: 1.3 !important; margin: 0 !important; flex: 1 !important; text-align: left !important;">' . esc_html($generation->title) . '</h4>';
    }
    
    // Data e ora di creazione (affianco al titolo)
    echo '<div class="generation-datetime" style="font-size: 0.8rem !important; color: #64748b !important; white-space: nowrap !important; text-align: right !important; margin: 0 !important;">';
    echo '<span><strong>' . date_i18n(get_option('date_format') . ' H:i', strtotime($generation->created_at)) . '</strong></span>';
    echo '</div>';
    
    echo '</div>'; // chiusura title-date-row
    
    // Seconda riga: Prompt come sottotitolo (sotto al titolo)
    if (!empty($generation->prompt)) {
        echo '<p class="generation-prompt" style="font-size: 0.95rem !important; font-weight: 400 !important; color: #4a5568 !important; line-height: 1.4 !important; margin: 0 0 10px 0 !important; display: block !important; text-align: left !important;">' . esc_html($generation->prompt) . '</p>';
    }
    
    // Terza riga: Durata (solo durata, data è già sopra)
    echo '<div class="generation-meta" style="font-size: 0.85rem !important; color: #64748b !important; text-align: left !important; margin: 0 0 10px 0 !important;">';
    echo '<span><strong>Durata:</strong> ' . esc_html($generation->duration) . 's</span>';
    echo '</div>';
    
    echo '</div>';
}

/**
 * Renderizza la sezione player e download
 */
function renderPlayerSection($generation) {
    $valid_image_url = !empty($generation->image_url) && filter_var($generation->image_url, FILTER_VALIDATE_URL) ? $generation->image_url : '';
    $player_style = 'display: flex !important; align-items: center !important; gap: 15px !important; flex-wrap: wrap !important; margin: 0 !important; padding: 0 !important;';
    
    echo '<div class="generation-player-section" style="' . $player_style . '">';
    echo '<audio class="original-audio-player" controls preload="none" src="' . esc_url($generation->audio_url) . '" style="flex: 1 !important; min-width: 250px !important; max-width: 350px !important; height: 45px !important; margin: 0 !important;"></audio>';
    
    echo '<div class="download-buttons-container" style="display: flex !important; gap: 8px !important; flex-wrap: wrap !important;">';
    echo '<a href="' . esc_url($generation->audio_url) . '" class="download-button-archive download-mp3" download style="background: #667eea !important; color: white !important; padding: 8px 12px !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 0.8rem !important; white-space: nowrap !important; border: none !important;">🎵 MP3</a>';
    echo '<button class="download-button-archive download-qr" onclick="downloadQRCode(\'' . esc_js($generation->audio_url) . '\', \'qr_' . $generation->id . '\')" style="background: #28a745 !important; color: white !important; padding: 8px 12px !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 0.8rem !important; white-space: nowrap !important; border: none !important; cursor: pointer !important;">📱 QR</button>';
    
    if ($valid_image_url) {
        echo '<button class="download-button-archive download-combo" onclick="downloadImageWithQR(\'' . esc_js($generation->audio_url) . '\', \'' . esc_js($valid_image_url) . '\', \'' . esc_js($generation->title ? $generation->title : $generation->prompt) . '\', \'' . esc_js(date_i18n(get_option('date_format') . ' H:i', strtotime($generation->created_at))) . '\', \'combo_' . $generation->id . '\')" style="background: #fd7e14 !important; color: white !important; padding: 8px 12px !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 0.8rem !important; white-space: nowrap !important; border: none !important; cursor: pointer !important;">🖼️ IMG+QR</button>';
    }
    
    // Pulsante Elimina con conferma (ULTIMO)
    echo '<button class="download-button-archive download-delete" data-generation-id="' . intval($generation->id) . '" data-generation-title="' . esc_attr($generation->title ? $generation->title : $generation->prompt) . '" onclick="deleteGeneration(this)" style="background: #dc3545 !important; color: white !important; padding: 8px 12px !important; text-decoration: none !important; border-radius: 6px !important; font-weight: 500 !important; font-size: 0.8rem !important; white-space: nowrap !important; border: none !important; cursor: pointer !important;">🗑️ Elimina</button>';
    
    echo '</div>';
    echo '</div>';
}

/**
 * Renderizza i controlli di paginazione con STILI INLINE FORZATI
 * SOLUZIONE 1: Garantisce allineamento a sinistra su una riga
 */
function renderPaginazione($current_page, $total_pages) {
    // Stili inline ultra-specifici per il container principale
    $pagination_container_style = '
        display: flex !important;
        justify-content: flex-start !important;
        align-items: center !important;
        gap: 10px !important;
        margin: 30px 0 0 0 !important;
        flex-wrap: nowrap !important;
        width: 100% !important;
        position: relative !important;
        clear: both !important;
        overflow: visible !important;
        box-sizing: border-box !important;
        flex-direction: row !important;
        text-align: left !important;
        float: none !important;
    ';
    
    // Stili per i pulsanti prev/next
    $btn_style = '
        background: #667eea !important;
        color: white !important;
        padding: 10px 16px !important;
        text-decoration: none !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        font-size: 0.9rem !important;
        border: none !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        white-space: nowrap !important;
        box-sizing: border-box !important;
        transition: background-color 0.3s ease !important;
        flex-shrink: 0 !important;
        margin: 0 !important;
        line-height: 1.2 !important;
        float: none !important;
        clear: none !important;
        position: relative !important;
    ';
    
    // Stili per il container dei numeri
    $numbers_container_style = '
        display: flex !important;
        align-items: center !important;
        gap: 5px !important;
        flex-shrink: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        flex-wrap: nowrap !important;
        overflow: visible !important;
        width: auto !important;
        float: none !important;
        clear: none !important;
        position: relative !important;
    ';
    
    // Stili per i numeri
    $number_style = '
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 8px 12px !important;
        text-decoration: none !important;
        border-radius: 4px !important;
        font-weight: 500 !important;
        font-size: 0.9rem !important;
        color: #64748b !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        cursor: pointer !important;
        line-height: 1.2 !important;
        box-sizing: border-box !important;
        transition: all 0.3s ease !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
        white-space: nowrap !important;
        float: none !important;
        clear: none !important;
        position: relative !important;
    ';
    
    $current_number_style = '
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 8px 12px !important;
        border-radius: 4px !important;
        font-weight: 500 !important;
        font-size: 0.9rem !important;
        background: #667eea !important;
        color: white !important;
        border: 1px solid #667eea !important;
        line-height: 1.2 !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
        white-space: nowrap !important;
        float: none !important;
        clear: none !important;
        position: relative !important;
    ';
    
    $dots_style = '
        color: #64748b !important;
        font-weight: bold !important;
        padding: 8px 4px !important;
        display: inline-flex !important;
        align-items: center !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
        float: none !important;
        clear: none !important;
        position: relative !important;
    ';
    
    echo '<div class="ps-pagination-controls" style="' . $pagination_container_style . '">';
    
    $current_url = remove_query_arg('ps_page');
    
    // Pulsante Precedente
    if ($current_page > 1) {
        $prev_url = add_query_arg('ps_page', $current_page - 1, $current_url);
        echo '<a href="' . esc_url($prev_url) . '" class="ps-pagination-btn ps-prev-btn" style="' . $btn_style . '" onmouseover="this.style.background=\'#5a67d8\'" onmouseout="this.style.background=\'#667eea\'">« Precedente</a>';
    }
    
    // Container numeri con stili inline
    echo '<div class="ps-pagination-numbers" style="' . $numbers_container_style . '">';
    
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) {
        $page_url = add_query_arg('ps_page', 1, $current_url);
        echo '<a href="' . esc_url($page_url) . '" class="ps-pagination-number" style="' . $number_style . '" onmouseover="this.style.background=\'#667eea\'; this.style.color=\'white\'; this.style.borderColor=\'#667eea\'" onmouseout="this.style.background=\'#f8fafc\'; this.style.color=\'#64748b\'; this.style.borderColor=\'#e2e8f0\'">1</a>';
        if ($start_page > 2) {
            echo '<span class="ps-pagination-dots" style="' . $dots_style . '">...</span>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            echo '<span class="ps-pagination-number ps-current-page" style="' . $current_number_style . '">' . $i . '</span>';
        } else {
            $page_url = add_query_arg('ps_page', $i, $current_url);
            echo '<a href="' . esc_url($page_url) . '" class="ps-pagination-number" style="' . $number_style . '" onmouseover="this.style.background=\'#667eea\'; this.style.color=\'white\'; this.style.borderColor=\'#667eea\'" onmouseout="this.style.background=\'#f8fafc\'; this.style.color=\'#64748b\'; this.style.borderColor=\'#e2e8f0\'">' . $i . '</a>';
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            echo '<span class="ps-pagination-dots" style="' . $dots_style . '">...</span>';
        }
        $page_url = add_query_arg('ps_page', $total_pages, $current_url);
        echo '<a href="' . esc_url($page_url) . '" class="ps-pagination-number" style="' . $number_style . '" onmouseover="this.style.background=\'#667eea\'; this.style.color=\'white\'; this.style.borderColor=\'#667eea\'" onmouseout="this.style.background=\'#f8fafc\'; this.style.color=\'#64748b\'; this.style.borderColor=\'#e2e8f0\'">' . $total_pages . '</a>';
    }
    
    echo '</div>'; // chiusura numbers
    
    // Pulsante Successivo
    if ($current_page < $total_pages) {
        $next_url = add_query_arg('ps_page', $current_page + 1, $current_url);
        echo '<a href="' . esc_url($next_url) . '" class="ps-pagination-btn ps-next-btn" style="' . $btn_style . '" onmouseover="this.style.background=\'#5a67d8\'" onmouseout="this.style.background=\'#667eea\'">Successivo »</a>';
    }
    
    echo '</div>';
}

/**
 * Renderizza il JavaScript
 */
function renderJavaScript() {
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>
    <script>
    // Verifica immediata che pictosoundAjax sia definito
    console.log('🔍 Stato pictosoundAjax all\'avvio:', typeof pictosoundAjax);
    if (typeof pictosoundAjax !== 'undefined') {
        console.log('✅ pictosoundAjax disponibile:', pictosoundAjax);
    } else {
        console.warn('⚠️ pictosoundAjax non definito, sarà creato dal shortcode');
    }
    
    function downloadQRCode(audioUrl, filename) {
        if (typeof QRCode === 'undefined') {
            alert('Errore: Libreria QR Code non caricata. Ricarica la pagina.');
            return;
        }
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 300;
        canvas.height = 300;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        QRCode.toCanvas(canvas, audioUrl, {
            width: 300,
            margin: 2,
            color: { dark: '#000000', light: '#ffffff' }
        }, function (error) {
            if (error) {
                console.error('Errore generazione QR:', error);
                alert('Errore nella generazione del QR code: ' + error.message);
                return;
            }
            
            try {
                const link = document.createElement('a');
                link.download = filename + '_qr.png';
                link.href = canvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (downloadError) {
                console.error('Errore download:', downloadError);
                alert('Errore durante il download: ' + downloadError.message);
            }
        });
    }

    function downloadImageWithQR(audioUrl, imageUrl, prompt, date, filename) {
        if (typeof QRCode === 'undefined') {
            alert('Errore: Libreria QR Code non caricata. Ricarica la pagina.');
            return;
        }
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = function() {
            const imgWidth = img.width;
            const imgHeight = img.height;
            const qrSize = Math.min(150, imgWidth * 0.2);
            const bottomSpace = 80;
            
            canvas.width = imgWidth;
            canvas.height = imgHeight + bottomSpace;
            
            ctx.drawImage(img, 0, 0, imgWidth, imgHeight);
            ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
            ctx.fillRect(0, imgHeight, imgWidth, bottomSpace);
            
            const qrCanvas = document.createElement('canvas');
            QRCode.toCanvas(qrCanvas, audioUrl, {
                width: qrSize,
                margin: 1,
                color: { dark: '#000000', light: '#ffffff' }
            }, function (error) {
                if (error) {
                    console.error('Errore generazione QR per immagine:', error);
                    alert('Errore nella generazione del QR code: ' + error.message);
                    return;
                }
                
                try {
                    const qrX = 10;
                    const qrY = imgHeight + 10;
                    ctx.drawImage(qrCanvas, qrX, qrY, qrSize, qrSize);
                    
                    const textX = qrX + qrSize + 15;
                    const textY = imgHeight + 25;
                    
                    ctx.fillStyle = '#333333';
                    ctx.font = 'bold 14px Arial, sans-serif';
                    ctx.fillText(date, textX, textY);
                    
                    ctx.font = '12px Arial, sans-serif';
                    ctx.fillStyle = '#667eea';
                    ctx.fillText('by pictosound.com', textX, textY + 25);
                    
                    if (prompt && prompt.length > 0) {
                        ctx.font = '11px Arial, sans-serif';
                        ctx.fillStyle = '#666666';
                        const maxWidth = imgWidth - textX - 10;
                        const truncatedPrompt = prompt.length > 50 ? prompt.substring(0, 47) + '...' : prompt;
                        ctx.fillText(truncatedPrompt, textX, textY + 45, maxWidth);
                    }
                    
                    const link = document.createElement('a');
                    link.download = filename + '_with_qr.png';
                    link.href = canvas.toDataURL('image/png', 0.9);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } catch (processError) {
                    console.error('Errore processing immagine:', processError);
                    alert('Errore durante la creazione dell\'immagine: ' + processError.message);
                }
            });
        };
        
        img.onerror = function() {
            console.error('Errore caricamento immagine:', imageUrl);
            alert('Errore nel caricamento dell\'immagine. URL: ' + imageUrl);
        };
        
        img.src = imageUrl;
    }
    
    // Funzione per eliminare una generazione con conferma PERSONALIZZATA
    function deleteGeneration(buttonElement) {
        const generationId = buttonElement.getAttribute('data-generation-id');
        const title = buttonElement.getAttribute('data-generation-title');
        
        console.log('🗑️ Richiesta eliminazione:', generationId, title);
        
        // Crea finestra di conferma personalizzata
        showCustomConfirm(
            'Conferma Eliminazione',
            `Sei sicuro di voler eliminare definitivamente:<br><br><strong>"${title}"</strong><br><br>Questa azione non può essere annullata.`,
            function() {
                // Conferma - procedi con eliminazione
                console.log('✅ Eliminazione confermata, invio richiesta AJAX...');
                executeDelete(generationId, buttonElement);
            },
            function() {
                // Annulla
                console.log('❌ Eliminazione annullata dall\'utente');
            }
        );
    }
    
    // Funzione per mostrare finestra di conferma personalizzata
    function showCustomConfirm(title, message, onConfirm, onCancel) {
        // Rimuovi eventuali finestre esistenti
        const existingModal = document.getElementById('ps-confirm-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Crea la finestra modale
        const modal = document.createElement('div');
        modal.id = 'ps-confirm-modal';
        modal.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            z-index: 10000 !important;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        `;
        
        // Contenuto della finestra
        modal.innerHTML = `
            <div class="ps-confirm-dialog" style="
                background: white !important;
                border-radius: 12px !important;
                padding: 30px !important;
                max-width: 450px !important;
                width: 90% !important;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
                text-align: center !important;
                position: relative !important;
                animation: ps-modal-appear 0.3s ease-out !important;
            ">
                <div class="ps-confirm-icon" style="
                    font-size: 3.5rem !important;
                    color: #dc3545 !important;
                    margin-bottom: 20px !important;
                ">⚠️</div>
                
                <h3 style="
                    margin: 0 0 15px 0 !important;
                    color: #1a202c !important;
                    font-size: 1.4rem !important;
                    font-weight: 700 !important;
                ">${title}</h3>
                
                <div class="ps-confirm-message" style="
                    color: #4a5568 !important;
                    font-size: 1rem !important;
                    line-height: 1.5 !important;
                    margin-bottom: 30px !important;
                ">${message}</div>
                
                <div class="ps-confirm-buttons" style="
                    display: flex !important;
                    gap: 15px !important;
                    justify-content: center !important;
                ">
                    <button class="ps-btn-cancel" style="
                        background: #f8fafc !important;
                        color: #64748b !important;
                        border: 1px solid #e2e8f0 !important;
                        padding: 12px 24px !important;
                        border-radius: 8px !important;
                        font-weight: 500 !important;
                        font-size: 0.95rem !important;
                        cursor: pointer !important;
                        transition: all 0.2s ease !important;
                    ">Annulla</button>
                    
                    <button class="ps-btn-confirm" style="
                        background: #dc3545 !important;
                        color: white !important;
                        border: none !important;
                        padding: 12px 24px !important;
                        border-radius: 8px !important;
                        font-weight: 500 !important;
                        font-size: 0.95rem !important;
                        cursor: pointer !important;
                        transition: all 0.2s ease !important;
                    ">🗑️ Elimina</button>
                </div>
            </div>
        `;
        
        // Aggiungi CSS per l'animazione
        if (!document.getElementById('ps-modal-styles')) {
            const styles = document.createElement('style');
            styles.id = 'ps-modal-styles';
            styles.textContent = `
                @keyframes ps-modal-appear {
                    from {
                        opacity: 0;
                        transform: scale(0.9) translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1) translateY(0);
                    }
                }
                
                .ps-btn-cancel:hover {
                    background: #e2e8f0 !important;
                    color: #475569 !important;
                }
                
                .ps-btn-confirm:hover {
                    background: #c82333 !important;
                    transform: translateY(-1px) !important;
                    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3) !important;
                }
            `;
            document.head.appendChild(styles);
        }
        
        // Aggiungi al body
        document.body.appendChild(modal);
        
        // Event listeners
        const cancelBtn = modal.querySelector('.ps-btn-cancel');
        const confirmBtn = modal.querySelector('.ps-btn-confirm');
        
        // Chiudi su annulla
        cancelBtn.addEventListener('click', function() {
            modal.style.animation = 'ps-modal-appear 0.2s ease-in reverse';
            setTimeout(() => {
                modal.remove();
                if (onCancel) onCancel();
            }, 200);
        });
        
        // Conferma su elimina
        confirmBtn.addEventListener('click', function() {
            modal.style.animation = 'ps-modal-appear 0.2s ease-in reverse';
            setTimeout(() => {
                modal.remove();
                if (onConfirm) onConfirm();
            }, 200);
        });
        
        // Chiudi cliccando fuori
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.animation = 'ps-modal-appear 0.2s ease-in reverse';
                setTimeout(() => {
                    modal.remove();
                    if (onCancel) onCancel();
                }, 200);
            }
        });
        
        // Chiudi con ESC
        const escHandler = function(e) {
            if (e.key === 'Escape') {
                modal.style.animation = 'ps-modal-appear 0.2s ease-in reverse';
                setTimeout(() => {
                    modal.remove();
                    if (onCancel) onCancel();
                }, 200);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }
    
    // Funzione separata per eseguire l'eliminazione
    function executeDelete(generationId, deleteBtn) {
        console.log('🔄 Esecuzione eliminazione per ID:', generationId);
        
        // Verifica che pictosoundAjax sia definito con fallback
        if (typeof pictosoundAjax === 'undefined') {
            console.error('❌ pictosoundAjax ancora non definito, uso fallback');
            // Fallback hardcoded per WordPress standard
            window.pictosoundAjax = {
                ajaxurl: '/wp-admin/admin-ajax.php',
                nonce: 'fallback_nonce' // Questo non funzionerà ma almeno non crasha
            };
            showNotification('❌ Errore di configurazione AJAX. Ricarica la pagina.', 'error');
            return;
        }
        
        console.log('✅ pictosoundAjax OK:', pictosoundAjax);
        
        // Feedback visivo sul pulsante
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '⏳ Eliminando...';
        deleteBtn.disabled = true;
        deleteBtn.style.opacity = '0.6';
        
        // Dati da inviare
        const formData = new FormData();
        formData.append('action', 'delete_pictosound_generation');
        formData.append('generation_id', generationId);
        formData.append('nonce', pictosoundAjax.nonce);
        
        console.log('📤 Invio richiesta AJAX a:', pictosoundAjax.ajaxurl);
        console.log('📤 Dati:', {
            action: 'delete_pictosound_generation',
            generation_id: generationId,
            nonce: pictosoundAjax.nonce
        });
        
        // Richiesta AJAX
        fetch(pictosoundAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Risposta ricevuta, status:', response.status);
            return response.text(); // Prima leggi come testo per debugging
        })
        .then(responseText => {
            console.log('📡 Risposta raw:', responseText);
            
            try {
                const data = JSON.parse(responseText);
                console.log('📡 Risposta parsed:', data);
                
                if (data.success) {
                    console.log('✅ Eliminazione completata con successo');
                    showNotification('✅ Eliminazione completata con successo!', 'success');
                    
                    // Trova l'elemento da rimuovere
                    const generationItem = deleteBtn.closest('.generation-item');
                    if (generationItem) {
                        // Animazione di uscita
                        generationItem.style.transition = 'all 0.4s ease';
                        generationItem.style.transform = 'scale(0.95)';
                        generationItem.style.opacity = '0';
                        
                        // Rimuovi dopo animazione
                        setTimeout(() => {
                            generationItem.remove();
                            console.log('🎯 Elemento rimosso dalla UI');
                            
                            // Verifica se ci sono ancora elementi
                            const container = document.getElementById('pictosound-archive-container');
                            const remainingItems = container.querySelectorAll('.generation-item');
                            
                            if (remainingItems.length === 0) {
                                const wrapper = container.querySelector('.pictosound-generations-archive-wrapper');
                                wrapper.innerHTML = `
                                    <div class="pictosound-no-generations">
                                        <h3>Nessuna Creazione Trovata</h3>
                                        <p>Non hai ancora creato nessuna traccia musicale. <a href="/">Inizia ora!</a></p>
                                    </div>
                                `;
                            }
                        }, 400);
                    } else {
                        location.reload(); // Fallback
                    }
                } else {
                    console.error('❌ Errore eliminazione:', data.data);
                    showNotification('❌ Errore durante l\'eliminazione: ' + (data.data || 'Errore sconosciuto'), 'error');
                    
                    // Ripristina pulsante
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                    deleteBtn.style.opacity = '1';
                }
            } catch (parseError) {
                console.error('❌ Errore parsing JSON:', parseError);
                console.error('❌ Risposta non JSON:', responseText);
                showNotification('❌ Errore nella risposta del server', 'error');
                
                // Ripristina pulsante
                deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
                deleteBtn.style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('❌ Errore AJAX:', error);
            showNotification('❌ Errore di connessione. Riprova più tardi.', 'error');
            
            // Ripristina pulsante
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
            deleteBtn.style.opacity = '1';
        });
    }
    
    // Funzione per mostrare notifiche toast
    function showNotification(message, type = 'info') {
        // Rimuovi notifiche esistenti
        const existingNotification = document.getElementById('ps-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.id = 'ps-notification';
        
        const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
        
        notification.style.cssText = `
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            background: ${bgColor} !important;
            color: white !important;
            padding: 15px 20px !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            z-index: 10001 !important;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
            max-width: 350px !important;
            animation: ps-notification-slide 0.3s ease-out !important;
        `;
        
        notification.textContent = message;
        
        // Aggiungi CSS animazione se non esiste
        if (!document.getElementById('ps-notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'ps-notification-styles';
            styles.textContent = `
                @keyframes ps-notification-slide {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(notification);
        
        // Rimuovi automaticamente dopo 4 secondi
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'ps-notification-slide 0.3s ease-in reverse';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 4000);
        
        // Clic per chiudere
        notification.addEventListener('click', function() {
            notification.style.animation = 'ps-notification-slide 0.3s ease-in reverse';
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    console.log('✅ Funzioni QR Code caricate correttamente');
    
    // DEBUG e FORZA CSS: Assicuriamoci che il layout griglia funzioni
    document.addEventListener('DOMContentLoaded', function() {
        // Verifica che le variabili AJAX siano caricate
        if (typeof pictosoundAjax !== 'undefined') {
            console.log('✅ PictosoundAjax caricato correttamente:', pictosoundAjax);
        } else {
            console.error('❌ PictosoundAjax NON caricato - eliminazione non funzionerà');
        }
        
        const container = document.getElementById('pictosound-archive-container');
        if (container) {
            console.log('🔍 Container trovato:', container.className);
            
            // FORZA LAYOUT SCHEDA VIA JAVASCRIPT
            if (container.classList.contains('ps-layout-scheda')) {
                console.log('📐 Forzando layout scheda...');
                
                const items = container.querySelectorAll('.generation-item');
                items.forEach((item, index) => {
                    console.log(`🔧 Forzando item ${index + 1}...`);
                    
                    // Forza flexbox sull'item
                    item.style.display = 'flex';
                    item.style.flexDirection = 'row';
                    item.style.alignItems = 'flex-start';
                    item.style.gap = '20px';
                    item.style.width = '100%';
                    item.style.boxSizing = 'border-box';
                    
                    // Forza thumbnail a sinistra
                    const thumbnail = item.querySelector('.generation-thumbnail');
                    if (thumbnail) {
                        thumbnail.style.width = '120px';
                        thumbnail.style.height = '120px';
                        thumbnail.style.flexShrink = '0';
                        thumbnail.style.display = 'block';
                        thumbnail.style.float = 'none';
                        console.log(`✅ Thumbnail ${index + 1} forzata`);
                    }
                    
                    // Forza contenuto a destra
                    const content = item.querySelector('.generation-content');
                    if (content) {
                        content.style.flex = '1';
                        content.style.display = 'flex';
                        content.style.flexDirection = 'column';
                        content.style.minWidth = '0';
                        content.style.width = 'auto';
                        content.style.float = 'none';
                        console.log(`✅ Content ${index + 1} forzato`);
                    }
                });
                
                console.log('✅ Layout scheda forzato su tutti gli elementi');
            }
            
            // Se è layout griglia, forza il CSS
            if (container.classList.contains('ps-layout-griglia')) {
                const list = container.querySelector('.ps-generation-list');
                if (list) {
                    console.log('🔍 Lista trovata, forzando CSS griglia...');
                    list.style.display = 'grid';
                    list.style.gap = '20px';
                    list.style.width = '100%';
                    
                    // Determina il numero di colonne
                    let columns = '2'; // default
                    if (container.classList.contains('ps-colonne-1')) columns = '1';
                    else if (container.classList.contains('ps-colonne-2')) columns = '2';
                    else if (container.classList.contains('ps-colonne-3')) columns = '3';
                    else if (container.classList.contains('ps-colonne-4')) columns = '4';
                    else if (container.classList.contains('ps-colonne-5')) columns = '5';
                    else if (container.classList.contains('ps-colonne-6')) columns = '6';
                    
                    list.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
                    console.log(`✅ CSS griglia forzato: ${columns} colonne`);
                }
            }
            
            console.log('✅ Layout configurato correttamente');
        }
    });
    </script>
    <?php
}

add_shortcode('pictosound_generations_archive', 'pictosound_cm_generations_archive_shortcode');

/**
 * Gestione AJAX per eliminazione generazioni
 */
function pictosound_delete_generation_ajax() {
    // Log per debugging
    error_log('PictoSound: Richiesta eliminazione AJAX ricevuta');
    error_log('PictoSound: POST data: ' . print_r($_POST, true));
    
    // Header JSON
    header('Content-Type: application/json');
    
    // Verifica nonce per sicurezza
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pictosound_delete_nonce')) {
        error_log('PictoSound: Nonce non valido');
        wp_send_json_error('Nonce non valido. Ricarica la pagina e riprova.');
        return;
    }
    
    // Verifica che l'utente sia loggato
    if (!is_user_logged_in()) {
        error_log('PictoSound: Utente non loggato');
        wp_send_json_error('Devi essere loggato per eliminare le generazioni.');
        return;
    }
    
    // Verifica ID generazione
    if (!isset($_POST['generation_id'])) {
        error_log('PictoSound: ID generazione mancante');
        wp_send_json_error('ID generazione mancante.');
        return;
    }
    
    $generation_id = intval($_POST['generation_id']);
    $user_id = get_current_user_id();
    
    if ($generation_id <= 0) {
        error_log('PictoSound: ID generazione non valido: ' . $generation_id);
        wp_send_json_error('ID generazione non valido.');
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ps_generations';
    
    error_log("PictoSound: Verifico generazione ID {$generation_id} per utente {$user_id}");
    
    // Verifica che la generazione appartenga all'utente corrente
    $generation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $generation_id,
        $user_id
    ));
    
    if (!$generation) {
        error_log("PictoSound: Generazione {$generation_id} non trovata per utente {$user_id}");
        wp_send_json_error('Generazione non trovata o non autorizzato a eliminarla.');
        return;
    }
    
    error_log("PictoSound: Generazione trovata, procedo con eliminazione");
    
    // Elimina dal database
    $deleted = $wpdb->delete(
        $table_name,
        [
            'id' => $generation_id,
            'user_id' => $user_id
        ],
        ['%d', '%d']
    );
    
    if ($deleted === false) {
        error_log("PictoSound: Errore eliminazione DB - Last error: " . $wpdb->last_error);
        wp_send_json_error('Errore durante l\'eliminazione dal database.');
        return;
    }
    
    if ($deleted === 0) {
        error_log("PictoSound: Nessuna riga eliminata - ID: {$generation_id}, User: {$user_id}");
        wp_send_json_error('Nessuna generazione trovata da eliminare.');
        return;
    }
    
    // Log dell'eliminazione
    error_log("PictoSound: Eliminazione completata - ID {$generation_id} da utente {$user_id}");
    
    // Risposta di successo
    wp_send_json_success('Generazione eliminata con successo.');
}

// Hook AJAX per utenti loggati
add_action('wp_ajax_delete_pictosound_generation', 'pictosound_delete_generation_ajax');

/**
 * Enqueue scripts e CSS esterno
 */
function pictosound_archive_enqueue_scripts() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pictosound_generations_archive')) {
        // Assicurati che jQuery sia caricato
        wp_enqueue_script('jquery');
        
        // Carica il CSS esterno
        wp_enqueue_style(
            'pictosound-snapshot-style',
            'https://pictosound.com/wp-content/pictosound/css/snapshotstyle.css',
            array(),
            '1.0.0' // Versione - cambia questo numero quando aggiorni il CSS
        );
    }
}
add_action('wp_enqueue_scripts', 'pictosound_archive_enqueue_scripts');
?>