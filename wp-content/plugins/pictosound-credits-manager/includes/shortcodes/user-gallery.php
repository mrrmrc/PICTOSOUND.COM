<?php
function pictosound_cm_user_gallery_shortcode() {
    if (!is_user_logged_in()) {
        return '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 20px 0;">'
            . '<h3 style="margin: 0 0 15px 0;">\ud83d\udd12 Accesso Richiesto</h3>'
            . '<p style="margin: 0 0 20px 0;">Effettua il login per visualizzare la tua gallery</p>'
            . '<a href="/wp-login.php" style="background: white; color: #667eea; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">\ud83d\udd17 ACCEDI ORA</a>'
            . '</div>';
    }

    $user_id = get_current_user_id();
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

    ob_start();
    if ($images) {
        $nonce = wp_create_nonce('pictosound_delete_media');
        ?>
        <div class="pictosound-user-gallery" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;">
            <?php foreach ($images as $img):
                $img_url = wp_get_attachment_image_url($img->ID, 'medium');
                $audio_id = get_post_meta($img->ID, '_pictosound_audio_id', true);
                $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';
                ?>
                <div class="ps-gallery-item" data-id="<?php echo esc_attr($img->ID); ?>" style="position:relative;border:1px solid #e9ecef;border-radius:8px;padding:10px;text-align:center;">
                    <img src="<?php echo esc_url($img_url); ?>" style="max-width:100%;height:auto;border-radius:6px;" />
                    <?php if ($audio_url): ?>
                        <audio controls style="width:100%;margin-top:10px;">
                            <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg" />
                        </audio>
                        <div style="margin-top:8px;">
                            <a href="<?php echo esc_url($audio_url); ?>" download>\u2b07\ufe0f Scarica MP3</a>
                            <button type="button" class="ps-share-btn" data-url="<?php echo esc_url($audio_url); ?>" style="margin-left:10px;">\ud83d\udd0d QR</button>
                        </div>
                        <canvas class="ps-qr-canvas" data-url="<?php echo esc_url($audio_url); ?>" style="display:none;margin-top:10px;"></canvas>
                    <?php endif; ?>
                    <button type="button" class="ps-delete-media" data-id="<?php echo esc_attr($img->ID); ?>" style="position:absolute;top:5px;right:5px;background:#ff4d4d;color:white;border:none;border-radius:3px;padding:2px 6px;cursor:pointer;">\u2716</button>
                </div>
            <?php endforeach; ?>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded',function(){
            document.querySelectorAll('.ps-delete-media').forEach(btn=>{
                btn.addEventListener('click',function(){
                    if(!confirm('Eliminare questo elemento?')) return;
                    const id=this.dataset.id;
                    const data=new FormData();
                    data.append('action','pictosound_delete_media');
                    data.append('media_id',id);
                    data.append('_wpnonce','<?php echo $nonce; ?>');
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>',{method:'POST',credentials:'same-origin',body:data})
                    .then(r=>r.json()).then(res=>{if(res.success){btn.closest('.ps-gallery-item').remove();}else{alert(res.data?.message||'Errore');}});
                });
            });
            if(typeof QRCode!=='undefined'){
                document.querySelectorAll('.ps-qr-canvas').forEach(canvas=>{
                    const url=canvas.dataset.url;
                    QRCode.toCanvas(canvas,url,{width:120},function(){canvas.style.display='block';});
                });
            }
        });
        </script>
        <?php
    } else {
        echo '<p>Nessuna immagine trovata.</p>';
    }
    return ob_get_clean();
}
add_shortcode('pictosound_user_gallery','pictosound_cm_user_gallery_shortcode');

function pictosound_cm_ajax_delete_media(){
    check_ajax_referer('pictosound_delete_media');
    $media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;
    if(!$media_id){
        wp_send_json_error(['message'=>'ID non valido']);
    }
    $attachment = get_post($media_id);
    if(!$attachment || $attachment->post_type !== 'attachment' || intval($attachment->post_author) !== get_current_user_id()){
        wp_send_json_error(['message'=>'Permesso negato']);
    }
    $deleted = wp_delete_attachment($media_id, true);
    if($deleted){
        wp_send_json_success(['deleted'=>true]);
    } else {
        wp_send_json_error(['message'=>'Errore eliminazione']);
    }
}
add_action('wp_ajax_pictosound_delete_media','pictosound_cm_ajax_delete_media');
