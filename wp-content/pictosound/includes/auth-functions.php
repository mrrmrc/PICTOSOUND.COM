<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PictosoundAuth {
    private static $wpdb;
    private static $session_name = 'pictosound_session';
    
    public static function init() {
        global $wpdb;
        self::$wpdb = $wpdb;
        
        // Avvia sessione se non già avviata
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Registrazione utente
    public static function register_user($email, $password, $name = '') {
        // Validazione email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email non valida'];
        }
        
        // Validazione password (minimo 6 caratteri)
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password deve essere almeno 6 caratteri'];
        }
        
        // Controlla se email già esiste
        $existing_user = self::$wpdb->get_var(self::$wpdb->prepare(
            "SELECT id FROM {self::$wpdb->prefix}ps_users WHERE email = %s",
            $email
        ));
        
        if ($existing_user) {
            return ['success' => false, 'error' => 'Email già registrata'];
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Inserisci nuovo utente
        $result = self::$wpdb->insert(
            self::$wpdb->prefix . 'ps_users',
            [
                'email' => $email,
                'password_hash' => $password_hash,
                'name' => sanitize_text_field($name),
                'credits_balance' => 0, // Nessun credito bonus
                'registration_ip' => self::get_client_ip(),
                'status' => 'active'
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            return ['success' => false, 'error' => 'Errore durante la registrazione'];
        }
        
        $user_id = self::$wpdb->insert_id;
        
        // Login automatico dopo registrazione
        self::login_user($user_id);
        
        return [
            'success' => true, 
            'message' => 'Registrazione completata con successo!',
            'user_id' => $user_id
        ];
    }
    
    // Login utente
    public static function authenticate_user($email, $password) {
        // Ottieni utente dal database
        $user = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT * FROM {self::$wpdb->prefix}ps_users WHERE email = %s AND status = 'active'",
            $email
        ));
        
        if (!$user) {
            return ['success' => false, 'error' => 'Credenziali non valide'];
        }
        
        // Verifica password
        if (!password_verify($password, $user->password_hash)) {
            return ['success' => false, 'error' => 'Credenziali non valide'];
        }
        
        // Aggiorna ultimo login
        self::$wpdb->update(
            self::$wpdb->prefix . 'ps_users',
            ['last_login' => current_time('mysql')],
            ['id' => $user->id],
            ['%s'],
            ['%d']
        );
        
        // Login utente
        self::login_user($user->id);
        
        return [
            'success' => true, 
            'message' => 'Login effettuato con successo!',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'credits' => $user->credits_balance
            ]
        ];
    }
    
    // Effettua login (imposta sessione)
    private static function login_user($user_id) {
        $_SESSION[self::$session_name] = [
            'user_id' => $user_id,
            'login_time' => time(),
            'ip_address' => self::get_client_ip()
        ];
        
        // Rigenera session ID per sicurezza
        session_regenerate_id(true);
    }
    
    // Logout utente
    public static function logout_user() {
        unset($_SESSION[self::$session_name]);
        session_destroy();
        
        return ['success' => true, 'message' => 'Logout effettuato'];
    }
    
    // Controlla se utente è loggato
    public static function is_user_logged_in() {
        if (!isset($_SESSION[self::$session_name])) {
            return false;
        }
        
        $session_data = $_SESSION[self::$session_name];
        
        // Controlla timeout sessione (24 ore)
        if (time() - $session_data['login_time'] > 86400) {
            self::logout_user();
            return false;
        }
        
        // Controllo IP per sicurezza (opzionale, può dare problemi con proxy)
        // if ($session_data['ip_address'] !== self::get_client_ip()) {
        //     self::logout_user();
        //     return false;
        // }
        
        return true;
    }
    
    // Ottieni utente corrente
    public static function get_current_user() {
        if (!self::is_user_logged_in()) {
            return null;
        }
        
        $user_id = $_SESSION[self::$session_name]['user_id'];
        
        $user = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT id, email, name, credits_balance, total_credits_purchased, created_at, last_login 
             FROM {self::$wpdb->prefix}ps_users 
             WHERE id = %d AND status = 'active'",
            $user_id
        ));
        
        return $user;
    }
    
    // Ottieni ID utente corrente
    public static function get_current_user_id() {
        if (!self::is_user_logged_in()) {
            return null;
        }
        
        return $_SESSION[self::$session_name]['user_id'];
    }
    
    // Reset password (da implementare con email)
    public static function request_password_reset($email) {
        $user = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT id, name FROM {self::$wpdb->prefix}ps_users WHERE email = %s AND status = 'active'",
            $email
        ));
        
        if (!$user) {
            // Per sicurezza, non dire se l'email esiste o no
            return ['success' => true, 'message' => 'Se l\'email esiste, riceverai le istruzioni per il reset'];
        }
        
        // Genera token reset (da implementare)
        $reset_token = bin2hex(random_bytes(32));
        
        // TODO: Salvare token nel database e inviare email
        
        return ['success' => true, 'message' => 'Istruzioni per il reset inviate via email'];
    }
    
    // Utility: ottieni IP client
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// Initialize auth system
add_action('init', ['PictosoundAuth', 'init']);
?>