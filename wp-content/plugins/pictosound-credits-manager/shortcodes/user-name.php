<?php
function pictosound_cm_user_name_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $current_user = wp_get_current_user();
    $name_text = trim( $current_user->first_name . ' ' . $current_user->last_name );
    $display_name = !empty($name_text) ? $name_text : ($current_user->display_name ? $current_user->display_name : $current_user->user_login);

    // Logica per ottenere l'URL del profilo (simile a pictosound_cm_user_area_shortcode)
    $profile_page_slug = 'profilo-utente'; // Assicurati che questa pagina esista
    $profile_page_url = get_permalink( get_page_by_path( $profile_page_slug ) );
    if ( !$profile_page_url ) {
        $profile_page_url = admin_url( 'profile.php' ); // Fallback alla pagina profilo di WordPress
    }

    // Costruisci il link HTML
    $linked_name = sprintf(
        '<a href="%s" title="%s">%s</a>',
        esc_url( $profile_page_url ),
        esc_attr__( 'Vai al tuo profilo', 'pictosound-credits-manager' ), // Testo per l'attributo title del link
        esc_html( $display_name )
    );

    return $linked_name; // Restituisce il nome come link HTML
}
add_shortcode( 'pictosound_user_name', 'pictosound_cm_user_name_shortcode' );
