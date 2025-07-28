/**
 * Camera Handler Module
 * Gestisce tutte le funzionalità della camera per PictoSound
 * 
 * Dipendenze: Richiede che domElements sia disponibile nel scope globale
 * ✨ VERSIONE CORRETTA - Nessuno specchio per fotocamera frontale
 */

const CameraHandler = {
  // Variabili private del modulo
  currentStream: null,
  currentFacingMode: "environment",

  /**
   * Inizializza gli event listeners della camera
   * Deve essere chiamato dopo che domElements è disponibile
   */
  init(domElements, processImageCallback, setStatusMessageCallback) {
    console.log("LOG: Inizializzazione CameraHandler");

    // Salva i riferimenti necessari
    this.domElements = domElements;
    this.processImage = processImageCallback;
    this.setStatusMessage = setStatusMessageCallback;

    // Verifica che tutti gli elementi necessari esistano
    if (!this._validateDOMElements()) {
      console.error("ERRORE: Elementi DOM camera mancanti");
      return false;
    }

    // Aggiungi event listeners
    this._attachEventListeners();

    console.log("LOG: CameraHandler inizializzato con successo");
    return true;
  },

  /**
   * Verifica che tutti gli elementi DOM necessari esistano
   */
  _validateDOMElements() {
    const requiredElements = [
      'takePictureButton',
      'captureImageButton',
      'switchCameraButton',
      'closeCameraButton',
      'cameraViewContainer',
      'cameraFeed'
    ];

    return requiredElements.every(elementKey => {
      const exists = this.domElements[elementKey] !== null;
      if (!exists) {
        console.error(`ERRORE: Elemento ${elementKey} non trovato`);
      }
      return exists;
    });
  },

  /**
   * Aggiungi tutti gli event listeners
   */
  _attachEventListeners() {
    // Bottone "Scatta Foto" - Apri camera
    this.domElements.takePictureButton.addEventListener('click', () => {
      this.openCamera();
    });

    // Bottone "Scatta!" - Cattura immagine
    this.domElements.captureImageButton.addEventListener('click', () => {
      this.captureImage();
    });

    // Bottone "Cambia" - Cambia camera
    this.domElements.switchCameraButton.addEventListener('click', () => {
      this.switchCamera();
    });

    // Bottone "Annulla" - Chiudi camera
    this.domElements.closeCameraButton.addEventListener('click', () => {
      this.closeCamera();
    });

    console.log("LOG: Event listeners camera collegati");
  },

  /**
   * 🪞 Rileva se è fotocamera frontale
   */
  _isFrontCamera() {
    try {
      // Metodo 1: Controllo currentFacingMode
      if (this.currentFacingMode === 'user') {
        return true;
      }

      // Metodo 2: Controllo stream settings se disponibile
      if (this.currentStream) {
        const videoTrack = this.currentStream.getVideoTracks()[0];
        if (videoTrack) {
          const settings = videoTrack.getSettings();
          if (settings.facingMode === 'user') {
            return true;
          }
        }
      }

      // Default: fotocamera posteriore
      return false;

    } catch (error) {
      console.warn("⚠️ Errore rilevamento tipo fotocamera:", error);
      return false; // Default: posteriore (no flip)
    }
  },

  /**
   * 📷 MODIFICATA - Nessuno specchio applicato all'anteprima
   */
  _updateVideoDisplay() {
    if (!this.domElements.cameraFeed) return;

    const isFront = this._isFrontCamera();

    this.domElements.cameraFeed.style.transform = isFront ? 'scaleX(-1)' : 'scaleX(1)';
    this.domElements.cameraFeed.style.webkitTransform = isFront ? 'scaleX(-1)' : 'scaleX(1)';
    this.domElements.cameraFeed.style.transition = 'transform 0.3s ease';

    console.log(`📷 Anteprima aggiornata. Frontale: ${isFront}`);
  }


    /**
     * Apre la camera
     */
    async openCamera() {
    console.log("LOG: Apertura camera richiesta");

    try {
      // Verifica supporto getUserMedia
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error("getUserMedia non supportato dal browser");
      }

      // Configurazione camera
      const constraints = {
        video: {
          facingMode: this.currentFacingMode,
          width: { ideal: 1280 },
          height: { ideal: 720 }
        },
        audio: false
      };

      // Richiedi accesso
      this.currentStream = await navigator.mediaDevices.getUserMedia(constraints);
      this.domElements.cameraFeed.srcObject = this.currentStream;

      // 🪞 Aggiorna display quando il video è pronto
      this.domElements.cameraFeed.onloadedmetadata = () => {
        this._updateVideoDisplay();
      };

      // Mostra interfaccia camera
      this.domElements.cameraViewContainer.style.display = 'block';

      console.log("LOG: Camera attivata con successo");

    } catch (error) {
      console.error("ERRORE accesso camera:", error);
      this._handleCameraError(error);
    }
  },

  /**
   * 📸 MODIFICATA - Cattura semplice senza correzioni specchio
   */
  captureImage() {
    console.log("LOG: Cattura immagine richiesta");

    if (!this.currentStream) {
      console.error("ERRORE: Stream camera non disponibile");
      this.setStatusMessage("Errore: Camera non attiva", "error");
      return;
    }

    try {
      // Crea canvas per cattura
      const canvas = document.createElement('canvas');
      const video = this.domElements.cameraFeed;

      // Assicurati che il video sia pronto
      if (video.videoWidth === 0 || video.videoHeight === 0) {
        throw new Error("Video non pronto per la cattura");
      }

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;

      const ctx = canvas.getContext('2d');

      console.log(`📸 Cattura: ${canvas.width}x${canvas.height}`);

      // CATTURA SEMPRE NORMALE - Nessuna distinzione tra fotocamere
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

      // Converti in base64
      const imageDataURL = canvas.toDataURL('image/jpeg', 0.8);

      // Chiudi camera
      this.closeCamera();

      // Processa l'immagine (callback al sistema principale)
      this.processImage(imageDataURL);

      console.log("LOG: Immagine catturata e processata");

    } catch (error) {
      console.error("ERRORE cattura immagine:", error);
      this.setStatusMessage(`Errore cattura: ${error.message}`, "error");
    }
  },

  /**
   * 🔄 Cambia tra camera frontale e posteriore con anti-crash
   */
  async switchCamera() {
    console.log("LOG: Cambio camera richiesto");

    try {
      // Disabilita temporaneamente il pulsante per prevenire click multipli
      if (this.domElements.switchCameraButton) {
        this.domElements.switchCameraButton.disabled = true;
      }

      // Ferma lo stream corrente in modo sicuro
      if (this.currentStream) {
        console.log("🛑 Fermando stream corrente...");
        this.currentStream.getTracks().forEach(track => {
          console.log(`🛑 Fermando track: ${track.label}`);
          track.stop();
        });
        this.currentStream = null;
      }

      // Reset video element
      this.domElements.cameraFeed.srcObject = null;
      this.domElements.cameraFeed.style.transform = 'scaleX(1)';
      this.domElements.cameraFeed.style.webkitTransform = 'scaleX(1)';

      // Cambia facing mode
      this.currentFacingMode = this.currentFacingMode === "environment" ? "user" : "environment";

      console.log(`🔄 Passaggio a modalità: ${this.currentFacingMode}`);

      // Attendi che la camera sia completamente liberata
      await new Promise(resolve => setTimeout(resolve, 800));

      // Riapri con la nuova modalità
      await this.openCamera();

      console.log("✅ Cambio camera completato con successo");

    } catch (error) {
      console.error("❌ Errore cambio camera:", error);
      this.setStatusMessage(`Errore cambio camera: ${error.message}`, "error");

      // Tentativo di ripristino automatico
      try {
        console.log("🔧 Tentativo di ripristino automatico...");
        await new Promise(resolve => setTimeout(resolve, 1000));
        await this.openCamera();
      } catch (recoveryError) {
        console.error("❌ Ripristino fallito:", recoveryError);
        this.closeCamera();
        this.setStatusMessage("Ripristino fallito. Riprova ad aprire la camera.", "error");
      }
    } finally {
      // Riabilita il pulsante
      if (this.domElements.switchCameraButton) {
        this.domElements.switchCameraButton.disabled = false;
      }
    }
  },

  /**
   * Chiude la camera
   */
  closeCamera() {
    console.log("LOG: Chiusura camera richiesta");

    // Ferma tutti i track del stream
    if (this.currentStream) {
      this.currentStream.getTracks().forEach(track => {
        track.stop();
      });
      this.currentStream = null;
    }

    // Reset transform CSS
    if (this.domElements.cameraFeed) {
      this.domElements.cameraFeed.style.transform = 'scaleX(1)';
      this.domElements.cameraFeed.style.webkitTransform = 'scaleX(1)';
    }

    // Nascondi interfaccia camera
    this.domElements.cameraViewContainer.style.display = 'none';

    // Pulisci video element
    this.domElements.cameraFeed.srcObject = null;

    console.log("LOG: Camera chiusa");
  },

  /**
   * Gestisce gli errori della camera
   */
  _handleCameraError(error) {
    let errorMessage = "Impossibile accedere alla camera. ";

    switch (error.name) {
      case 'NotAllowedError':
        errorMessage += "Permesso negato dall'utente.";
        break;
      case 'NotFoundError':
        errorMessage += "Nessuna camera trovata.";
        break;
      case 'NotSupportedError':
        errorMessage += "Browser non supportato.";
        break;
      case 'NotReadableError':
        errorMessage += "Camera già in uso da un'altra applicazione.";
        break;
      case 'OverconstrainedError':
        errorMessage += "Configurazione camera non supportata.";
        break;
      default:
        errorMessage += error.message;
    }

    this.setStatusMessage(errorMessage, "error");
  },

  /**
   * Verifica se la camera è supportata
   */
  isSupported() {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  },

  /**
   * Verifica se la camera è attualmente attiva
   */
  isActive() {
    return this.currentStream !== null;
  },

  /**
   * 🔍 Ottieni informazioni sulla camera corrente
   */
  getCurrentCameraInfo() {
    return {
      isActive: this.isActive(),
      facingMode: this.currentFacingMode,
      isFrontCamera: this._isFrontCamera(),
      streamActive: this.currentStream !== null
    };
  },

  /**
   * Cleanup per quando si cambia pagina
   */
  cleanup() {
    console.log("LOG: Cleanup CameraHandler");
    this.closeCamera();
  }
};

// Esponi il modulo globalmente
window.CameraHandler = CameraHandler;