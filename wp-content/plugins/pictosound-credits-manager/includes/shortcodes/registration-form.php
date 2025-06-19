<?php
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
