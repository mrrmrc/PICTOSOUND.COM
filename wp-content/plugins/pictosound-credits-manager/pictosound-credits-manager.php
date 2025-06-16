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
                    <div class="trust-text">Stripe Secure</div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon">🎯</div>
                    <div class="trust-text">Nessun Abbonamento</div>
                </div>
            </div>
            <div class="trust-description">
                I crediti non scadono mai e vengono attivati immediatamente dopo il pagamento
            </div>
        </div>
        
        <!-- STATUS MESSAGES -->
        <div id="package-status-message" class="status-message" style="display: none;"></div>
        
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
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => sprintf('Ricarica %d Crediti Pictosound', $package['credits']),
                        'description' => sprintf('Pacchetto di %d crediti per Pictosound', $package['credits']),
                    ],
                    'unit_amount' => $amount_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => home_url('/pagamento-completato/?status=success&session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => home_url('/pagamento-completato/?status=cancelled'),
            'metadata' => [
                'user_id' => $user_id,
                'package_key' => $package_key,
                'credits' => $package['credits'],
                'plugin' => 'pictosound-credits-manager',
                'timestamp' => time()
            ],
            'customer_email' => wp_get_current_user()->user_email ?? null,
        ]);
        
        write_log_cm("Stripe Session creata per user $user_id, package $package_key, amount $amount_euros EUR");
        
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
                             has_shortcode(get_post()->post_content, 'pictosound_credit_packages')
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
                            showPackageMessage('🚀 Reindirizzamento a Stripe per il pagamento sicuro...', 'info');
                            
                            // Effetto di reindirizzamento
                            $('body').append('<div class="stripe-redirect-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(102, 126, 234, 0.95); z-index: 10000; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold;"><div style="text-align: center;"><div style="font-size: 4rem; margin-bottom: 20px;">💳</div><div>Reindirizzamento a Stripe...</div><div style="margin-top: 15px; font-size: 1rem; opacity: 0.8;">Pagamento sicuro e crittografato</div></div></div>');
                            
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
                            
                            // Tenta il refresh automatico dei nonce
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
        
        // Funzione per mostrare messaggi
        function showPackageMessage(message, type) {
            const $messageDiv = $('#package-status-message');
            $messageDiv.removeClass('success error warning info').addClass(type);
            $messageDiv.html(message).show();
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $messageDiv.offset().top - 100
            }, 500);
            
            // Auto-hide success/info messages after 8 seconds
            if (type === 'success' || type === 'info') {
                setTimeout(function() {
                    $messageDiv.fadeOut();
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
        
        // Effetti hover migliorati per le card
        $('.package-card').hover(
            function() {
                $(this).find('.package-icon').css('transform', 'scale(1.1) rotate(5deg)');
            },
            function() {
                $(this).find('.package-icon').css('transform', '');
            }
        );
        
        // Aggiorna saldo crediti in tempo reale se cambia
        function updateCreditsDisplay(newCredits) {
            $('#current-credits-display').text(newCredits);
            
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
        
        // Polling periodico per aggiornare i crediti (utile dopo pagamenti completati)
        setInterval(function() {
            if (typeof pictosound_vars !== 'undefined' && pictosound_vars.ajax_url) {
                $.ajax({
                    url: pictosound_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pictosound_get_current_credits'
                    },
                    success: function(response) {
                        if (response.success && response.data.credits !== undefined) {
                            const currentDisplayed = parseInt($('#current-credits-display').text()) || 0;
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
                    }
                });
            }
        }, 10000); // Controlla ogni 10 secondi
        
        // ⚡ Sistema di auto-recovery OTTIMIZZATO per hosting
        // Traccia l'attività dell'utente per ottimizzare i refresh
        let lastUserActivity = Date.now();
        
        // Aggiorna timestamp attività su ogni interazione
        $(document).on('click keypress scroll', function() {
            lastUserActivity = Date.now();
        });
        
        // Funzione globale per aggiornare i nonce - CON RATE LIMITING CLIENT-SIDE
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
                $.ajax({
                    url: pictosound_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pictosound_regenerate_nonce'
                    },
                    timeout: 10000, // ⚡ Timeout di 10 secondi
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
        
        // ⚡ Auto-refresh INTELLIGENTE - Solo se utente attivo
        if (pictosound_vars.auto_refresh_nonces && pictosound_vars.user_recently_active) {
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
            }, 1200000); // ⚡ Ridotto a 20 minuti invece di 10
        }
        
    });
    </script>
    <?php
}
add_action('wp_footer', 'pictosound_cm_modern_packages_script');

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
// ============================================
// FUNZIONI GALLERY PICTOSOUND
// ============================================

function pictosound_cm_create_gallery_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(255) NOT NULL DEFAULT '',
        description text,
        original_image_path varchar(500) DEFAULT '',
        audio_file_path varchar(500) NOT NULL DEFAULT '',
        audio_file_url varchar(500) NOT NULL DEFAULT '',
        prompt_text text,
        duration int(11) DEFAULT 40,
        audio_format varchar(10) DEFAULT 'mp3',
        file_size int(11) DEFAULT 0,
        image_analysis_data longtext,
        detected_objects varchar(1000) DEFAULT '',
        detected_emotions varchar(500) DEFAULT '',
        musical_settings longtext,
        share_token varchar(64) NOT NULL DEFAULT '',
        is_public tinyint(1) DEFAULT 0,
        downloads_count int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY share_token (share_token),
        KEY created_at (created_at),
        KEY is_public (is_public)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    write_log_cm("Tabella gallery creata/aggiornata");
}
register_activation_hook(__FILE__, 'pictosound_cm_create_gallery_table');

function pictosound_cm_get_user_creations($user_id, $options = []) {
    global $wpdb;
    
    $defaults = [
        'limit' => 12,
        'offset' => 0,
        'order_by' => 'created_at',
        'order' => 'DESC',
        'search' => '',
        'date_from' => '',
        'date_to' => ''
    ];
    
    $options = wp_parse_args($options, $defaults);
    
    $table_name = $wpdb->prefix . 'pictosound_user_creations';
    
    $where_conditions = ['user_id = %d'];
    $where_values = [$user_id];
    
    if (!empty($options['search'])) {
        $where_conditions[] = '(title LIKE %s OR description LIKE %s OR detected_objects LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($options['search']) . '%';
        $where_values[] = $search_term;
        $where_values[] = $search_term;
        $where_values[] = $search_term;
    }
    
    if (!empty($options['date_from'])) {
        $where_conditions[] = 'created_at >= %s';
        $where_values[] = $options['date_from'] . ' 00:00:00';
    }
    
    if (!empty($options['date_to'])) {
        $where_conditions[] = 'created_at <= %s';
        $where_values[] = $options['date_to'] . ' 23:59:59';
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    $order_by = sanitize_sql_orderby($options['order_by'] . ' ' . $options['order']);
    if (!$order_by) {
        $order_by = 'created_at DESC';
    }
    
    $limit = intval($options['limit']);
    $offset = intval($options['offset']);
    
    $sql = $wpdb->prepare(
        "SELECT * FROM {$table_name} {$where_clause} ORDER BY {$order_by} LIMIT %d OFFSET %d",
        array_merge($where_values, [$limit, $offset])
    );
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    $count_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} {$where_clause}",
        $where_values
    );
    $total = $wpdb->get_var($count_sql);
    
    return [
        'creations' => $results,
        'total' => $total,
        'has_more' => ($offset + $limit) < $total
    ];
}

function pictosound_cm_my_gallery_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 20px 0;">
            <h3>🔒 Accesso Richiesto</h3>
            <p>Effettua il <a href="/wp-login.php" style="color: #ffd700; font-weight: bold;">login</a> per vedere la tua galleria personale.</p>
        </div>';
    }
    
    $atts = shortcode_atts([
        'per_page' => 12,
        'view' => 'grid'
    ], $atts);
    
    $user_id = get_current_user_id();
    
    $current_page = max(1, intval($_GET['gallery_page'] ?? 1));
    $search = sanitize_text_field($_GET['gallery_search'] ?? '');
    $date_from = sanitize_text_field($_GET['date_from'] ?? '');
    $date_to = sanitize_text_field($_GET['date_to'] ?? '');
    
    $offset = ($current_page - 1) * $atts['per_page'];
    
    $options = [
        'limit' => $atts['per_page'],
        'offset' => $offset,
        'search' => $search,
        'date_from' => $date_from,
        'date_to' => $date_to
    ];
    
    $data = pictosound_cm_get_user_creations($user_id, $options);
    
    ob_start();
    ?>
    
    <div class="pictosound-my-gallery" data-user-id="<?php echo esc_attr($user_id); ?>">
        
        <!-- HEADER -->
        <div style="text-align: center; margin-bottom: 40px; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; color: white;">
            <h2 style="margin: 0 0 20px 0; font-size: 2.5rem; font-weight: 700;">🎵 La Mia Galleria Pictosound</h2>
            <div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap;">
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 15px 20px; border-radius: 12px;">
                    <div style="font-size: 2rem; font-weight: 800; color: #ffd700;"><?php echo count($data['creations']); ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Creazioni</div>
                </div>
            </div>
        </div>
        
        <!-- FILTRI -->
        <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto; gap: 20px; align-items: end;">
                <div>
                    <input type="text" name="gallery_search" placeholder="🔍 Cerca nelle tue creazioni..." 
                           value="<?php echo esc_attr($search); ?>" 
                           style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px;" />
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" 
                           style="padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px;" />
                    <span>-</span>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" 
                           style="padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px;" />
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer;">
                        Filtra
                    </button>
                    <a href="<?php echo remove_query_arg(['gallery_search', 'date_from', 'date_to', 'gallery_page']); ?>" 
                       style="color: #64748b; text-decoration: none; padding: 12px 16px; border-radius: 8px;">Reset</a>
                </div>
            </form>
        </div>
        
        <?php if (empty($data['creations'])): ?>
            <!-- STATO VUOTO -->
            <div style="text-align: center; padding: 80px 20px; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border-radius: 20px;">
                <div style="font-size: 5rem; margin-bottom: 20px; opacity: 0.7;">🎨</div>
                <h3 style="font-size: 1.8rem; color: #334155; margin: 0 0 10px 0;">La tua galleria è vuota</h3>
                <p style="color: #64748b; font-size: 1.1rem; margin: 0 0 30px 0;">Inizia a creare la tua prima traccia musicale!</p>
                <a href="/" style="display: inline-block; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 1.1rem;">
                    🚀 Crea Ora
                </a>
            </div>
        <?php else: ?>
            <!-- GRID CREAZIONI -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; margin-bottom: 40px;">
                <?php foreach ($data['creations'] as $creation): ?>
                    <div style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">
                        
                        <!-- IMMAGINE PLACEHOLDER -->
                        <div style="position: relative; aspect-ratio: 16/9; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); display: flex; align-items: center; justify-content: center;">
                            <div style="font-size: 4rem; color: #cbd5e0;">🎵</div>
                            
                            <!-- PLAY BUTTON -->
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s;" 
                                 onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                                <button onclick="playAudio('<?php echo esc_js($creation['audio_file_url']); ?>')" 
                                        style="background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 80px; height: 80px; font-size: 2rem; cursor: pointer;">
                                    ▶️
                                </button>
                            </div>
                        </div>
                        
                        <!-- INFO -->
                        <div style="padding: 20px;">
                            <h4 style="font-size: 1.25rem; font-weight: 700; color: #1a202c; margin: 0 0 10px 0;">
                                <?php echo esc_html($creation['title'] ?: 'Senza titolo'); ?>
                            </h4>
                            <p style="display: flex; gap: 15px; font-size: 0.9rem; color: #64748b; margin: 0 0 15px 0;">
                                <span>📅 <?php echo date('d/m/Y H:i', strtotime($creation['created_at'])); ?></span>
                                <span>⏱️ <?php echo $creation['duration']; ?>s</span>
                                <span>📥 <?php echo $creation['downloads_count']; ?></span>
                            </p>
                            
                            <?php if (!empty($creation['detected_objects'])): ?>
                                <p style="font-size: 0.85rem; color: #64748b; margin: 0;">
                                    🏷️ <?php echo esc_html(substr($creation['detected_objects'], 0, 50)); ?>
                                    <?php if (strlen($creation['detected_objects']) > 50) echo '...'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- AZIONI -->
                        <div style="padding: 15px 20px 20px; display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="<?php echo esc_url($creation['audio_file_url'] . '&creation_id=' . $creation['id']); ?>" 
                               style="background: #22c55e; color: white; padding: 8px 12px; text-decoration: none; border-radius: 8px; font-size: 0.85rem;" download>
                                📥 Download
                            </a>
                            <button onclick="shareCreation('<?php echo esc_js($creation['share_token']); ?>')" 
                                    style="background: #667eea; color: white; border: none; padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; cursor: pointer;">
                                🔗 Condividi
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- AUDIO PLAYER GLOBALE -->
    <div id="gallery-audio-player" style="display: none; position: fixed; bottom: 20px; right: 20px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); z-index: 1000;">
        <audio controls preload="none" style="width: 300px;"></audio>
    </div>
    
    <script>
    function playAudio(url) {
        const player = document.getElementById('gallery-audio-player');
        const audio = player.querySelector('audio');
        
        player.style.display = 'block';
        audio.src = url;
        audio.play();
        
        audio.addEventListener('ended', function() {
            player.style.display = 'none';
        });
    }
    
    function shareCreation(token) {
        const shareUrl = window.location.origin + '/condividi/' + token;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert('🔗 Link copiato negli appunti!');
            });
        } else {
            prompt('Copia questo link:', shareUrl);
        }
    }
    </script>
    
    <?php
    return ob_get_clean();
}
add_shortcode('pictosound_my_gallery', 'pictosound_cm_my_gallery_shortcode')
?>