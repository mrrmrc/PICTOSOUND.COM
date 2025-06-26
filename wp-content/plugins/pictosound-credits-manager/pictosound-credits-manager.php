<?php
// 🚨 PLUGIN COMPLETO AGGIORNATO CON INTERFACCIA MODERNA - VERSIONE PULITA
/**
 * Plugin Name:        Pictosound Credits Manager (Modern UI + Hosting-Optimized)
 * Plugin URI:         [Lascia vuoto o metti l'URL del tuo sito se vuoi]
 * Description:        Gestisce i crediti utente, la registrazione, il login e l'integrazione per la generazione musicale di Pictosound. Versione con interfaccia moderna per pacchetti crediti.
 * Version:            1.5.0-modern-ui-clean
 * Author:             [Metti il tuo nome o il nome del tuo sito]
 * Author URI:         [Lascia vuoto o metti l'URL del tuo sito se vuoi]
 * License:            GPL-2.0-or-later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        pictosound-credits-manager
 * Domain Path:        /languages
 */

// Impedisci l'accesso diretto al file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// CONFIGURAZIONE STRIPE - SICURA ⚡
// Le chiavi sono ora definite in wp-config.php per maggiore sicurezza
if (!defined('PICTOSOUND_STRIPE_PUBLISHABLE_KEY')) {
    wp_die('ERRORE: Chiavi Stripe non configurate in wp-config.php. Contatta l\'amministratore del sito.');
}
if (!defined('PICTOSOUND_STRIPE_SECRET_KEY')) {
    wp_die('ERRORE: Chiavi Stripe non configurate in wp-config.php. Contatta l\'amministratore del sito.');
}
if (!defined('PICTOSOUND_STRIPE_WEBHOOK_SECRET')) {
    wp_die('ERRORE: Chiavi Stripe non configurate in wp-config.php. Contatta l\'amministratore del sito.');
}


/**
 * Definiamo le costanti
 */
define( 'PICTOSOUND_CREDITS_USER_META_KEY', '_pictosound_user_credits' );
define( 'PICTOSOUND_PRIVACY_OPTIN_META_KEY', '_pictosound_privacy_optin' );

/**
 * ⚡ RATE LIMITING SYSTEM - Hosting-Friendly
 */
function pictosound_cm_check_rate_limit($action, $user_id = null, $limit = 10, $period = HOUR_IN_SECONDS) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $key = "pictosound_rate_limit_{$action}_{$user_id}";
    $attempts = get_transient($key) ?: 0;
    
    if ($attempts >= $limit) {
        write_log_cm("Rate limit exceeded for user $user_id, action $action ($attempts/$limit)");
        return false;
    }
    
    set_transient($key, $attempts + 1, $period);
    return true;
}

/**
 * ⚡ ACTIVITY TRACKING - Per ottimizzare auto-refresh
 */
function pictosound_cm_update_user_activity($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if ($user_id > 0) {
        set_transient("pictosound_user_activity_$user_id", time(), 2 * HOUR_IN_SECONDS);
    }
}

function pictosound_cm_is_user_recently_active($user_id = null, $threshold = 1800) { // 30 minuti default
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $last_activity = get_transient("pictosound_user_activity_$user_id");
    return $last_activity && (time() - $last_activity) < $threshold;
}

/**
 * ⚡ INCLUDE SHORTCODES DA FILE SEPARATI
 */
function pictosound_cm_load_shortcode_files() {
    $shortcode_files = [
        'login-modern.php',
        'credit-packages.php', 
        'payment-return.php',
        'login-form.php',
        'registration-form.php',
        'credits-balance.php',
        'user-area.php',
        'user-name.php',
        'user-email.php',
        'edit-profile.php',
        'user-gallery.php',
        'ms-display-credits.php',
        'generations-archive.php'
    ];
    
    $plugin_dir = plugin_dir_path(__FILE__);
    write_log_cm("Plugin directory: " . $plugin_dir);
    
    $loaded_count = 0;
    $total_count = count($shortcode_files);
    
    foreach ($shortcode_files as $file) {
        $file_path = $plugin_dir . 'shortcodes/' . $file;
        write_log_cm("Tentativo di caricare: " . $file_path);
        
        if (file_exists($file_path)) {
            require_once $file_path;
            $loaded_count++;
            write_log_cm("✅ Shortcode file loaded: $file");
        } else {
            write_log_cm("❌ WARNING: Shortcode file not found: $file_path");
        }
    }
    
    write_log_cm("Shortcode loading summary: $loaded_count/$total_count files loaded successfully");
}

// Carica i file shortcode all'inizializzazione
add_action('init', 'pictosound_cm_load_shortcode_files');

/**
 * Includi Stripe PHP SDK
 */
function pictosound_cm_load_stripe_sdk() {
    if (!class_exists('\Stripe\Stripe')) {
        // Prima tenta di usare Composer
        if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
            require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
        } 
        // Altrimenti usa l'inclusione manuale
        elseif (file_exists(plugin_dir_path(__FILE__) . 'stripe-php/init.php')) {
            require_once plugin_dir_path(__FILE__) . 'stripe-php/init.php';
        }
        // Ultima risorsa: carica da CDN (non raccomandato per produzione)
        else {
            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], '3.0', true);
        }
    }
}
add_action('init', 'pictosound_cm_load_stripe_sdk');

/**
 * Funzione per il logging di debug - Sicura
 */
function write_log_cm($message) {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        // Sanitizza il messaggio per evitare di loggare dati sensibili
        $safe_message = preg_replace('/\b(sk_|pk_)[a-zA-Z0-9_]+/', '[STRIPE_KEY_HIDDEN]', $message);
        error_log('Pictosound CM: ' . $safe_message);
    }
}

/**
 * ⚡ Crea sessione di pagamento Stripe con PayPal - Ottimizzata
 */
function pictosound_cm_create_stripe_session($package_key, $user_id) {
    // Rate limiting per creazione sessioni
    if (!pictosound_cm_check_rate_limit('create_session', $user_id, 5, 10 * MINUTE_IN_SECONDS)) {
        write_log_cm("Rate limit exceeded for Stripe session creation - user $user_id");
        return false;
    }
    
    $packages = pictosound_cm_get_credit_recharge_packages();
    if (!array_key_exists($package_key, $packages)) {
        return false;
    }
    
    $package = $packages[$package_key];
    
    // Converti prezzo da "2.00€" a centesimi
    $amount_euros = floatval(str_replace(['€', ','], ['', '.'], $package['price_simulated']));
    $amount_cents = round($amount_euros * 100); // Stripe usa centesimi
    
    try {
        \Stripe\Stripe::setApiKey(PICTOSOUND_STRIPE_SECRET_KEY);
        
        $session = \Stripe\Checkout\Session::create([
            // ⚡ AGGIUNTO PAYPAL AI METODI DI PAGAMENTO
            'payment_method_types' => ['card', 'paypal'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => sprintf('Ricarica %d Crediti Pictosound', $package['credits']),
                        'description' => sprintf('Pacchetto di %d crediti per Pictosound', $package['credits']),
                        'images' => [
                            'https://pictosound.com/wp-content/uploads/2024/pictosound-logo.png' // Opzionale: logo del prodotto
                        ],
                    ],
                    'unit_amount' => $amount_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => home_url('/pagamento-completato/?status=success&session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => home_url('/pagamento-completato/?status=cancelled'),
            
            // ⚡ CONFIGURAZIONI AGGIUNTIVE PER PAYPAL
            'payment_intent_data' => [
                'setup_future_usage' => null, // Non salvare metodi di pagamento per futuri acquisti
            ],
            
            // ⚡ PERSONALIZZAZIONI UI
            'custom_text' => [
                'submit' => [
                    'message' => 'I tuoi crediti verranno attivati immediatamente dopo il pagamento.'
                ]
            ],
            
            'metadata' => [
                'user_id' => $user_id,
                'package_key' => $package_key,
                'credits' => $package['credits'],
                'plugin' => 'pictosound-credits-manager',
                'timestamp' => time()
            ],
            
            // ⚡ MIGLIORAMENTI UX
            'customer_email' => wp_get_current_user()->user_email ?? null,
            'billing_address_collection' => 'auto', // Raccoglie indirizzo automaticamente se necessario
            'phone_number_collection' => [
                'enabled' => false // Disabilita raccolta telefono per semplificare
            ],
            
            // ⚡ CONFIGURAZIONI LOCALI
            'locale' => 'it', // Interfaccia in italiano
            'automatic_tax' => [
                'enabled' => false // Disabilita calcolo automatico tasse per ora
            ]
        ]);
        
        write_log_cm("Stripe Session (con PayPal) creata per user $user_id, package $package_key, amount $amount_euros EUR");
        
        return $session->url;
        
    } catch (\Exception $e) {
        write_log_cm("Errore Stripe Session: " . $e->getMessage());
        return false;
    }
}

/**
 * ⚡ Gestisce webhook Stripe - Ottimizzato per hosting
 */
function pictosound_cm_handle_stripe_webhook() {
    write_log_cm("Stripe Webhook ricevuto");
    
    // ⚡ RISPOSTA IMMEDIATA per evitare timeout
    http_response_code(200);
    echo 'OK';
    
    // Termina la risposta HTTP se possibile (evita retry di Stripe)
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    // Rate limiting webhook per evitare spam
    $webhook_key = 'pictosound_webhook_' . md5($payload);
    if (get_transient($webhook_key)) {
        write_log_cm("Webhook duplicate detected, skipping");
        exit;
    }
    set_transient($webhook_key, true, 5 * MINUTE_IN_SECONDS);
    
    try {
        \Stripe\Stripe::setApiKey(PICTOSOUND_STRIPE_SECRET_KEY);
        
        // ⚡ Verifica SEMPRE la signature se configurata
        if (PICTOSOUND_STRIPE_WEBHOOK_SECRET !== 'whsec_TUA_WEBHOOK_SECRET') {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, PICTOSOUND_STRIPE_WEBHOOK_SECRET
            );
        } else {
            // ⚠️ SOLO per testing - in produzione SEMPRE verificare
            write_log_cm("WARNING: Webhook signature not verified (development mode)");
            $event = json_decode($payload);
        }
        
        write_log_cm("Stripe Webhook Event Type: " . $event->type);
        
        // Gestisci solo pagamenti completati
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            
            write_log_cm("Stripe: Processing completed session " . $session->id);
            
            // Estrai metadata
            $user_id = intval($session->metadata->user_id ?? 0);
            $package_key = sanitize_text_field($session->metadata->package_key ?? '');
            $credits = intval($session->metadata->credits ?? 0);
            
            if ($user_id > 0 && !empty($package_key)) {
                
                // Controlla se la transazione non è già stata processata
                if (!get_option("stripe_processed_" . $session->id)) {
                    
                    $packages = pictosound_cm_get_credit_recharge_packages();
                    if (array_key_exists($package_key, $packages)) {
                        $credits_to_add = $packages[$package_key]['credits'];
                        
                        // Aggiungi crediti all'utente
                        $success = pictosound_cm_update_user_credits($user_id, $credits_to_add, 'add');
                        
                        if ($success) {
                            // Segna come processata
                            update_option("stripe_processed_" . $session->id, [
                                'user_id' => $user_id,
                                'credits' => $credits_to_add,
                                'amount' => $session->amount_total / 100, // Converti da centesimi
                                'processed_at' => time(),
                                'package_key' => $package_key,
                                'payment_status' => $session->payment_status
                            ]);
                            
                            write_log_cm("Stripe: SUCCESS - Aggiunti $credits_to_add crediti a user $user_id");
                            
                            // Salva transazione per storico
                            pictosound_cm_save_transaction($user_id, $session->id, $packages[$package_key], 'completed', 'stripe');
                        } else {
                            write_log_cm("Stripe: ERRORE - Impossibile aggiornare crediti per user $user_id");
                        }
                    } else {
                        write_log_cm("Stripe: ERRORE - Package key $package_key non valido");
                    }
                } else {
                    write_log_cm("Stripe: SKIP - Session " . $session->id . " già processata");
                }
            } else {
                write_log_cm("Stripe: ERRORE - Metadata mancanti: user_id=$user_id, package_key=$package_key");
            }
        }
        
    } catch (\Exception $e) {
        write_log_cm("Stripe Webhook ERROR: " . $e->getMessage());
        // Non uscire con errore per evitare retry infiniti
    }
    
    exit;
}
add_action('wp_ajax_nopriv_pictosound_stripe_webhook', 'pictosound_cm_handle_stripe_webhook');
add_action('wp_ajax_pictosound_stripe_webhook', 'pictosound_cm_handle_stripe_webhook');

/**
 * Salva transazione nel database
 */
function pictosound_cm_save_transaction($user_id, $transaction_id, $package, $status, $method = 'stripe') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_transactions';
    
    // Verifica se la tabella esiste prima di inserire
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        write_log_cm("ERRORE: Tabella transazioni '$table_name' non esiste. Impossibile salvare la transazione.");
        return;
    }

    $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'transaction_id' => $transaction_id,
            'credits' => $package['credits'],
            'amount' => $package['price_simulated'],
            'status' => $status,
            'method' => $method,
            'created_at' => current_time('mysql')
        ],
        ['%d', '%s', '%d', '%s', '%s', '%s', '%s']
    );
    
    write_log_cm("Transazione salvata: user $user_id, txn $transaction_id, credits {$package['credits']}");
}

/**
 * Crea tabella transazioni all'attivazione del plugin
 */
function pictosound_cm_create_transactions_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_transactions';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        transaction_id varchar(255) NOT NULL,
        credits int(11) NOT NULL,
        amount varchar(10) NOT NULL,
        status varchar(20) NOT NULL,
        method varchar(20) DEFAULT 'stripe',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY transaction_id (transaction_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    write_log_cm("Tabella transazioni creata/aggiornata");
}
register_activation_hook(__FILE__, 'pictosound_cm_create_transactions_table');

/**
 * ⚡ Handler AJAX per rigenerare i nonce - Con rate limiting
 */
function pictosound_cm_regenerate_nonce() {
    write_log_cm("Regenerate nonce chiamata - User logged in: " . (is_user_logged_in() ? 'SI' : 'NO'));
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Utente non loggato', 'pictosound-credits-manager')]);
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Rate limiting per rinnovo nonce
    if (!pictosound_cm_check_rate_limit('regenerate_nonce', $user_id, 20, 10 * MINUTE_IN_SECONDS)) {
        wp_send_json_error(['message' => __('Troppi tentativi di rinnovo. Riprova tra qualche minuto.', 'pictosound-credits-manager')]);
        return;
    }
    
    // Aggiorna attività utente
    pictosound_cm_update_user_activity($user_id);
    
    $new_nonce_recharge = wp_create_nonce('pictosound_recharge_credits_nonce');
    $new_nonce_check = wp_create_nonce('pictosound_check_credits_nonce');
    
    write_log_cm("Nonce rigenerati per user $user_id");
    
    wp_send_json_success([
        'nonce_recharge' => $new_nonce_recharge,
        'nonce_check_credits' => $new_nonce_check,
        'timestamp' => time(),
        'user_id' => $user_id
    ]);
}
add_action('wp_ajax_pictosound_regenerate_nonce', 'pictosound_cm_regenerate_nonce');

/**
 * Ottiene il saldo crediti per un dato utente.
 */
function pictosound_cm_get_user_credits( $user_id = 0 ) {
    if ( empty( $user_id ) ) {
        $user_id = get_current_user_id();
    }
    if ( $user_id <= 0 ) {
        return 0;
    }
    $credits = get_user_meta( $user_id, PICTOSOUND_CREDITS_USER_META_KEY, true );
    return ! empty( $credits ) ? absint( $credits ) : 0;
}

/**
 * Aggiorna il saldo crediti per un dato utente.
 */
function pictosound_cm_update_user_credits( $user_id, $amount, $operation = 'add' ) {
    if ( empty( $user_id ) || $user_id <= 0 ) {
        return false;
    }
    $amount = absint( $amount );
    $current_credits = pictosound_cm_get_user_credits( $user_id );
    $new_credits = $current_credits;

    switch ( $operation ) {
        case 'deduct':
            $new_credits = $current_credits - $amount;
            if ( $new_credits < 0 ) $new_credits = 0;
            break;
        case 'set':
            $new_credits = $amount;
            break;
        case 'add': default:
            $new_credits = $current_credits + $amount;
            break;
    }
    $update_result = update_user_meta( $user_id, PICTOSOUND_CREDITS_USER_META_KEY, $new_credits );
    if ($update_result !== false) return true;
    
    $credits_after_attempt = get_user_meta( $user_id, PICTOSOUND_CREDITS_USER_META_KEY, true );
    return absint($credits_after_attempt) === $new_credits;
}

/**
 * Mostra il campo per i crediti nella pagina del profilo utente (admin).
 */
function pictosound_cm_show_user_profile_credits_field( $user ) {
    if ( ! current_user_can( 'edit_users' ) ) return;
    ?>
    <h3><?php _e( 'Gestione Crediti Pictosound', 'pictosound-credits-manager' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="pictosound_credits"><?php _e( 'Crediti Utente', 'pictosound-credits-manager' ); ?></label></th>
            <td>
                <input type="number" name="pictosound_credits" id="pictosound_credits" value="<?php echo esc_attr( pictosound_cm_get_user_credits( $user->ID ) ); ?>" class="regular-text" min="0" step="1" />
                <p class="description"><?php _e( 'Numero di crediti disponibili per questo utente.', 'pictosound-credits-manager' ); ?></p>
                <?php wp_nonce_field( 'pictosound_save_user_credits_nonce', 'pictosound_credits_nonce_field_admin' ); ?>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'pictosound_cm_show_user_profile_credits_field' );
add_action( 'edit_user_profile', 'pictosound_cm_show_user_profile_credits_field' );

function pictosound_cm_save_user_profile_credits_field( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return false;
    if ( ! isset( $_POST['pictosound_credits_nonce_field_admin'] ) || ! wp_verify_nonce( $_POST['pictosound_credits_nonce_field_admin'], 'pictosound_save_user_credits_nonce' ) ) return false;
    if ( isset( $_POST['pictosound_credits'] ) ) {
        pictosound_cm_update_user_credits( $user_id, absint( $_POST['pictosound_credits'] ), 'set' );
    }
    return true;
}
add_action( 'personal_options_update', 'pictosound_cm_save_user_profile_credits_field' );
add_action( 'edit_user_profile_update', 'pictosound_cm_save_user_profile_credits_field' );

function pictosound_cm_get_duration_costs() {
    return [ '40' => 0, '60' => 1, '120' => 2, '180' => 3, '240' => 4, '360' => 5 ];
}

function pictosound_cm_get_credit_recharge_packages() {
    return [
        '20'  => ['credits' => 20, 'price_simulated' => '2.00€'],
        '40'  => ['credits' => 40, 'price_simulated' => '3.80€'],
        '60'  => ['credits' => 60, 'price_simulated' => '5.50€'],
        '100' => ['credits' => 100, 'price_simulated' => '8.00€'],
    ];
}

/**
 * ⚡ CSS MODERNO PER PACCHETTI CREDITI
 */
function pictosound_cm_modern_packages_styles() {
    ?>
    <style>
    /* =================================
       PICTOSOUND MODERN PACKAGES STYLES
       ================================= */
    
    .pictosound-modern-packages {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        background: transparent;
    }
    
    /* HEADER SECTION */
    .packages-header {
        text-align: center;
        margin-bottom: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 50px 30px;
        border-radius: 20px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .packages-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: headerGlow 6s ease-in-out infinite alternate;
    }
    
    @keyframes headerGlow {
        0% { transform: scale(1) rotate(0deg); }
        100% { transform: scale(1.1) rotate(5deg); }
    }
    
    .header-content {
        position: relative;
        z-index: 1;
    }
    
    .header-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        animation: bounce 2s ease-in-out infinite;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    .header-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        background: linear-gradient(45deg, #fff, #f0f8ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .header-subtitle {
        font-size: 1.2rem;
        margin: 0 0 30px 0;
        opacity: 0.9;
        font-weight: 300;
    }
    
    .current-balance {
        display: inline-flex;
        align-items: center;
        background: rgba(255,255,255,0.2);
        padding: 15px 25px;
        border-radius: 50px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .balance-amount {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 8px;
        color: #ffd700;
        text-shadow: 0 0 10px rgba(255,215,0,0.5);
    }
    
    /* PACKAGES GRID */
    .packages-grid {
        display: grid;
        gap: 30px;
        margin-bottom: 50px;
    }
    
    .packages-grid[data-columns="4"] {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
    
    .packages-grid[data-columns="3"] {
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }
    
    .packages-grid[data-columns="2"] {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    }
    
    /* PACKAGE CARDS */
    .package-card {
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 30px;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 
            0 4px 6px -1px rgba(0, 0, 0, 0.1),
            0 2px 4px -1px rgba(0, 0, 0, 0.06);
        cursor: pointer;
    }
    
    .package-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .package-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 
            0 25px 50px -12px rgba(0, 0, 0, 0.25),
            0 0 0 1px rgba(102, 126, 234, 0.1);
        border-color: #667eea;
    }
    
    .package-card:hover::before {
        transform: scaleX(1);
    }
    
    /* POPULAR PACKAGE */
    .package-card[data-popular="true"] {
        background: linear-gradient(145deg, #667eea, #764ba2);
        color: white;
        transform: scale(1.05);
        box-shadow: 
            0 25px 50px -12px rgba(102, 126, 234, 0.4),
            0 0 0 1px rgba(255, 255, 255, 0.1);
    }
    
    .package-card[data-popular="true"]:hover {
        transform: translateY(-8px) scale(1.08);
    }
    
    /* BADGES */
    .popular-badge {
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(45deg, #ffd700, #ffed4e);
        color: #1a202c;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: translateX(-50%) scale(1); }
        50% { transform: translateX(-50%) scale(1.05); }
    }
    
    .savings-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: linear-gradient(45deg, #ff6b6b, #ee5a52);
        color: white;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
    }
    
    /* PACKAGE CONTENT */
    .package-header {
        text-align: center;
        margin-bottom: 25px;
    }
    
    .package-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
    }
    
    .package-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: #1a202c;
    }
    
    .package-card[data-popular="true"] .package-title {
        color: white;
    }
    
    .package-subtitle {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .package-card[data-popular="true"] .package-subtitle {
        color: rgba(255,255,255,0.8);
    }
    
    .package-price {
        text-align: center;
        margin-bottom: 25px;
        padding: 20px;
        background: rgba(102, 126, 234, 0.05);
        border-radius: 15px;
    }
    
    .package-card[data-popular="true"] .package-price {
        background: rgba(255,255,255,0.1);
    }
    
    .price-main {
        display: flex;
        align-items: baseline;
        justify-content: center;
        margin-bottom: 5px;
    }
    
    .currency {
        font-size: 1.2rem;
        font-weight: 600;
        color: #667eea;
        margin-right: 2px;
    }
    
    .package-card[data-popular="true"] .currency {
        color: #ffd700;
    }
    
    .amount {
        font-size: 3rem;
        font-weight: 800;
        color: #1a202c;
        line-height: 1;
    }
    
    .package-card[data-popular="true"] .amount {
        color: white;
    }
    
    .price-detail {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }
    
    .package-card[data-popular="true"] .price-detail {
        color: rgba(255,255,255,0.7);
    }
    
    /* FEATURES */
    .package-features {
        margin-bottom: 30px;
    }
    
    .feature {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        padding: 8px 0;
    }
    
    .feature-icon {
        width: 20px;
        height: 20px;
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        margin-right: 12px;
        flex-shrink: 0;
    }
    
    .package-card[data-popular="true"] .feature-icon {
        background: linear-gradient(45deg, #ffd700, #ffed4e);
        color: #1a202c;
    }
    
    .feature-text {
        font-size: 0.95rem;
        color: #4a5568;
        font-weight: 500;
    }
    
    .package-card[data-popular="true"] .feature-text {
        color: rgba(255,255,255,0.9);
    }
    
    /* PURCHASE BUTTON */
    .purchase-btn {
        width: 100%;
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 16px 24px;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
    }
    
    .package-card[data-popular="true"] .purchase-btn {
        background: linear-gradient(45deg, #ffd700, #ffed4e);
        color: #1a202c;
        box-shadow: 0 4px 14px rgba(255, 215, 0, 0.4);
    }
    
    .purchase-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .package-card[data-popular="true"] .purchase-btn:hover {
        box-shadow: 0 8px 25px rgba(255, 215, 0, 0.5);
    }
    
    .purchase-btn:active {
        transform: translateY(0);
    }
    
    .btn-icon {
        font-size: 1.1rem;
    }
    
    .loading-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 8px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* TRUST SECTION */
    .trust-section {
        background: linear-gradient(135deg, #f8fafc, #edf2f7);
        padding: 40px 30px;
        border-radius: 20px;
        text-align: center;
        margin-top: 40px;
    }
    
    .trust-icons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        margin-bottom: 25px;
    }
    
    .trust-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .trust-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
        animation: float 3s ease-in-out infinite;
    }
    
    .trust-text {
        font-size: 0.9rem;
        font-weight: 600;
        color: #4a5568;
    }
    
    .trust-description {
        font-size: 1rem;
        color: #64748b;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    /* STATUS MESSAGES */
    .status-message {
        padding: 15px 20px;
        border-radius: 10px;
        margin: 20px 0;
        font-weight: 500;
        text-align: center;
    }
    
    .status-message.success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .status-message.error {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .status-message.warning {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .status-message.info {
        background: linear-gradient(135deg, #cce5ff, #b8daff);
        color: #004085;
        border: 1px solid #b8daff;
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 768px) {
        .pictosound-modern-packages {
            padding: 0 15px;
            margin: 20px auto;
        }
        
        .packages-header {
            padding: 30px 20px;
            margin-bottom: 30px;
        }
        
        .header-title {
            font-size: 2rem;
        }
        
        .header-subtitle {
            font-size: 1rem;
        }
        
        .packages-grid {
            gap: 20px;
            grid-template-columns: 1fr;
        }
        
        .package-card {
            padding: 25px 20px;
        }
        
        .package-card[data-popular="true"] {
            transform: none;
        }
        
        .package-card[data-popular="true"]:hover {
            transform: translateY(-4px) scale(1.02);
        }
        
        .trust-icons {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .current-balance {
            flex-direction: column;
            gap: 5px;
        }
    }
    
    @media (max-width: 480px) {
        .trust-icons {
            grid-template-columns: 1fr;
        }
        
        .package-title {
            font-size: 1.5rem;
        }
        
        .amount {
            font-size: 2.5rem;
        }
    }
    
    /* GLASS EFFECT VARIANT */
    .pictosound-modern-packages[data-style="glass"] .package-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    /* PREMIUM VARIANT */
    .pictosound-modern-packages[data-style="premium"] .package-card {
        background: linear-gradient(145deg, #1a202c, #2d3748);
        color: white;
        border-color: #4a5568;
    }
    
    .pictosound-modern-packages[data-style="premium"] .package-title,
    .pictosound-modern-packages[data-style="premium"] .amount {
        color: white;
    }
    
    .pictosound-modern-packages[data-style="premium"] .package-subtitle,
    .pictosound-modern-packages[data-style="premium"] .price-detail {
        color: #cbd5e0;
    }
    
    /* Stili aggiuntivi per stati di elaborazione */
    .package-card.processing-payment {
        opacity: 0.8;
        pointer-events: none;
    }
    
    .package-card.processing-payment .purchase-btn {
        background: linear-gradient(45deg, #94a3b8, #64748b) !important;
    }
    
    .stripe-redirect-overlay {
        animation: fadeInOverlay 0.5s ease-in;
    }
    
    @keyframes fadeInOverlay {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    </style>
    <?php
}
add_action('wp_head', 'pictosound_cm_modern_packages_styles');

/**
 * ⚡ Enqueue degli script frontend ottimizzati
 */
function pictosound_cm_frontend_scripts_and_data() {
    
    $slug_pagina_ricarica_crediti = 'ricarica-crediti';

    // ⚡ Carica script base (senza script.js pesante) per shortcode
    $load_basic_scripts = is_front_page() || 
                         is_page( $slug_pagina_ricarica_crediti ) ||
                         is_page('login') ||           
                         is_page('registrazione') ||   
                         is_page('test-shortcode') ||
                         is_page('pagina-mio-account') ||
                         is_page('profilo-utente') ||
                         (!is_admin() && !empty(get_post()) && (
                             has_shortcode(get_post()->post_content, 'pictosound_registration_form') ||
                             has_shortcode(get_post()->post_content, 'pictosound_login_form') ||
                             has_shortcode(get_post()->post_content, 'pictosound_user_area') ||
                             has_shortcode(get_post()->post_content, 'pictosound_credits_balance') ||
                             has_shortcode(get_post()->post_content, 'pictosound_credit_packages') ||
                             has_shortcode(get_post()->post_content, 'pictosound_edit_profile')
                         ));

    // ⚡ Carica script COMPLETI solo su homepage e ricarica crediti
    $load_full_scripts = is_front_page() || is_page( $slug_pagina_ricarica_crediti );

    if ( $load_basic_scripts ) { 
        
        // ⚡ CARICA SOLO JQUERY E STRIPE per le pagine di shortcode
        wp_enqueue_script('jquery');
        
        if ($load_full_scripts) {
            // Script completi solo dove servono
            $pictosound_js_base_url = content_url( 'pictosound/js/' ); 

            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], '3.0', true);
            wp_enqueue_script('tf-js', $pictosound_js_base_url . 'tf.min.js', [], '1.0.0', true);
            wp_enqueue_script('coco-ssd-js', $pictosound_js_base_url . 'coco-ssd.min.js', ['tf-js'], '1.0.0', true);
            wp_enqueue_script('face-api-js', $pictosound_js_base_url . 'face-api.min.js', ['tf-js'], '1.0.0', true);
            wp_enqueue_script('qrcode-js', $pictosound_js_base_url . 'qrcode.min.js', [], '1.0.0', true);
            wp_enqueue_script('auth-manager-js', $pictosound_js_base_url . 'auth-manager.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('camera-handler-js', $pictosound_js_base_url . 'camera-handler.js', ['jquery'], '1.4.1', true);

        $main_script_handle = 'pictosound-main-script';
        wp_enqueue_script( 
            $main_script_handle, 
            $pictosound_js_base_url . 'script.js', 
            ['jquery', 'stripe-js', 'tf-js', 'coco-ssd-js', 'face-api-js', 'qrcode-js', 'auth-manager-js', 'camera-handler-js'], // ⭐ AGGIUNTO 'camera-handler-js'
            '1.4.1', 
            true 
        );

            $main_script_handle = 'pictosound-main-script';
            wp_enqueue_script( 
                $main_script_handle, 
                $pictosound_js_base_url . 'script.js', 
                ['jquery', 'stripe-js', 'tf-js', 'coco-ssd-js', 'face-api-js', 'qrcode-js', 'auth-manager-js'], 
                '1.4.1', 
                true 
            );
        } else {
            // Solo stripe per pagamenti nelle altre pagine
            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], '3.0', true);
            $main_script_handle = 'pictosound-basic-script';
        }

        $user_id = get_current_user_id();
        $user_credits = ( $user_id > 0 ) ? pictosound_cm_get_user_credits( $user_id ) : 0;

        $script_data = [
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce_check_credits' => wp_create_nonce( 'pictosound_check_credits_nonce' ),
            'nonce_recharge'  => wp_create_nonce( 'pictosound_recharge_credits_nonce' ),
            'nonce_generate'  => wp_create_nonce( 'pictosound_generate_nonce' ),
            'is_user_logged_in' => is_user_logged_in(),
            'user_credits'    => $user_credits,
            'user_id'         => $user_id,
            'duration_costs'  => pictosound_cm_get_duration_costs(),
            'credit_packages' => pictosound_cm_get_credit_recharge_packages(),
            'text_login_required' => __( 'Devi effettuare il login per questa opzione.', 'pictosound-credits-manager' ),
            'text_insufficient_credits' => __( 'Crediti insufficienti.', 'pictosound-credits-manager' ),
            'text_generating_music' => __( 'Generazione musica in corso...', 'pictosound-credits-manager' ),
            'text_error_generating' => __( 'Errore durante la generazione.', 'pictosound-credits-manager' ),
            'text_checking_credits' => __( 'Verifica crediti...', 'pictosound-credits-manager'),
            'text_nonce_expired' => __( 'Sessione scaduta. Aggiornamento automatico in corso...', 'pictosound-credits-manager'),
            'text_nonce_refreshed' => __( 'Sessione aggiornata. Riprova ora.', 'pictosound-credits-manager'),
            'auto_refresh_nonces' => $load_full_scripts, // Solo auto-refresh completo se script completi
            'stripe_enabled' => true,
            'stripe_publishable_key' => PICTOSOUND_STRIPE_PUBLISHABLE_KEY,
            'user_recently_active' => pictosound_cm_is_user_recently_active($user_id),
            'full_scripts_loaded' => $load_full_scripts // Flag per sapere se script completi
        ];
        
        wp_localize_script( $main_script_handle, 'pictosound_vars', $script_data );
        
        // Aggiorna attività utente quando carica la pagina
        if ($user_id > 0) {
            pictosound_cm_update_user_activity($user_id);
        }
        
        $script_type = $load_full_scripts ? "FULL SCRIPTS" : "BASIC SCRIPTS";
        write_log_cm("Pictosound $script_type loaded for page: " . (is_front_page() ? "Front Page" : (get_the_title() ?: "ID " . get_the_ID())));

    } else {
        write_log_cm("Pictosound scripts NOT enqueued on this page (ID: " . get_the_ID() . "). Page title: " . get_the_title());
    }
}
add_action( 'wp_enqueue_scripts', 'pictosound_cm_frontend_scripts_and_data' );

/**
 * ⚡ JAVASCRIPT MODERNO PER GESTIONE ACQUISTI
 */
function pictosound_cm_modern_packages_script() {
    if (!is_user_logged_in()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        
        // ⚡ VERIFICA CHE GLI ELEMENTI ESISTANO PRIMA DI USARLI
        if ($('.purchase-btn').length === 0) {
            console.log('Pictosound: Elementi pacchetti non trovati, script non caricato');
            return;
        }
        
        console.log('Pictosound: Inizializzazione script pacchetti crediti');
        
        // Gestione click sui bottoni di acquisto
        $('.purchase-btn').on('click', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $card = $btn.closest('.package-card');
            const packageKey = $btn.data('package-key');
            const credits = $btn.data('credits');
            const price = $btn.data('price');
            
            // Verifica se già in elaborazione
            if ($btn.hasClass('processing')) {
                return;
            }
            
            console.log('Pictosound: Acquisto pacchetto', packageKey, credits, 'crediti');
            
            // Mostra stato di caricamento
            $btn.addClass('processing');
            $btn.find('.btn-text').hide();
            $btn.find('.btn-loading').show();
            
            // Aggiungi effetto di elaborazione alla card
            $card.addClass('processing-payment');
            
            // Effetto visivo
            $card.css('transform', 'scale(0.98)');
            setTimeout(() => {
                $card.css('transform', '');
            }, 200);
            
            // ⚡ VERIFICA CHE pictosound_vars ESISTA
            if (typeof pictosound_vars === 'undefined') {
                console.error('Pictosound: pictosound_vars non definito');
                showPackageMessage('❌ Errore di configurazione. Ricarica la pagina.', 'error');
                resetButton($btn);
                return;
            }
            
            // Chiama la funzione di ricarica crediti esistente
            $.ajax({
                url: pictosound_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'pictosound_recharge_credits',
                    recharge_nonce: pictosound_vars.nonce_recharge,
                    credits_package_key: packageKey
                },
                timeout: 15000,
                success: function(response) {
                    console.log('Pictosound: Risposta acquisto ricevuta', response);
                    
                    if (response.success) {
                        if (response.data.is_redirect && response.data.redirect_url) {
                            // Mostra messaggio di reindirizzamento
                            showPackageMessage('🚀 Reindirizzamento a Stripe per il pagamento sicuro (Carte + PayPal)...', 'info');
                            
                            // Effetto di reindirizzamento
                            $('body').append('<div class="stripe-redirect-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(102, 126, 234, 0.95); z-index: 10000; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold;"><div style="text-align: center;"><div style="font-size: 4rem; margin-bottom: 20px;">💳</div><div>Reindirizzamento a Stripe...</div><div style="margin-top: 15px; font-size: 1rem; opacity: 0.8;">Accettiamo Carte di Credito e PayPal</div></div></div>');
                            
                            // Reindirizza dopo 1.5 secondi
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                            
                        } else {
                            showPackageMessage('✅ ' + response.data.message, 'success');
                            resetButton($btn);
                        }
                    } else {
                        // Gestione errori con auto-refresh nonce se necessario
                        if (response.data && response.data.code === 'nonce_expired' && response.data.auto_refresh) {
                            showPackageMessage('🔄 Sessione scaduta. Rinnovo automatico in corso...', 'warning');
                            
                            // ⚡ VERIFICA CHE LA FUNZIONE ESISTA
                            if (typeof window.pictosoundRefreshNonces === 'function') {
                                window.pictosoundRefreshNonces().then(function(result) {
                                    if (!result.skip) {
                                        showPackageMessage('✅ Sessione rinnovata. Riprova ora.', 'success');
                                        resetButton($btn);
                                    } else {
                                        showPackageMessage('⚠️ Riprova tra qualche secondo.', 'warning');
                                        resetButton($btn);
                                    }
                                }).catch(function() {
                                    showPackageMessage('❌ Errore nel rinnovo sessione. Ricarica la pagina.', 'error');
                                    resetButton($btn);
                                });
                            } else {
                                showPackageMessage('❌ Ricarica la pagina e riprova.', 'error');
                                resetButton($btn);
                            }
                        } else {
                            const errorMsg = response.data ? response.data.message : 'Errore sconosciuto';
                            showPackageMessage('❌ ' + errorMsg, 'error');
                            resetButton($btn);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Pictosound: Errore AJAX acquisto', error);
                    showPackageMessage('❌ Errore di connessione. Riprova tra qualche minuto.', 'error');
                    resetButton($btn);
                }
            });
        });
        
        // ⚡ FUNZIONE SICURA PER MOSTRARE MESSAGGI
        function showPackageMessage(message, type) {
            const $messageDiv = $('#package-status-message');
            
            // ⚡ VERIFICA CHE L'ELEMENTO ESISTA
            if ($messageDiv.length === 0) {
                console.warn('Pictosound: Elemento #package-status-message non trovato, creo uno nuovo');
                
                // Crea l'elemento se non esiste
                $('.pictosound-modern-packages').append('<div id="package-status-message" class="status-message" style="display: none;"></div>');
                const $newMessageDiv = $('#package-status-message');
                
                if ($newMessageDiv.length === 0) {
                    // Fallback: usa alert se non può creare l'elemento
                    alert(message);
                    return;
                }
            }
            
            const $finalMessageDiv = $('#package-status-message');
            $finalMessageDiv.removeClass('success error warning info').addClass(type);
            $finalMessageDiv.html(message).show();
            
            // ⚡ SCROLL SICURO
            try {
                const elementOffset = $finalMessageDiv.offset();
                if (elementOffset && elementOffset.top) {
                    $('html, body').animate({
                        scrollTop: elementOffset.top - 100
                    }, 500);
                }
            } catch (scrollError) {
                console.warn('Pictosound: Errore scroll, ignorato:', scrollError);
            }
            
            // Auto-hide success/info messages after 8 seconds
            if (type === 'success' || type === 'info') {
                setTimeout(function() {
                    $finalMessageDiv.fadeOut();
                }, 8000);
            }
        }
        
        // Funzione per resettare il bottone
        function resetButton($btn) {
            $btn.removeClass('processing');
            $btn.find('.btn-loading').hide();
            $btn.find('.btn-text').show();
            $btn.closest('.package-card').removeClass('processing-payment');
        }
        
        // ⚡ EFFETTI HOVER SICURI
        $('.package-card').hover(
            function() {
                const $icon = $(this).find('.package-icon');
                if ($icon.length > 0) {
                    $icon.css('transform', 'scale(1.1) rotate(5deg)');
                }
            },
            function() {
                const $icon = $(this).find('.package-icon');
                if ($icon.length > 0) {
                    $icon.css('transform', '');
                }
            }
        );
        
        // ⚡ AGGIORNA CREDITI DISPLAY SICURO
        function updateCreditsDisplay(newCredits) {
            const $currentCreditsDisplay = $('#current-credits-display');
            if ($currentCreditsDisplay.length > 0) {
                $currentCreditsDisplay.text(newCredits);
            }
            
            // Aggiorna anche altri display di crediti nella pagina
            $('.balance-amount').text(newCredits);
            $('.pictosound-saldo-display-widget').each(function() {
                const currentText = $(this).text();
                const newText = currentText.replace(/\d+(?=\s*crediti)/g, newCredits);
                $(this).text(newText);
            });
            
            // Aggiorna pictosound_vars se esiste
            if (typeof pictosound_vars !== 'undefined') {
                pictosound_vars.user_credits = newCredits;
            }
        }
        
        // ⚡ POLLING PERIODICO SICURO per aggiornare i crediti
        if (typeof pictosound_vars !== 'undefined' && pictosound_vars.ajax_url) {
            setInterval(function() {
                $.ajax({
                    url: pictosound_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pictosound_get_current_credits'
                    },
                    success: function(response) {
                        if (response.success && response.data.credits !== undefined) {
                            const $currentCreditsDisplay = $('#current-credits-display');
                            const currentDisplayed = $currentCreditsDisplay.length > 0 ? parseInt($currentCreditsDisplay.text()) || 0 : 0;
                            const actualCredits = parseInt(response.data.credits) || 0;
                            
                            if (actualCredits !== currentDisplayed) {
                                updateCreditsDisplay(actualCredits);
                                
                                // Mostra notifica se i crediti sono aumentati
                                if (actualCredits > currentDisplayed) {
                                    const difference = actualCredits - currentDisplayed;
                                    showPackageMessage(`🎉 Crediti aggiornati! Hai ricevuto ${difference} nuovi crediti. Saldo attuale: ${actualCredits}`, 'success');
                                }
                            }
                        }
                    },
                    error: function() {
                        // Ignora errori di polling silenziosamente
                    }
                });
            }, 10000); // Controlla ogni 10 secondi
        }
        
        // ⚡ SISTEMA DI AUTO-RECOVERY SICURO
        let lastUserActivity = Date.now();
        
        // Aggiorna timestamp attività su ogni interazione
        $(document).on('click keypress scroll', function() {
            lastUserActivity = Date.now();
        });
        
        // ⚡ FUNZIONE GLOBALE SICURA per aggiornare i nonce
        let lastNonceRefresh = 0;
        const NONCE_REFRESH_COOLDOWN = 30000; // 30 secondi tra refresh
        
        window.pictosoundRefreshNonces = function() {
            const now = Date.now();
            if (now - lastNonceRefresh < NONCE_REFRESH_COOLDOWN) {
                console.log('Pictosound: Nonce refresh in cooldown, skipping');
                return Promise.resolve({skip: true});
            }
            
            lastNonceRefresh = now;
            
            return new Promise(function(resolve, reject) {
                // ⚡ VERIFICA CHE pictosound_vars ESISTA
                if (typeof pictosound_vars === 'undefined' || !pictosound_vars.ajax_url) {
                    reject('pictosound_vars non definito');
                    return;
                }
                
                $.ajax({
                    url: pictosound_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pictosound_regenerate_nonce'
                    },
                    timeout: 10000,
                    success: function(response) {
                        if (response.success) {
                            console.log('Pictosound: Nonce aggiornati con successo');
                            pictosound_vars.nonce_recharge = response.data.nonce_recharge;
                            pictosound_vars.nonce_check_credits = response.data.nonce_check_credits;
                            resolve(response.data);
                        } else {
                            reject(response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Pictosound: Errore nel refresh dei nonce:', error);
                        reject(error);
                    }
                });
            });
        };
        
        // ⚡ AUTO-REFRESH INTELLIGENTE E SICURO
        if (typeof pictosound_vars !== 'undefined' && 
            pictosound_vars.auto_refresh_nonces && 
            pictosound_vars.user_recently_active) {
            
            setInterval(function() {
                // Solo se l'utente è stato attivo negli ultimi 30 minuti
                if (Date.now() - lastUserActivity < 1800000) { // 30 minuti
                    window.pictosoundRefreshNonces().then(function(result) {
                        if (!result.skip) {
                            console.log('Pictosound: Nonce aggiornati automaticamente (preventivo)');
                        }
                    }).catch(function(error) {
                        console.warn('Pictosound: Errore nel refresh automatico:', error);
                    });
                }
            }, 1200000); // 20 minuti
        }
        
        console.log('Pictosound: Script pacchetti crediti inizializzato correttamente');
    });
    </script>
    <?php
}
add_action('wp_footer', 'pictosound_cm_modern_packages_script');

function pictosound_cm_error_safe_styles() {
    ?>
    <style>
    /* ⚡ STILI SICURI PER GESTIONE ERRORI */
    .pictosound-error-fallback {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        border: 1px solid #f5c6cb;
        text-align: center;
    }
    
    .stripe-redirect-overlay {
        animation: fadeInOverlay 0.5s ease-in;
        pointer-events: all;
    }
    
    @keyframes fadeInOverlay {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Nasconde elementi che potrebbero causare errori se non caricati */
    .pictosound-modern-packages.loading .purchase-btn {
        opacity: 0.7;
        pointer-events: none;
    }
    </style>
    <?php
}
add_action('wp_head', 'pictosound_cm_error_safe_styles');

/**
 * ⚡ AJAX Handler per VERIFICARE e DEDURRE crediti - Con rate limiting
 */
function pictosound_cm_ajax_check_and_deduct_credits() {
    $user_id = get_current_user_id();
    
    // Rate limiting per check credits
    if (!pictosound_cm_check_rate_limit('check_credits', $user_id, 30, 10 * MINUTE_IN_SECONDS)) {
        wp_send_json_error(['message' => __('Troppi tentativi. Riprova tra qualche minuto.', 'pictosound-credits-manager')]);
        return;
    }
    
    // Aggiorna attività utente
    if ($user_id > 0) {
        pictosound_cm_update_user_activity($user_id);
    }
    
    // Verifica nonce con logging dettagliato
    $nonce_received = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : 'MISSING';
    $nonce_valid = wp_verify_nonce($nonce_received, 'pictosound_check_credits_nonce');
    
    write_log_cm("Check Credits - User ID: $user_id, Nonce valido: " . ($nonce_valid ? 'SI' : 'NO'));
    
    if ( ! isset( $_POST['nonce'] ) || ! $nonce_valid ) {
        wp_send_json_error( [
            'message' => __( 'Sessione scaduta. Aggiornamento automatico in corso...', 'pictosound-credits-manager' ),
            'code' => 'nonce_expired',
            'auto_refresh' => true,
            'action_type' => 'check_credits'
        ] );
        return;
    }

    if ( ! isset( $_POST['duration'] ) ) {
        wp_send_json_error( ['message' => __( 'Durata non specificata (CD02).', 'pictosound-credits-manager' )] );
        return;
    }
    $selected_duration = sanitize_text_field( $_POST['duration'] );
   $duration_costs = pictosound_cm_get_duration_costs();

   if ( ! array_key_exists( $selected_duration, $duration_costs ) ) {
       wp_send_json_error( ['message' => __( 'Durata selezionata non valida (CD03).', 'pictosound-credits-manager' )] );
       return;
   }
   $required_credits = $duration_costs[$selected_duration];

   if ( $required_credits === 0 ) {
       wp_send_json_success( [
           'message'          => __( 'Procedi pure con la generazione gratuita.', 'pictosound-credits-manager' ),
           'can_proceed'      => true,
           'remaining_credits' => ( $user_id > 0 ? pictosound_cm_get_user_credits( $user_id ) : 0 )
       ] );
       return;
   }

   if ( ! is_user_logged_in() ) {
       wp_send_json_error( ['message' => __( 'Devi effettuare il login per questa durata.', 'pictosound-credits-manager' ), 'can_proceed' => false] );
       return;
   }

   $user_current_credits = pictosound_cm_get_user_credits( $user_id );
   if ( $user_current_credits < $required_credits ) {
       wp_send_json_error( [
           'message'          => sprintf( __( 'Crediti insufficienti. Hai %d, ne servono %d.', 'pictosound-credits-manager' ), $user_current_credits, $required_credits ),
           'can_proceed'      => false,
           'current_credits'  => $user_current_credits
       ] );
       return;
   }

   $credits_updated = pictosound_cm_update_user_credits( $user_id, $required_credits, 'deduct' );
   if ( ! $credits_updated ) {
       wp_send_json_error( [
           'message' => __( 'Problema tecnico nell\'aggiornare i crediti (CD04). Contatta l\'assistenza.', 'pictosound-credits-manager' ),
           'can_proceed' => false,
           'current_credits' => $user_current_credits
       ]);
       return;
   }

   wp_send_json_success( [
       'message'          => sprintf(__( '%d crediti detratti. Puoi procedere con la generazione.', 'pictosound-credits-manager' ), $required_credits ),
       'can_proceed'      => true,
       'remaining_credits' => pictosound_cm_get_user_credits( $user_id )
   ] );
}
add_action( 'wp_ajax_pictosound_check_credits', 'pictosound_cm_ajax_check_and_deduct_credits' );
add_action( 'wp_ajax_nopriv_pictosound_check_credits', 'pictosound_cm_ajax_check_and_deduct_credits' );

/**
* ⚡ Gestisce la richiesta AJAX per la ricarica dei crediti - OTTIMIZZATA
*/
function pictosound_cm_handle_ajax_recharge_credits() {
   $user_id = get_current_user_id();
   
   // Rate limiting per ricarica
   if (!pictosound_cm_check_rate_limit('recharge_credits', $user_id, 10, 10 * MINUTE_IN_SECONDS)) {
       wp_send_json_error(['message' => __('Troppi tentativi di ricarica. Riprova tra qualche minuto.', 'pictosound-credits-manager')]);
       return;
   }
   
   // Aggiorna attività utente
   if ($user_id > 0) {
       pictosound_cm_update_user_activity($user_id);
   }
   
   // Debug dettagliato del nonce
   $nonce_received = isset($_POST['recharge_nonce']) ? sanitize_text_field($_POST['recharge_nonce']) : 'MISSING';
   $nonce_valid = wp_verify_nonce($nonce_received, 'pictosound_recharge_credits_nonce');
   
   write_log_cm("Recharge Credits - User ID: $user_id, Nonce valido: " . ($nonce_valid ? 'SI' : 'NO'));
   
   // Se il nonce non è valido, gestisci auto-recovery
   if ( ! isset( $_POST['recharge_nonce'] ) || ! $nonce_valid ) {
       write_log_cm("Recharge Credits - Nonce non valido, tentativo di auto-recovery");
       
       wp_send_json_error( [
           'message' => __( 'Sessione scaduta. Aggiornamento automatico in corso...', 'pictosound-credits-manager' ),
           'code' => 'nonce_expired',
           'auto_refresh' => true,
           'action_type' => 'recharge_credits'
       ] );
       return;
   }
   
   if ( ! is_user_logged_in() ) {
       wp_send_json_error( ['message' => __( 'Devi effettuare il login per ricaricare i crediti.', 'pictosound-credits-manager' )] );
       return;
   }
   
   write_log_cm("Recharge Credits - Processo iniziato per user ID: $user_id");
   
   if ( ! isset( $_POST['credits_package_key'] ) ) {
       wp_send_json_error( ['message' => __( 'Pacchetto crediti non specificato.', 'pictosound-credits-manager' )] );
       return;
   }
   
   $selected_package_key = sanitize_text_field( $_POST['credits_package_key'] );
   $available_packages = pictosound_cm_get_credit_recharge_packages();

   if ( ! array_key_exists( $selected_package_key, $available_packages ) ) {
       wp_send_json_error( ['message' => __( 'Pacchetto crediti selezionato non valido.', 'pictosound-credits-manager' )] );
       return;
   }
   
   write_log_cm("Recharge Credits - Pacchetto selezionato: $selected_package_key");

   // CREA SESSIONE STRIPE invece di PayPal
   $stripe_url = pictosound_cm_create_stripe_session($selected_package_key, $user_id);
   
   if ($stripe_url) {
       write_log_cm("Recharge Credits - Stripe Session creata, reindirizzamento...");
       
       wp_send_json_success([
           'message' => __('Reindirizzamento a Stripe per il pagamento...', 'pictosound-credits-manager'),
           'redirect_url' => $stripe_url,
           'is_redirect' => true,
           'payment_method' => 'stripe'
       ]);
   } else {
       write_log_cm("Recharge Credits - Errore nella creazione Stripe Session");
       wp_send_json_error(['message' => __('Errore nella creazione del pagamento Stripe.', 'pictosound-credits-manager')]);
   }
}
add_action( 'wp_ajax_pictosound_recharge_credits', 'pictosound_cm_handle_ajax_recharge_credits' );

/**
 * ⚡ AJAX Handler per ottenere il saldo crediti aggiornato
 */
function pictosound_cm_ajax_get_current_credits() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utente non loggato']);
        return;
    }
    
    $user_id = get_current_user_id();
    $current_credits = pictosound_cm_get_user_credits($user_id);
    
    wp_send_json_success([
        'credits' => $current_credits,
        'user_id' => $user_id,
        'timestamp' => time()
    ]);
}
add_action('wp_ajax_pictosound_get_current_credits', 'pictosound_cm_ajax_get_current_credits');

/**
 * =============================================================
 * FUNZIONE AJAX PER LA GENERAZIONE MUSICALE (FINALE)
 * =============================================================
 */
/**
 * Funzione AJAX per la generazione di musica e il salvataggio dell'immagine.
 */
function pictosound_ajax_generate_music() {
    if (!function_exists('write_log_cm')) {
        function write_log_cm($message) { error_log('Pictosound Log: ' . print_r($message, true)); }
    }

    if (empty($_POST['action']) || empty($_POST['prompt']) || empty($_POST['duration'])) {
        wp_send_json_error(['error' => 'Richiesta malformata.'], 400);
        wp_die();
    }

    $image_url_to_save = '';
    if (!empty($_POST['image_data'])) {
        write_log_cm("Dati immagine ricevuti. Tentativo di salvataggio...");
        $upload_dir_info = wp_upload_dir();
        $upload_dir = $upload_dir_info['basedir'] . '/pictosound_images/';
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        if (strpos($_POST['image_data'], 'base64,') !== false) {
            list(, $data) = explode(',', $_POST['image_data']);
            $decoded_data = base64_decode($data);
            $image_filename = 'img_gen_' . time() . '_' . wp_generate_password(8, false) . '.jpg';
            $image_filepath = $upload_dir . $image_filename;

            if (file_put_contents($image_filepath, $decoded_data)) {
                $image_url_to_save = $upload_dir_info['baseurl'] . '/pictosound_images/' . $image_filename;
                write_log_cm("SUCCESSO: Immagine salvata in -> " . $image_filepath);
            } else {
                write_log_cm("ERRORE CRITICO: file_put_contents() ha fallito! Controllare i permessi della cartella: " . $upload_dir);
            }
        }
    }

    $prompt_text = sanitize_textarea_field(stripslashes($_POST['prompt']));
    $duration_seconds = intval($_POST['duration']);
    $user_id = is_user_logged_in() ? get_current_user_id() : null;

    $audio_dir = WP_CONTENT_DIR . '/pictosound/audio/';
    if (!file_exists($audio_dir)) { wp_mkdir_p($audio_dir); }
    
    $api_key = 'sk-EQyuyCbTzRuI9InYbQZtsCVPLSNAy202c5veU8iXOoY9KcTA'; // ⚠️ SOSTITUIRE
    $api_url = 'https://api.stability.ai/v2beta/audio/stable-audio-2/text-to-audio';
    
    $fields_to_send = ['prompt' => $prompt_text, 'output_format' => 'mp3', 'duration' => $duration_seconds, 'steps' => 30];
    $boundary = "------------------------" . uniqid();
    $request_body = "";
    foreach ($fields_to_send as $name => $value) { $request_body .= "--" . $boundary . "\r\n" . "Content-Disposition: form-data; name=\"" . $name . "\"\r\n\r\n" . $value . "\r\n"; }
    $request_body .= "--" . $boundary . "--\r\n";

    $response = wp_remote_post($api_url, ['method' => 'POST', 'timeout' => 180, 'headers' => ["Authorization" => "Bearer $api_key", "Accept" => "audio/*", "Content-Type" => "multipart/form-data; boundary=" . $boundary], 'body' => $request_body]);

    if (is_wp_error($response)) { wp_send_json_error(['error' => "Errore API musicale."], 500); wp_die(); }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code >= 200 && $http_code < 300) {
        $audio_filename = 'audio_gen_' . time() . '_' . wp_generate_password(8, false) . '.mp3';
        $filepath = $audio_dir . $audio_filename;
        if (file_put_contents($filepath, wp_remote_retrieve_body($response))) {
            $download_url = content_url("/pictosound/includes/download.php?file=" . urlencode($audio_filename));
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'ps_generations';
            $wpdb->insert($table_name, ['user_id' => $user_id, 'duration' => $duration_seconds, 'prompt' => $prompt_text, 'audio_filename' => $audio_filename, 'audio_url' => $download_url, 'image_url' => $image_url_to_save, 'created_at' => current_time('mysql')]);
            
            wp_send_json_success(['audioUrl' => $download_url, 'downloadUrl' => $download_url, 'fileName' => $audio_filename]);
        } else {
            wp_send_json_error(['error' => "Errore scrittura file audio."], 500);
        }
    } else {
        $error_data = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json_error(['error' => $error_data['message'] ?? 'Errore API musicale.'], $http_code);
    }
    wp_die();
}
add_action('wp_ajax_pictosound_generate_music', 'pictosound_ajax_generate_music');
add_action('wp_ajax_nopriv_pictosound_generate_music', 'pictosound_ajax_generate_music');

/**
 * Carica il text domain per le traduzioni.
 */
function pictosound_ms_load_textdomain() {
   load_plugin_textdomain( 'pictosound-mostra-saldo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pictosound_ms_load_textdomain' );

?>