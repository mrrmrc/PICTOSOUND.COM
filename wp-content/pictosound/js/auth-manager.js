// Verifica se la classe esiste già per evitare ridichiarazioni
if (typeof window.PictosoundAuthManager === 'undefined') {

/**
 * PictoSound Authentication & Credits Manager
 * Gestisce login, registrazione, crediti e integrazione UI
 */
class PictosoundAuthManager {
    constructor() {
        this.currentUser = null;
        this.isInitialized = false;
        this.modalsContainer = null;
        
        // URLs API
        this.apiBaseUrl = '/wp-content/pictosound/api/auth-handler.php';
        
        // Elementi DOM che verranno inizializzati
        this.elements = {};
        
        // Bind methods
        this.init = this.init.bind(this);
        this.handleLogin = this.handleLogin.bind(this);
        this.handleRegister = this.handleRegister.bind(this);
        this.handleLogout = this.handleLogout.bind(this);
    }
    
    // Inizializza il sistema
    async init() {
        console.log('PictoSound Auth Manager: Initializing...');
        
        try {
            await this.loadModalsHTML();
            this.initializeElements();
            this.attachEventListeners();
            await this.checkAuthStatus();
            this.isInitialized = true;
            
            console.log('PictoSound Auth Manager: Initialized successfully');
        } catch (error) {
            console.error('PictoSound Auth Manager: Initialization failed', error);
        }
    }
    
    // Carica HTML dei modals - VERSIONE CORRETTA
    async loadModalsHTML() {
        console.log('Auth Manager: Using inline modals from HTML');
        
        // Non caricare da file esterno, usa i modals già presenti nell'HTML
        this.modalsContainer = document.body;
        
        // Carica CSS se non già presente
        if (!document.querySelector('link[href*="auth-styles.css"]')) {
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = '/wp-content/pictosound/css/auth-styles.css';
            document.head.appendChild(cssLink);
        }
    }
    
    // Inizializza riferimenti agli elementi DOM
    initializeElements() {
        this.elements = {
            // Modals
            authModal: document.getElementById('authModal'),
            creditsModal: document.getElementById('creditsModal'),
            
            // Auth Modal Elements
            authModalTitle: document.getElementById('authModalTitle'),
            loginTab: document.querySelector('[data-tab="login"]'),
            registerTab: document.querySelector('[data-tab="register"]'),
            loginForm: document.getElementById('loginForm'),
            registerForm: document.getElementById('registerForm'),
            
            // Forms
            loginFormElement: document.getElementById('loginFormElement'),
            registerFormElement: document.getElementById('registerFormElement'),
            
            // Login fields
            loginEmail: document.getElementById('loginEmail'),
            loginPassword: document.getElementById('loginPassword'),
            loginBtn: document.getElementById('loginBtn'),
            loginError: document.getElementById('loginError'),
            
            // Register fields
            registerName: document.getElementById('registerName'),
            registerEmail: document.getElementById('registerEmail'),
            registerPassword: document.getElementById('registerPassword'),
            registerPasswordConfirm: document.getElementById('registerPasswordConfirm'),
            registerBtn: document.getElementById('registerBtn'),
            registerError: document.getElementById('registerError'),
            acceptTerms: document.getElementById('acceptTerms'),
            passwordStrength: document.getElementById('passwordStrength'),
            
            // Credits Modal
            creditsNeeded: document.getElementById('creditsNeeded'),
            creditsAvailable: document.getElementById('creditsAvailable'),
            packageBtns: document.querySelectorAll('.ps-package-btn'),
            
            // Credits Widget
            creditsWidget: document.getElementById('creditsWidget'),
            creditsCount: document.getElementById('creditsCount'),
            buyCreditsBtn: document.getElementById('buyCreditsBtn'),
            userMenuBtn: document.getElementById('userMenuBtn'),
            userDropdown: document.getElementById('userDropdown'),
            userNameDisplay: document.getElementById('userNameDisplay'),
            logoutBtn: document.getElementById('logoutBtn'),
            
            // Modal closes
            modalCloses: document.querySelectorAll('.ps-modal-close')
        };
        
        console.log('Auth elements initialized:', Object.keys(this.elements).length, 'elements found');
    }
    
    // Attacca event listeners
    attachEventListeners() {
        // Tab switching
        this.elements.loginTab?.addEventListener('click', () => this.switchTab('login'));
        this.elements.registerTab?.addEventListener('click', () => this.switchTab('register'));
        
        // Form submissions
        this.elements.loginFormElement?.addEventListener('submit', this.handleLogin);
        this.elements.registerFormElement?.addEventListener('submit', this.handleRegister);
        
        // Password strength
        this.elements.registerPassword?.addEventListener('input', (e) => {
            this.updatePasswordStrength(e.target.value);
        });
        
        // Password confirmation
        this.elements.registerPasswordConfirm?.addEventListener('input', (e) => {
            this.validatePasswordConfirmation();
        });
        
        // Modal closes
        this.elements.modalCloses?.forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => {
                const modal = e.target.closest('.ps-modal');
                if (modal) this.closeModal(modal);
            });
        });
        
        // Click outside modal
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('ps-modal')) {
                this.closeModal(e.target);
            }
        });
        
        // Credits widget interactions
        this.elements.buyCreditsBtn?.addEventListener('click', () => this.showCreditsModal());
        this.elements.userMenuBtn?.addEventListener('click', () => this.toggleUserMenu());
        this.elements.logoutBtn?.addEventListener('click', this.handleLogout);
        
        // Package purchase buttons
        this.elements.packageBtns?.forEach(btn => {
            btn.addEventListener('click', () => this.handlePackagePurchase(btn));
        });
        
        // ESC key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
        
        console.log('Event listeners attached');
    }
    
    // Controlla stato autenticazione corrente
    async checkAuthStatus() {
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=check_status`);
            const data = await response.json();
            
            if (data.logged_in && data.user) {
                this.currentUser = data.user;
                this.updateUIForLoggedInUser();
            } else {
                this.currentUser = null;
                this.updateUIForLoggedOutUser();
            }
            
            return data;
        } catch (error) {
            console.error('Failed to check auth status:', error);
            return { logged_in: false };
        }
    }
    
    // Switch tra tabs login/register
    switchTab(tabName) {
        // Update tab buttons
        this.elements.loginTab?.classList.toggle('active', tabName === 'login');
        this.elements.registerTab?.classList.toggle('active', tabName === 'register');
        
        // Update forms
        this.elements.loginForm?.classList.toggle('active', tabName === 'login');
        this.elements.registerForm?.classList.toggle('active', tabName === 'register');
        
        // Update modal title
        const title = tabName === 'login' ? 'Accedi a PictoSound' : 'Registrati su PictoSound';
        if (this.elements.authModalTitle) {
            this.elements.authModalTitle.textContent = title;
        }
        
        // Clear errors
        this.clearErrors();
    }
    
    // Gestisce login
    async handleLogin(e) {
        e.preventDefault();
        
        const email = this.elements.loginEmail?.value.trim();
        const password = this.elements.loginPassword?.value;
        
        if (!email || !password) {
            this.showError('loginError', 'Email e password sono richiesti');
            return;
        }
        
        this.setButtonLoading(this.elements.loginBtn, true);
        this.clearErrors();
        
        try {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);
            
            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentUser = data.user;
                this.updateUIForLoggedInUser();
                this.closeModal(this.elements.authModal);
                this.showSuccessMessage('Login effettuato con successo!');
                
                // Trigger evento personalizzato
                this.dispatchAuthEvent('login', data.user);
            } else {
                this.showError('loginError', data.error || 'Errore durante il login');
            }
            
        } catch (error) {
            console.error('Login error:', error);
            this.showError('loginError', 'Errore di connessione. Riprova.');
        } finally {
            this.setButtonLoading(this.elements.loginBtn, false);
        }
    }
    
    // Gestisce registrazione
    async handleRegister(e) {
        e.preventDefault();
        
        const name = this.elements.registerName?.value.trim();
        const email = this.elements.registerEmail?.value.trim();
        const password = this.elements.registerPassword?.value;
        const passwordConfirm = this.elements.registerPasswordConfirm?.value;
        const acceptTerms = this.elements.acceptTerms?.checked;
        
        // Validazioni
        if (!email || !password) {
            this.showError('registerError', 'Email e password sono richiesti');
            return;
        }
        
        if (password !== passwordConfirm) {
            this.showError('registerError', 'Le password non coincidono');
            return;
        }
        
        if (password.length < 6) {
            this.showError('registerError', 'La password deve essere almeno 6 caratteri');
            return;
        }
        
        if (!acceptTerms) {
            this.showError('registerError', 'Devi accettare i termini di servizio');
            return;
        }
        
        this.setButtonLoading(this.elements.registerBtn, true);
        this.clearErrors();
        
        try {
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('name', name);
            formData.append('email', email);
            formData.append('password', password);
            
            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentUser = data.user || { email: email, name: name, credits: 0 };
                this.updateUIForLoggedInUser();
                this.closeModal(this.elements.authModal);
                this.showSuccessMessage('Registrazione completata! Benvenuto su PictoSound!');
                
                // Trigger evento personalizzato
                this.dispatchAuthEvent('register', this.currentUser);
            } else {
                this.showError('registerError', data.error || 'Errore durante la registrazione');
            }
            
        } catch (error) {
            console.error('Register error:', error);
            this.showError('registerError', 'Errore di connessione. Riprova.');
        } finally {
            this.setButtonLoading(this.elements.registerBtn, false);
        }
    }
    
    // Gestisce logout
    async handleLogout(e) {
        e.preventDefault();
        
        try {
            await fetch(`${this.apiBaseUrl}?action=logout`, { method: 'POST' });
            
            this.currentUser = null;
            this.updateUIForLoggedOutUser();
            this.closeAllModals();
            this.showSuccessMessage('Logout effettuato');
            
            // Trigger evento personalizzato
            this.dispatchAuthEvent('logout', null);
            
        } catch (error) {
            console.error('Logout error:', error);
        }
    }
    
    // Mostra modal login/register
    showAuthModal(defaultTab = 'login') {
        this.switchTab(defaultTab);
        this.showModal(this.elements.authModal);
    }
    
    // Mostra modal crediti
    showCreditsModal(creditsNeeded = 0, creditsAvailable = 0) {
        if (this.elements.creditsNeeded) {
            this.elements.creditsNeeded.textContent = creditsNeeded;
        }
        if (this.elements.creditsAvailable) {
            this.elements.creditsAvailable.textContent = creditsAvailable;
        }
        
        this.showModal(this.elements.creditsModal);
    }
    
    // Utility: mostra modal
    showModal(modal) {
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Utility: chiudi modal
    closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }
    }
    
    // Chiudi tutti i modals
    closeAllModals() {
        const modals = document.querySelectorAll('.ps-modal');
        modals.forEach(modal => this.closeModal(modal));
    }
    
    // Aggiorna UI per utente loggato
    updateUIForLoggedInUser() {
        if (this.elements.creditsWidget) {
            this.elements.creditsWidget.style.display = 'flex';
        }
        
        if (this.elements.creditsCount && this.currentUser) {
            this.elements.creditsCount.textContent = this.currentUser.credits || 0;
        }
        
        if (this.elements.userNameDisplay && this.currentUser) {
            const displayName = this.currentUser.name || this.currentUser.email?.split('@')[0] || 'Utente';
            this.elements.userNameDisplay.textContent = displayName;
        }
    }
    
    // Aggiorna UI per utente non loggato
    updateUIForLoggedOutUser() {
        if (this.elements.creditsWidget) {
            this.elements.creditsWidget.style.display = 'none';
        }
        
        this.closeUserMenu();
    }
    
    // Toggle menu utente
    toggleUserMenu() {
        if (this.elements.userDropdown) {
            this.elements.userDropdown.classList.toggle('show');
        }
    }
    
    // Chiudi menu utente
    closeUserMenu() {
        if (this.elements.userDropdown) {
            this.elements.userDropdown.classList.remove('show');
        }
    }
    
    // Gestisce acquisto pacchetto crediti
    async handlePackagePurchase(btn) {
        const packageElement = btn.closest('.ps-package');
        const credits = parseInt(packageElement.dataset.credits);
        const price = parseFloat(packageElement.dataset.price);
        
        console.log(`Purchase requested: ${credits} credits for €${price}`);
        alert(`Acquisto di ${credits} crediti per €${price} - Implementazione pagamenti in arrivo!`);
    }
    
    // Validazione forza password
    updatePasswordStrength(password) {
        const strength = this.calculatePasswordStrength(password);
        const strengthElement = this.elements.passwordStrength;
        
        if (strengthElement) {
            strengthElement.className = 'ps-password-strength';
            
            if (password.length > 0) {
                if (strength < 3) {
                    strengthElement.classList.add('weak');
                } else if (strength < 5) {
                    strengthElement.classList.add('medium');
                } else {
                    strengthElement.classList.add('strong');
                }
            }
        }
    }
    
    // Calcola forza password
    calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return strength;
    }
    
    // Valida conferma password
    validatePasswordConfirmation() {
        const password = this.elements.registerPassword?.value;
        const confirm = this.elements.registerPasswordConfirm?.value;
        
        if (confirm && password !== confirm) {
            this.elements.registerPasswordConfirm.style.borderColor = '#ef4444';
        } else {
            this.elements.registerPasswordConfirm.style.borderColor = '#e5e7eb';
        }
    }
    
    // Mostra errore
    showError(elementId, message) {
        const errorElement = this.elements[elementId];
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }
    
    // Pulisci errori
    clearErrors() {
        const errorElements = ['loginError', 'registerError'];
        errorElements.forEach(id => {
            const element = this.elements[id];
            if (element) {
                element.style.display = 'none';
                element.textContent = '';
            }
        });
    }
    
    // Mostra messaggio successo
    showSuccessMessage(message) {
        console.log('Success:', message);
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 10001;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    // Set button loading state
    setButtonLoading(button, loading) {
        if (!button) return;
        
        const textSpan = button.querySelector('.ps-btn-text');
        const spinner = button.querySelector('.ps-spinner');
        
        if (textSpan && spinner) {
            if (loading) {
                textSpan.style.display = 'none';
                spinner.style.display = 'block';
                button.disabled = true;
            } else {
                textSpan.style.display = 'block';
                spinner.style.display = 'none';
                button.disabled = false;
            }
        }
    }
    
    // Dispatch custom events
    dispatchAuthEvent(type, userData) {
        const event = new CustomEvent(`pictosound:${type}`, {
            detail: { user: userData }
        });
        document.dispatchEvent(event);
    }
    
    // API Pubbliche per integrazione con script principale
    
    isLoggedIn() {
        return this.currentUser !== null;
    }
    
    getCurrentUser() {
        return this.currentUser;
    }
    
    async getCurrentCredits() {
        if (!this.isLoggedIn()) return 0;
        
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=check_credits`);
            const data = await response.json();
            return data.available || 0;
        } catch (error) {
            console.error('Failed to get current credits:', error);
            return 0;
        }
    }
    
    async checkCreditsForDuration(duration) {
        const creditsNeeded = this.calculateCreditsNeeded(duration);
        
        if (creditsNeeded === 0) {
            return { hasEnough: true, needed: 0, available: 0 };
        }
        
        if (!this.isLoggedIn()) {
            return { hasEnough: false, needed: creditsNeeded, available: 0, loginRequired: true };
        }
        
        const available = await this.getCurrentCredits();
        
        return {
            hasEnough: available >= creditsNeeded,
            needed: creditsNeeded,
            available: available,
            loginRequired: false
        };
    }
    
    calculateCreditsNeeded(duration) {
        if (duration <= 40) return 0;
        if (duration <= 60) return 1;
        if (duration <= 120) return 2;
        if (duration <= 180) return 3;
        if (duration <= 240) return 4;
        if (duration <= 360) return 5;
        return Math.ceil(duration / 60);
    }
    
    async handleInsufficientCredits(duration) {
        const creditsCheck = await this.checkCreditsForDuration(duration);
        
        if (creditsCheck.loginRequired) {
            this.showAuthModal('login');
        } else if (!creditsCheck.hasEnough) {
            this.showCreditsModal(creditsCheck.needed, creditsCheck.available);
        }
        
        return creditsCheck;
    }
    
    async updateCreditsDisplay() {
        if (this.isLoggedIn()) {
            const credits = await this.getCurrentCredits();
            if (this.elements.creditsCount) {
                this.elements.creditsCount.textContent = credits;
            }
            if (this.currentUser) {
                this.currentUser.credits = credits;
            }
        }
    }
}

// Esporta la classe
window.PictosoundAuthManager = PictosoundAuthManager;

} // Fine controllo esistenza classe

// Inizializza SOLO se non esiste già
if (typeof window.pictosoundAuth === 'undefined') {
    window.pictosoundAuth = new window.PictosoundAuthManager();
    
    // Auto-inizializza quando il DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.pictosoundAuth.init();
        });
    } else {
        window.pictosoundAuth.init();
    }
}