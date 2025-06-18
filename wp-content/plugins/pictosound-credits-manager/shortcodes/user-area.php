function pictosound_cm_user_area_shortcode() {
   ob_start();
   if ( ! is_user_logged_in() ) {
       $login_page_slug = 'login';
       $registration_page_slug = 'registrazione';

       $login_url = get_permalink( get_page_by_path( $login_page_slug ) );
       $registration_url = get_permalink( get_page_by_path( $registration_page_slug ) );

       if ( !$login_url ) $login_url = wp_login_url(get_permalink()); 
       if ( !$registration_url ) $registration_url = wp_registration_url(); 

     ?>
     <div class="pictosound-user-area pictosound-user-area-logged-out">
         <a href="<?php echo esc_url( $login_url ); ?>"><?php _e( 'Login', 'pictosound-credits-manager' ); ?></a>
         <span class="pictosound-user-area-separator" style="margin: 0 5px;">-</span>
         <a href="<?php echo esc_url( $registration_url ); ?>"><?php _e( 'Registrati', 'pictosound-credits-manager' ); ?></a>
     </div>
     <?php
 } else {
     $current_user = wp_get_current_user();
     
     $profile_page_slug = 'profilo-utente';
     $profile_page_url = get_permalink( get_page_by_path( $profile_page_slug ) );
     if ( !$profile_page_url ) $profile_page_url = admin_url( 'profile.php' );

     $logout_url = wp_logout_url( home_url() );

     ?>
     <div class="pictosound-user-area pictosound-user-area-logged-in">
         <span class="user-greeting">
             <?php _e( 'Ciao', 'pictosound-credits-manager' ); ?>, 
             <a href="<?php echo esc_url( $profile_page_url ); ?>" title="<?php esc_attr_e( 'Vai al tuo profilo', 'pictosound-credits-manager' ); ?>">
                 <?php echo esc_html( $current_user->display_name ? $current_user->display_name : $current_user->user_login ); ?>
             </a>!
         </span>
         <span class="user-credits-area" style="margin-left: 10px;">
             <?php echo do_shortcode('[pictosound_credits_balance]'); ?>
         </span>
         <span class="user-actions" style="margin-left: 10px;">
              | <a href="<?php echo esc_url( $logout_url ); ?>"><?php _e( 'Logout', 'pictosound-credits-manager' ); ?></a>
         </span>
     </div>
     <?php
 }
 return ob_get_clean();
}
add_shortcode( 'pictosound_user_area', 'pictosound_cm_user_area_shortcode' );

