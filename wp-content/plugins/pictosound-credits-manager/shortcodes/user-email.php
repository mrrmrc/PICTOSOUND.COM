function pictosound_cm_user_email_shortcode() {
 if ( ! is_user_logged_in() ) return '';
 $current_user = wp_get_current_user();
 return esc_html( $current_user->user_email );
}
add_shortcode( 'pictosound_user_email', 'pictosound_cm_user_email_shortcode' );

