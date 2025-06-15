<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PictosoundCredits {
    private static $wpdb;
    
    public static function init() {
        global $wpdb;
        self::$wpdb = $wpdb;
    }
    
    // Ottieni crediti utente
    public static function get_user_credits($user_id) {
        if (!$user_id) return 0;
        
        $credits = self::$wpdb->get_var(self::$wpdb->prepare(
            "SELECT credits_balance FROM {self::$wpdb->prefix}ps_users WHERE id = %d AND status = 'active'",
            $user_id
        ));
        
        return $credits ? intval($credits) : 0;
    }
    
    // Scala crediti
    public static function deduct_credits($user_id, $credits_to_deduct, $description = '') {
        if (!$user_id || $credits_to_deduct <= 0) {
            return false;
        }
        
        $current_credits = self::get_user_credits($user_id);
        
        if ($current_credits < $credits_to_deduct) {
            return false; // Crediti insufficienti
        }
        
        // Inizia transazione
        self::$wpdb->query('START TRANSACTION');
        
        try {
            // Aggiorna crediti utente
            $update_result = self::$wpdb->update(
                self::$wpdb->prefix . 'ps_users',
                ['credits_balance' => $current_credits - $credits_to_deduct],
                ['id' => $user_id],
                ['%d'],
                ['%d']
            );
            
            if ($update_result === false) {
                throw new Exception('Failed to update user credits');
            }
            
            // Registra transazione
            $transaction_result = self::$wpdb->insert(
                self::$wpdb->prefix . 'ps_transactions',
                [
                    'user_id' => $user_id,
                    'type' => 'usage',
                    'credits_amount' => -$credits_to_deduct,
                    'description' => $description ?: "Used {$credits_to_deduct} credits",
                    'ip_address' => self::get_client_ip()
                ],
                ['%d', '%s', '%d', '%s', '%s']
            );
            
            if ($transaction_result === false) {
                throw new Exception('Failed to record transaction');
            }
            
            self::$wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            self::$wpdb->query('ROLLBACK');
            error_log('PictoSound Credits Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Aggiungi crediti
    public static function add_credits($user_id, $credits_to_add, $description = '', $payment_id = '') {
        if (!$user_id || $credits_to_add <= 0) {
            return false;
        }
        
        $current_credits = self::get_user_credits($user_id);
        
        // Inizia transazione
        self::$wpdb->query('START TRANSACTION');
        
        try {
            // Aggiorna crediti utente
            $update_result = self::$wpdb->update(
                self::$wpdb->prefix . 'ps_users',
                [
                    'credits_balance' => $current_credits + $credits_to_add,
                    'total_credits_purchased' => self::$wpdb->get_var(self::$wpdb->prepare(
                        "SELECT total_credits_purchased FROM {self::$wpdb->prefix}ps_users WHERE id = %d",
                        $user_id
                    )) + $credits_to_add
                ],
                ['id' => $user_id],
                ['%d', '%d'],
                ['%d']
            );
            
            if ($update_result === false) {
                throw new Exception('Failed to update user credits');
            }
            
            // Registra transazione
            $transaction_result = self::$wpdb->insert(
                self::$wpdb->prefix . 'ps_transactions',
                [
                    'user_id' => $user_id,
                    'type' => 'purchase',
                    'credits_amount' => $credits_to_add,
                    'description' => $description ?: "Added {$credits_to_add} credits",
                    'payment_id' => $payment_id,
                    'ip_address' => self::get_client_ip()
                ],
                ['%d', '%s', '%d', '%s', '%s', '%s']
            );
            
            if ($transaction_result === false) {
                throw new Exception('Failed to record transaction');
            }
            
            self::$wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            self::$wpdb->query('ROLLBACK');
            error_log('PictoSound Credits Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Calcola crediti necessari in base alla durata
    public static function calculate_credits_needed($duration) {
        if ($duration <= 40) {
            return 0; // 40s gratuiti
        }
        
        // Scala crediti: 60s=1cr, 120s=2cr, 180s=3cr, 240s=4cr, 360s=5cr
        if ($duration <= 60) return 1;
        if ($duration <= 120) return 2;
        if ($duration <= 180) return 3;
        if ($duration <= 240) return 4;
        if ($duration <= 360) return 5;
        
        // Per durate superiori, calcola proporzionalmente
        return ceil($duration / 60);
    }
    
    // Ottieni IP del client
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

// Initialize credits system
add_action('init', ['PictosoundCredits', 'init']);
?>