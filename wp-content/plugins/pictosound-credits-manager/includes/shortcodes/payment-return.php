<?php
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
