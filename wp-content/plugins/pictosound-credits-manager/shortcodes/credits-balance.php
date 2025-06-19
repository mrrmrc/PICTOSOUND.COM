function pictosound_cm_user_credits_balance_shortcode() {
    if ( ! is_user_logged_in() ) {
        return ''; // Non mostra nulla se l'utente non è loggato.
    }

    $user_id = get_current_user_id();
    $credits = pictosound_cm_get_user_credits( $user_id );
    $recharge_page_url = 'https://pictosound.com/ricarica-crediti/';

    // Icona SVG per i crediti. Veloce da caricare e facilmente stilizzabile.
    $coin_icon_svg = '<svg class="pictosound-credits-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2Zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8-8-3.589 8-8 8Z"></path><path d="M13.293 8.707a1 1 0 0 0-1.414-1.414l-4 4a1 1 0 0 0 0 1.414l4 4a1 1 0 0 0 1.414-1.414L10.414 12l2.879-2.879Z"></path></svg>';

    // Usa una variabile statica per assicurarsi che gli stili CSS vengano inseriti una sola volta per pagina.
    static $styles_printed = false;
    $styles_html = '';
    if ( ! $styles_printed ) {
        $styles_printed = true;
        // Heredoc syntax per inserire un blocco di CSS in modo pulito.
        $styles_html = <<<HTML
<style>
    :root {
        --pictosound-primary-color: #007cba;
        --pictosound-primary-hover: #005a87;
        --pictosound-gold-color: #ffc107;
        --pictosound-bg-color: #f0f5f9;
        --pictosound-text-color: #334155;
        --pictosound-border-color: #e2e8f0;
    }
    .pictosound-credits-balance {
        display: inline-flex;
        align-items: center;
        background-color: var(--pictosound-bg-color);
        border: 1px solid var(--pictosound-border-color);
        border-radius: 50px; /* Forma a pillola */
        padding: 5px 8px 5px 12px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        vertical-align: middle; /* Allineamento migliore con il testo circostante */
    }
    .pictosound-credits-balance:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .pictosound-credits-icon {
        width: 20px;
        height: 20px;
        color: var(--pictosound-gold-color);
        margin-right: 8px;
        flex-shrink: 0;
    }
    .pictosound-credits-text {
        color: var(--pictosound-text-color);
        font-weight: 500;
        margin-right: 12px;
        white-space: nowrap;
    }
    .pictosound-credits-text .credits-amount {
        font-weight: 700;
        color: var(--pictosound-primary-color);
    }
    .pictosound-recharge-link {
        background: var(--pictosound-primary-color);
        color: white !important; /* !important per sovrascrivere stili del tema */
        padding: 6px 14px;
        border-radius: 20px;
        text-decoration: none !important; /* Rimuove sottolineatura */
        font-weight: bold;
        font-size: 13px;
        transition: background-color 0.2s ease, transform 0.2s ease;
        display: inline-block;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        border: none;
    }
    .pictosound-recharge-link:hover, 
    .pictosound-recharge-link:focus {
        background: var(--pictosound-primary-hover);
        color: white !important;
        transform: scale(1.05);
    }
</style>
HTML;
    }

    // Usa ob_start per costruire l'HTML in modo leggibile.
    ob_start();

    // Inserisce gli stili (solo la prima volta)
    echo $styles_html;
    ?>
    
    <div class="pictosound-credits-balance">
        <?php echo $coin_icon_svg; ?>
        <span class="pictosound-credits-text">
            Saldo: <span class="credits-amount"><?php echo esc_html( $credits ); ?></span>
        </span>
        <a href="<?php echo esc_url($recharge_page_url); ?>" class="pictosound-recharge-link">Ricarica</a>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'pictosound_credits_balance', 'pictosound_cm_user_credits_balance_shortcode' );

