<?php
/**
 * ⚡ PICTOSOUND USER GALLERY - VERSIONE CORRETTA
 * Mostra solo i media generati da Pictosound, non tutti i media dell'utente
 */

function pictosound_cm_user_gallery_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 20px; text-align: center; margin: 20px 0; box-shadow: 0 8px 25px rgba(102,126,234,0.3);">
            <div style="font-size: 4rem; margin-bottom: 20px;">🔒</div>
            <h3 style="margin: 0 0 15px 0; font-size: 1.8rem; font-weight: 300;">Accesso Richiesto</h3>
            <p style="margin: 0 0 25px 0; font-size: 1.1rem; opacity: 0.9;">Effettua il login per accedere alla tua galleria musicale</p>
            <a href="/wp-login.php" style="background: white; color: #667eea; padding: 15px 30px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(255,255,255,0.3); transition: all 0.3s ease; display: inline-block;">🎵 ACCEDI ALLA TUA GALLERY</a>
        </div>';
    }

    $atts = shortcode_atts([
        'per_page' => 20
    ], $atts);

    $user_id = get_current_user_id();
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    // ⚡ STEP 1: VERIFICA TABELLA DATABASE (NOME COMPLETO CORRETTO!)
    global $wpdb;
    
    // Verifica prima il prefisso reale
    $real_prefix = $wpdb->prefix;
    write_log_cm("Prefisso DB rilevato: '$real_prefix'");
    
    // Nome tabella completo corretto
    $table_name = 'aDOtz4PiG8_pictosound_creations';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    $creations_from_db = [];
    $total_from_db = 0;
    
    if ($table_exists) {
        $count_query = $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d", $user_id);
        $total_from_db = intval($wpdb->get_var($count_query));
        
        if ($total_from_db > 0) {
            $offset = ($paged - 1) * $atts['per_page'];
            $data_query = $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $user_id, $atts['per_page'], $offset
            );
            $creations_from_db = $wpdb->get_results($data_query);
        }
    }
    
    // ⚡ STEP 2: QUERY ATTACHMENT - PRIMA PROVA CON FILTRO PICTOSOUND
    $attachment_args_filtered = [
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'author' => $user_id,
        'posts_per_page' => $atts['per_page'],
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_mime_type' => ['image', 'audio'],
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => '_pictosound_prompt',
                'compare' => 'EXISTS'
            ],
            [
                'key' => '_pictosound_audio_id', 
                'compare' => 'EXISTS'
            ],
            [
                'key' => '_pictosound_duration',
                'compare' => 'EXISTS'
            ],
            [
                'key' => '_pictosound_generated',
                'compare' => 'EXISTS'
            ]
        ]
    ];
    
    $attachment_query = new WP_Query($attachment_args_filtered);
    
    // 🔄 FALLBACK: Se non trova nulla con filtro, prova senza filtro
    if (!$attachment_query->have_posts()) {
        $attachment_args_all = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'author' => $user_id,
            'posts_per_page' => $atts['per_page'],
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_mime_type' => ['image', 'audio']
        ];
        
        $attachment_query = new WP_Query($attachment_args_all);
    }
    
    // ⚡ STEP 3: DECIDI COSA MOSTRARE
    $show_database = !empty($creations_from_db);
    $show_attachments = !$show_database && $attachment_query->have_posts();
    $total_items = $show_database ? $total_from_db : $attachment_query->found_posts;
    
    // ⚡ DEBUG COMPLETO - Verifichiamo tutto
    $all_attachments_args = [
        'post_type' => 'attachment',
        'post_status' => 'inherit', 
        'author' => $user_id,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ];
    $all_attachments = get_posts($all_attachments_args);
    $total_all_attachments = count($all_attachments);
    
    // Verifica meta_key presenti
    $meta_counts = [];
    $meta_keys_to_check = ['_pictosound_prompt', '_pictosound_audio_id', '_pictosound_duration', '_pictosound_generated'];
    
    foreach ($meta_keys_to_check as $meta_key) {
        $meta_args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'author' => $user_id,
            'meta_query' => [['key' => $meta_key, 'compare' => 'EXISTS']],
            'fields' => 'ids'
        ];
        $meta_posts = get_posts($meta_args);
        $meta_counts[$meta_key] = count($meta_posts);
    }
    
    // 🔍 DEBUG SEMPLIFICATO - Ora sappiamo la tabella corretta
    $db_debug = "Tabella non esiste";
    if ($table_exists) {
        $total_db_all = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $total_db_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d", $user_id));
        
        $db_debug = "Totale righe DB: $total_db_all, Tue righe: $total_db_user";
        
        // Se ci sono poche righe, mostra esempi
        if ($total_db_all <= 10) {
            $sample_data = $wpdb->get_results("SELECT id, user_id, title, created_at FROM {$table_name} ORDER BY created_at DESC LIMIT 5");
            if ($sample_data) {
                $samples = [];
                foreach ($sample_data as $sample) {
                    $samples[] = "#{$sample->id}: {$sample->title} (User: {$sample->user_id})";
                }
                $db_debug .= ", Esempi: " . implode('; ', $samples);
            }
        }
    }
    
    $debug_info = "DB: " . ($table_exists ? 'SI' : 'NO') . 
                  ", Items user: $total_from_db" . 
                  ", Attachment: {$attachment_query->found_posts}" .
                  ", $db_debug" .
                  ", Showing: " . ($show_database ? 'DATABASE' : 'ATTACHMENTS');
    
    write_log_cm("Gallery DEBUG - User $user_id: $debug_info");
    
    $debug_info = "DB: " . ($table_exists ? 'SI' : 'NO') . 
                  ", DB items user: $total_from_db" . 
                  ", Totale attachment: $total_all_attachments" .
                  ", Pictosound Attachments: {$attachment_query->found_posts}" .
                  ", Meta counts: " . json_encode($meta_counts) .
                  ", $db_debug" .
                  ", Showing: " . ($show_database ? 'DATABASE' : 'ATTACHMENTS');
    
    write_log_cm("Gallery DEBUG - User $user_id: $debug_info");

    ob_start();
    ?>

    <!-- CSS STYLES -->
    <style>
    .pictosound-gallery-list {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    .gallery-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .gallery-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: headerGlow 6s ease-in-out infinite alternate;
    }

    @keyframes headerGlow {
        0% { transform: scale(1) rotate(0deg); }
        100% { transform: scale(1.1) rotate(3deg); }
    }

    .header-content {
        position: relative;
        z-index: 1;
    }

    .gallery-stats {
        display: inline-flex;
        align-items: center;
        background: rgba(255,255,255,0.2);
        padding: 10px 20px;
        border-radius: 25px;
        margin-top: 15px;
        backdrop-filter: blur(10px);
    }

    .creations-list {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
    }

    .creation-item {
        display: grid;
        grid-template-columns: 120px 1fr auto;
        gap: 20px;
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: all 0.3s ease;
        align-items: center;
    }

    .creation-item:last-child {
        border-bottom: none;
    }

    .creation-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .creation-thumbnail {
        width: 120px;
        height: 120px;
        border-radius: 15px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        flex-shrink: 0;
    }

    .creation-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .creation-item:hover .creation-thumbnail img {
        transform: scale(1.1);
    }

    .thumbnail-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
    }

    .play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .creation-item:hover .play-overlay {
        opacity: 1;
    }

    .play-icon {
        color: white;
        font-size: 2rem;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    }

    .creation-info {
        flex: 1;
        min-width: 0;
    }

    .creation-title {
        font-size: 1.3rem;
        font-weight: bold;
        margin: 0 0 8px 0;
        color: #333;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .creation-description {
        color: #666;
        font-size: 1rem;
        line-height: 1.5;
        margin: 0 0 10px 0;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .creation-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 0.9rem;
        color: #888;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .creation-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-end;
    }

    .action-btn {
        background: none;
        border: 1px solid #e9ecef;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.9rem;
        color: #666;
        min-width: 80px;
    }

    .action-btn:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
        transform: translateY(-2px);
    }

    .btn-delete:hover {
        background: #dc3545;
        border-color: #dc3545;
    }

    .gallery-empty {
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 20px;
        margin: 40px 0;
    }

    .fullscreen-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.95);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .fullscreen-modal.active {
        display: flex;
        opacity: 1;
    }

    .fullscreen-content {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .fullscreen-image {
        max-width: 100%;
        max-height: 70vh;
        object-fit: contain;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        margin-bottom: 30px;
    }

    .fullscreen-controls {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(20px);
        padding: 25px;
        border-radius: 20px;
        color: white;
        text-align: center;
        min-width: 400px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }

    .fullscreen-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0 0 10px 0;
    }

    .fullscreen-description {
        font-size: 1rem;
        opacity: 0.9;
        margin: 0 0 20px 0;
        line-height: 1.5;
    }

    .audio-player-full {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 20px 0;
        padding: 15px;
        background: rgba(255,255,255,0.1);
        border-radius: 15px;
    }

    .play-btn-full {
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .play-btn-full:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.1);
    }

    .progress-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .progress-bar {
        height: 6px;
        background: rgba(255,255,255,0.2);
        border-radius: 3px;
        position: relative;
        cursor: pointer;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(45deg, #667eea, #764ba2);
        border-radius: 3px;
        width: 0%;
        transition: width 0.1s ease;
    }

    .time-info {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .close-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255,255,255,0.1);
        border: none;
        color: white;
        font-size: 2rem;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .close-btn:hover {
        background: rgba(255,255,255,0.2);
        transform: scale(1.1);
    }

    @media (max-width: 768px) {
        .creation-item {
            grid-template-columns: 80px 1fr;
            gap: 15px;
        }
        
        .creation-actions {
            grid-column: 1 / -1;
            flex-direction: row;
            justify-content: center;
            margin-top: 15px;
        }
        
        .creation-thumbnail {
            width: 80px;
            height: 80px;
        }
        
        .fullscreen-controls {
            min-width: 90vw;
            margin: 0 20px;
        }
    }
    </style>

    <div class="pictosound-gallery-list">
        
        <!-- HEADER -->
        <div class="gallery-header">
            <div class="header-content">
                <h1 style="margin: 0 0 10px 0; font-size: 2.2rem; font-weight: 300;">🎵 La Tua Galleria Musicale</h1>
                <p style="margin: 0; font-size: 1.1rem; opacity: 0.9;">Clicca su una creazione per visualizzarla e ascoltarla</p>
                
                <div class="gallery-stats">
                    <span style="font-weight: bold;"><?php echo $total_items; ?> creazioni Pictosound</span>
                    <span style="margin: 0 10px;">•</span>
                    <span><?php echo pictosound_cm_get_user_credits($user_id); ?> crediti disponibili</span>
                </div>
                
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-top: 15px; font-size: 0.9rem; text-align: left;">
                    <strong>🔧 Debug Sistema:</strong><br>
                    • Prefisso WP: "<?php echo esc_html($real_prefix); ?>"<br>
                    • Tabella cercata: "<?php echo esc_html($table_name); ?>"<br>
                    • Tabella esiste: <?php echo $table_exists ? '✅ SI' : '❌ NO'; ?><br>
                    • Database items: <?php echo $total_from_db; ?><br>
                    • Attachment trovati: <?php echo $attachment_query->found_posts; ?><br>
                    • Strategia: <?php echo ($show_database ? 'DATABASE' : ($attachment_query->found_posts > 0 ? 'ATTACHMENTS' : 'NESSUN DATO')); ?><br>
                    <?php if ($table_exists && defined('WP_DEBUG') && WP_DEBUG): ?>
                        <small><?php echo $db_debug; ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- GALLERY CONTENT -->
        <?php if ($total_items > 0): ?>
            
            <div class="creations-list">
                
                <?php if ($show_database): ?>
                    <!-- MOSTRA DATI DAL DATABASE -->
                    <?php foreach ($creations_from_db as $creation): 
                        $title = $creation->title;
                        $description = $creation->description ?: $creation->prompt;
                        $image_url = $creation->image_url;
                        $audio_url = $creation->audio_url;
                        $duration = $creation->duration;
                        $creation_date = date('j M Y, H:i', strtotime($creation->created_at));
                        
                        // Override con attachment WordPress se disponibili
                        if ($creation->image_id) {
                            $wp_image = wp_get_attachment_image_url($creation->image_id, 'medium');
                            if ($wp_image) $image_url = $wp_image;
                        }
                        if ($creation->audio_id) {
                            $wp_audio = wp_get_attachment_url($creation->audio_id);
                            if ($wp_audio) $audio_url = $wp_audio;
                        }
                        ?>
                        
                        <div class="creation-item" 
                             data-id="<?php echo esc_attr($creation->id); ?>"
                             data-title="<?php echo esc_attr($title); ?>"
                             data-description="<?php echo esc_attr($description); ?>"
                             data-image="<?php echo esc_url($image_url); ?>"
                             data-audio="<?php echo esc_url($audio_url); ?>"
                             data-source="database"
                             onclick="openFullscreen(this)">
                            
                            <div class="creation-thumbnail">
                                <?php if ($image_url): ?>
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                                <?php else: ?>
                                    <div class="thumbnail-placeholder">🎵</div>
                                <?php endif; ?>
                                
                                <?php if ($audio_url): ?>
                                    <div class="play-overlay">
                                        <div class="play-icon">▶️</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="creation-info">
                                <h3 class="creation-title"><?php echo esc_html($title); ?></h3>
                                <p class="creation-description"><?php echo esc_html($description); ?></p>
                                <div class="creation-meta">
                                    <div class="meta-item">
                                        <span>📅</span>
                                        <span><?php echo $creation_date; ?></span>
                                    </div>
                                    <?php if ($duration): ?>
                                        <div class="meta-item">
                                            <span>⏱️</span>
                                            <span><?php echo esc_html($duration); ?>s</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="meta-item">
                                        <span>💾</span>
                                        <span>Database</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="creation-actions">
                                <?php if ($audio_url): ?>
                                    <button class="action-btn" onclick="event.stopPropagation(); downloadAudio('<?php echo esc_url($audio_url); ?>', '<?php echo esc_js($title); ?>')">
                                        ⬇️ Scarica
                                    </button>
                                <?php endif; ?>
                                <button class="action-btn btn-delete" onclick="event.stopPropagation(); deleteCreationDB(<?php echo $creation->id; ?>, '<?php echo esc_js($title); ?>')">
                                    🗑️ Elimina
                                </button>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    
                <?php elseif ($show_attachments): ?>
                    <!-- MOSTRA ATTACHMENT WORDPRESS (SOLO PICTOSOUND) -->
                    <?php while ($attachment_query->have_posts()): $attachment_query->the_post();
                        $attachment_id = get_the_ID();
                        $title = get_the_title() ?: 'Creazione del ' . get_the_date('j M Y');
                        $audio_id = get_post_meta($attachment_id, '_pictosound_audio_id', true);
                        $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';
                        $prompt = get_post_meta($attachment_id, '_pictosound_prompt', true);
                        $description = $prompt ?: 'Nessuna descrizione disponibile';
                        $duration = get_post_meta($attachment_id, '_pictosound_duration', true);
                        $creation_date = get_the_date('j M Y, H:i');
                        
                        // Se non c'è audio associato, controlla se il file stesso è audio
                        if (!$audio_url && strpos(get_post_mime_type($attachment_id), 'audio') !== false) {
                            $audio_url = wp_get_attachment_url($attachment_id);
                        }
                        
                        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
                        $image_full = wp_get_attachment_image_url($attachment_id, 'large');
                        ?>
                        
                        <div class="creation-item" 
                             data-id="<?php echo esc_attr($attachment_id); ?>"
                             data-title="<?php echo esc_attr($title); ?>"
                             data-description="<?php echo esc_attr($description); ?>"
                             data-image="<?php echo esc_url($image_full ?: $image_url); ?>"
                             data-audio="<?php echo esc_url($audio_url); ?>"
                             data-source="attachment"
                             onclick="openFullscreen(this)">
                            
                            <div class="creation-thumbnail">
                                <?php if ($image_url): ?>
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                                <?php else: ?>
                                    <div class="thumbnail-placeholder"><?php echo $audio_url ? '🎵' : '📁'; ?></div>
                                <?php endif; ?>
                                
                                <?php if ($audio_url): ?>
                                    <div class="play-overlay">
                                        <div class="play-icon">▶️</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="creation-info">
                                <h3 class="creation-title"><?php echo esc_html($title); ?></h3>
                                <p class="creation-description"><?php echo esc_html($description); ?></p>
                                <div class="creation-meta">
                                    <div class="meta-item">
                                        <span>📅</span>
                                        <span><?php echo $creation_date; ?></span>
                                    </div>
                                    <?php if ($duration): ?>
                                        <div class="meta-item">
                                            <span>⏱️</span>
                                            <span><?php echo esc_html($duration); ?>s</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="meta-item">
                                        <span>🎵</span>
                                        <span>Pictosound</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="creation-actions">
                                <?php if ($audio_url): ?>
                                    <button class="action-btn" onclick="event.stopPropagation(); downloadAudio('<?php echo esc_url($audio_url); ?>', '<?php echo esc_js($title); ?>')">
                                        ⬇️ Scarica
                                    </button>
                                <?php endif; ?>
                                <button class="action-btn btn-delete" onclick="event.stopPropagation(); deleteAttachment(<?php echo $attachment_id; ?>, '<?php echo esc_js($title); ?>')">
                                    🗑️ Elimina
                                </button>
                            </div>
                        </div>
                        
                    <?php endwhile; ?>
                    
                <?php endif; ?>
                
            </div>
            
        <?php else: ?>
            
            <!-- EMPTY STATE -->
            <div class="gallery-empty">
                <div style="font-size: 6rem; margin-bottom: 20px; opacity: 0.7;">🎵</div>
                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 1.5rem;">Nessuna creazione Pictosound trovata</h3>
                <p style="margin: 0 0 20px 0; color: #666;">La tua galleria Pictosound è vuota. Inizia a creare musica con il nostro generatore!</p>
                <a href="/" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 1.1rem;">
                    🎼 Crea la Tua Prima Musica
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>

    <!-- FULLSCREEN MODAL -->
    <div class="fullscreen-modal" id="fullscreenModal">
        <button class="close-btn" onclick="closeFullscreen()">&times;</button>
        
        <div class="fullscreen-content">
            <img class="fullscreen-image" id="fullscreenImage" src="" alt="" />
            
            <div class="fullscreen-controls">
                <h2 class="fullscreen-title" id="fullscreenTitle"></h2>
                <p class="fullscreen-description" id="fullscreenDescription"></p>
                
                <div class="audio-player-full" id="audioPlayerFull" style="display: none;">
                    <button class="play-btn-full" id="playBtnFull">▶️</button>
                    
                    <div class="progress-container">
                        <div class="progress-bar" id="progressBar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="time-info">
                            <span id="currentTime">0:00</span>
                            <span id="totalTime">0:00</span>
                        </div>
                    </div>
                </div>
                
                <audio id="fullscreenAudio" preload="none"></audio>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
    jQuery(document).ready(function($) {
        let currentAudio = null;
        let isPlaying = false;

        // FULLSCREEN FUNCTIONS
        window.openFullscreen = function(element) {
            const title = element.dataset.title;
            const description = element.dataset.description;
            const image = element.dataset.image;
            const audio = element.dataset.audio;

            document.getElementById('fullscreenTitle').textContent = title;
            document.getElementById('fullscreenDescription').textContent = description;
            
            const imageElement = document.getElementById('fullscreenImage');
            if (image) {
                imageElement.src = image;
                imageElement.style.display = 'block';
            } else {
                imageElement.style.display = 'none';
            }

            if (audio) {
                const audioPlayer = document.getElementById('audioPlayerFull');
                const audioElement = document.getElementById('fullscreenAudio');
                
                audioPlayer.style.display = 'flex';
                audioElement.src = audio;
                currentAudio = audioElement;

                document.getElementById('playBtnFull').textContent = '▶️';
                document.getElementById('progressFill').style.width = '0%';
                document.getElementById('currentTime').textContent = '0:00';
                document.getElementById('totalTime').textContent = '0:00';
                isPlaying = false;

                setTimeout(() => {
                    playFullscreenAudio();
                }, 500);
            } else {
                document.getElementById('audioPlayerFull').style.display = 'none';
            }

            document.getElementById('fullscreenModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.closeFullscreen = function() {
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                currentAudio = null;
                isPlaying = false;
            }

            document.getElementById('fullscreenModal').classList.remove('active');
            document.body.style.overflow = '';
        };

        function playFullscreenAudio() {
            if (!currentAudio) return;

            if (isPlaying) {
                currentAudio.pause();
                document.getElementById('playBtnFull').textContent = '▶️';
                isPlaying = false;
            } else {
                currentAudio.play().then(() => {
                    document.getElementById('playBtnFull').textContent = '⏸️';
                    isPlaying = true;
                }).catch(error => {
                    console.error('Errore riproduzione audio:', error);
                });
            }
        }

        // AUDIO EVENTS
        document.getElementById('playBtnFull').addEventListener('click', playFullscreenAudio);

        document.getElementById('fullscreenAudio').addEventListener('loadedmetadata', function() {
            const duration = this.duration;
            const minutes = Math.floor(duration / 60);
            const seconds = Math.floor(duration % 60);
            document.getElementById('totalTime').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        });

        document.getElementById('fullscreenAudio').addEventListener('timeupdate', function() {
            const progress = (this.currentTime / this.duration) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
            
            const currentMinutes = Math.floor(this.currentTime / 60);
            const currentSeconds = Math.floor(this.currentTime % 60);
            document.getElementById('currentTime').textContent = `${currentMinutes}:${currentSeconds.toString().padStart(2, '0')}`;
        });

        document.getElementById('fullscreenAudio').addEventListener('ended', function() {
            document.getElementById('playBtnFull').textContent = '▶️';
            document.getElementById('progressFill').style.width = '0%';
            document.getElementById('currentTime').textContent = '0:00';
            isPlaying = false;
        });

        // KEYBOARD CONTROLS
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('fullscreenModal').classList.contains('active')) {
                switch(e.key) {
                    case 'Escape':
                        closeFullscreen();
                        break;
                    case ' ':
                        e.preventDefault();
                        playFullscreenAudio();
                        break;
                }
            }
        });

        // UTILITY FUNCTIONS
        window.downloadAudio = function(audioUrl, title) {
            const link = document.createElement('a');
            link.href = audioUrl;
            link.download = title + '.mp3';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        window.deleteCreationDB = function(creationId, title) {
            if (!confirm(`Elimina "${title}"?`)) return;
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'pictosound_delete_creation_db',
                    creation_id: creationId,
                    _wpnonce: '<?php echo wp_create_nonce('pictosound_delete_creation_db'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + (response.data?.message || 'Errore sconosciuto'));
                    }
                },
                error: function() {
                    alert('Errore di connessione');
                }
            });
        };

        window.deleteAttachment = function(attachmentId, title) {
            if (!confirm(`Elimina "${title}"?`)) return;
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'pictosound_delete_media',
                    media_id: attachmentId,
                    _wpnonce: '<?php echo wp_create_nonce('pictosound_delete_media'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + (response.data?.message || 'Errore sconosciuto'));
                    }
                },
                error: function() {
                    alert('Errore di connessione');
                }
            });
        };

        // Close modal on overlay click
        document.getElementById('fullscreenModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFullscreen();
            }
        });
    });
    </script>

    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('pictosound_user_gallery', 'pictosound_cm_user_gallery_shortcode');

/**
 * AJAX Handlers per eliminazione
 */
function pictosound_cm_ajax_delete_creation_db() {
    if (!check_ajax_referer('pictosound_delete_creation_db', '_wpnonce', false)) {
        wp_send_json_error(['message' => 'Sessione scaduta']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Login richiesto']);
        return;
    }

    $creation_id = isset($_POST['creation_id']) ? intval($_POST['creation_id']) : 0;
    
    if (!$creation_id) {
        wp_send_json_error(['message' => 'ID non valido']);
        return;
    }

    global $wpdb;
    $table_name = 'aDOtz4PiG8_pictosound_creations'; // ✅ NOME COMPLETO CORRETTO!
    
    $creation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d AND user_id = %d",
        $creation_id, get_current_user_id()
    ));
    
    if (!$creation) {
        wp_send_json_error(['message' => 'Creazione non trovata']);
        return;
    }

    $deleted = $wpdb->delete(
        $table_name,
        ['id' => $creation_id, 'user_id' => get_current_user_id()],
        ['%d', '%d']
    );
    
    if ($deleted) {
        wp_send_json_success(['message' => 'Eliminata con successo']);
    } else {
        wp_send_json_error(['message' => 'Errore eliminazione']);
    }
}
add_action('wp_ajax_pictosound_delete_creation_db', 'pictosound_cm_ajax_delete_creation_db');

function pictosound_cm_ajax_delete_media() {
    if (!check_ajax_referer('pictosound_delete_media', '_wpnonce', false)) {
        wp_send_json_error(['message' => 'Sessione scaduta']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Login richiesto']);
        return;
    }

    $media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;
    
    if (!$media_id) {
        wp_send_json_error(['message' => 'ID non valido']);
        return;
    }

    $attachment = get_post($media_id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        wp_send_json_error(['message' => 'Media non trovato']);
        return;
    }

    if (intval($attachment->post_author) !== get_current_user_id()) {
        wp_send_json_error(['message' => 'Permesso negato']);
        return;
    }

    $deleted = wp_delete_attachment($media_id, true);
    
    if ($deleted) {
        wp_send_json_success(['message' => 'Eliminato con successo']);
    } else {
        wp_send_json_error(['message' => 'Errore eliminazione']);
    }
}
add_action('wp_ajax_pictosound_delete_media', 'pictosound_cm_ajax_delete_media');
