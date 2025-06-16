<?php
/**
 * Plugin Name: Pictosound User Gallery
 * Description: Shortcode [pictosound_user_gallery] shows logged in users their generated images and audio.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pictosound_user_gallery_assets() {
    wp_register_style( 'pictosound-user-gallery-inline', false );
    wp_enqueue_style( 'pictosound-user-gallery-inline' );
    $css = '.pictosound-user-gallery{display:flex;flex-wrap:wrap;gap:20px}.pictosound-user-gallery .ps-item{border:1px solid #ccc;padding:10px;border-radius:4px;width:200px;text-align:center}.pictosound-user-gallery .ps-item img{max-width:100%;height:auto;display:block;margin-bottom:8px}.pictosound-user-gallery .ps-actions a{display:inline-block;margin:4px 2px;font-size:0.9em}.pictosound-user-gallery .ps-qrcode{margin-top:8px}';
    wp_add_inline_style( 'pictosound-user-gallery-inline', $css );

    wp_enqueue_script( 'qrious', 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js', [], '4.0.2', true );
    $js = "document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.ps-qrcode').forEach(function(c){new QRious({element:c,value:c.dataset.url,size:120});});});";
    wp_add_inline_script( 'qrious', $js );
}
add_action( 'wp_enqueue_scripts', 'pictosound_user_gallery_assets' );

function pictosound_user_gallery_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . sprintf( __( 'Please <a href="%s">log in</a> to view your gallery.', 'pictosound-user-gallery' ), esc_url( wp_login_url() ) ) . '</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $table    = $wpdb->prefix . 'ps_generations';
    $items    = [];

    // Handle deletion
    if ( isset( $_GET['ps_delete'] ) ) {
        $del_id = sanitize_text_field( $_GET['ps_delete'] );
        $upload_dir = wp_upload_dir();
        $base_dir   = trailingslashit( $upload_dir['basedir'] ) . 'pictosound/';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table && is_numeric( $del_id ) ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT audio_filename FROM {$table} WHERE id=%d AND user_id=%d", $del_id, $user_id ) );
            if ( $row ) {
                $wpdb->delete( $table, [ 'id' => $del_id, 'user_id' => $user_id ] );
                if ( $row->audio_filename ) {
                    @unlink( $base_dir . $row->audio_filename );
                    $img = preg_replace( '/\.(mp3|wav)$/', '.jpg', $row->audio_filename );
                    @unlink( $base_dir . $img );
                }
            }
        } else {
            $file = basename( $del_id );
            if ( strpos( $file, $user_id . '_' ) === 0 ) {
                @unlink( $base_dir . $file );
                $img = preg_replace( '/\.(mp3|wav)$/', '.jpg', $file );
                @unlink( $base_dir . $img );
            }
        }
        wp_safe_redirect( remove_query_arg( 'ps_delete' ) );
        exit;
    }

    // Fetch generations
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT id, audio_filename, audio_url FROM {$table} WHERE user_id=%d ORDER BY created_at DESC", $user_id ) );
    } else {
        $upload_dir = wp_upload_dir();
        $pattern = trailingslashit( $upload_dir['basedir'] ) . 'pictosound/' . $user_id . '_*.*';
        foreach ( glob( $pattern ) as $path ) {
            if ( preg_match( '/\.(mp3|wav)$/i', $path ) ) {
                $items[] = [
                    'id' => basename( $path ),
                    'audio_filename' => basename( $path ),
                    'audio_url' => trailingslashit( $upload_dir['baseurl'] ) . 'pictosound/' . basename( $path ),
                ];
            }
        }
    }

    if ( empty( $items ) ) {
        return '<p>' . __( 'No items found.', 'pictosound-user-gallery' ) . '</p>';
    }

    $output = '<div class="pictosound-user-gallery">';
    foreach ( $items as $item ) {
        $audio_url = isset( $item->audio_url ) ? $item->audio_url : $item['audio_url'];
        $audio_file = isset( $item->audio_filename ) ? $item->audio_filename : $item['audio_filename'];
        $id = isset( $item->id ) ? $item->id : $item['id'];
        $image_url = preg_replace( '/\.(mp3|wav)$/i', '.jpg', $audio_url );
        $del_link = add_query_arg( 'ps_delete', urlencode( $id ) );
        $output .= '<div class="ps-item">';
        $output .= '<img src="' . esc_url( $image_url ) . '" alt="" />';
        $output .= '<audio controls src="' . esc_url( $audio_url ) . '"></audio>';
        $output .= '<div class="ps-actions">';
        $output .= '<a class="ps-delete" href="' . esc_url( $del_link ) . '">' . __( 'Delete', 'pictosound-user-gallery' ) . '</a>';
        $output .= '<a class="ps-download" href="' . esc_url( $audio_url ) . '" download>' . __( 'Download MP3', 'pictosound-user-gallery' ) . '</a>';
        $output .= '<a class="ps-share" href="' . esc_url( $audio_url ) . '" target="_blank">' . __( 'Share', 'pictosound-user-gallery' ) . '</a>';
        $output .= '<canvas class="ps-qrcode" data-url="' . esc_url( $audio_url ) . '"></canvas>';
        $output .= '</div></div>';
    }
    $output .= '</div>';

    return $output;
}
add_shortcode( 'pictosound_user_gallery', 'pictosound_user_gallery_shortcode' );
?>
