<?php
/**
 * Shortcode per l'archivio delle generazioni.
 */
function pictosound_cm_generations_archive_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ps-archive-login-prompt"><p>Devi effettuare il <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> per vedere le tue creazioni musicali.</p></div>';
    }

    ob_start();

    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'ps_generations';

    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id);
    $generations = $wpdb->get_results($query);

    echo '<div class="pictosound-generations-archive-wrapper">';
    if ($generations) {
        echo '<h3>Le Tue Creazioni Musicali</h3>';
        echo '<ul class="ps-generation-list">';
        foreach ($generations as $generation) {
            echo '<li class="generation-item">';
            
            // ⚡ DEBUG: Controlliamo cosa c'è nel database
            $image_url = $generation->image_url ?? '';
            $debug_info = '';
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $debug_info = '<br><small style="color: #999;">DEBUG: image_url = "' . esc_html($image_url) . '"</small>';
                
                // Verifica se il file esiste fisicamente
                if ($image_url) {
                    $parsed_url = parse_url($image_url);
                    if ($parsed_url && isset($parsed_url['path'])) {
                        $file_path = ABSPATH . ltrim($parsed_url['path'], '/');
                        $file_exists = file_exists($file_path);
                        $debug_info .= '<br><small style="color: #999;">File exists: ' . ($file_exists ? 'YES' : 'NO') . ' (' . $file_path . ')</small>';
                    }
                }
            }
            
            // ⚡ Gestione sicura delle immagini  
            $valid_image_url = '';
            if (!empty($image_url)) {
                // Controlla se è un URL valido
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $valid_image_url = $image_url;
                } 
                // Se non è un URL completo, prova a costruirlo
                elseif (strpos($image_url, '/') === 0) {
                    $valid_image_url = home_url($image_url);
                }
                // Se è solo un nome file, prova a costruire l'URL completo
                elseif (!empty($image_url)) {
                    $valid_image_url = content_url('/uploads/pictosound_images/' . basename($image_url));
                }
            }
            
            echo '<div class="generation-thumbnail">';
            if ($valid_image_url) {
                echo '<a href="#" class="open-generation-modal" data-full-image-url="' . esc_url($valid_image_url) . '" data-audio-url="' . esc_url($generation->audio_url) . '" data-prompt="' . esc_attr($generation->prompt) . '">';
                echo '<img src="' . esc_url($valid_image_url) . '" loading="lazy" onerror="this.parentElement.innerHTML=\'<div style=\\\'width: 100%; height: 120px; background: linear-gradient(45deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 8px;\\\'>🎵</div>\'" />';
                echo '</a>';
            } else {
                echo '<a href="#" class="open-generation-modal" data-full-image-url="" data-audio-url="' . esc_url($generation->audio_url) . '" data-prompt="' . esc_attr($generation->prompt) . '">';
                echo '<div style="width: 100%; height: 120px; background: linear-gradient(45deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 8px;">🎵</div>';
                echo '</a>';
            }
            echo '</div>';
            
            echo '<div class="generation-details"><strong class="generation-prompt">' . esc_html($generation->prompt) . '</strong>' . $debug_info;
            echo '<div class="generation-meta"><span><strong>Data:</strong> ' . date_i18n(get_option('date_format'), strtotime($generation->created_at)) . '</span><span><strong>Durata:</strong> ' . esc_html($generation->duration) . 's</span></div></div>';
            echo '<div class="generation-player-action"><audio class="original-audio-player" controls preload="none" src="' . esc_url($generation->audio_url) . '"></audio><a href="' . esc_url($generation->audio_url) . '" class="download-button-archive" download>Scarica</a></div></li>';
        }
        echo '</ul>';
    } else {
        echo '<div class="pictosound-no-generations"><h3>Nessuna Creazione Trovata</h3><p>Non hai ancora creato nessuna traccia musicale. <a href="/">Inizia ora!</a></p></div>';
    }
    echo '</div>';
    ?>
    <div id="ps-generation-modal" class="ps-modal-overlay"><div class="ps-modal-content"><span class="ps-modal-close">&times;</span><img id="ps-modal-image" src="" /><div id="ps-modal-audio-container"><h4 id="ps-modal-prompt"></h4></div></div></div>
    <?php
    return ob_get_clean();
}
add_shortcode('pictosound_generations_archive', 'pictosound_cm_generations_archive_shortcode');

/**
 * CSS e JS per l'archivio.
 */
function pictosound_cm_archive_styles_and_scripts() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pictosound_generations_archive')) {
        ?>
        <style>
        .ps-generation-list {
            list-style: none;
            padding: 0;
        }
        .generation-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        .generation-thumbnail {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }
        .generation-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        .generation-details {
            flex: 1;
        }
        .generation-prompt {
            display: block;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .generation-meta span {
            margin-right: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        .original-audio-player {
            width: 300px;
            margin-bottom: 10px;
        }
        .download-button-archive {
            background: #007cba;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
        }
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
        }
        .ps-modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 80vw;
            max-height: 80vh;
            position: relative;
            text-align: center;
        }
        .ps-modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 2rem;
            cursor: pointer;
        }
        #ps-modal-image {
            max-width: 100%;
            max-height: 60vh;
            margin-bottom: 20px;
        }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(e) {
                const trigger = e.target.closest('.open-generation-modal');
                if (!trigger) return;
                e.preventDefault();
                const modal = document.getElementById('ps-generation-modal');
                if (!modal) return;
                
                const imageUrl = trigger.dataset.fullImageUrl;
                const modalImage = modal.querySelector('#ps-modal-image');
                
                if (imageUrl) {
                    modalImage.src = imageUrl;
                    modalImage.style.display = 'block';
                } else {
                    modalImage.style.display = 'none';
                }
                
                modal.querySelector('#ps-modal-prompt').textContent = trigger.dataset.prompt;
                const audioContainer = modal.querySelector('#ps-modal-audio-container');
                audioContainer.innerHTML = '<h4>' + trigger.dataset.prompt + '</h4>';
                const audioPlayer = document.createElement('audio');
                audioPlayer.controls = true;
                audioPlayer.autoplay = true;
                audioPlayer.src = trigger.dataset.audioUrl;
                audioContainer.appendChild(audioPlayer);
                modal.style.display = 'flex';
            });
            const modal = document.getElementById('ps-generation-modal');
            if (modal) {
                const closeModal = () => {
                    modal.style.display = 'none';
                    const player = modal.querySelector('audio');
                    if (player) player.pause();
                };
                modal.querySelector('.ps-modal-close').addEventListener('click', closeModal);
                modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
                document.addEventListener('keydown', e => { if (e.key === "Escape") closeModal(); });
            }
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'pictosound_cm_archive_styles_and_scripts');
?>