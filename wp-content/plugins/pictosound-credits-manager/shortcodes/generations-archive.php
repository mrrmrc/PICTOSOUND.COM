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
        
            // Blocco della miniatura
            echo '<div class="generation-thumbnail">';
            $valid_image_url = '';
            if (!empty($generation->image_url) && filter_var($generation->image_url, FILTER_VALIDATE_URL)) {
                $valid_image_url = $generation->image_url;
            }
            
            $modal_trigger_class = $valid_image_url ? 'open-generation-modal' : '';
        
            echo '<a href="#" class="' . $modal_trigger_class . '" data-full-image-url="' . esc_url($valid_image_url) . '" data-audio-url="' . esc_url($generation->audio_url) . '" data-prompt="' . esc_attr($generation->prompt) . '">';
            if ($valid_image_url) {
                echo '<img src="' . esc_url($valid_image_url) . '" loading="lazy" onerror="this.parentElement.innerHTML=\'<div class=\\\'ps-no-image-placeholder\\\'>🎵</div>\'" />';
            } else {
                echo '<div class="ps-no-image-placeholder">🎵</div>';
            }
            echo '</a>';
            echo '</div>'; // Fine di .generation-thumbnail
        
            // Blocco dettagli che ora CONTIENE anche il player
            echo '<div class="generation-details">';
                echo '<strong class="generation-prompt">' . esc_html($generation->prompt) . '</strong>';
                echo '<div class="generation-meta"><span><strong>Data:</strong> ' . date_i18n(get_option('date_format'), strtotime($generation->created_at)) . '</span><span><strong>Durata:</strong> ' . esc_html($generation->duration) . 's</span></div>';
                
                // Il player è stato spostato qui dentro
                echo '<div class="generation-player-action">';
                    echo '<audio class="original-audio-player" controls preload="none" src="' . esc_url($generation->audio_url) . '"></audio>';
                    echo '<a href="' . esc_url($generation->audio_url) . '" class="download-button-archive" download>Scarica</a>';
                echo '</div>';
            echo '</div>'; // Fine di .generation-details
            
            echo '</li>';
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
        /* =================================
           STILI ARCHIVIO GENERAZIONI
           ================================= */

        /* Layout Principale della Riga */
        .ps-generation-list {
            list-style: none;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .generation-item {
            display: flex; /* <-- La magia di Flexbox per allineare gli elementi */
            align-items: flex-start; /* Allinea gli elementi all'inizio del contenitore */
            gap: 25px; /* Spazio tra miniatura e dettagli */
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.3s ease;
        }
        .generation-item:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Miniatura Immagine */
        .generation-thumbnail {
            width: 120px;
            height: 120px;
            flex-shrink: 0; /* Impedisce alla miniatura di rimpicciolirsi */
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .generation-thumbnail:hover {
            transform: scale(1.05);
        }
        .generation-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        .ps-no-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            border-radius: 8px;
        }


        /* Dettagli (Prompt e Player) */
        .generation-details {
            flex: 1; /* Occupa tutto lo spazio rimanente */
            display: flex;
            flex-direction: column; /* Mette gli elementi in colonna */
            gap: 15px; /* Spazio tra prompt, meta e player */
        }
        .generation-prompt {
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
        }
        .generation-meta {
            font-size: 0.85rem;
            color: #64748b;
        }
        .generation-meta span {
            margin-right: 15px;
        }

        /* Player e Pulsante Download */
        .generation-player-action {
            display: flex;
            align-items: center;
            flex-wrap: wrap; /* Va a capo su schermi piccoli */
            gap: 15px;
        }
        .original-audio-player {
            width: 100%;
            max-width: 350px; /* Limite massimo per non essere enorme */
            height: 40px;
        }
        .download-button-archive {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        .download-button-archive:hover {
            background: #5a67d8;
        }

        /* =================================
           STILE DELLA FINESTRA MODALE (POPUP)
           ================================= */
        .ps-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85);
            z-index: 10000;
            display: none; /* Verrà cambiato in 'flex' dal JS */
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeInOverlay 0.3s ease;
        }
        @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }

        .ps-modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 90vw;
            max-height: 90vh;
            position: relative;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2), 0 10px 10px -5px rgba(0,0,0,0.1);
            animation: slideInModal 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        @keyframes slideInModal { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .ps-modal-close {
            position: absolute;
            top: 10px; right: 15px;
            font-size: 2.5rem;
            color: #999;
            cursor: pointer;
            transition: color 0.3s ease;
            line-height: 1;
        }
        .ps-modal-close:hover {
            color: #333;
        }

        #ps-modal-image {
            display: block; /* Assicura che sia visibile */
            max-width: 100%;
            max-height: 60vh; /* Limita l'altezza per lasciare spazio al player */
            margin-bottom: 20px;
            border-radius: 10px;
        }

        #ps-modal-audio-container h4 {
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            color: #333;
        }
        #ps-modal-audio-container audio {
            width: 100%;
        }

        /* Stili per il prompt di login e archivio vuoto */
        .ps-archive-login-prompt, .pictosound-no-generations {
            background: #f8f9fa;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .generation-item {
                flex-direction: column;
                align-items: stretch;
            }
            .generation-thumbnail {
                width: auto;
                height: 180px; /* Altezza fissa per coerenza */
                cursor: pointer;
            }
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