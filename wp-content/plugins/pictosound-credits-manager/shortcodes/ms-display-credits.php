function pictosound_ms_display_credits_shortcode_callback( $atts ) {
   $a = shortcode_atts( [
       'etichetta'         => __( 'Crediti Disponibili:', 'pictosound-mostra-saldo' ),
       'testo_non_loggato' => '',
       'mostra_icona'      => 'si',
   ], $atts );

   if ( ! is_user_logged_in() ) {
       return esc_html( $a['testo_non_loggato'] );
   }

   $user_id = get_current_user_id();
   $credits_meta_key = '_pictosound_user_credits';
   $credits = get_user_meta( $user_id, $credits_meta_key, true );
   $saldo = ! empty( $credits ) ? absint( $credits ) : 0;

   $output = '<span class="pictosound-saldo-display-widget">';

   if ( filter_var( $a['mostra_icona'], FILTER_VALIDATE_BOOLEAN ) ) {
       $coin_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1em; height: 1em; vertical-align: -0.125em; display: inline-block; margin-right: 0.2em; opacity:0.8;">
           <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
       </svg>';
       $output .= $coin_icon_svg;
   }

   $output .= esc_html( $a['etichetta'] ) . ' ' . esc_html( $saldo );
   $output .= '</span>';

   return $output;
}
add_shortcode( 'mio_saldo_crediti_pictosound', 'pictosound_ms_display_credits_shortcode_callback' );

