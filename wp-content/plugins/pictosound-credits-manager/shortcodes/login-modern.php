
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

