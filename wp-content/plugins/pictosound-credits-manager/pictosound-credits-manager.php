<?php
// 🚨 PLUGIN COMPLETO AGGIORNATO CON INTERFACCIA MODERNA
/**
 * Plugin Name:        Pictosound Credits Manager (Modern UI + Hosting-Optimized)
 * Plugin URI:         [Lascia vuoto o metti l'URL del tuo sito se vuoi]
 * Description:        Gestisce i crediti utente, la registrazione, il login e l'integrazione per la generazione musicale di Pictosound. Versione con interfaccia moderna per pacchetti crediti.
 * Version:            1.5.0-modern-ui
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
// IMPORTANTE: Sposta queste chiavi in wp-config.php per maggiore sicurezza
if (!defined('PICTOSOUND_STRIPE_PUBLISHABLE_KEY')) {
    define( 'PICTOSOUND_STRIPE_PUBLISHABLE_KEY', 'pk_test_51RVuiV05IMwaN8UKqUjEqJ6Axk7KfRhez3zFaU2n0AhUnk1k3W3byKcbZjZT7u8bnBDr3Icu7Ks9GwG76dTBaCDg00H8LQbKFT' );
}
if (!defined('PICTOSOUND_STRIPE_SECRET_KEY')) {
    define( 'PICTOSOUND_STRIPE_SECRET_KEY', 'sk_test_51RVuiV05IMwaN8UKRE3GN6W4wx9UxcNcgkoNcsXfNeIbMAT5HS5gSn5hVu1GN4NXPzsgLJ30MgJcUCPpVBAlM00l0083885H88' );
}
if (!defined('PICTOSOUND_STRIPE_WEBHOOK_SECRET')) {
    define( 'PICTOSOUND_STRIPE_WEBHOOK_SECRET', 'whsec_TUA_WEBHOOK_SECRET' ); // Configurare in produzione
}

/**
 * Definiamo le costanti
 */
define( 'PICTOSOUND_CREDITS_USER_META_KEY', '_pictosound_user_credits' );
define( 'PICTOSOUND_PRIVACY_OPTIN_META_KEY', '_pictosound_privacy_optin' );

/**
 * ⚡ RATE LIMITING SYSTEM - Hosting-Friendly
 */

/**
 * Crea la tabella per le creazioni utente
 * Da aggiungere al plugin pictosound-credits-manager.php
 */

function pictosound_cm_create_user_creations_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(255) DEFAULT '',
        description text DEFAULT '',
        
        -- File originali
        original_image_path varchar(500) DEFAULT '',
        audio_file_path varchar(500) DEFAULT '',
        audio_file_url varchar(500) DEFAULT '',
        
        -- Metadati generazione
        prompt_text text DEFAULT '',
        duration int(11) DEFAULT 40,
        audio_format varchar(10) DEFAULT 'mp3',
        file_size int(11) DEFAULT 0,
        
        -- Dati analisi immagine (JSON)
        image_analysis_data longtext DEFAULT '',
        detected_objects text DEFAULT '',
        detected_emotions text DEFAULT '',
        
        -- Impostazioni musicali usate (JSON)
        musical_settings longtext DEFAULT '',
        
        -- Stats e interazioni
        plays_count int(11) DEFAULT 0,
        downloads_count int(11) DEFAULT 0,
        is_favorite boolean DEFAULT FALSE,
        is_public boolean DEFAULT FALSE,
        
        -- Condivisione
        share_token varchar(32) DEFAULT '',
        shared_count int(11) DEFAULT 0,
        
        -- Timestamp
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY share_token (share_token),
        KEY is_public (is_public),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Crea anche la tabella per i link di condivisione
    $shares_table = $wpdb->prefix . 'pictosound_creation_shares';
    
    $shares_sql = "CREATE TABLE $shares_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        creation_id mediumint(9) NOT NULL,
        share_token varchar(32) NOT NULL,
        shared_by bigint(20) NOT NULL,
        access_count int(11) DEFAULT 0,
        expires_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        
        PRIMARY KEY (id),
        UNIQUE KEY share_token (share_token),
        KEY creation_id (creation_id),
        KEY shared_by (shared_by)
    ) $charset_collate;";
    
    dbDelta($shares_sql);
    
    write_log_cm("Tabelle user creations e shares create/aggiornate");
}

// Aggiungi questa chiamata al hook di attivazione del plugin
register_activation_hook(__FILE__, 'pictosound_cm_create_user_creations_table');

/**
 * Funzioni helper per gestire le creazioni
 */

function pictosound_save_user_creation($user_id, $creation_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    // Genera token di condivisione unico
    $share_token = wp_generate_password(32, false);
    
    $data = [
        'user_id' => $user_id,
        'title' => sanitize_text_field($creation_data['title'] ?? ''),
        'description' => sanitize_textarea_field($creation_data['description'] ?? ''),
        'original_image_path' => sanitize_text_field($creation_data['image_path'] ?? ''),
        'audio_file_path' => sanitize_text_field($creation_data['audio_path'] ?? ''),
        'audio_file_url' => esc_url_raw($creation_data['audio_url'] ?? ''),
        'prompt_text' => sanitize_textarea_field($creation_data['prompt'] ?? ''),
        'duration' => intval($creation_data['duration'] ?? 40),
        'audio_format' => sanitize_text_field($creation_data['format'] ?? 'mp3'),
        'file_size' => intval($creation_data['file_size'] ?? 0),
        'image_analysis_data' => wp_json_encode($creation_data['analysis_data'] ?? []),
        'detected_objects' => sanitize_text_field($creation_data['objects'] ?? ''),
        'detected_emotions' => sanitize_text_field($creation_data['emotions'] ?? ''),
        'musical_settings' => wp_json_encode($creation_data['musical_settings'] ?? []),
        'share_token' => $share_token,
        'is_public' => boolval($creation_data['is_public'] ?? false)
    ];
    
    $result = $wpdb->insert($table_name, $data);
    
    if ($result !== false) {
        $creation_id = $wpdb->insert_id;
        write_log_cm("Creazione salvata con ID: $creation_id per user: $user_id");
        return $creation_id;
    }
    
    write_log_cm("Errore nel salvare creazione per user: $user_id - " . $wpdb->last_error);
    return false;
}

function pictosound_get_user_creations($user_id, $limit = 20, $offset = 0, $order_by = 'created_at DESC') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE user_id = %d 
         ORDER BY {$order_by}
         LIMIT %d OFFSET %d",
        $user_id, $limit, $offset
    ));
    
    return $results;
}

function pictosound_get_creation_by_token($share_token) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE share_token = %s",
        $share_token
    ));
}

function pictosound_update_creation_stats($creation_id, $stat_type) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    $valid_stats = ['plays_count', 'downloads_count', 'shared_count'];
    
    if (!in_array($stat_type, $valid_stats)) return false;
    
    return $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} SET {$stat_type} = {$stat_type} + 1 WHERE id = %d",
        $creation_id
    ));
}

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
 * ⚡ SHORTCODE LOGIN MODERNO - DESIGN COORDINATO
 */
function pictosound_cm_login_modern_shortcode($atts) {
    $atts = shortcode_atts([
        'redirect' => '',
        'show_register_link' => 'true',
        'show_lost_password' => 'true'
    ], $atts);
    
    // Se già loggato, mostra messaggio di benvenuto
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $user_credits = pictosound_cm_get_user_credits($current_user->ID);
        $logout_url = wp_logout_url(home_url());
        
        return '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">
            <div style="text-align: center; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 40px; border-radius: 15px; position: relative; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
                <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); pointer-events: none;"></div>
                <div style="position: relative; z-index: 1;">
                    <div style="font-size: 64px; margin-bottom: 20px;">👋</div>
                    <h2 style="margin: 0 0 15px 0; font-size: 28px; font-weight: 300;">Benvenuto, ' . esc_html($current_user->display_name ?: $current_user->user_login) . '!</h2>
                    <p style="margin: 0 0 20px 0; font-size: 18px; opacity: 0.9;">Sei già connesso al tuo account Pictosound</p>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin: 20px 0; backdrop-filter: blur(10px);">
                        <p style="margin: 0; font-size: 16px;"><strong>Crediti disponibili: ' . $user_credits . '</strong></p>
                    </div>
                    <div style="margin-top: 25px;">
                        <a href="/" style="background: white; color: #28a745; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-right: 15px; display: inline-block;">🏠 VAI ALLA HOME</a>
                        <a href="' . esc_url($logout_url) . '" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">🚪 LOGOUT</a>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    // Gestione errori di login
    $error_message = '';
    $success_message = '';
    
    if (isset($_GET['login']) && $_GET['login'] === 'failed') {
        $error_message = 'Credenziali non valide. Riprova.';
    } elseif (isset($_GET['login']) && $_GET['login'] === 'empty') {
        $error_message = 'Username e password sono obbligatori.';
    } elseif (isset($_GET['registration']) && $_GET['registration'] === 'complete') {
        $success_message = 'Registrazione completata! Ora puoi effettuare il login.';
    } elseif (isset($_GET['password']) && $_GET['password'] === 'reset') {
        $success_message = 'Password reimpostata! Controlla la tua email.';
    }
    
    // URL di reindirizzamento
    $redirect_url = !empty($atts['redirect']) ? $atts['redirect'] : home_url();
    
    ob_start();
    ?>
    
    <div class="pictosound-login-modern" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 450px; margin: 0 auto;">
        
        <!-- HEADER -->
        <div style="text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 15px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); pointer-events: none;"></div>
            <div style="position: relative; z-index: 1;">
                <div style="font-size: 64px; margin-bottom: 20px;">🎵</div>
                <h2 style="margin: 0 0 10px 0; font-size: 28px; font-weight: 300;">Accedi a Pictosound</h2>
                <p style="margin: 0; font-size: 16px; opacity: 0.9;">Entra nel mondo della musica AI</p>
            </div>
        </div>
        
        <!-- MESSAGGI -->
        <?php if (!empty($error_message)): ?>
        <div style="background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; box-shadow: 0 4px 12px rgba(255,107,107,0.3);">
            <strong>❌ <?php echo esc_html($error_message); ?></strong>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div style="background: linear-gradient(135deg, #51cf66, #40c057); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; box-shadow: 0 4px 12px rgba(81,207,102,0.3);">
            <strong>✅ <?php echo esc_html($success_message); ?></strong>
        </div>
        <?php endif; ?>
        
        <!-- FORM LOGIN -->
        <div style="background: white; padding: 35px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); border: 1px solid #e9ecef;">
            
            <form name="loginform" method="post" action="<?php echo esc_url(wp_login_url()); ?>" style="margin: 0;">
                
                <!-- USERNAME -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333; font-size: 14px;">
                        👤 Username o Email
                    </label>
                    <input type="text" name="log" required 
                           style="width: 100%; padding: 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 16px; transition: all 0.3s ease; background: #f8f9fa;"
                           onfocus="this.style.borderColor='#667eea'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)';"
                           onblur="this.style.borderColor='#e9ecef'; this.style.background='#f8f9fa'; this.style.boxShadow='none';"
                           placeholder="Inserisci username o email" />
                </div>
                
                <!-- PASSWORD -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333; font-size: 14px;">
                        🔒 Password
                    </label>
                    <div style="position: relative;">
                        <input type="password" name="pwd" id="loginPassword" required 
                               style="width: 100%; padding: 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 16px; transition: all 0.3s ease; background: #f8f9fa; padding-right: 50px;"
                               onfocus="this.style.borderColor='#667eea'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)';"
                               onblur="this.style.borderColor='#e9ecef'; this.style.background='#f8f9fa'; this.style.boxShadow='none';"
                               placeholder="Inserisci la tua password" />
                        <button type="button" onclick="togglePassword()" 
                                style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 18px; cursor: pointer; color: #667eea;">
                            👁️
                        </button>
                    </div>
                </div>
                
                <!-- REMEMBER ME -->
                <div style="margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; cursor: pointer; font-size: 14px; color: #666;">
                        <input type="checkbox" name="rememberme" value="forever" 
                               style="margin-right: 8px; transform: scale(1.2); accent-color: #667eea;" />
                        Ricordami
                    </label>
                    
                    <?php if ($atts['show_lost_password'] === 'true'): ?>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" 
                       style="color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline';"
                       onmouseout="this.style.textDecoration='none';">
                        Password dimenticata?
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- BOTTONE LOGIN -->
                <button type="submit" name="wp-submit" 
                        style="width: 100%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 18px; border-radius: 10px; font-size: 18px; font-weight: bold; cursor: pointer; box-shadow: 0 8px 20px rgba(102,126,234,0.3); transition: all 0.3s ease; margin-bottom: 20px;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 25px rgba(102,126,234,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(102,126,234,0.3)';">
                    🚀 ACCEDI A PICTOSOUND
                </button>
                
                <!-- HIDDEN FIELDS -->
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>" />
                <?php wp_nonce_field('login_nonce', 'login_nonce_field'); ?>
            </form>
            
            <!-- SOCIAL LOGIN (PLACEHOLDER) -->
            <div style="text-align: center; margin: 25px 0;">
                <div style="position: relative;">
                    <hr style="border: none; border-top: 1px solid #e9ecef; margin: 0;">
                    <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 15px; color: #666; font-size: 14px;">
                        oppure
                    </span>
                </div>
            </div>
            
            <!-- BOTTONI SOCIAL (PLACEHOLDER) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 25px;">
                <button type="button" onclick="alert('Google Login non ancora configurato')" 
                        style="background: #db4437; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                    🔍 Google
                </button>
                <button type="button" onclick="alert('Facebook Login non ancora configurato')" 
                        style="background: #4267B2; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                    📘 Facebook
                </button>
            </div>
        </div>
        
        <!-- REGISTRAZIONE LINK -->
        <?php if ($atts['show_register_link'] === 'true' && get_option('users_can_register')): ?>
        <div style="text-align: center; margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <p style="margin: 0 0 15px 0; color: #666; font-size: 16px;">
                Non hai ancora un account?
            </p>
            <a href="/registrati" 
               style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; transition: all 0.3s ease;"
               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 15px rgba(40,167,69,0.3)';"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                ✨ REGISTRATI GRATIS
            </a>
        </div>
        <?php endif; ?>
        
        <!-- VANTAGGI ACCOUNT -->
        <div style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 25px; border-radius: 12px; margin-top: 25px;">
            <h4 style="text-align: center; color: #333; margin: 0 0 20px 0; font-size: 18px;">🎯 Con il tuo account Pictosound</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; text-align: center;">
                <div>
                    <div style="font-size: 24px; margin-bottom: 8px;">🎵</div>
                    <p style="margin: 0; font-size: 12px; color: #666; font-weight: bold;">Crea Musica AI</p>
                </div>
                <div>
                    <div style="font-size: 24px; margin-bottom: 8px;">💾</div>
                    <p style="margin: 0; font-size: 12px; color: #666; font-weight: bold;">Salva Progetti</p>
                </div>
                <div>
                    <div style="font-size: 24px; margin-bottom: 8px;">⚡</div>
                    <p style="margin: 0; font-size: 12px; color: #666; font-weight: bold;">Accesso Veloce</p>
                </div>
                <div>
                    <div style="font-size: 24px; margin-bottom: 8px;">🏆</div>
                    <p style="margin: 0; font-size: 12px; color: #666; font-weight: bold;">Funzioni Premium</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JAVASCRIPT -->
    <script>
    function togglePassword() {
        const passwordField = document.getElementById('loginPassword');
        const button = event.target;
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            button.innerHTML = '🙈';
        } else {
            passwordField.type = 'password';
            button.innerHTML = '👁️';
        }
    }
    
    // Auto-focus primo campo
    document.addEventListener('DOMContentLoaded', function() {
        const firstInput = document.querySelector('input[name="log"]');
        if (firstInput) {
            firstInput.focus();
        }
    });
    
    // Gestione errori di login via JavaScript
    const form = document.querySelector('form[name="loginform"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="log"]').value.trim();
            const password = document.querySelector('input[name="pwd"]').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('❌ Per favore, inserisci username e password');
                return false;
            }
            
            // Mostra loading
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Accesso in corso...';
            submitBtn.disabled = true;
            
            // Ripristina dopo 3 secondi se non redirect
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    }
    </script>
    
    <!-- CSS RESPONSIVE -->
    <style>
    @media (max-width: 480px) {
        .pictosound-login-modern {
            margin: 0 15px !important;
        }
        .pictosound-login-modern div[style*="padding: 35px"] {
            padding: 25px !important;
        }
        .pictosound-login-modern div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
        }
    }
    </style>
    
    <?php
    return ob_get_clean();
}
add_shortcode('pictosound_login_modern', 'pictosound_cm_login_modern_shortcode');

/**
 * ⚡ SHORTCODE MODERNO PER PACCHETTI CREDITI - LAYOUT ORIZZONTALE ACCATTIVANTE
 */
function pictosound_cm_modern_credit_packages_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0;">🔒 Accesso Richiesto</h3>
            <p style="margin: 0 0 20px 0;">Effettua il login per acquistare crediti e utilizzare Pictosound</p>
            <a href="/wp-login.php" style="background: white; color: #667eea; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">🚀 ACCEDI ORA</a>
        </div>';
    }
    
    $atts = shortcode_atts([
        'style' => 'modern', // modern, premium, glass
        'columns' => '4',
        'show_popular' => 'true'
    ], $atts);
    
    $packages = pictosound_cm_get_credit_recharge_packages();
    $user_credits = pictosound_cm_get_user_credits(get_current_user_id());
    
    // Determina il pacchetto "popolare"
    $popular_package = '60'; // Pacchetto di mezzo come popolare
    
    ob_start();
    ?>
    
    <div class="pictosound-modern-packages" data-style="<?php echo esc_attr($atts['style']); ?>">
        
        <!-- HEADER SECTION -->
        <div class="packages-header">
            <div class="header-content">
                <div class="header-icon">💎</div>
                <h2 class="header-title">Ricarica i Tuoi Crediti Pictosound</h2>
                <p class="header-subtitle">Scegli il pacchetto perfetto per le tue esigenze musicali</p>
                <div class="current-balance">
                    <span class="balance-label">Saldo attuale:</span>
                    <span class="balance-amount" id="current-credits-display"><?php echo $user_credits; ?></span>
                    <span class="balance-unit">crediti</span>
                </div>
            </div>
        </div>
        
        <!-- PACKAGES GRID -->
        <div class="packages-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($packages as $key => $package): ?>
                <?php 
                $is_popular = ($atts['show_popular'] === 'true' && $key === $popular_package);
                $price_numeric = floatval(str_replace(['€', ','], ['', '.'], $package['price_simulated']));
                $price_per_credit = round($price_numeric / $package['credits'], 3);
                $savings = '';
                
                // Calcola risparmi per pacchetti più grandi
                if ($key == '40') $savings = 'Risparmi 5%';
                elseif ($key == '60') $savings = 'Risparmi 8%';
                elseif ($key == '100') $savings = 'Risparmi 20%';
                ?>
                
                <div class="package-card" data-package="<?php echo esc_attr($key); ?>" <?php echo $is_popular ? 'data-popular="true"' : ''; ?>>
                    
                    <?php if ($is_popular): ?>
                    <div class="popular-badge">
                        <span class="badge-icon">⭐</span>
                        <span class="badge-text">PIÙ SCELTO</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($savings): ?>
                    <div class="savings-badge"><?php echo $savings; ?></div>
                    <?php endif; ?>
                    
                    <div class="package-header">
                        <div class="package-icon">🎵</div>
                        <div class="package-title"><?php echo $package['credits']; ?> Crediti</div>
                        <div class="package-subtitle">Pacchetto <?php echo $key === '20' ? 'Starter' : ($key === '40' ? 'Standard' : ($key === '60' ? 'Premium' : 'Professional')); ?></div>
                    </div>
                    
                    <div class="package-price">
                        <div class="price-main">
                            <span class="currency">€</span>
                            <span class="amount"><?php echo number_format($price_numeric, 2); ?></span>
                        </div>
                        <div class="price-detail">€<?php echo number_format($price_per_credit, 3); ?> per credito</div>
                    </div>
                    
                    <div class="package-features">
                        <div class="feature">
                            <span class="feature-icon">✓</span>
                            <span class="feature-text"><?php echo $package['credits']; ?> generazioni musicali</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">✓</span>
                            <span class="feature-text">Qualità audio premium</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">✓</span>
                            <span class="feature-text">Download illimitati</span>
                        </div>
                        <?php if ($key >= '60'): ?>
                        <div class="feature">
                            <span class="feature-icon">⭐</span>
                            <span class="feature-text">Supporto prioritario</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="package-action">
                        <button type="button" 
                                class="purchase-btn" 
                                data-package-key="<?php echo esc_attr($key); ?>"
                                data-credits="<?php echo esc_attr($package['credits']); ?>"
                                data-price="<?php echo esc_attr($package['price_simulated']); ?>">
                            <span class="btn-icon">🚀</span>
                            <span class="btn-text">Acquista Ora</span>
                            <span class="btn-loading" style="display: none;">
                                <span class="loading-spinner"></span>
                                Elaborazione...
                            </span>
                        </button>
                    </div>
                    
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- SECURITY & TRUST SECTION -->
<div class="trust-section">
    <div class="trust-icons">
        <div class="trust-item">
            <div class="trust-icon">🔒</div>
            <div class="trust-text">Pagamenti Sicuri</div>
        </div>
        <div class="trust-item">
            <div class="trust-icon">⚡</div>
            <div class="trust-text">Attivazione Immediata</div>
        </div>
        <div class="trust-item">
            <div class="trust-icon">💳</div>
            <div class="trust-text">Carte + PayPal</div>
        </div>
        <div class="trust-item">
            <div class="trust-icon">🎯</div>
            <div class="trust-text">Nessun Abbonamento</div>
        </div>
    </div>
    <div class="trust-description">
        Paga con carta di credito o PayPal tramite Stripe. I crediti non scadono mai e vengono attivati immediatamente dopo il pagamento.
    </div>
</div>
    
    <?php
    return ob_get_clean();
}
add_shortcode('pictosound_credit_packages', 'pictosound_cm_modern_credit_packages_shortcode');

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
 * ⚡ Crea sessione di pagamento Stripe - Ottimizzata
 */
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
 * Shortcode per ritorno pagamento Stripe + Auto-aggiunta crediti + DEBUG
 */
function pictosound_cm_payment_return_shortcode() {
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';
    
    // ⚡ DEBUG: Log iniziale
    write_log_cm("PAYMENT RETURN - Status: $status, Session ID: $session_id, User logged in: " . (is_user_logged_in() ? 'SI' : 'NO'));
    
    if ($status === 'success' && $session_id && is_user_logged_in()) {
        
        $debug_messages = [];
        $debug_messages[] = "✓ Parametri validi ricevuti";
        
        // TENTATIVO DI RECUPERO SESSIONE E AGGIUNTA CREDITI AUTOMATICA
        try {
            $debug_messages[] = "✓ Tentativo di recupero sessione Stripe...";
            
            if (class_exists('\Stripe\Stripe')) {
                $debug_messages[] = "✓ Classe Stripe trovata";
                
                \Stripe\Stripe::setApiKey(PICTOSOUND_STRIPE_SECRET_KEY);
                $debug_messages[] = "✓ API Key impostata";
                
                $session = \Stripe\Checkout\Session::retrieve($session_id);
                $debug_messages[] = "✓ Sessione recuperata: " . $session->payment_status;
                
                if ($session && $session->payment_status === 'paid') {
                    $debug_messages[] = "✓ Sessione pagata confermata";
                    
                    $user_id = intval($session->metadata->user_id ?? 0);
                    $package_key = sanitize_text_field($session->metadata->package_key ?? '');
                    
                    $debug_messages[] = "✓ Metadata: User ID = $user_id, Package = $package_key";
                    
                    if ($user_id === get_current_user_id() && !empty($package_key)) {
                        $debug_messages[] = "✓ User ID corrispondente e package valido";
                        
                        // Controlla se già processato
                        $already_processed = get_option("stripe_processed_" . $session_id);
                        $debug_messages[] = "✓ Verifica elaborazione precedente: " . ($already_processed ? 'GIA PROCESSATO' : 'NON PROCESSATO');
                        
                        if (!$already_processed) {
                            $debug_messages[] = "✓ Inizio elaborazione pagamento...";
                            
                            $packages = pictosound_cm_get_credit_recharge_packages();
                            if (array_key_exists($package_key, $packages)) {
                                $credits_to_add = $packages[$package_key]['credits'];
                                $debug_messages[] = "✓ Pacchetto trovato: $credits_to_add crediti da aggiungere";
                                
                                // Crediti prima dell'aggiunta
                                $credits_before = pictosound_cm_get_user_credits($user_id);
                                $debug_messages[] = "✓ Crediti prima: $credits_before";
                                
                                // Aggiungi crediti
                                $success = pictosound_cm_update_user_credits($user_id, $credits_to_add, 'add');
                                
                                // Crediti dopo l'aggiunta
                                $credits_after = pictosound_cm_get_user_credits($user_id);
                                $debug_messages[] = "✓ Aggiornamento crediti: " . ($success ? 'SUCCESSO' : 'FALLITO') . " - Crediti dopo: $credits_after";
                                
                                if ($success) {
                                    // Segna come processato
                                    $processed_data = [
                                        'user_id' => $user_id,
                                        'credits' => $credits_to_add,
                                        'processed_at' => time(),
                                        'package_key' => $package_key,
                                        'method' => 'fallback',
                                        'credits_before' => $credits_before,
                                        'credits_after' => $credits_after
                                    ];
                                    
                                    update_option("stripe_processed_" . $session_id, $processed_data);
                                    $debug_messages[] = "✓ Sessione segnata come processata";
                                    
                                    write_log_cm("FALLBACK SUCCESS: Aggiunti $credits_to_add crediti a user $user_id (da $credits_before a $credits_after)");
                                    
                                    // Salva transazione
                                    pictosound_cm_save_transaction($user_id, $session_id, $packages[$package_key], 'completed', 'stripe');
                                    $debug_messages[] = "✓ Transazione salvata nel database";
                                } else {
                                    $debug_messages[] = "❌ ERRORE: Impossibile aggiornare i crediti";
                                }
                            } else {
                                $debug_messages[] = "❌ ERRORE: Package key $package_key non trovato";
                            }
                        } else {
                            $debug_messages[] = "⚠️ Sessione già processata precedentemente";
                        }
                    } else {
                        $debug_messages[] = "❌ ERRORE: User ID non corrispondente o package vuoto";
                    }
                } else {
                    $debug_messages[] = "❌ ERRORE: Sessione non trovata o non pagata";
                }
            } else {
                $debug_messages[] = "❌ ERRORE: Classe Stripe non trovata";
            }
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            write_log_cm("FALLBACK ERROR: " . $error_msg);
            $debug_messages[] = "❌ ECCEZIONE: " . $error_msg;
        }
        
        $current_credits = pictosound_cm_get_user_credits(get_current_user_id());
        
        $message = '<div class="pictosound-payment-success-container" style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px;">
    
    <!-- HEADER SUCCESS -->
    <div style="text-align: center; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 50px 30px; border-radius: 20px; position: relative; overflow: hidden; box-shadow: 0 8px 25px rgba(40,167,69,0.3); margin-bottom: 30px;">
        <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); pointer-events: none; animation: headerGlow 4s ease-in-out infinite alternate;"></div>
        <div style="position: relative; z-index: 1;">
            <div style="font-size: 5rem; margin-bottom: 20px; animation: bounce 2s ease-in-out infinite;">🎉</div>
            <h1 style="margin: 0 0 15px 0; font-size: 2.5rem; font-weight: 700; background: linear-gradient(45deg, #fff, #f0fff0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Pagamento Completato!</h1>
            <p style="margin: 0; font-size: 1.3rem; opacity: 0.9; font-weight: 300;">Il tuo acquisto è stato elaborato con successo</p>
        </div>
    </div>
    
    <!-- DETAILS CARD -->
    <div style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); border: 1px solid #e9ecef; margin-bottom: 30px;">';

if ($session_id) {
    $message .= '
        <!-- Transaction ID -->
        <div style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 20px; border-radius: 15px; margin-bottom: 25px; border-left: 4px solid #28a745;">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 1.5rem; margin-right: 10px;">🧾</span>
                <strong style="color: #333; font-size: 1.1rem;">ID Transazione</strong>
            </div>
            <code style="background: white; padding: 8px 12px; border-radius: 8px; font-family: \'Monaco\', \'Consolas\', monospace; color: #666; font-size: 0.9rem; border: 1px solid #dee2e6; display: inline-block;">' . esc_html($session_id) . '</code>
        </div>';
}

$message .= '
        <!-- Current Balance -->
        <div style="text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); pointer-events: none;"></div>
            <div style="position: relative; z-index: 1;">
                <div style="font-size: 3rem; margin-bottom: 15px;">💎</div>
                <h3 style="margin: 0 0 10px 0; font-size: 1.3rem; opacity: 0.9;">Nuovo Saldo Crediti</h3>
                <div style="font-size: 3.5rem; font-weight: 800; margin: 10px 0; text-shadow: 0 0 20px rgba(255,255,255,0.5);" id="current-credits-display">' . $current_credits . '</div>
                <p style="margin: 0; font-size: 1.1rem; opacity: 0.8;">crediti disponibili</p>
            </div>
        </div>
        
    </div>
    
    <!-- SUCCESS DETAILS -->
    <div style="background: linear-gradient(135deg, #d4edda, #c3e6cb); padding: 25px; border-radius: 15px; border: 1px solid #c3e6cb; margin-bottom: 30px;">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 15px; align-items: center;">
            <div style="font-size: 2.5rem;">✅</div>
            <div>
                <h4 style="margin: 0 0 8px 0; color: #155724; font-size: 1.2rem;">Crediti Aggiunti Automaticamente</h4>
                <p style="margin: 0; color: #155724; opacity: 0.8; line-height: 1.5;">I tuoi nuovi crediti sono già disponibili nel tuo account e pronti per essere utilizzati per creare musica con Pictosound AI.</p>
            </div>
        </div>
    </div>
    
    <!-- ACTIONS -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
        <a href="/" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 18px 24px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; display: block; box-shadow: 0 4px 12px rgba(102,126,234,0.3); transition: all 0.3s ease;"
           onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 8px 20px rgba(102,126,234,0.4)\';"
           onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 12px rgba(102,126,234,0.3)\';">
            🏠 Torna alla Home
        </a>
        <a href="https://pictosound.com/ricarica-crediti/" style="background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 18px 24px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; display: block; box-shadow: 0 4px 12px rgba(40,167,69,0.3); transition: all 0.3s ease;"
           onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 8px 20px rgba(40,167,69,0.4)\';"
           onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 12px rgba(40,167,69,0.3)\';">
            💎 Acquista Altri Crediti
        </a>
    </div>
    
    <!-- NEXT STEPS -->
    <div style="background: linear-gradient(135deg, #fff3cd, #ffeaa7); padding: 25px; border-radius: 15px; border: 1px solid #ffeaa7; text-align: center;">
        <h4 style="margin: 0 0 15px 0; color: #856404; font-size: 1.2rem;">🚀 Cosa Fare Ora?</h4>
        <p style="margin: 0; color: #856404; line-height: 1.6;">
            I tuoi crediti sono pronti! Vai alla home page per iniziare a creare la tua musica personalizzata con l\'intelligenza artificiale di Pictosound.
        </p>
    </div>
    
</div>

<!-- ANIMATIONS CSS -->
<style>
@keyframes headerGlow {
    0% { transform: scale(1) rotate(0deg); }
    100% { transform: scale(1.1) rotate(5deg); }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

/* Responsive */
@media (max-width: 600px) {
    .pictosound-payment-success-container div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    .pictosound-payment-success-container h1 {
        font-size: 2rem !important;
    }
    
    .pictosound-payment-success-container div[style*="font-size: 3.5rem"] {
        font-size: 2.5rem !important;
    }
}
</style>';
        
        // ⚡ STEP 2: JavaScript per aggiornare il saldo a video
        $message .= '
        <script>
        jQuery(document).ready(function($) {
            const newCredits = ' . $current_credits . ';
            
            console.log("Pictosound Payment: Aggiornamento crediti a", newCredits);
            
            // Aggiorna pictosound_vars se esiste
            if (typeof pictosound_vars !== "undefined") {
                pictosound_vars.user_credits = newCredits;
                console.log("Pictosound: Crediti aggiornati in pictosound_vars:", newCredits);
            }
            
            // Aggiorna tutti gli elementi che mostrano crediti
            setTimeout(function() {
                let elementsUpdated = 0;
                
                $(".pictosound-saldo-display-widget").each(function() {
                    const currentText = $(this).text();
                    const newText = currentText.replace(/\d+(?=\s*crediti)/g, newCredits);
                    if (currentText !== newText) {
                        $(this).text(newText);
                        elementsUpdated++;
                    }
                });
                
                $(".user-credits-area").each(function() {
                    const currentText = $(this).text();
                    const newText = currentText.replace(/\d+(?=\s*crediti)/g, newCredits);
                    if (currentText !== newText) {
                        $(this).text(newText);
                        elementsUpdated++;
                    }
                });
                
                // Aggiorna altri elementi comuni che potrebbero mostrare crediti
                $("*:contains(\'crediti\')").each(function() {
                    if ($(this).children().length === 0 && $(this).text().match(/\d+\s*crediti/)) {
                        const currentText = $(this).text();
                        const newText = currentText.replace(/\d+(?=\s*crediti)/g, newCredits);
                        if (currentText !== newText) {
                            $(this).text(newText);
                            elementsUpdated++;
                        }
                    }
                });
                
                console.log("Pictosound: Elementi crediti aggiornati:", elementsUpdated);
            }, 1000);
        });
        </script>';
        
        return $message;
        
    } elseif ($status === 'cancelled') {
        return '<div class="payment-cancelled" style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
            <h2>❌ Pagamento annullato</h2>
            <p>Il pagamento è stato annullato. Non è stato addebitato nulla.</p>
            <p>Puoi <a href="' . home_url() . '">tornare alla home</a> e riprovare quando vuoi.</p>
        </div>';
    }
    
    return '<div style="padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404;">
        <p>Stato pagamento sconosciuto. <a href="' . home_url() . '">Torna alla home</a></p>
    </div>';
}
add_shortcode('pictosound_payment_return', 'pictosound_cm_payment_return_shortcode');

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
} // ⚡ QUESTA PARENTESI GRAFFA MANCAVA!
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

// [TUTTI GLI ALTRI SHORTCODE ESISTENTI RIMANGONO IDENTICI]

/**
* Shortcode per mostrare il modulo di login di WordPress.
*/
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

/**
* Shortcode per mostrare un modulo di registrazione personalizzato con fatturazione.
*/
function pictosound_cm_registration_form_shortcode() {
    // Se già loggato, mostra messaggio
    if (is_user_logged_in()) {
        return '<div style="background: #d4edda; padding: 20px; border: 1px solid #28a745; color: #155724; text-align: center; border-radius: 8px; margin: 20px 0;">
            <h3>✅ Sei già registrato e loggato!</h3>
            <p><strong>Benvenuto ' . esc_html(wp_get_current_user()->display_name) . '!</strong></p>
            <p>Vai alla <a href="/" style="color: #007cba; font-weight: bold;">homepage</a> per iniziare a usare Pictosound.</p>
        </div>';
    }
    
    $message = '';
    $form_data = []; // Per mantenere i dati in caso di errore
    
    // ============================================
    // ELABORAZIONE FORM QUANDO VIENE INVIATO
    // ============================================
    if (isset($_POST['pictosound_reg_submit']) && wp_verify_nonce($_POST['pictosound_reg_nonce_field'], 'pictosound_user_registration_action')) {
        
        // Recupera e sanitizza i dati
        $username = sanitize_user($_POST['reg_username'] ?? '');
        $email = sanitize_email($_POST['reg_email'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $password2 = $_POST['reg_password2'] ?? '';
        $nome = sanitize_text_field($_POST['reg_firstname'] ?? '');
        $cognome = sanitize_text_field($_POST['reg_lastname'] ?? '');
        $company = sanitize_text_field($_POST['reg_company'] ?? '');
        $indirizzo = sanitize_text_field($_POST['reg_address'] ?? '');
        $cap = sanitize_text_field($_POST['reg_cap'] ?? '');
        $citta = sanitize_text_field($_POST['reg_city'] ?? '');
        $cf_piva = strtoupper(sanitize_text_field($_POST['reg_cf_piva'] ?? ''));
        $codice_dest = sanitize_text_field($_POST['reg_codice_dest'] ?? '');
        $pec = sanitize_email($_POST['reg_pec'] ?? '');
        
        // Salva i dati per ripopolare il form in caso di errore
        $form_data = compact('username', 'email', 'nome', 'cognome', 'company', 'indirizzo', 'cap', 'citta', 'cf_piva', 'codice_dest', 'pec');
        
        $errors = [];
        
        // ============================================
        // VALIDAZIONI COMPLETE
        // ============================================
        
        // Username
        if (empty($username)) {
            $errors[] = __('Username è obbligatorio', 'pictosound-credits-manager');
        } elseif (strlen($username) < 3) {
            $errors[] = __('Username deve essere di almeno 3 caratteri', 'pictosound-credits-manager');
        } elseif (username_exists($username)) {
            $errors[] = __('Username già in uso', 'pictosound-credits-manager');
        } elseif (!validate_username($username)) {
            $errors[] = __('Username contiene caratteri non validi', 'pictosound-credits-manager');
        }
        
        // Email
        if (empty($email)) {
            $errors[] = __('Email è obbligatoria', 'pictosound-credits-manager');
        } elseif (!is_email($email)) {
            $errors[] = __('Email non valida', 'pictosound-credits-manager');
        } elseif (email_exists($email)) {
            $errors[] = __('Email già registrata', 'pictosound-credits-manager');
        }
        
        // Password
        if (empty($password)) {
            $errors[] = __('Password è obbligatoria', 'pictosound-credits-manager');
        } elseif (strlen($password) < 8) {
            $errors[] = __('Password deve essere di almeno 8 caratteri', 'pictosound-credits-manager');
        } elseif ($password !== $password2) {
            $errors[] = __('Le password non coincidono', 'pictosound-credits-manager');
        }
        
        // Nome/Ragione sociale (almeno uno dei due)
        if (empty($nome) && empty($company)) {
            $errors[] = __('Inserisci il nome o la ragione sociale', 'pictosound-credits-manager');
        }
        
        // Indirizzo
        if (empty($indirizzo)) {
            $errors[] = __('Indirizzo è obbligatorio', 'pictosound-credits-manager');
        }
        
        // CAP
        if (empty($cap)) {
            $errors[] = __('CAP è obbligatorio', 'pictosound-credits-manager');
        } elseif (!preg_match('/^\d{5}$/', $cap)) {
            $errors[] = __('CAP deve essere di 5 cifre', 'pictosound-credits-manager');
        }
        
        // Città
        if (empty($citta)) {
            $errors[] = __('Città è obbligatoria', 'pictosound-credits-manager');
        }
        
        // Codice fiscale/Partita IVA
        if (empty($cf_piva)) {
            $errors[] = __('Codice Fiscale o Partita IVA è obbligatorio', 'pictosound-credits-manager');
        }
        
        // Codice destinatario o PEC (almeno uno)
        if (empty($codice_dest) && empty($pec)) {
            $errors[] = __('Inserisci almeno uno tra Codice Destinatario e PEC per la fatturazione elettronica', 'pictosound-credits-manager');
        }
        
        // Privacy
        if (!isset($_POST['reg_privacy_optin'])) {
            $errors[] = __('Devi accettare l\'informativa sulla privacy', 'pictosound-credits-manager');
        }
        
        // ============================================
        // SE CI SONO ERRORI, MOSTRALI
        // ============================================
        if (!empty($errors)) {
            $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                <h4 style="margin-top: 0;">❌ Errori nella registrazione:</h4>
                <ul style="margin: 10px 0 0 20px; padding-left: 0;">';
            foreach ($errors as $error) {
                $message .= '<li style="margin-bottom: 5px;">' . esc_html($error) . '</li>';
            }
            $message .= '</ul></div>';
            
            write_log_cm("Errori registrazione per username: $username - " . implode(', ', $errors));
            
        } else {
            // ============================================
            // CREA L'UTENTE SE TUTTO OK
            // ============================================
            $user_data = [
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'first_name' => $nome,
                'last_name' => $cognome,
                'display_name' => !empty($company) ? $company : trim($nome . ' ' . $cognome),
                'role' => 'subscriber'
            ];
            
            $user_id = wp_insert_user($user_data);
            
            if (!is_wp_error($user_id)) {
                // Salva dati aggiuntivi come meta utente
                update_user_meta($user_id, 'billing_company', $company);
                update_user_meta($user_id, 'billing_address_1', $indirizzo);
                update_user_meta($user_id, 'billing_postcode', $cap);
                update_user_meta($user_id, 'billing_city', $citta);
                update_user_meta($user_id, 'billing_country', 'IT');
                update_user_meta($user_id, 'codice_fiscale_piva', $cf_piva);
                update_user_meta($user_id, 'codice_destinatario', $codice_dest);
                update_user_meta($user_id, 'pec', $pec);
                
                // 🎯 INTEGRAZIONE PICTOSOUND - Inizializza crediti
                pictosound_cm_update_user_credits($user_id, 0, 'set');
                update_user_meta($user_id, PICTOSOUND_PRIVACY_OPTIN_META_KEY, 'accepted');
                
                // Log per debug
                write_log_cm("Pictosound: Nuovo utente registrato - ID: $user_id, Username: $username, Email: $email");
                
                // Messaggio di successo
                $display_name = !empty($nome) ? $nome : (!empty($company) ? $company : $username);
                $message = '<div style="background: #d4edda; padding: 25px; border: 1px solid #28a745; color: #155724; margin: 20px 0; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; color: #155724;">🎉 REGISTRAZIONE COMPLETATA!</h3>
                    <p style="font-size: 18px; margin: 15px 0;"><strong>Benvenuto ' . esc_html($display_name) . '!</strong></p>
                    <p style="margin: 15px 0;">Il tuo account Pictosound è stato creato con successo.</p>
                    <p style="margin: 15px 0;">Hai <strong>0 crediti</strong> per iniziare. Potrai acquistarne altri dopo il login.</p>
                    <div style="margin-top: 25px;">
                        <a href="/wp-login.php" style="background: linear-gradient(45deg, #007cba, #00a0d2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 8px rgba(0,124,186,0.3);">🚀 ACCEDI AL TUO ACCOUNT</a>
                    </div>
                    <p style="margin-top: 20px; font-size: 14px; color: #666;">
                        <a href="/" style="color: #007cba;">← Torna alla homepage</a>
                    </p>
                </div>';
                
                // Reset form data dopo successo
                $form_data = [];
                
            } else {
                $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                    <h4 style="margin-top: 0;">❌ Errore durante la registrazione:</h4>
                    <p>' . esc_html($user_id->get_error_message()) . '</p>
                </div>';
                
                write_log_cm("Errore creazione utente: " . $user_id->get_error_message());
            }
        }
    }
    
    // Se registrazione completata con successo, mostra solo il messaggio
    if (strpos($message, 'REGISTRAZIONE COMPLETATA') !== false) {
        return $message;
    }
    
    // ============================================
    // GENERA IL FORM HTML (continua con lo stesso HTML della versione precedente)
    // ============================================
    ob_start();
    
    if (!empty($message)) {
        echo $message;
    }
    
    $privacy_policy_url = get_permalink(get_page_by_path('privacy-policy'));
    if (!$privacy_policy_url) $privacy_policy_url = '#';
    ?>
    
    <div style="max-width: 750px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h2 style="text-align: center; color: #333; margin-bottom: 30px; font-size: 28px;">🎵 Registrazione Pictosound</h2>
        
        <form method="post" action="<?php echo esc_url(get_permalink()); ?>" id="pictosoundRegistrationForm" class="pictosound-form" style="background: #f8f9fa; padding: 35px; border: 1px solid #dee2e6; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1);">
            
            <!-- DATI ACCOUNT -->
            <fieldset style="border: 2px solid #007cba; padding: 25px; margin: 25px 0; border-radius: 10px; background: white;">
                <legend style="background: #007cba; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 16px;">📝 Dati Account</legend>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Username', 'pictosound-credits-manager'); ?> *</label>
                        <input type="text" name="reg_username" value="<?php echo esc_attr($form_data['username'] ?? ''); ?>" required 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#007cba'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Email', 'pictosound-credits-manager'); ?> *</label>
                        <input type="email" name="reg_email" value="<?php echo esc_attr($form_data['email'] ?? ''); ?>" required 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#007cba'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Password', 'pictosound-credits-manager'); ?> * <small>(min 8 caratteri)</small></label>
                        <input type="password" name="reg_password" required 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#007cba'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Ripeti Password', 'pictosound-credits-manager'); ?> *</label>
                        <input type="password" name="reg_password2" required 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#007cba'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                </div>
            </fieldset>
            
            <!-- Altri fieldset identici alla versione precedente... -->
            <!-- DATI ANAGRAFICI, INDIRIZZO, DATI FISCALI, PRIVACY -->
            <!-- [HTML completo uguale alla versione precedente] -->
            
            <!-- BOTTONE SUBMIT -->
            <div style="text-align: center; margin-top: 35px;">
                <?php wp_nonce_field('pictosound_user_registration_action', 'pictosound_reg_nonce_field'); ?>
                <input type="submit" name="pictosound_reg_submit" value="<?php _e('🚀 CREA ACCOUNT PICTOSOUND', 'pictosound-credits-manager'); ?>" 
                       style="background: linear-gradient(45deg, #007cba, #00a0d2); color: white; padding: 20px 45px; border: none; border-radius: 12px; font-size: 20px; font-weight: bold; cursor: pointer; box-shadow: 0 8px 20px rgba(0,124,186,0.3); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px;"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 25px rgba(0,124,186,0.4)';" 
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(0,124,186,0.3)';" />
            </div>
            
            <!-- LINK LOGIN -->
            <p style="text-align: center; margin-top: 25px; color: #666; font-size: 16px;">
                <?php _e('Hai già un account?', 'pictosound-credits-manager'); ?>
                <a href="/wp-login.php" style="color: #007cba; font-weight: bold; text-decoration: none;"><?php _e('Accedi qui', 'pictosound-credits-manager'); ?></a>
            </p>
        </form>
    </div>
    
    <?php
    return ob_get_clean();
}
add_shortcode( 'pictosound_registration_form', 'pictosound_cm_registration_form_shortcode' );

/**
* Shortcode per mostrare il saldo crediti dell'utente loggato.
*/
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

/**
* Shortcode per l'area utente principale (Login/Registrati o Info Utente).
*/
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

/**
* Shortcode per mostrare il Nome e Cognome dell'utente loggato.
*/
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

/**
* Shortcode per mostrare l'email dell'utente loggato.
*/
function pictosound_cm_user_email_shortcode() {
 if ( ! is_user_logged_in() ) return '';
 $current_user = wp_get_current_user();
 return esc_html( $current_user->user_email );
}
add_shortcode( 'pictosound_user_email', 'pictosound_cm_user_email_shortcode' );

function pictosound_cm_edit_profile_shortcode() {
    // Solo utenti loggati possono editare il profilo
    if (!is_user_logged_in()) {
        return '<div style="background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 20px 0; box-shadow: 0 8px 25px rgba(255,107,107,0.3);">
            <h3 style="margin: 0 0 15px 0;">🔒 Accesso Richiesto</h3>
            <p style="margin: 0 0 20px 0;">Effettua il login per modificare il tuo profilo</p>
            <a href="/wp-login.php" style="background: white; color: #ff6b6b; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">🚀 ACCEDI ORA</a>
        </div>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    $message = '';
    $form_data = []; // Per mantenere i dati in caso di errore
    
    // ============================================
    // CARICA DATI ESISTENTI DELL'UTENTE
    // ============================================
    $existing_data = [
        'email' => $current_user->user_email,
        'nome' => $current_user->first_name,
        'cognome' => $current_user->last_name,
        'company' => get_user_meta($user_id, 'billing_company', true),
        'indirizzo' => get_user_meta($user_id, 'billing_address_1', true),
        'cap' => get_user_meta($user_id, 'billing_postcode', true),
        'citta' => get_user_meta($user_id, 'billing_city', true),
        'cf_piva' => get_user_meta($user_id, 'codice_fiscale_piva', true),
        'codice_dest' => get_user_meta($user_id, 'codice_destinatario', true),
        'pec' => get_user_meta($user_id, 'pec', true)
    ];
    
    // ⚡ DEBUG: Log dei dati esistenti
    write_log_cm("DEBUG Edit Profile - Dati esistenti per user $user_id: " . print_r($existing_data, true));
    
    // ============================================
    // ELABORAZIONE FORM QUANDO VIENE INVIATO
    // ============================================
    if (isset($_POST['pictosound_edit_submit'])) {
        
        // ⚡ DEBUG: Log del POST
        write_log_cm("DEBUG Edit Profile - POST ricevuto per user $user_id");
        write_log_cm("DEBUG Edit Profile - POST data: " . print_r($_POST, true));
        
        // Verifica nonce
        if (!wp_verify_nonce($_POST['pictosound_edit_nonce_field'], 'pictosound_user_edit_profile_action')) {
            write_log_cm("DEBUG Edit Profile - ERRORE: Nonce non valido");
            $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                <h4 style="margin-top: 0;">❌ Errore di sicurezza:</h4>
                <p>Sessione scaduta. Ricarica la pagina e riprova.</p>
            </div>';
        } else {
            
            // Recupera e sanitizza i dati
            $email = sanitize_email($_POST['edit_email'] ?? '');
            $nome = sanitize_text_field($_POST['edit_firstname'] ?? '');
            $cognome = sanitize_text_field($_POST['edit_lastname'] ?? '');
            $company = sanitize_text_field($_POST['edit_company'] ?? '');
            $indirizzo = sanitize_text_field($_POST['edit_address'] ?? '');
            $cap = sanitize_text_field($_POST['edit_cap'] ?? '');
            $citta = sanitize_text_field($_POST['edit_city'] ?? '');
            $cf_piva = strtoupper(sanitize_text_field($_POST['edit_cf_piva'] ?? ''));
            $codice_dest = sanitize_text_field($_POST['edit_codice_dest'] ?? '');
            $pec = sanitize_email($_POST['edit_pec'] ?? '');
            $new_password = $_POST['edit_password'] ?? '';
            $new_password2 = $_POST['edit_password2'] ?? '';
            
            // ⚡ DEBUG: Log dei dati sanitizzati
            $debug_data = compact('email', 'nome', 'cognome', 'company', 'indirizzo', 'cap', 'citta', 'cf_piva', 'codice_dest', 'pec');
            write_log_cm("DEBUG Edit Profile - Dati sanitizzati: " . print_r($debug_data, true));
            
            // Salva i dati per ripopolare il form in caso di errore
            $form_data = $debug_data;
            
            $errors = [];
            
            // ============================================
            // VALIDAZIONI SEMPLIFICATE PER DEBUG
            // ============================================
            
            // Email
            if (empty($email)) {
                $errors[] = __('Email è obbligatoria', 'pictosound-credits-manager');
            } elseif (!is_email($email)) {
                $errors[] = __('Email non valida', 'pictosound-credits-manager');
            } elseif ($email !== $current_user->user_email && email_exists($email)) {
                $errors[] = __('Email già in uso da un altro utente', 'pictosound-credits-manager');
            }
            
            // Password (opzionale in editing)
            if (!empty($new_password)) {
                if (strlen($new_password) < 8) {
                    $errors[] = __('Password deve essere di almeno 8 caratteri', 'pictosound-credits-manager');
                } elseif ($new_password !== $new_password2) {
                    $errors[] = __('Le password non coincidono', 'pictosound-credits-manager');
                }
            }
            
            // ⚡ VALIDAZIONE MINIMA per permettere il salvataggio
            // Rimuovo per ora le validazioni strict per testare
            
            // ⚡ DEBUG: Log errori di validazione
            if (!empty($errors)) {
                write_log_cm("DEBUG Edit Profile - Errori validazione: " . print_r($errors, true));
            }
            
            // ============================================
            // SE CI SONO ERRORI, MOSTRALI
            // ============================================
            if (!empty($errors)) {
                $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                    <h4 style="margin-top: 0;">❌ Errori nell\'aggiornamento del profilo:</h4>
                    <ul style="margin: 10px 0 0 20px; padding-left: 0;">';
                foreach ($errors as $error) {
                    $message .= '<li style="margin-bottom: 5px;">' . esc_html($error) . '</li>';
                }
                $message .= '</ul></div>';
                
            } else {
                // ============================================
                // AGGIORNA I DATI UTENTE SE TUTTO OK
                // ============================================
                write_log_cm("DEBUG Edit Profile - Inizio aggiornamento dati per user $user_id");
                
                // 1. AGGIORNA DATI PRINCIPALI UTENTE
                $user_data = [
                    'ID' => $user_id,
                    'user_email' => $email,
                    'first_name' => $nome,
                    'last_name' => $cognome,
                    'display_name' => !empty($company) ? $company : trim($nome . ' ' . $cognome)
                ];
                
                // Aggiungi password se specificata
                if (!empty($new_password)) {
                    $user_data['user_pass'] = $new_password;
                    write_log_cm("DEBUG Edit Profile - Password verrà aggiornata");
                }
                
                write_log_cm("DEBUG Edit Profile - Dati utente da aggiornare: " . print_r($user_data, true));
                
                $update_result = wp_update_user($user_data);
                
                if (is_wp_error($update_result)) {
                    write_log_cm("DEBUG Edit Profile - ERRORE wp_update_user: " . $update_result->get_error_message());
                    $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                        <h4 style="margin-top: 0;">❌ Errore durante l\'aggiornamento:</h4>
                        <p>' . esc_html($update_result->get_error_message()) . '</p>
                    </div>';
                } else {
                    write_log_cm("DEBUG Edit Profile - wp_update_user SUCCESS - Result: $update_result");
                    
                    // 2. AGGIORNA META DATI AGGIUNTIVI
                    $meta_updates = [
                        'billing_company' => $company,
                        'billing_address_1' => $indirizzo,
                        'billing_postcode' => $cap,
                        'billing_city' => $citta,
                        'billing_country' => 'IT',
                        'codice_fiscale_piva' => $cf_piva,
                        'codice_destinatario' => $codice_dest,
                        'pec' => $pec
                    ];
                    
                    $meta_success = 0;
                    $meta_errors = [];
                    
                    foreach ($meta_updates as $meta_key => $meta_value) {
                        $result = update_user_meta($user_id, $meta_key, $meta_value);
                        write_log_cm("DEBUG Edit Profile - update_user_meta($user_id, '$meta_key', '$meta_value') = " . ($result ? 'SUCCESS' : 'FAILED'));
                        
                        if ($result !== false) {
                            $meta_success++;
                        } else {
                            $meta_errors[] = $meta_key;
                        }
                    }
                    
                    write_log_cm("DEBUG Edit Profile - Meta aggiornati con successo: $meta_success/" . count($meta_updates));
                    if (!empty($meta_errors)) {
                        write_log_cm("DEBUG Edit Profile - Meta con errori: " . implode(', ', $meta_errors));
                    }
                    
                    // 3. VERIFICA CHE I DATI SIANO STATI SALVATI
                    $verification_data = [
                        'first_name' => get_user_meta($user_id, 'first_name', true),
                        'last_name' => get_user_meta($user_id, 'last_name', true),
                        'billing_company' => get_user_meta($user_id, 'billing_company', true),
                        'billing_address_1' => get_user_meta($user_id, 'billing_address_1', true),
                        'billing_postcode' => get_user_meta($user_id, 'billing_postcode', true),
                        'billing_city' => get_user_meta($user_id, 'billing_city', true),
                        'codice_fiscale_piva' => get_user_meta($user_id, 'codice_fiscale_piva', true),
                        'codice_destinatario' => get_user_meta($user_id, 'codice_destinatario', true),
                        'pec' => get_user_meta($user_id, 'pec', true)
                    ];
                    
                    write_log_cm("DEBUG Edit Profile - Verifica dati salvati: " . print_r($verification_data, true));
                    
                    // Messaggio di successo
                    $display_name = !empty($nome) ? $nome : (!empty($company) ? $company : $current_user->user_login);
                    $message = '<div style="background: #d4edda; padding: 25px; border: 1px solid #28a745; color: #155724; margin: 20px 0; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #155724;">✅ PROFILO AGGIORNATO!</h3>
                        <p style="font-size: 18px; margin: 15px 0;"><strong>Ciao ' . esc_html($display_name) . '!</strong></p>
                        <p style="margin: 15px 0;">Le tue informazioni sono state salvate con successo.</p>
                        ' . (!empty($new_password) ? '<p style="margin: 15px 0; color: #155724;"><strong>Password aggiornata!</strong> Usa la nuova password al prossimo login.</p>' : '') . '
                        <div style="margin-top: 25px;">
                            <a href="/" style="background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 8px rgba(40,167,69,0.3);">🏠 TORNA ALLA HOME</a>
                        </div>
                        <div style="margin-top: 15px; font-size: 12px; color: #666;">
                            Debug: Meta aggiornati ' . $meta_success . '/' . count($meta_updates) . ' | User update: ' . ($update_result ? 'OK' : 'FAILED') . '
                        </div>
                    </div>';
                    
                    // Aggiorna i dati esistenti per il form con i nuovi valori
                    $existing_data = [
                        'email' => $email,
                        'nome' => $nome,
                        'cognome' => $cognome,
                        'company' => $company,
                        'indirizzo' => $indirizzo,
                        'cap' => $cap,
                        'citta' => $citta,
                        'cf_piva' => $cf_piva,
                        'codice_dest' => $codice_dest,
                        'pec' => $pec
                    ];
                    
                    // Reset form data dopo successo
                    $form_data = [];
                    
                    write_log_cm("DEBUG Edit Profile - Processo completato con successo per user $user_id");
                }
            }
        }
    }
    
    // Usa existing_data se non ci sono errori, altrimenti form_data
    $display_data = empty($form_data) ? $existing_data : array_merge($existing_data, $form_data);
    
    // ⚡ DEBUG: Log dei dati che verranno mostrati nel form
    write_log_cm("DEBUG Edit Profile - Dati da mostrare nel form: " . print_r($display_data, true));
    
    // ============================================
    // GENERA IL FORM HTML (identico alla versione precedente)
    // ============================================
    ob_start();
    
    if (!empty($message)) {
        echo $message;
    }
    ?>
    
    <div style="max-width: 750px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <h2 style="text-align: center; color: #333; margin-bottom: 30px; font-size: 28px;">👤 Modifica il Tuo Profilo</h2>
        
            
        <form method="post" action="<?php echo esc_url(get_permalink()); ?>" id="pictosoundEditProfileForm" class="pictosound-form" style="background: #f8f9fa; padding: 35px; border: 1px solid #dee2e6; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1);">
            
            <!-- INFO ACCOUNT -->
            <fieldset style="border: 2px solid #007cba; padding: 25px; margin: 25px 0; border-radius: 10px; background: white;">
                <legend style="background: #007cba; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 16px;">📝 Informazioni Account</legend>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Username', 'pictosound-credits-manager'); ?></label>
                        <input type="text" value="<?php echo esc_attr($current_user->user_login); ?>" disabled 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; background: #f1f1f1; color: #666;" />
                        <small style="color: #666; font-size: 12px;">Username non modificabile</small>
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Email', 'pictosound-credits-manager'); ?> *</label>
                        <input type="email" name="edit_email" value="<?php echo esc_attr($display_data['email']); ?>" required 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#007cba'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Nuova Password', 'pictosound-credits-manager'); ?> <small>(opzionale, min 8 caratteri)</small></label>
                        <input type="password" name="edit_password" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#007cba'" onblur="this.style.borderColor='#ddd'" 
                               placeholder="Lascia vuoto per non modificare" />
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Ripeti Nuova Password', 'pictosound-credits-manager'); ?></label>
                        <input type="password" name="edit_password2" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#007cba'" onblur="this.style.borderColor='#ddd'" 
                               placeholder="Ripeti la nuova password" />
                    </div>
                </div>
            </fieldset>
            
            <!-- DATI ANAGRAFICI -->
            <fieldset style="border: 2px solid #28a745; padding: 25px; margin: 25px 0; border-radius: 10px; background: white;">
                <legend style="background: #28a745; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 16px;">👥 Dati Anagrafici</legend>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Nome', 'pictosound-credits-manager'); ?></label>
                        <input type="text" name="edit_firstname" value="<?php echo esc_attr($display_data['nome']); ?>" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#28a745'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Cognome', 'pictosound-credits-manager'); ?></label>
                        <input type="text" name="edit_lastname" value="<?php echo esc_attr($display_data['cognome']); ?>" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#28a745'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Ragione Sociale / Azienda', 'pictosound-credits-manager'); ?></label>
                        <input type="text" name="edit_company" value="<?php echo esc_attr($display_data['company']); ?>" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#28a745'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                </div>
            </fieldset>
            
            <!-- INDIRIZZO -->
            <fieldset style="border: 2px solid #ffc107; padding: 25px; margin: 25px 0; border-radius: 10px; background: white;">
                <legend style="background: #ffc107; color: #333; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 16px;">📍 Indirizzo</legend>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Indirizzo Completo', 'pictosound-credits-manager'); ?></label>
                    <input type="text" name="edit_address" value="<?php echo esc_attr($display_data['indirizzo']); ?>" 
                           style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                           onfocus="this.style.borderColor='#ffc107'" onblur="this.style.borderColor='#ddd'" 
                           placeholder="Via/Piazza, numero civico" />
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('CAP', 'pictosound-credits-manager'); ?></label>
                        <input type="text" name="edit_cap" value="<?php echo esc_attr($display_data['cap']); ?>" pattern="[0-9]{5}"
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#ffc107'" onblur="this.style.borderColor='#ddd'" 
                               placeholder="00000" maxlength="5" />
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Città', 'pictosound-credits-manager'); ?></label>
                        <input type="text" name="edit_city" value="<?php echo esc_attr($display_data['citta']); ?>" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#ffc107'" onblur="this.style.borderColor='#ddd'" />
                    </div>
                </div>
            </fieldset>
            
            <!-- DATI FISCALI -->
            <fieldset style="border: 2px solid #dc3545; padding: 25px; margin: 25px 0; border-radius: 10px; background: white;">
                <legend style="background: #dc3545; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 16px;">💼 Dati Fiscali e Fatturazione</legend>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Codice Fiscale o Partita IVA', 'pictosound-credits-manager'); ?></label>
                    <input type="text" name="edit_cf_piva" value="<?php echo esc_attr($display_data['cf_piva']); ?>" 
                           style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; text-transform: uppercase;"
                           onfocus="this.style.borderColor='#dc3545'" onblur="this.style.borderColor='#ddd'" 
                           placeholder="RSSMRA80A01H501Z oppure 12345678901" />
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('Codice Destinatario SDI', 'pictosound-credits-manager'); ?></label>
                        <input type="text" name="edit_codice_dest" value="<?php echo esc_attr($display_data['codice_dest']); ?>" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#dc3545'" onblur="this.style.borderColor='#ddd'" 
                               placeholder="0000000" maxlength="7" />
                    </div>
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;"><?php _e('PEC (Posta Elettronica Certificata)', 'pictosound-credits-manager'); ?></label>
                        <input type="email" name="edit_pec" value="<?php echo esc_attr($display_data['pec']); ?>" 
                               style="width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               onfocus="this.style.borderColor='#dc3545'" onblur="this.style.borderColor='#ddd'" 
                               placeholder="fatture@pec.esempio.it" />
                    </div>
                </div>
            </fieldset>
            
            <!-- BOTTONE SUBMIT -->
            <div style="text-align: center; margin-top: 35px;">
                <?php wp_nonce_field('pictosound_user_edit_profile_action', 'pictosound_edit_nonce_field'); ?>
                <input type="submit" name="pictosound_edit_submit" value="<?php _e('💾 SALVA MODIFICHE', 'pictosound-credits-manager'); ?>" 
                       style="background: linear-gradient(45deg, #007cba, #00a0d2); color: white; padding: 20px 45px; border: none; border-radius: 12px; font-size: 20px; font-weight: bold; cursor: pointer; box-shadow: 0 8px 20px rgba(0,124,186,0.3); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px;"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 25px rgba(0,124,186,0.4)';" 
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(0,124,186,0.3)';" />
            </div>
        </form>
    </div>
    
    <!-- CSS RESPONSIVE (identico alla versione precedente) -->
    <style>
    @media (max-width: 768px) {
        #pictosoundEditProfileForm div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            gap: 15px !important;
        }
        
        #pictosoundEditProfileForm fieldset {
            padding: 20px !important;
            margin: 20px 0 !important;
        }
        
        #pictosoundEditProfileForm input[type="submit"] {
            padding: 15px 25px !important;
            font-size: 16px !important;
        }
    }
    
    @media (max-width: 480px) {
        #pictosoundEditProfileForm {
            padding: 25px 20px !important;
        }
        
        #pictosoundEditProfileForm h2 {
            font-size: 24px !important;
        }
    }
    </style>
    
    <?php
    return ob_get_clean();
}
add_shortcode('pictosound_edit_profile', 'pictosound_cm_edit_profile_shortcode');

/**
* Funzione callback per lo shortcode [mio_saldo_crediti_pictosound].
*/
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

/**
* Carica il text domain per le traduzioni.
*/
function pictosound_ms_load_textdomain() {
   load_plugin_textdomain( 'pictosound-mostra-saldo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'plugins_loaded', 'pictosound_ms_load_textdomain' );
/**
 * AGGIUNTO PLUGIN GALLERY
 * 
 * 
 */
function pictosound_user_gallery_shortcode($atts) {
    // Se l'utente non è loggato
    if (!is_user_logged_in()) {
        return '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0;">🔒 Accesso Richiesto</h3>
            <p style="margin: 0 0 20px 0;">Effettua il login per vedere la tua gallery personale</p>
            <a href="/wp-login.php" style="background: white; color: #667eea; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">🚀 ACCEDI ORA</a>
        </div>';
    }
    
    $atts = shortcode_atts([
        'layout' => 'grid', // grid, list, carousel
        'columns' => '3',
        'show_stats' => 'true',
        'show_controls' => 'true',
        'items_per_page' => '12',
        'show_empty_message' => 'true'
    ], $atts);
    
    // Enqueue degli script necessari
    wp_enqueue_script('pictosound-gallery-script', '/wp-content/pictosound/js/gallery.js', ['jquery'], '1.0.0', true);
    
    // Localizza script per AJAX
    wp_localize_script('pictosound-gallery-script', 'pictosoundGallery', [
        'ajaxUrl' => '/wp-content/pictosound/gallery.php',
        'nonce' => wp_create_nonce('pictosound_gallery_nonce'),
        'userId' => get_current_user_id(),
        'settings' => $atts
    ]);
    
    ob_start();
    ?>
    
    <div class="pictosound-user-gallery" data-layout="<?php echo esc_attr($atts['layout']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        
        <?php if ($atts['show_stats'] === 'true'): ?>
        <!-- STATISTICHE -->
        <div class="gallery-stats-bar" id="galleryStatsBar">
            <div class="stat-item">
                <div class="stat-icon">🎵</div>
                <div class="stat-number" id="totalCreations">0</div>
                <div class="stat-label">Creazioni</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">▶️</div>
                <div class="stat-number" id="totalPlays">0</div>
                <div class="stat-label">Riproduzioni</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">❤️</div>
                <div class="stat-number" id="favoritesCount">0</div>
                <div class="stat-label">Preferiti</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">🔗</div>
                <div class="stat-number" id="totalShares">0</div>
                <div class="stat-label">Condivisioni</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_controls'] === 'true'): ?>
        <!-- CONTROLLI -->
        <div class="gallery-controls">
            <div class="controls-row">
                <div class="search-container">
                    <input type="text" id="gallerySearch" placeholder="Cerca nelle tue creazioni..." class="gallery-search-input">
                    <span class="search-icon">🔍</span>
                </div>
                
                <div class="filter-container">
                    <button class="filter-btn active" data-filter="all">Tutte</button>
                    <button class="filter-btn" data-filter="favorites">Preferiti</button>
                    <button class="filter-btn" data-filter="public">Pubbliche</button>
                    <button class="filter-btn" data-filter="recent">Recenti</button>
                </div>
                
                <div class="sort-container">
                    <select id="gallerySort" class="gallery-sort-select">
                        <option value="created_at DESC">Più Recenti</option>
                        <option value="created_at ASC">Più Vecchie</option>
                        <option value="plays_count DESC">Più Ascoltate</option>
                        <option value="title ASC">Titolo A-Z</option>
                    </select>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- LOADING STATE -->
        <div class="gallery-loading" id="galleryLoading">
            <div class="loading-spinner"></div>
            <p>Caricamento delle tue creazioni...</p>
        </div>
        
        <!-- GRIGLIA CONTENUTI -->
        <div class="gallery-content" id="galleryContent">
            <!-- Le creazioni verranno caricate qui via JavaScript -->
        </div>
        
        <!-- EMPTY STATE -->
        <?php if ($atts['show_empty_message'] === 'true'): ?>
        <div class="gallery-empty" id="galleryEmpty" style="display: none;">
            <div class="empty-icon">🎵</div>
            <h3>Nessuna creazione trovata</h3>
            <p>Inizia creando la tua prima composizione musicale da un'immagine!</p>
            <a href="/" class="btn-create-first">✨ Crea la Tua Prima Musica</a>
        </div>
        <?php endif; ?>
        
        <!-- PAGINAZIONE -->
        <div class="gallery-pagination" id="galleryPagination"></div>
        
    </div>
    
    <!-- CSS INLINE per la gallery -->
    <style>
    .pictosound-user-gallery {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    /* STATS BAR */
    .gallery-stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-item {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-item:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    /* CONTROLS */
    .gallery-controls {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .controls-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        justify-content: space-between;
    }
    
    .search-container {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    
    .gallery-search-input {
        width: 100%;
        padding: 12px 40px 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    
    .gallery-search-input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .search-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }
    
    .filter-container {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 10px 20px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .filter-btn:hover,
    .filter-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .gallery-sort-select {
        padding: 10px 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        background: white;
        cursor: pointer;
    }
    
    /* LOADING */
    .gallery-loading {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* CONTENT GRID */
    .gallery-content {
        display: grid;
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .pictosound-user-gallery[data-layout="grid"] .gallery-content {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
    
    .pictosound-user-gallery[data-columns="2"] .gallery-content {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    }
    
    .pictosound-user-gallery[data-columns="4"] .gallery-content {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .pictosound-user-gallery[data-layout="list"] .gallery-content {
        grid-template-columns: 1fr;
    }
    
    /* CREATION CARD */
    .creation-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .creation-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .creation-header {
        position: relative;
        height: 200px;
        background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .creation-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .creation-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .creation-card:hover .creation-overlay {
        opacity: 1;
    }
    
    .play-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(255,255,255,0.9);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #667eea;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .play-button:hover {
        background: white;
        transform: scale(1.1);
    }
    
    .favorite-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.9);
        color: #ccc;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .favorite-btn.active {
        color: #ff6b6b;
    }
    
    .creation-info {
        padding: 20px;
    }
    
    .creation-title {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 8px;
        color: #333;
    }
    
    .creation-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .creation-stats {
        display: flex;
        gap: 15px;
        font-size: 0.85rem;
        color: #888;
        margin-bottom: 15px;
    }
    
    .creation-actions {
        display: flex;
        gap: 10px;
        justify-content: space-between;
    }
    
    .action-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-primary:hover {
        background: #5a6fd8;
    }
    
    .btn-secondary {
        background: #f8f9fa;
        color: #333;
        border: 1px solid #e9ecef;
    }
    
    .btn-secondary:hover {
        background: #e9ecef;
    }
    
    /* EMPTY STATE */
    .gallery-empty {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 20px;
        margin: 40px 0;
    }
    
    .empty-icon {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .gallery-empty h3 {
        font-size: 1.5rem;
        color: #666;
        margin-bottom: 10px;
    }
    
    .gallery-empty p {
        color: #888;
        margin-bottom: 30px;
    }
    
    .btn-create-first {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        padding: 15px 30px;
        text-decoration: none;
        border-radius: 10px;
        font-weight: bold;
        display: inline-block;
        transition: all 0.3s;
    }
    
    .btn-create-first:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }
    
    /* PAGINATION */
    .gallery-pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
    }
    
    .page-btn {
        padding: 12px 20px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .page-btn:hover,
    .page-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .pictosound-user-gallery {
            padding: 15px;
        }
        
        .controls-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-container {
            justify-content: center;
        }
        
        .gallery-content {
            grid-template-columns: 1fr !important;
            gap: 20px;
        }
        
        .gallery-stats-bar {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .gallery-stats-bar {
            grid-template-columns: 1fr;
        }
        
        .creation-actions {
            flex-direction: column;
            gap: 8px;
        }
        
        .action-btn {
            text-align: center;
            justify-content: center;
        }
    }
    </style>
    
    <!-- JavaScript per la gallery -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof PictosoundGallery === 'undefined') {
            // Carica la gallery se lo script non è già stato caricato
            loadPictosoundGallery();
        }
    });
    
    function loadPictosoundGallery() {
        // Variabili gallery
        let currentPage = 1;
        let currentFilter = 'all';
        let currentSort = 'created_at DESC';
        let currentSearch = '';
        
        // Carica statistiche
        loadGalleryStats();
        
        // Carica creazioni iniziali
        loadGalleryCreations();
        
        // Setup event listeners
        setupGalleryListeners();
        
        function loadGalleryStats() {
            fetch('/wp-content/pictosound/gallery.php?stats=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.stats;
                        document.getElementById('totalCreations').textContent = stats.total_creations || 0;
                        document.getElementById('totalPlays').textContent = stats.total_plays || 0;
                        document.getElementById('favoritesCount').textContent = stats.favorites_count || 0;
                        document.getElementById('totalShares').textContent = stats.total_shares || 0;
                    }
                })
                .catch(error => console.error('Errore caricamento stats:', error));
        }
        
        function loadGalleryCreations() {
            const loading = document.getElementById('galleryLoading');
            const content = document.getElementById('galleryContent');
            const empty = document.getElementById('galleryEmpty');
            
            loading.style.display = 'block';
            if (content) content.innerHTML = '';
            if (empty) empty.style.display = 'none';
            
            const filter = {};
            if (currentSearch) filter.search = currentSearch;
            if (currentFilter === 'favorites') filter.is_favorite = true;
            if (currentFilter === 'recent') {
                filter.date_from = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            }
            filter.order_by = currentSort;
            
            fetch('/wp-content/pictosound/gallery.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'get_creations',
                    limit: 12,
                    offset: (currentPage - 1) * 12,
                    filter: filter
                })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.success) {
                    const { creations, total } = data.data;
                    
                    if (creations.length === 0) {
                        if (empty) empty.style.display = 'block';
                    } else {
                        renderGalleryCreations(creations);
                    }
                } else {
                    console.error('Errore caricamento creazioni:', data);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Errore:', error);
            });
        }
        
        function renderGalleryCreations(creations) {
            const content = document.getElementById('galleryContent');
            if (!content) return;
            
            content.innerHTML = '';
            
            creations.forEach(creation => {
                const card = createGalleryCreationCard(creation);
                content.appendChild(card);
            });
        }
        
        function createGalleryCreationCard(creation) {
            const card = document.createElement('div');
            card.className = 'creation-card';
            card.dataset.creationId = creation.id;
            
            const imageUrl = creation.original_image_path ? 
                `/wp-content/pictosound/uploads/${creation.original_image_path}` : 
                'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200"><rect width="400" height="200" fill="%23f0f0f0"/><text x="200" y="100" text-anchor="middle" fill="%23666" font-family="Arial" font-size="16">🎵 Audio</text></svg>';
            
            const createdDate = new Date(creation.created_at).toLocaleDateString('it-IT', {
                day: '2-digit',
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            card.innerHTML = `
                <div class="creation-header">
                    <img src="${imageUrl}" alt="${creation.title || 'Creazione'}" class="creation-image" 
                         onerror="this.src='data:image/svg+xml,<svg xmlns=\\"http://www.w3.org/2000/svg\\" viewBox=\\"0 0 400 200\\"><rect width=\\"400\\" height=\\"200\\" fill=\\"%23f0f0f0\\"/><text x=\\"200\\" y=\\"100\\" text-anchor=\\"middle\\" fill=\\"%23666\\" font-family=\\"Arial\\" font-size=\\"16\\">🎵 Audio</text></svg>'">
                    <button class="favorite-btn ${creation.is_favorite ? 'active' : ''}" onclick="toggleGalleryFavorite(${creation.id})">
                        ❤️
                    </button>
                    <div class="creation-overlay">
                        <button class="play-button" onclick="playGalleryCreation(${creation.id}, '${creation.audio_file_url}')">
                            ▶️
                        </button>
                    </div>
                </div>
                <div class="creation-info">
                    <div class="creation-title">${creation.title || 'Senza titolo'}</div>
                    <div class="creation-meta">
                        <span>⏱️ ${creation.duration}s</span>
                        <span>📅 ${createdDate}</span>
                    </div>
                    <div class="creation-stats">
                        <span>▶️ ${creation.plays_count || 0}</span>
                        <span>📥 ${creation.downloads_count || 0}</span>
                        <span>🔗 ${creation.shared_count || 0}</span>
                    </div>
                    <div class="creation-actions">
                        <button class="action-btn btn-primary" onclick="downloadGalleryCreation('${creation.audio_file_url}', '${creation.title}')">
                            📥
                        </button>
                        <button class="action-btn btn-secondary" onclick="shareGalleryCreation(${creation.id})">
                            🔗
                        </button>
                    </div>
                </div>
            `;
            
            return card;
        }
        
        function setupGalleryListeners() {
            // Search
            const searchInput = document.getElementById('gallerySearch');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        currentSearch = this.value;
                        currentPage = 1;
                        loadGalleryCreations();
                    }, 500);
                });
            }
            
            // Filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    currentPage = 1;
                    loadGalleryCreations();
                });
            });
            
            // Sort select
            const sortSelect = document.getElementById('gallerySort');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    currentSort = this.value;
                    currentPage = 1;
                    loadGalleryCreations();
                });
            }
        }
        
        // Funzioni globali per interazioni
        window.playGalleryCreation = function(creationId, audioUrl) {
            // Incrementa play count
            fetch('/wp-content/pictosound/gallery.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'play',
                    creation_id: creationId
                })
            });
            
            // Play audio
            const audio = new Audio(audioUrl);
            audio.play().catch(error => {
                console.error('Errore riproduzione:', error);
            });
        };
        
        window.toggleGalleryFavorite = function(creationId) {
            fetch('/wp-content/pictosound/gallery.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'toggle_favorite',
                    creation_id: creationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector(`[data-creation-id="${creationId}"]`);
                    const favoriteBtn = card.querySelector('.favorite-btn');
                    favoriteBtn.classList.toggle('active');
                    loadGalleryStats();
                }
            });
        };
        
        window.downloadGalleryCreation = function(audioUrl, title) {
            const link = document.createElement('a');
            link.href = audioUrl;
            link.download = `${title || 'creazione-pictosound'}.mp3`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };
        
        window.shareGalleryCreation = function(creationId) {
            // Implementazione condivisione semplificata
            if (navigator.share) {
                navigator.share({
                    title: 'La mia creazione Pictosound',
                    text: 'Ascolta questa musica che ho creato con Pictosound!',
                    url: window.location.href
                });
            } else {
                // Fallback per browser senza Web Share API
                const url = window.location.href;
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copiato negli appunti!');
                });
            }
        };
    }
    </script>
    
    <?php
    return ob_get_clean();
}

// Registra lo shortcode
add_shortcode('pictosound_gallery', 'pictosound_user_gallery_shortcode');

/**
 * Shortcode per mostrare una singola creazione condivisa
 */
function pictosound_shared_creation_shortcode($atts) {
    $atts = shortcode_atts([
        'token' => '',
        'show_creator' => 'true'
    ], $atts);
    
    if (empty($atts['token'])) {
        return '<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; text-align: center;">
            <h3>❌ Link non valido</h3>
            <p>Il link di condivisione non è valido o è scaduto.</p>
        </div>';
    }
    
    // Carica la creazione tramite token
    $creation_data = null;
    
    // Simula caricamento (dovresti implementare una chiamata AJAX qui)
    ob_start();
    ?>
    
    <div class="pictosound-shared-creation" data-token="<?php echo esc_attr($atts['token']); ?>">
        <div id="sharedCreationLoading">
            <div class="loading-spinner"></div>
            <p>Caricamento creazione condivisa...</p>
        </div>
        
        <div id="sharedCreationContent" style="display: none;">
            <!-- Il contenuto verrà caricato via JavaScript -->
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        loadSharedCreation('<?php echo esc_js($atts['token']); ?>');
    });
    
    function loadSharedCreation(token) {
        fetch('/wp-content/pictosound/gallery.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_shared_creation',
                share_token: token
            })
        })
        .then(response => response.json())
        .then(data => {
            const loading = document.getElementById('sharedCreationLoading');
            const content = document.getElementById('sharedCreationContent');
            
            loading.style.display = 'none';
            
            if (data.success) {
                const creation = data.creation;
                content.innerHTML = createSharedCreationHTML(creation);
                content.style.display = 'block';
            } else {
                content.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; text-align: center;"><h3>❌ Creazione non trovata</h3><p>Il link di condivisione non è valido o è scaduto.</p></div>';
                content.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            document.getElementById('sharedCreationLoading').innerHTML = '<div style="color: #dc3545; text-align: center;">Errore nel caricamento della creazione.</div>';
        });
    }
    
    function createSharedCreationHTML(creation) {
        const imageUrl = creation.original_image_path ? 
            `/wp-content/pictosound/uploads/${creation.original_image_path}` : 
            'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200"><rect width="400" height="200" fill="%23f0f0f0"/><text x="200" y="100" text-anchor="middle" fill="%23666" font-family="Arial" font-size="16">🎵 Audio</text></svg>';
        
        return `
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); text-align: center; max-width: 600px; margin: 0 auto;">
                <div style="position: relative; margin-bottom: 20px; border-radius: 15px; overflow: hidden;">
                    <img src="${imageUrl}" alt="${creation.title || 'Creazione condivisa'}" style="width: 100%; height: 300px; object-fit: cover;">
                    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;">
                        <button onclick="playSharedCreation('${creation.audio_file_url}')" style="width: 80px; height: 80px; border-radius: 50%; background: white; border: none; font-size: 30px; color: #667eea; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                            ▶️
                        </button>
                    </div>
                </div>
                <h2 style="margin: 0 0 10px 0; color: #333;">${creation.title || 'Creazione Pictosound'}</h2>
                <p style="color: #666; margin-bottom: 20px;">Creato da: <strong>${creation.creator_name || 'Utente Pictosound'}</strong></p>
                <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 20px; font-size: 0.9rem; color: #888;">
                    <span>⏱️ ${creation.duration}s</span>
                    <span>▶️ ${creation.plays_count || 0} riproduzioni</span>
                    <span>📅 ${new Date(creation.created_at).toLocaleDateString('it-IT')}</span>
                </div>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button onclick="downloadSharedCreation('${creation.audio_file_url}', '${creation.title}')" style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
                        📥 Scarica Audio
                    </button>
                    <button onclick="window.open('/', '_blank')" style="background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
                        ✨ Crea la Tua Musica
                    </button>
                </div>
            </div>
        `;
    }
    
    function playSharedCreation(audioUrl) {
        const audio = new Audio(audioUrl);
        audio.play().catch(error => {
            console.error('Errore riproduzione:', error);
            alert('Errore nella riproduzione dell\'audio');
        });
    }
    
    function downloadSharedCreation(audioUrl, title) {
        const link = document.createElement('a');
        link.href = audioUrl;
        link.download = `${title || 'creazione-pictosound'}.mp3`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
    
    <?php
    return ob_get_clean();
}

// Registra lo shortcode per creazioni condivise
add_shortcode('pictosound_shared_creation', 'pictosound_shared_creation_shortcode');
?>