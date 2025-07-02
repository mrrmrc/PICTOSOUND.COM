<?php
/**
 * Shortcode parametrizzato per l'archivio delle generazioni.
 * VERSIONE RESPONSIVA CON LIGHTBOX E AUTOPLAY AUDIO
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
        'ps-layout-' . $layout,
        'ps-responsive'
    ];
    
    if (!empty($classe_css)) {
        $container_classes[] = $classe_css;
    }
    
    if ($layout === 'griglia') {
        $container_classes[] = 'ps-colonne-' . $colonne;
    }

    // Aggiungi CSS responsivo
    renderResponsiveCSS();

    echo '<div id="pictosound-archive-container" class="' . esc_attr(implode(' ', $container_classes)) . '" data-layout="' . esc_attr($layout) . '" data-colonne="' . esc_attr($colonne) . '">';
    echo '<div class="pictosound-generations-archive-wrapper">';
    
    // Debug: mostra le classi applicate
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<!-- DEBUG ARCHIVIO: Classi = ' . esc_html(implode(' ', $container_classes)) . ' -->';
    }
    
    if ($total_items > 0) {
        if (!empty($titolo)) {
            echo '<h3 class="ps-archive-title">' . $titolo . '</h3>';
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
            echo '<li class="generation-item">';
            
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
 * Renderizza il CSS responsivo con lightbox
 */
function renderResponsiveCSS() {
    static $css_rendered = false;
    if ($css_rendered) return;
    $css_rendered = true;
    
    ?>
    <style>
    /* CSS BASE RESPONSIVO */
    .pictosound-archive-container {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding: 0;
        box-sizing: border-box;
    }
    
    .pictosound-generations-archive-wrapper {
        width: 100%;
        padding: 15px;
        box-sizing: border-box;
    }
    
    .ps-archive-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a202c;
        margin: 0 0 20px 0;
        text-align: center;
    }
    
    .ps-generation-list {
        list-style: none;
        margin: 0;
        padding: 0;
        width: 100%;
    }
    
    /* LAYOUT SCHEDA - DESKTOP */
    .ps-layout-scheda .generation-item {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 20px;
        padding: 20px;
        margin-bottom: 15px;
        background: #ffffff;
        border: 1px solid #eef0f3;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        width: 100%;
        box-sizing: border-box;
        list-style: none;
    }
    
    .ps-layout-scheda .generation-thumbnail {
        width: 120px;
        height: 120px;
        flex-shrink: 0;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
        display: block;
        margin: 0;
        padding: 0;
        float: none;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .ps-layout-scheda .generation-thumbnail:hover {
        transform: scale(1.05);
    }
    
    .ps-layout-scheda .generation-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        min-height: 120px;
        min-width: 0;
        width: auto;
        margin: 0;
        padding: 0;
        float: none;
        position: relative;
    }
    
    /* LAYOUT GRIGLIA - DESKTOP */
    .ps-layout-griglia .ps-generation-list {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(var(--grid-columns, 2), 1fr);
    }
    
    .ps-layout-griglia.ps-colonne-1 .ps-generation-list { --grid-columns: 1; }
    .ps-layout-griglia.ps-colonne-2 .ps-generation-list { --grid-columns: 2; }
    .ps-layout-griglia.ps-colonne-3 .ps-generation-list { --grid-columns: 3; }
    .ps-layout-griglia.ps-colonne-4 .ps-generation-list { --grid-columns: 4; }
    .ps-layout-griglia.ps-colonne-5 .ps-generation-list { --grid-columns: 5; }
    .ps-layout-griglia.ps-colonne-6 .ps-generation-list { --grid-columns: 6; }
    
    .ps-layout-griglia .generation-item {
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border: 1px solid #eef0f3;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        list-style: none;
    }
    
    .ps-layout-griglia .generation-thumbnail {
        width: 100%;
        height: 200px;
        overflow: hidden;
        display: block;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .ps-layout-griglia .generation-thumbnail:hover {
        transform: scale(1.02);
    }
    
    .ps-layout-griglia .generation-content {
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }
    
    /* ELEMENTI COMUNI */
    .generation-thumbnail img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: none;
        transition: all 0.3s ease;
    }
    
    .ps-no-image-placeholder {
        background: linear-gradient(45deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        width: 100%;
        height: 100%;
        transition: all 0.3s ease;
    }
    
    .ps-no-image-placeholder:hover {
        background: linear-gradient(45deg, #5a67d8, #6b46c1);
    }
    
    .generation-info {
        text-align: left;
        margin-bottom: 15px;
    }
    
    .title-date-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin: 0 0 6px 0;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .generation-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1a202c;
        line-height: 1.3;
        margin: 0;
        flex: 1;
        min-width: 0;
        text-align: left;
    }
    
    .generation-datetime {
        font-size: 0.8rem;
        color: #64748b;
        white-space: nowrap;
        flex-shrink: 0;
        text-align: right;
        margin: 0;
    }
    
    .generation-prompt {
        font-size: 0.95rem;
        font-weight: 400;
        color: #4a5568;
        line-height: 1.4;
        margin: 0 0 10px 0;
        display: block;
        text-align: left;
    }
    
    .generation-meta {
        font-size: 0.85rem;
        color: #64748b;
        margin: 0 0 10px 0;
        text-align: left;
    }
    
    .generation-player-section {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        margin: 0;
        padding: 0;
    }
    
    .original-audio-player {
        flex: 1;
        min-width: 250px;
        max-width: 350px;
        height: 45px;
        margin: 0;
    }
    
    .download-buttons-container {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .download-button-archive {
        padding: 8px 12px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.8rem;
        white-space: nowrap;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .download-mp3 { background: #667eea !important; color: white !important; }
    .download-qr { background: #28a745 !important; color: white !important; }
    .download-combo { background: #fd7e14 !important; color: white !important; }
    .download-delete { background: #dc3545 !important; color: white !important; }
    
    .download-button-archive:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    /* LIGHTBOX MODALE */
    .ps-lightbox {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        padding: 20px;
        box-sizing: border-box;
    }
    
    .ps-lightbox.active {
        opacity: 1;
        visibility: visible;
    }
    
    .ps-lightbox-content {
        max-width: 90%;
        max-height: 90%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        animation: ps-lightbox-appear 0.3s ease-out;
    }
    
    .ps-lightbox-image {
        max-width: 100%;
        max-height: 70vh;
        object-fit: contain;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .ps-lightbox-player {
        background: rgba(255, 255, 255, 0.95);
        padding: 20px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        min-width: 300px;
    }
    
    .ps-lightbox-info {
        text-align: center;
        color: #1a202c;
        margin-bottom: 10px;
    }
    
    .ps-lightbox-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: #1a202c;
    }
    
    .ps-lightbox-prompt {
        font-size: 0.95rem;
        color: #4a5568;
        margin: 0;
        line-height: 1.4;
    }
    
    .ps-lightbox-audio {
        width: 100%;
        height: 50px;
        border-radius: 8px;
    }
    
    .ps-lightbox-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 10001;
    }
    
    .ps-lightbox-close:hover {
        background: white;
        transform: scale(1.1);
    }
    
    @keyframes ps-lightbox-appear {
        from {
            opacity: 0;
            transform: scale(0.8) translateY(20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    /* PAGINAZIONE */
    .ps-pagination-controls {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 10px;
        margin: 30px 0 0 0;
        flex-wrap: nowrap;
        width: 100%;
        position: relative;
        clear: both;
        overflow: visible;
        box-sizing: border-box;
        flex-direction: row;
        text-align: left;
        float: none;
    }
    
    .ps-pagination-btn {
        background: #667eea;
        color: white;
        padding: 10px 16px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        box-sizing: border-box;
        transition: background-color 0.3s ease;
        flex-shrink: 0;
        margin: 0;
        line-height: 1.2;
        float: none;
        clear: none;
        position: relative;
    }
    
    .ps-pagination-btn:hover {
        background: #5a67d8;
    }
    
    .ps-pagination-numbers {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
        margin: 0;
        padding: 0;
        flex-wrap: nowrap;
        overflow: visible;
        width: auto;
        float: none;
        clear: none;
        position: relative;
    }
    
    .ps-pagination-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 12px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 500;
        font-size: 0.9rem;
        color: #64748b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        line-height: 1.2;
        box-sizing: border-box;
        transition: all 0.3s ease;
        margin: 0;
        flex-shrink: 0;
        white-space: nowrap;
        float: none;
        clear: none;
        position: relative;
        min-width: 40px;
    }
    
    .ps-pagination-number:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .ps-pagination-number.ps-current-page {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .ps-pagination-dots {
        color: #64748b;
        font-weight: bold;
        padding: 8px 4px;
        display: inline-flex;
        align-items: center;
        margin: 0;
        flex-shrink: 0;
        float: none;
        clear: none;
        position: relative;
    }
    
    /* RESPONSIVE - TABLET */
    @media (max-width: 1024px) {
        .ps-layout-griglia.ps-colonne-4 .ps-generation-list,
        .ps-layout-griglia.ps-colonne-5 .ps-generation-list,
        .ps-layout-griglia.ps-colonne-6 .ps-generation-list {
            --grid-columns: 3;
        }
        
        .ps-layout-griglia.ps-colonne-3 .ps-generation-list {
            --grid-columns: 2;
        }
        
        .ps-lightbox-content {
            max-width: 95%;
        }
        
        .ps-lightbox-player {
            min-width: 280px;
        }
    }
    
    /* RESPONSIVE - MOBILE */
    @media (max-width: 768px) {
        .pictosound-generations-archive-wrapper {
            padding: 10px;
        }
        
        .ps-archive-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        /* LAYOUT SCHEDA SU MOBILE - DIVENTA VERTICALE */
        .ps-layout-scheda .generation-item {
            flex-direction: column;
            gap: 15px;
            padding: 15px;
            margin-bottom: 12px;
        }
        
        .ps-layout-scheda .generation-thumbnail {
            width: 100%;
            height: 200px;
            align-self: center;
            max-width: 300px;
        }
        
        .ps-layout-scheda .generation-content {
            width: 100%;
            min-height: auto;
        }
        
        /* LAYOUT GRIGLIA SU MOBILE - UNA COLONNA */
        .ps-layout-griglia .ps-generation-list {
            --grid-columns: 1;
            gap: 15px;
        }
        
        .ps-layout-griglia .generation-thumbnail {
            height: 180px;
        }
        
        .ps-layout-griglia .generation-content {
            padding: 12px;
        }
        
        /* ELEMENTI INFO SU MOBILE */
        .title-date-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .generation-title {
            font-size: 1.1rem;
            order: 1;
        }
        
        .generation-datetime {
            font-size: 0.75rem;
            order: 2;
            align-self: flex-end;
        }
        
        .generation-prompt {
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .generation-meta {
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
        
        /* PLAYER E CONTROLLI SU MOBILE */
        .generation-player-section {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        
        .original-audio-player {
            width: 100%;
            min-width: auto;
            max-width: none;
            height: 40px;
        }
        
        .download-buttons-container {
            justify-content: center;
            gap: 6px;
        }
        
        .download-button-archive {
            padding: 10px 12px;
            font-size: 0.75rem;
            flex: 1;
            text-align: center;
            min-width: auto;
        }
        
        /* LIGHTBOX SU MOBILE */
        .ps-lightbox {
            padding: 15px;
        }
        
        .ps-lightbox-content {
            max-width: 100%;
            gap: 15px;
        }
        
        .ps-lightbox-image {
            max-height: 60vh;
        }
        
        .ps-lightbox-player {
            min-width: auto;
            width: 100%;
            max-width: 350px;
            padding: 15px;
        }
        
        .ps-lightbox-title {
            font-size: 1.1rem;
        }
        
        .ps-lightbox-prompt {
            font-size: 0.85rem;
        }
        
        .ps-lightbox-close {
            top: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            font-size: 1.3rem;
        }
        
        /* PAGINAZIONE SU MOBILE */
        .ps-pagination-controls {
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
        }
        
        .ps-pagination-numbers {
            order: 1;
            justify-content: center;
        }
        
        .ps-pagination-btn {
            padding: 12px 20px;
            font-size: 0.85rem;
            order: 2;
        }
        
        .ps-pagination-number {
            padding: 10px;
            font-size: 0.85rem;
            min-width: 44px;
        }
        
        /* NO GENERATIONS SU MOBILE */
        .pictosound-no-generations {
            text-align: center;
            padding: 40px 20px;
        }
        
        .pictosound-no-generations h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        .pictosound-no-generations p {
            font-size: 0.95rem;
            line-height: 1.5;
        }
    }
    
    /* RESPONSIVE - MOBILE PICCOLO */
    @media (max-width: 480px) {
        .pictosound-generations-archive-wrapper {
            padding: 8px;
        }
        
        .ps-layout-scheda .generation-item,
        .ps-layout-griglia .generation-item {
            margin-bottom: 10px;
        }
        
        .ps-layout-scheda .generation-item {
            padding: 12px;
        }
        
        .ps-layout-griglia .generation-content {
            padding: 10px;
        }
        
        /* PULSANTI SEMPRE AFFIANCATI */
        .download-buttons-container {
            gap: 6px;
            justify-content: center;
        }
        
        .download-button-archive {
            padding: 10px 8px;
            font-size: 0.7rem;
            flex: 1;
            max-width: 70px;
            text-align: center;
        }
        
        .ps-no-image-placeholder {
            font-size: 2rem;
        }
        
        .generation-title {
            font-size: 1rem;
        }
        
        .generation-prompt {
            font-size: 0.85rem;
        }
        
        /* LIGHTBOX SU MOBILE PICCOLO */
        .ps-lightbox {
            padding: 10px;
        }
        
        .ps-lightbox-image {
            max-height: 50vh;
        }
        
        .ps-lightbox-player {
            padding: 12px;
        }
        
        .ps-lightbox-close {
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }
    }
    </style>
    <?php
}

/**
 * Renderizza il layout scheda (immagine a sinistra, contenuto a destra)
 */
function renderSchedaLayout($generation) {
    echo '<div class="generation-thumbnail" onclick="openLightbox(\'' . esc_js($generation->id) . '\', \'' . esc_js($generation->image_url) . '\', \'' . esc_js($generation->audio_url) . '\', \'' . esc_js($generation->title ? $generation->title : $generation->prompt) . '\', \'' . esc_js($generation->prompt) . '\')">';
    renderThumbnail($generation);
    echo '</div>';
    
    echo '<div class="generation-content">';
    renderGenerationInfo($generation);
    renderPlayerSection($generation);
    echo '</div>';
}

/**
 * Renderizza il layout griglia (elemento verticale)
 */
function renderGrigliaLayout($generation) {
    echo '<div class="generation-thumbnail" onclick="openLightbox(\'' . esc_js($generation->id) . '\', \'' . esc_js($generation->image_url) . '\', \'' . esc_js($generation->audio_url) . '\', \'' . esc_js($generation->title ? $generation->title : $generation->prompt) . '\', \'' . esc_js($generation->prompt) . '\')">';
    renderThumbnail($generation);
    echo '</div>';
    
    echo '<div class="generation-content">';
    renderGenerationInfo($generation);
    renderPlayerSection($generation);
    echo '</div>';
}

/**
 * Renderizza la miniatura (ora cliccabile per lightbox)
 */
function renderThumbnail($generation) {
    $valid_image_url = '';
    if (!empty($generation->image_url) && filter_var($generation->image_url, FILTER_VALIDATE_URL)) {
        $valid_image_url = $generation->image_url;
    }

    if ($valid_image_url) {
        echo '<img src="' . esc_url($valid_image_url) . '" loading="lazy" alt="Miniatura per ' . esc_attr($generation->prompt) . '" title="Clicca per ingrandire e ascoltare" />';
    } else {
        echo '<div class="ps-no-image-placeholder" title="Clicca per ascoltare">🎵</div>';
    }
}

/**
 * Renderizza le informazioni della generazione
 */
function renderGenerationInfo($generation) {
    echo '<div class="generation-info">';
    
    // Prima riga: Titolo principale e Data/Ora affiancati
    echo '<div class="title-date-row">';
    
    // Titolo principale (dal campo title del database)
    if (!empty($generation->title)) {
        echo '<h4 class="generation-title">' . esc_html($generation->title) . '</h4>';
    }
    
    // Data e ora di creazione (affianco al titolo)
    echo '<div class="generation-datetime">';
    echo '<span><strong>' . date_i18n(get_option('date_format') . ' H:i', strtotime($generation->created_at)) . '</strong></span>';
    echo '</div>';
    
    echo '</div>'; // chiusura title-date-row
    
    // Seconda riga: Prompt come sottotitolo (sotto al titolo)
    if (!empty($generation->prompt)) {
        echo '<p class="generation-prompt">' . esc_html($generation->prompt) . '</p>';
    }
    
    // Terza riga: Durata (solo durata, data è già sopra)
    echo '<div class="generation-meta">';
    echo '<span><strong>Durata:</strong> ' . esc_html($generation->duration) . 's</span>';
    echo '</div>';
    
    echo '</div>';
}

/**
 * Renderizza la sezione player e download
 */
function renderPlayerSection($generation) {
    $valid_image_url = !empty($generation->image_url) && filter_var($generation->image_url, FILTER_VALIDATE_URL) ? $generation->image_url : '';
    
    echo '<div class="generation-player-section">';
    echo '<audio class="original-audio-player" controls preload="none" src="' . esc_url($generation->audio_url) . '"></audio>';
    
    echo '<div class="download-buttons-container">';
    echo '<a href="' . esc_url($generation->audio_url) . '" class="download-button-archive download-mp3" download>🎵 MP3</a>';
    echo '<button class="download-button-archive download-qr" onclick="downloadQRCode(\'' . esc_js($generation->audio_url) . '\', \'qr_' . $generation->id . '\')">📱 QR</button>';
    
    if ($valid_image_url) {
        echo '<button class="download-button-archive download-combo" onclick="downloadImageWithQR(\'' . esc_js($generation->audio_url) . '\', \'' . esc_js($valid_image_url) . '\', \'' . esc_js($generation->title ? $generation->title : $generation->prompt) . '\', \'' . esc_js(date_i18n(get_option('date_format') . ' H:i', strtotime($generation->created_at))) . '\', \'combo_' . $generation->id . '\')">🖼️ IMG+QR</button>';
    }
    
    // Pulsante Elimina con conferma (ULTIMO)
    echo '<button class="download-button-archive download-delete" data-generation-id="' . intval($generation->id) . '" data-generation-title="' . esc_attr($generation->title ? $generation->title : $generation->prompt) . '" onclick="deleteGeneration(this)">🗑️ Elimina</button>';
    
    echo '</div>';
    echo '</div>';
}

/**
 * Renderizza i controlli di paginazione responsivi
 */
function renderPaginazione($current_page, $total_pages) {
    echo '<div class="ps-pagination-controls">';
    
    $current_url = remove_query_arg('ps_page');
    
    // Pulsante Precedente
    if ($current_page > 1) {
        $prev_url = add_query_arg('ps_page', $current_page - 1, $current_url);
        echo '<a href="' . esc_url($prev_url) . '" class="ps-pagination-btn ps-prev-btn">« Precedente</a>';
    }
    
    // Container numeri
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
    
    echo '</div>'; // chiusura numbers
    
    // Pulsante Successivo
    if ($current_page < $total_pages) {
        $next_url = add_query_arg('ps_page', $current_page + 1, $current_url);
        echo '<a href="' . esc_url($next_url) . '" class="ps-pagination-btn ps-next-btn">Successivo »</a>';
    }
    
    echo '</div>';
}

/**
 * Renderizza il JavaScript con lightbox e autoplay
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
    
    // Variabile globale per il lightbox
    let currentLightboxAudio = null;
    
    // Funzione per aprire il lightbox con autoplay
    function openLightbox(id, imageUrl, audioUrl, title, prompt) {
        console.log('🖼️ Apertura lightbox per ID:', id);
        
        // Rimuovi lightbox esistente se presente
        const existingLightbox = document.getElementById('ps-lightbox');
        if (existingLightbox) {
            existingLightbox.remove();
        }
        
        // Crea il lightbox
        const lightbox = document.createElement('div');
        lightbox.id = 'ps-lightbox';
        lightbox.className = 'ps-lightbox';
        
        let imageContent = '';
        if (imageUrl && imageUrl !== '') {
            imageContent = `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" class="ps-lightbox-image" />`;
        } else {
            imageContent = `<div class="ps-lightbox-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(45deg, #667eea, #764ba2); color: white; font-size: 4rem; width: 300px; height: 300px; border-radius: 12px;">🎵</div>`;
        }
        
        lightbox.innerHTML = `
            <button class="ps-lightbox-close" onclick="closeLightbox()">✕</button>
            <div class="ps-lightbox-content">
                ${imageContent}
                <div class="ps-lightbox-player">
                    <div class="ps-lightbox-info">
                        <h3 class="ps-lightbox-title">${escapeHtml(title)}</h3>
                        ${prompt && prompt !== title ? `<p class="ps-lightbox-prompt">${escapeHtml(prompt)}</p>` : ''}
                    </div>
                    <audio id="ps-lightbox-audio" class="ps-lightbox-audio" controls preload="metadata" src="${escapeHtml(audioUrl)}"></audio>
                </div>
            </div>
        `;
        
        // Aggiungi al body
        document.body.appendChild(lightbox);
        
        // Mostra lightbox con animazione
        setTimeout(() => {
            lightbox.classList.add('active');
        }, 10);
        
        // Ottieni riferimento al player audio
        currentLightboxAudio = document.getElementById('ps-lightbox-audio');
        
        // Avvia riproduzione automatica dopo un breve delay
        setTimeout(() => {
            if (currentLightboxAudio) {
                currentLightboxAudio.play().then(() => {
                    console.log('🎵 Riproduzione automatica avviata');
                }).catch(error => {
                    console.log('⚠️ Autoplay bloccato dal browser:', error);
                    // Fallback: mostra messaggio per l'utente
                    showAutoplayNotification();
                });
            }
        }, 500);
        
        // Event listeners
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
        
        // Chiudi con ESC
        const escHandler = function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
        
        // Previeni scroll della pagina
        document.body.style.overflow = 'hidden';
    }
    
    // Funzione per chiudere il lightbox
    function closeLightbox() {
        const lightbox = document.getElementById('ps-lightbox');
        if (lightbox) {
            // Ferma audio se in riproduzione
            if (currentLightboxAudio) {
                currentLightboxAudio.pause();
                currentLightboxAudio.currentTime = 0;
                currentLightboxAudio = null;
            }
            
            // Animazione di chiusura
            lightbox.classList.remove('active');
            setTimeout(() => {
                lightbox.remove();
            }, 300);
            
            // Ripristina scroll
            document.body.style.overflow = '';
        }
    }
    
    // Funzione per mostrare notifica autoplay
    function showAutoplayNotification() {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(102, 126, 234, 0.95);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            z-index: 10002;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            animation: ps-notification-slide 0.3s ease-out;
        `;
        notification.textContent = '🎵 Premi play per ascoltare la musica';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'ps-notification-slide 0.3s ease-in reverse';
                setTimeout(() => notification.remove(), 300);
            }
        }, 3000);
    }
    
    // Funzione di escape per HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
            padding: 20px !important;
            box-sizing: border-box !important;
        `;
        
        // Contenuto della finestra
        modal.innerHTML = `
            <div class="ps-confirm-dialog" style="
                background: white !important;
                border-radius: 12px !important;
                padding: 30px !important;
                max-width: 450px !important;
                width: 100% !important;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
                text-align: center !important;
                position: relative !important;
                animation: ps-modal-appear 0.3s ease-out !important;
                box-sizing: border-box !important;
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
                    flex-wrap: wrap !important;
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
                        flex: 1 !important;
                        min-width: 120px !important;
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
                        flex: 1 !important;
                        min-width: 120px !important;
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
                
                @media (max-width: 480px) {
                    .ps-confirm-dialog {
                        padding: 20px !important;
                    }
                    .ps-confirm-buttons {
                        flex-direction: column !important;
                    }
                    .ps-btn-cancel, .ps-btn-confirm {
                        width: 100% !important;
                    }
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
    
    console.log('✅ Funzioni QR Code e Lightbox caricate correttamente');
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