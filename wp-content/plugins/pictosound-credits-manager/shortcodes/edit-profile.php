<?php
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
    // GESTIONE ELIMINAZIONE ACCOUNT
    // ============================================
    if (isset($_POST['pictosound_delete_account']) && isset($_POST['delete_confirmation_email'])) {
        
        // Verifica nonce per eliminazione
        if (!wp_verify_nonce($_POST['pictosound_delete_nonce_field'], 'pictosound_delete_account_action')) {
            write_log_cm("DEBUG Delete Account - ERRORE: Nonce non valido");
            $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                <h4 style="margin-top: 0;">❌ Errore di sicurezza:</h4>
                <p>Sessione scaduta. Ricarica la pagina e riprova.</p>
            </div>';
        } else {
            $confirmation_email = sanitize_email($_POST['delete_confirmation_email']);
            
            // Verifica che l'email di conferma corrisponda
            if ($confirmation_email !== $current_user->user_email) {
                write_log_cm("DEBUG Delete Account - Email di conferma non corrisponde: '$confirmation_email' vs '{$current_user->user_email}'");
                $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                    <h4 style="margin-top: 0;">❌ Errore di verifica:</h4>
                    <p>L\'email di conferma non corrisponde alla tua email registrata.</p>
                </div>';
            } else {
                // Procedi con l'eliminazione
                write_log_cm("DEBUG Delete Account - Inizio eliminazione account per user $user_id ({$current_user->user_email})");
                
                // 1. Elimina dati personalizzati dalle tabelle del plugin
                global $wpdb;
                
                // Elimina generazioni musicali
                $wpdb->delete(
                    $wpdb->prefix . 'ps_generations',
                    ['user_id' => $user_id],
                    ['%d']
                );
                write_log_cm("DEBUG Delete Account - Eliminate generazioni musicali per user $user_id");
                
                // Elimina transazioni
                $wpdb->delete(
                    $wpdb->prefix . 'pictosound_transactions',
                    ['user_id' => $user_id],
                    ['%d']
                );
                write_log_cm("DEBUG Delete Account - Eliminate transazioni per user $user_id");
                
                // 2. Salva dati per email di conferma prima dell'eliminazione
                $user_email = $current_user->user_email;
                $user_display_name = $current_user->display_name ?: $current_user->user_login;
                $deletion_date = current_time('mysql');
                
                // 3. Elimina l'utente WordPress (questo elimina automaticamente tutti i user_meta)
                if (!function_exists('wp_delete_user')) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                }
                
                $deletion_result = wp_delete_user($user_id);
                
                if ($deletion_result) {
                    write_log_cm("DEBUG Delete Account - Account eliminato con successo per user $user_id");
                    
                    // 4. Invia email di conferma eliminazione
                    $email_subject = 'Pictosound.com - Conferma Eliminazione Account';
                    $email_body = "Ciao " . $user_display_name . ",\n\n";
                    $email_body .= "Confermiamo che il tuo account Pictosound è stato eliminato con successo.\n\n";
                    $email_body .= "Dettagli eliminazione:\n";
                    $email_body .= "• Data: " . $deletion_date . "\n";
                    $email_body .= "• Email account: " . $user_email . "\n";
                    $email_body .= "• ID utente: " . $user_id . "\n\n";
                    $email_body .= "Tutti i tuoi dati sono stati rimossi dai nostri server, inclusi:\n";
                    $email_body .= "- Informazioni personali e di fatturazione\n";
                    $email_body .= "- Cronologia generazioni musicali\n";
                    $email_body .= "- Storico transazioni e crediti\n";
                    $email_body .= "- Preferenze e impostazioni account\n\n";
                    $email_body .= "Se hai eliminato l'account per errore, potrai sempre registrarti nuovamente visitando:\n";
                    $email_body .= "https://pictosound.com/registrazione/\n\n";
                    $email_body .= "Grazie per aver utilizzato Pictosound!\n\n";
                    $email_body .= "Il Team Pictosound\n";
                    $email_body .= "https://pictosound.com";
                    
                    $headers = array(
                        'Content-Type: text/plain; charset=UTF-8',
                        'From: Pictosound <noreply@pictosound.com>'
                    );
                    
                    wp_mail($user_email, $email_subject, $email_body, $headers);
                    write_log_cm("DEBUG Delete Account - Email di conferma inviata a: $user_email");
                    
                    // 5. Messaggio di conferma con reindirizzamento JavaScript (senza wp_logout per evitare errori header)
                    $message = '<div style="background: #d4edda; padding: 25px; border: 1px solid #28a745; color: #155724; margin: 20px 0; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #155724;">✅ ACCOUNT ELIMINATO</h3>
                        <p style="font-size: 18px; margin: 15px 0;"><strong>Il tuo account è stato eliminato con successo.</strong></p>
                        <p style="margin: 15px 0;">Tutti i tuoi dati sono stati rimossi dai nostri server.</p>
                        <p style="margin: 15px 0;">📧 Ti abbiamo inviato una email di conferma a <strong>' . esc_html($user_email) . '</strong></p>
                        <div style="margin-top: 25px;">
                            <a href="/wp-login.php?action=logout&redirect_to=" style="background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 8px rgba(40,167,69,0.3);">🚪 LOGOUT E TORNA ALLA HOME</a>
                        </div>
                        <p style="margin-top: 20px; font-size: 14px; color: #666;">
                            Clicca il pulsante sopra per disconnetterti e tornare alla homepage.<br>
                            Potrai sempre registrarti nuovamente se cambi idea.
                        </p>
                    </div>';
                    
                    // Logout e reindirizzamento automatico dopo 3 secondi
                    $message .= '<script>
                        // Reindirizza automaticamente al logout di WordPress dopo 3 secondi
                        setTimeout(function() {
                            window.location.href = "/wp-login.php?action=logout&redirect_to=' . urlencode(home_url()) . '";
                        }, 3000);
                        
                        // Mostra countdown
                        let countdown = 3;
                        const countdownElement = document.createElement("div");
                        countdownElement.style.cssText = "margin-top: 15px; font-size: 14px; color: #666; font-style: italic;";
                        countdownElement.innerHTML = "Reindirizzamento automatico in <span id=\'countdown\'>" + countdown + "</span> secondi...";
                        document.querySelector("div[style*=\'background: #d4edda\']").appendChild(countdownElement);
                        
                        const countdownTimer = setInterval(function() {
                            countdown--;
                            document.getElementById("countdown").textContent = countdown;
                            if (countdown <= 0) {
                                clearInterval(countdownTimer);
                            }
                        }, 1000);
                    </script>';
                    
                    return $message;
                    
                } else {
                    write_log_cm("DEBUG Delete Account - ERRORE: Impossibile eliminare user $user_id");
                    $message = '<div style="background: #f8d7da; padding: 15px; border: 1px solid #dc3545; color: #721c24; margin: 20px 0; border-radius: 8px;">
                        <h4 style="margin-top: 0;">❌ Errore nell\'eliminazione:</h4>
                        <p>Si è verificato un errore tecnico. Riprova più tardi o contatta l\'assistenza.</p>
                    </div>';
                }
            }
        }
    }
    
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
    // ELABORAZIONE FORM QUANDO VIENE INVIATO (MODIFICA PROFILO)
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
    // GENERA IL FORM HTML CON SEZIONE ELIMINAZIONE
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
            
            <!-- ZONA PERICOLOSA - ELIMINAZIONE ACCOUNT -->
            <fieldset style="border: 3px solid #e74c3c; padding: 25px; margin: 25px 0; border-radius: 10px; background: linear-gradient(135deg, #fff5f5, #fee); position: relative;">
                <legend style="background: linear-gradient(45deg, #e74c3c, #c0392b); color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 16px; box-shadow: 0 2px 8px rgba(231,76,60,0.3);">⚠️ ZONA PERICOLOSA</legend>
                
                <!-- Warning icon/pattern background -->
                <div style="position: absolute; top: 10px; right: 10px; opacity: 0.1; font-size: 100px; color: #e74c3c;">⚠️</div>
                
                <h4 style="color: #e74c3c; margin: 0 0 15px 0; font-size: 18px;">🗑️ Eliminazione Account Definitiva</h4>
                
                <div style="background: #fff; padding: 20px; border-radius: 8px; border: 2px solid #ffeaa7; margin-bottom: 20px;">
                    <h5 style="color: #d63031; margin: 0 0 10px 0;">⚠️ ATTENZIONE: Questa azione è IRREVERSIBILE</h5>
                    <p style="margin: 0 0 10px 0; color: #2d3436; line-height: 1.6;">
                        L'eliminazione del tuo account comporterà la <strong>cancellazione permanente</strong> di:
                    </p>
                    <ul style="color: #636e72; margin: 10px 0; padding-left: 20px;">
                        <li><strong>Tutti i tuoi dati personali</strong> (nome, email, indirizzo, etc.)</li>
                        <li><strong>Cronologia generazioni musicali</strong> e file audio</li>
                        <li><strong>Saldo crediti residuo</strong> (non rimborsabile)</li>
                        <li><strong>Storico transazioni e acquisti</strong></li>
                        <li><strong>Preferenze e impostazioni</strong> account</li>
                    </ul>
                    <p style="margin: 10px 0 0 0; color: #e17055; font-weight: bold; font-size: 14px;">
                        📧 Riceverai una email di conferma all'eliminazione completata.
                    </p>
                </div>
                
                <div style="border: 2px dashed #e74c3c; padding: 20px; border-radius: 8px; background: rgba(231,76,60,0.05);">
                    <p style="margin: 0 0 15px 0; color: #2d3436; font-weight: bold;">
                        Per procedere con l'eliminazione, <span style="color: #e74c3c;">digita la tua email</span> nel campo sottostante:
                    </p>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #2d3436;">
                            Conferma Email: <code style="background: #ddd; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($current_user->user_email); ?></code>
                        </label>
                        <input type="email" name="delete_confirmation_email" id="deleteConfirmationEmail" 
                               style="width: 100%; padding: 14px; border: 2px solid #e74c3c; border-radius: 8px; font-size: 16px; background: #fff;"
                               placeholder="Digita <?php echo esc_attr($current_user->user_email); ?> per confermare" 
                               autocomplete="off" />
                    </div>
                    
                    <?php wp_nonce_field('pictosound_delete_account_action', 'pictosound_delete_nonce_field'); ?>
                    
                    <button type="button" id="deleteAccountButton" 
                            style="background: linear-gradient(45deg, #e74c3c, #c0392b); color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; opacity: 0.5; box-shadow: 0 4px 15px rgba(231,76,60,0.3);" 
                            disabled>
                        🗑️ ELIMINA DEFINITIVAMENTE IL MIO ACCOUNT
                    </button>
                    
                    <p style="margin: 15px 0 0 0; font-size: 12px; color: #636e72; text-align: center;">
                        Il pulsante si attiverà solo quando avrai inserito correttamente la tua email
                    </p>
                </div>
            </fieldset>
            
            <!-- BOTTONE SUBMIT PER MODIFICA -->
            <div style="text-align: center; margin-top: 35px;">
                <?php wp_nonce_field('pictosound_user_edit_profile_action', 'pictosound_edit_nonce_field'); ?>
                <input type="submit" name="pictosound_edit_submit" value="<?php _e('💾 SALVA MODIFICHE', 'pictosound-credits-manager'); ?>" 
                       style="background: linear-gradient(45deg, #007cba, #00a0d2); color: white; padding: 20px 45px; border: none; border-radius: 12px; font-size: 20px; font-weight: bold; cursor: pointer; box-shadow: 0 8px 20px rgba(0,124,186,0.3); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px;"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 25px rgba(0,124,186,0.4)';" 
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(0,124,186,0.3)';" />
            </div>
        </form>
        
        <!-- FORM SEPARATO PER ELIMINAZIONE ACCOUNT -->
        <form method="post" action="<?php echo esc_url(get_permalink()); ?>" id="deleteAccountForm" style="display: none;">
            <?php wp_nonce_field('pictosound_delete_account_action', 'pictosound_delete_nonce_field'); ?>
            <input type="hidden" name="delete_confirmation_email" id="hiddenDeleteEmail" value="">
            <input type="hidden" name="pictosound_delete_account" value="1">
        </form>
    </div>
    
    <!-- CSS RESPONSIVE E JAVASCRIPT -->
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
        
        #deleteAccountButton {
            padding: 12px 20px !important;
            font-size: 14px !important;
        }
    }
    
    @media (max-width: 480px) {
        #pictosoundEditProfileForm {
            padding: 25px 20px !important;
        }
        
        #pictosoundEditProfileForm h2 {
            font-size: 24px !important;
        }
        
        #deleteAccountButton {
            width: 100% !important;
        }
    }
    
    /* Animazioni per il pulsante di eliminazione */
    #deleteAccountButton:enabled {
        opacity: 1 !important;
        cursor: pointer !important;
    }
    
    #deleteAccountButton:enabled:hover {
        background: linear-gradient(45deg, #c0392b, #a93226) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(231,76,60,0.4) !important;
    }
    
    #deleteAccountButton:disabled {
        cursor: not-allowed !important;
    }
    
    /* Effetto di avviso per il campo email */
    #deleteConfirmationEmail.warning {
        border-color: #f39c12 !important;
        background: #fef5e7 !important;
    }
    
    #deleteConfirmationEmail.correct {
        border-color: #27ae60 !important;
        background: #eafaf1 !important;
    }
    
    #deleteConfirmationEmail.incorrect {
        border-color: #e74c3c !important;
        background: #fdf2f2 !important;
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteEmailInput = document.getElementById('deleteConfirmationEmail');
        const deleteButton = document.getElementById('deleteAccountButton');
        const correctEmail = '<?php echo esc_js($current_user->user_email); ?>';
        const deleteForm = document.getElementById('deleteAccountForm');
        const hiddenEmailInput = document.getElementById('hiddenDeleteEmail');
        
        // Verifica email in tempo reale
        deleteEmailInput.addEventListener('input', function() {
            const currentValue = this.value.trim();
            
            // Reset classi
            this.classList.remove('warning', 'correct', 'incorrect');
            
            if (currentValue === '') {
                deleteButton.disabled = true;
                deleteButton.style.opacity = '0.5';
            } else if (currentValue === correctEmail) {
                this.classList.add('correct');
                deleteButton.disabled = false;
                deleteButton.style.opacity = '1';
            } else {
                this.classList.add('incorrect');
                deleteButton.disabled = true;
                deleteButton.style.opacity = '0.5';
            }
        });
        
        // Gestione click pulsante eliminazione
        deleteButton.addEventListener('click', function() {
            if (this.disabled) return;
            
            const emailValue = deleteEmailInput.value.trim();
            
            if (emailValue !== correctEmail) {
                alert('❌ Email di conferma non corretta!\n\nDevi digitare esattamente: ' + correctEmail);
                deleteEmailInput.focus();
                return;
            }
            
            // Doppia conferma
            const firstConfirm = confirm(
                '⚠️ ULTIMA POSSIBILITÀ DI ANNULLARE!\n\n' +
                'Stai per ELIMINARE DEFINITIVAMENTE il tuo account Pictosound.\n\n' +
                '🗑️ Tutti i tuoi dati verranno cancellati per sempre:\n' +
                '• Informazioni personali e di fatturazione\n' +
                '• Cronologia generazioni musicali\n' +
                '• Saldo crediti residuo (non rimborsabile)\n' +
                '• Storico transazioni e acquisti\n\n' +
                '❌ QUESTA AZIONE È IRREVERSIBILE!\n\n' +
                'Sei ASSOLUTAMENTE SICURO di voler procedere?'
            );
            
            if (!firstConfirm) {
                return;
            }
            
            // Conferma finale con digitazione
            const finalConfirm = prompt(
                '🚨 CONFERMA FINALE 🚨\n\n' +
                'Per completare l\'eliminazione, digita esattamente:\n\n' +
                'ELIMINA IL MIO ACCOUNT\n\n' +
                '(rispetta maiuscole e spazi)'
            );
            
            if (finalConfirm !== 'ELIMINA IL MIO ACCOUNT') {
                alert('❌ Conferma non corretta. Eliminazione annullata per sicurezza.');
                return;
            }
            
            // Procedi con l'eliminazione
            hiddenEmailInput.value = emailValue;
            deleteForm.submit();
        });
        
        // Previeni invio accidentale del form principale se si preme Invio nel campo email
        deleteEmailInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!deleteButton.disabled) {
                    deleteButton.click();
                }
            }
        });
        
        console.log('🔧 Script eliminazione account caricato correttamente');
    });
    </script>
    
    <?php
    return ob_get_clean();
}
add_shortcode('pictosound_edit_profile', 'pictosound_cm_edit_profile_shortcode');