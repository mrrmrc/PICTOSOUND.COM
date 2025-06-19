<?php
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
