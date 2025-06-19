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
