<?php
/**
 * ⚡ PICTOSOUND USER GALLERY - LISTA SEMPLICE CON FULLSCREEN
 * Gallery pulita con lista e visualizzazione fullscreen + audio
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
<<<<<<< HEAD
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    // Query per le creazioni dell'utente
    $query_args = [
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'author' => $user_id,
        'posts_per_page' => $atts['per_page'],
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => '_pictosound_creation',
                'value' => 'yes',
                'compare' => '='
            ],
            [
                'key' => '_pictosound_audio_id',
                'compare' => 'EXISTS'
            ]
        ]
    ];

    $creations = new WP_Query($query_args);
    $total_creations = $creations->found_posts;
=======
    $images = get_posts([
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'author'         => $user_id,
        'meta_query'     => [
            [
                'key'     => '_pictosound_audio_id',
                'compare' => 'EXISTS',
            ],
        ],
        'orderby'        => 'date',
        'order'          => 'DESC'
    ]);
>>>>>>> 3d1f10e2be49bd007057676990093a0c1f8dbba0

    ob_start();
    ?>

    <!-- CSS STYLES -->
    <style>
    /* ================================
       PICTOSOUND GALLERY LISTA STYLES
       ================================ */
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

    /* LISTA CREAZIONI */
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

    /* EMPTY STATE */
    .gallery-empty {
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 20px;
        margin: 40px 0;
    }

    /* FULLSCREEN MODAL */
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

    /* PAGINATION */
    .gallery-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 30px;
    }

    .page-btn {
        padding: 10px 15px;
        border: 1px solid #e9ecef;
        background: white;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .page-btn:hover, .page-btn.current {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    /* RESPONSIVE */
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
        
        .audio-player-full {
            flex-direction: column;
            gap: 10px;
        }
    }

    @media (max-width: 480px) {
        .pictosound-gallery-list {
            padding: 10px;
        }
        
        .gallery-header {
            padding: 20px 15px;
        }
        
        .creation-item {
            padding: 15px;
        }
    }

    /* LOADING STATE */
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* MESSAGE STYLES */
    .gallery-message {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    }

    .gallery-message.success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .gallery-message.error {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    </style>

    <div class="pictosound-gallery-list">
        
        <!-- HEADER -->
        <div class="gallery-header">
            <div class="header-content">
                <h1 style="margin: 0 0 10px 0; font-size: 2.2rem; font-weight: 300;">🎵 La Tua Galleria Musicale</h1>
                <p style="margin: 0; font-size: 1.1rem; opacity: 0.9;">Clicca su una creazione per visualizzarla e ascoltarla</p>
                
                <div class="gallery-stats">
                    <span style="font-weight: bold;"><?php echo $total_creations; ?> creazioni</span>
                    <span style="margin: 0 10px;">•</span>
                    <span><?php echo pictosound_cm_get_user_credits($user_id); ?> crediti disponibili</span>
                </div>
            </div>
        </div>

        <!-- MESSAGE AREA -->
        <div id="gallery-messages"></div>

        <!-- GALLERY CONTENT -->
        <?php if ($creations->have_posts()): ?>
            
            <div class="creations-list">
                <?php while ($creations->have_posts()): $creations->the_post();
                    $attachment_id = get_the_ID();
                    $attachment = get_post($attachment_id);
                    
                    // Metadata della creazione
                    $audio_id = get_post_meta($attachment_id, '_pictosound_audio_id', true);
                    $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';
                    $prompt = get_post_meta($attachment_id, '_pictosound_prompt', true) ?: get_post_meta($attachment_id, '_pictosound_description', true);
                    $duration = get_post_meta($attachment_id, '_pictosound_duration', true);
                    $creation_date = get_the_date('j M Y, H:i');
                    
                    // Immagine
                    $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
                    $image_full = wp_get_attachment_image_url($attachment_id, 'large');
                    
                    // Titolo (dal nome file o personalizzato)
                    $title = get_the_title() ?: 'Creazione del ' . get_the_date('j M Y');
                    
                    // Descrizione (prompt o descrizione AI)
                    $description = $prompt ?: 'Nessuna descrizione disponibile';
                    ?>
                    
                    <div class="creation-item" 
                         data-id="<?php echo esc_attr($attachment_id); ?>"
                         data-title="<?php echo esc_attr($title); ?>"
                         data-description="<?php echo esc_attr($description); ?>"
                         data-image="<?php echo esc_url($image_full ?: $image_url); ?>"
                         data-audio="<?php echo esc_url($audio_url); ?>"
                         onclick="openFullscreen(this)">
                        
                        <!-- THUMBNAIL -->
                        <div class="creation-thumbnail">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" 
                                     alt="<?php echo esc_attr($title); ?>"
                                     loading="lazy" />
                            <?php else: ?>
                                <div class="thumbnail-placeholder">🎵</div>
                            <?php endif; ?>
                            
                            <?php if ($audio_url): ?>
                                <div class="play-overlay">
                                    <div class="play-icon">▶️</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- INFO -->
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
                                <?php if ($audio_url): ?>
                                    <div class="meta-item">
                                        <span>🎵</span>
                                        <span>Audio disponibile</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- ACTIONS -->
                        <div class="creation-actions">
                            <?php if ($audio_url): ?>
                                <button class="action-btn" 
                                        onclick="event.stopPropagation(); downloadAudio('<?php echo esc_url($audio_url); ?>', '<?php echo esc_js($title); ?>')"
                                        title="Scarica MP3">
                                    ⬇️ Scarica
                                </button>
                            <?php endif; ?>
                            <button class="action-btn btn-delete" 
                                    onclick="event.stopPropagation(); deleteCreation(<?php echo $attachment_id; ?>, '<?php echo esc_js($title); ?>')"
                                    title="Elimina creazione">
                                🗑️ Elimina
                            </button>
                        </div>
                    </div>
                    
                <?php endwhile; ?>
            </div>
            
            <!-- PAGINATION -->
            <?php if ($creations->max_num_pages > 1): ?>
                <div class="gallery-pagination">
                    <?php
                    $current_page = max(1, $paged);
                    $pagination_args = [
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $current_page,
                        'total' => $creations->max_num_pages,
                        'prev_text' => '← Precedente',
                        'next_text' => 'Successiva →',
                        'type' => 'array'
                    ];
                    
                    $pagination_links = paginate_links($pagination_args);
                    if ($pagination_links) {
                        foreach ($pagination_links as $link) {
                            echo str_replace(['page-numbers', 'current'], ['page-btn', 'page-btn current'], $link);
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            
            <!-- EMPTY STATE -->
            <div class="gallery-empty">
                <div style="font-size: 6rem; margin-bottom: 20px; opacity: 0.7;">🎵</div>
                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 1.5rem;">Nessuna creazione trovata</h3>
                <p style="margin: 0 0 20px 0; color: #666;">La tua galleria è vuota. Inizia a creare musica con Pictosound!</p>
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
        console.log('Pictosound Gallery Lista: Inizializzazione...');

        let currentAudio = null;
        let isPlaying = false;

        // ===============================
        // FULLSCREEN FUNCTIONS
        // ===============================
        window.openFullscreen = function(element) {
            const title = element.dataset.title;
            const description = element.dataset.description;
            const image = element.dataset.image;
            const audio = element.dataset.audio;

            console.log('Apertura fullscreen:', title);

            // Populate modal
            document.getElementById('fullscreenTitle').textContent = title;
            document.getElementById('fullscreenDescription').textContent = description;
            document.getElementById('fullscreenImage').src = image;

            // Setup audio if available
            if (audio) {
                const audioPlayer = document.getElementById('audioPlayerFull');
                const audioElement = document.getElementById('fullscreenAudio');
                
                audioPlayer.style.display = 'flex';
                audioElement.src = audio;
                currentAudio = audioElement;

                // Reset player state
                document.getElementById('playBtnFull').textContent = '▶️';
                document.getElementById('progressFill').style.width = '0%';
                document.getElementById('currentTime').textContent = '0:00';
                document.getElementById('totalTime').textContent = '0:00';
                isPlaying = false;

                // Auto-play
                setTimeout(() => {
                    playFullscreenAudio();
                }, 500);
            } else {
                document.getElementById('audioPlayerFull').style.display = 'none';
            }

            // Show modal
            document.getElementById('fullscreenModal').classList.add('active');
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        };

        window.closeFullscreen = function() {
            console.log('Chiusura fullscreen');
            
            // Stop audio if playing
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                currentAudio = null;
                isPlaying = false;
            }

            // Hide modal
            document.getElementById('fullscreenModal').classList.remove('active');
            
            // Restore body scroll
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
                    showMessage('❌ Errore durante la riproduzione audio', 'error');
                });
            }
        }

        // ===============================
        // AUDIO EVENTS
        // ===============================
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

        // Progress bar click
        document.getElementById('progressBar').addEventListener('click', function(e) {
            if (!currentAudio) return;
            
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const width = rect.width;
            const percentage = clickX / width;
            
            currentAudio.currentTime = currentAudio.duration * percentage;
        });

        // ===============================
        // KEYBOARD CONTROLS
        // ===============================
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
                    case 'ArrowLeft':
                        if (currentAudio) {
                            currentAudio.currentTime = Math.max(0, currentAudio.currentTime - 10);
                        }
                        break;
                    case 'ArrowRight':
                        if (currentAudio) {
                            currentAudio.currentTime = Math.min(currentAudio.duration, currentAudio.currentTime + 10);
                        }
                        break;
                }
            }
        });

        // ===============================
        // UTILITY FUNCTIONS
        // ===============================
        window.downloadAudio = function(audioUrl, title) {
            console.log('Download audio:', title);
            
            const link = document.createElement('a');
            link.href = audioUrl;
            link.download = title + '.mp3';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showMessage('🎵 Download iniziato per "' + title + '"', 'success');
        };

        window.deleteCreation = function(creationId, title) {
            if (!confirm(`Sei sicuro di voler eliminare "${title}"?\n\nQuesta azione non può essere annullata.`)) {
                return;
            }

            console.log('Eliminazione creazione:', creationId);
            
            const $item = $(`.creation-item[data-id="${creationId}"]`);
            $item.css('opacity', '0.5').find('.action-btn').prop('disabled', true);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'pictosound_delete_media',
                    media_id: creationId,
                    _wpnonce: '<?php echo wp_create_nonce('pictosound_delete_media'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(400, function() {
                            $(this).remove();
                            
                            // Check if gallery is empty
                            if ($('.creation-item').length === 0) {
                                location.reload();
                            }
                        });
                        showMessage(`✅ "${title}" eliminata con successo`, 'success');
                    } else {
                        $item.css('opacity', '1').find('.action-btn').prop('disabled', false);
                        showMessage('❌ Errore durante l\'eliminazione: ' + (response.data?.message || 'Errore sconosciuto'), 'error');
                    }
                },
                error: function() {
                    $item.css('opacity', '1').find('.action-btn').prop('disabled', false);
                    showMessage('❌ Errore di connessione durante l\'eliminazione', 'error');
                }
            });
        };

        function showMessage(message, type = 'success') {
            const $messagesArea = $('#gallery-messages');
            const messageHtml = `<div class="gallery-message ${type}">${message}</div>`;
            $messagesArea.html(messageHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $messagesArea.fadeOut(() => $messagesArea.empty().show());
            }, 5000);
        }

        // Close modal on overlay click
        document.getElementById('fullscreenModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFullscreen();
            }
        });

        console.log('Pictosound Gallery Lista: Inizializzazione completata');
    });
    </script>

    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('pictosound_user_gallery', 'pictosound_cm_user_gallery_shortcode');

/**
 * ⚡ AJAX Handler per eliminazione media - Migliorato
 */
function pictosound_cm_ajax_delete_media() {
    // Verifica nonce
    if (!check_ajax_referer('pictosound_delete_media', '_wpnonce', false)) {
        wp_send_json_error(['message' => 'Sessione scaduta. Ricarica la pagina.']);
        return;
    }

    // Verifica login
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Devi essere loggato.']);
        return;
    }

    $media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;
    
    if (!$media_id) {
        wp_send_json_error(['message' => 'ID media non valido.']);
        return;
    }

    // Verifica che l'attachment esista
    $attachment = get_post($media_id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        wp_send_json_error(['message' => 'Media non trovato.']);
        return;
    }

    // Verifica che l'utente sia il proprietario
    if (intval($attachment->post_author) !== get_current_user_id()) {
        wp_send_json_error(['message' => 'Non hai i permessi per eliminare questo media.']);
        return;
    }

    // Log dell'operazione
    write_log_cm("Pictosound Gallery: Eliminazione media ID $media_id richiesta da user " . get_current_user_id());

    // Elimina anche l'audio associato se esiste
    $audio_id = get_post_meta($media_id, '_pictosound_audio_id', true);
    if ($audio_id) {
        $audio_deleted = wp_delete_attachment($audio_id, true);
        write_log_cm("Pictosound Gallery: Audio associato ID $audio_id " . ($audio_deleted ? 'eliminato' : 'non eliminato'));
    }

    // Elimina l'attachment principale
    $deleted = wp_delete_attachment($media_id, true);
    
    if ($deleted) {
        write_log_cm("Pictosound Gallery: Media ID $media_id eliminato con successo");
        wp_send_json_success([
            'deleted' => true,
            'message' => 'Media eliminato con successo.'
        ]);
    } else {
        write_log_cm("Pictosound Gallery: Errore nell'eliminazione del media ID $media_id");
        wp_send_json_error(['message' => 'Errore durante l\'eliminazione del media.']);
    }
}
add_action('wp_ajax_pictosound_delete_media', 'pictosound_cm_ajax_delete_media')