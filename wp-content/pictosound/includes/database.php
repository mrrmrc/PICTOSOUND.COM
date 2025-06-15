<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PictosoundDB {
    private static $instance = null;
    private $wpdb;
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->create_tables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Tabella utenti
        $sql_users = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}ps_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(100),
            credits_balance INT DEFAULT 0,
            total_credits_purchased INT DEFAULT 0,
            registration_ip VARCHAR(45),
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status ENUM('active', 'suspended', 'pending') DEFAULT 'active',
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        // Tabella transazioni
        $sql_transactions = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}ps_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            type ENUM('purchase', 'usage', 'bonus', 'refund') NOT NULL,
            credits_amount INT NOT NULL,
            money_amount DECIMAL(10,2) DEFAULT 0.00,
            description TEXT,
            payment_id VARCHAR(100),
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}ps_users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        // Tabella generazioni
        $sql_generations = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}ps_generations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            session_id VARCHAR(64),
            duration INT NOT NULL,
            credits_used INT DEFAULT 0,
            prompt TEXT,
            audio_filename VARCHAR(255),
            audio_url VARCHAR(500),
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}ps_users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_users);
        dbDelta($sql_transactions);
        dbDelta($sql_generations);
    }
    
    public function getConnection() {
        return $this->wpdb;
    }
}

// Initialize database
PictosoundDB::getInstance();
?>