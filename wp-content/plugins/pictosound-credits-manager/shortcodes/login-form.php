function pictosound_cm_login_form_shortcode( $atts ) {
   if ( is_user_logged_in() ) {
       $current_user = wp_get_current_user();
       $profile_page_url = get_permalink( get_page_by_path( 'pagina-mio-account' ) ); 
       if (!$profile_page_url) $profile_page_url = admin_url( 'profile.php' );

       return sprintf( __( 'Ciao %1$s! Sei già loggato. Vai al <a href="%2$s">tuo profilo</a> o <a href="%3$s">Esci</a>', 'pictosound-credits-manager' ), 
           esc_html( $current_user->display_name ), 
           esc_url( $profile_page_url ),
           esc_url( wp_logout_url( get_permalink() ) )
       );
   }
   $args = shortcode_atts( [
       'echo'           => false,
       'redirect'       => get_permalink(), 
       'form_id'        => 'loginform',
       'label_username' => __( 'Nome utente o email', 'pictosound-credits-manager' ),
       'label_password' => __( 'Password', 'pictosound-credits-manager' ),
       'label_remember' => __( 'Ricordami', 'pictosound-credits-manager' ),
       'label_log_in'   => __( 'Accedi', 'pictosound-credits-manager' ),
       'remember'       => true,
       'value_remember' => true,
   ], $atts );
   return wp_login_form( $args );
}
add_shortcode( 'pictosound_login_form', 'pictosound_cm_login_form_shortcode' );

