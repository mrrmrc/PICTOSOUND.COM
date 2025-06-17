document.addEventListener('DOMContentLoaded', async () => {
    console.log("LOG: DOMContentLoaded - Pagina pronta e script principale in esecuzione.");

    const CREATIVITY_LEVEL = 50;

    // Cache DOM elements for performance and convenience
    const domElements = {
        statusDiv: document.getElementById('status'),
        dynamicFeedbackArea: document.getElementById('dynamicFeedbackArea'),
        progressAndPlayerContainer: document.getElementById('progressAndPlayerContainer'),
        progressMessage: document.getElementById('progressMessage'),
        progressBarContainer: document.getElementById('progressBarContainer'),
        progressBarAnimated: document.getElementById('progressBarAnimated'),
        imageUpload: document.getElementById('imageUpload'),
        takePictureButton: document.getElementById('takePictureButton'),
        cameraViewContainer: document.getElementById('cameraViewContainer'),
        cameraFeed: document.getElementById('cameraFeed'),
        captureImageButton: document.getElementById('captureImageButton'),
        switchCameraButton: document.getElementById('switchCameraButton'),
        closeCameraButton: document.getElementById('closeCameraButton'),
        imageCanvas: document.getElementById('imageCanvas'),
        compositeImageCanvas: document.getElementById('compositeImageCanvas'),
        imagePreview: document.getElementById('imagePreview'),
        detectionCanvas: document.getElementById('detectionCanvas'),
        generateMusicButton: document.getElementById('generateMusicButton'),
        musicSpinner: document.getElementById('musicSpinner'),
        moodPillsContainer: document.getElementById('moodPills'),
        genrePillsContainer: document.getElementById('genrePills'),
        instrumentPillsContainer: document.getElementById('instrumentPills'),
        rhythmPillsContainer: document.getElementById('rhythmPills'),
        bpmSlider: document.getElementById('bpmSlider'),
        bpmValueDisplay: document.getElementById('bpmValue'),
        aiInsightsSection: document.getElementById('aiInsightsSection'),
        aiInterpretationText: document.getElementById('aiInterpretationText'),
        detailsAccordionHeader: document.querySelector('.details-accordion-header'),
        aiInsightsContent: document.getElementById('aiInsightsContent'),
        aiProcessingSimulationDiv: document.getElementById('aiProcessingSimulation'),
        audioPlayerContainer: document.getElementById('audioPlayerContainer'),
        audioPlayer: document.getElementById('audioPlayer'),
        audioInfo: document.getElementById('audioInfo'),
        downloadAudioLink: document.getElementById('downloadAudioLink'),
        downloadCompositeImageLink: document.getElementById('downloadCompositeImageLink'),
        downloadQrOnlyLink: document.getElementById('downloadQrOnlyLink'),
        fullscreenImageModal: document.getElementById('fullscreenImageModal'),
        fullscreenImage: document.getElementById('fullscreenImage'),
        closeFullscreenButton: document.getElementById('closeFullscreenButton')
    };

    /**
     * ⚡ FUNZIONE DI DEBUG VISIVO ⚡
     * Aggiunge messaggi di log direttamente nell'area di stato per un facile debug.
     */
    function logStep(message) {
        console.log(`[UI_LOG] ${message}`);
        if (domElements.progressMessage) {
            domElements.dynamicFeedbackArea.style.display = 'block';
            domElements.progressAndPlayerContainer.style.display = 'block';
            domElements.progressBarContainer.style.display = 'none'; // Nasconde la barra di progresso per mostrare solo i log

            const logEntry = document.createElement('div');
            const timestamp = new Date().toLocaleTimeString('it-IT');
            logEntry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
            logEntry.style.fontSize = '12px';
            logEntry.style.fontFamily = 'monospace';
            logEntry.style.whiteSpace = 'pre-wrap';
            logEntry.style.borderBottom = '1px solid #eee';
            logEntry.style.padding = '3px 0';
            logEntry.style.textAlign = 'left';

            // Inserisce il nuovo log in cima
            domElements.progressMessage.prepend(logEntry);
        }
    }


    // --- SISTEMA AUTO-RECOVERY PER NONCE SCADUTI ---
    function handleNonceExpiredError(originalAjaxCall, originalData) {
        console.log('Pictosound: Nonce scaduto rilevato, aggiornamento automatico...');

        // Richiedi nuovi nonce
        jQuery.ajax({
            url: pictosound_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'pictosound_regenerate_nonce'
            },
            success: function (response) {
                if (response.success) {
                    console.log('Pictosound: Nonce aggiornati, ripetizione richiesta...');

                    // Aggiorna i nonce globali
                    pictosound_vars.nonce_recharge = response.data.nonce_recharge;
                    pictosound_vars.nonce_check_credits = response.data.nonce_check_credits;

                    // Aggiorna i dati della richiesta originale
                    if (originalData.recharge_nonce) {
                        originalData.recharge_nonce = response.data.nonce_recharge;
                    }
                    if (originalData.nonce) {
                        originalData.nonce = response.data.nonce_check_credits;
                    }

                    // Ripeti la richiesta originale
                    originalAjaxCall(originalData);
                } else {
                    console.error('Pictosound: Impossibile aggiornare i nonce');
                    alert('Sessione scaduta. Ricarica la pagina e riprova.');
                    location.reload();
                }
            },
            error: function () {
                console.error('Pictosound: Errore nel refresh dei nonce');
                alert('Sessione scaduta. Ricarica la pagina e riprova.');
                location.reload();
            }
        });
    }

    // Funzione per ricarica crediti con auto-recovery
    function rechargeCredits(packageKey) {
        const requestData = {
            action: 'pictosound_recharge_credits',
            recharge_nonce: pictosound_vars.nonce_recharge,
            credits_package_key: packageKey
        };

        function makeRechargeRequest(data) {
            jQuery.ajax({
                url: pictosound_vars.ajax_url,
                type: 'POST',
                data: data,
                success: function (response) {
                    if (response.success) {
                        if (response.data.is_redirect && response.data.redirect_url) {
                            const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');
                            if (rechargeStatusDiv) {
                                setStatusMessage(rechargeStatusDiv, 'Reindirizzamento per il pagamento...', "info");
                            }
                            setTimeout(() => {
                                window.location.href = response.data.redirect_url;
                            }, 1000);
                        } else {
                            const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');
                            if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, response.data.message, "success");
                            if (response.data.new_balance) {
                                pictosound_vars.user_credits = response.data.new_balance;
                                updateCreditsDisplay(response.data.new_balance);
                                if (typeof updateDurationOptionsUI === 'function') {
                                    updateDurationOptionsUI();
                                }
                            }
                        }
                    } else {
                        const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');
                        if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, response.data.message || 'Errore durante la ricarica', "error");
                    }
                },
                error: function (xhr) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.code === 'nonce_expired') {
                            handleNonceExpiredError(makeRechargeRequest, data);
                            return;
                        }
                    } catch (e) { }
                    const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');
                    if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, 'Errore di connessione. Riprova.', "error");
                }
            });
        }
        makeRechargeRequest(requestData);
    }

    // Funzione per check crediti con auto-recovery
    function checkCredits(duration) {
        const requestData = {
            action: 'pictosound_check_credits',
            nonce: pictosound_vars.nonce_check_credits,
            duration: duration
        };

        function makeCheckRequest(data) {
            jQuery.ajax({
                url: pictosound_vars.ajax_url,
                type: 'POST',
                data: data,
                success: function (response) {
                    if (response.success) {
                        if (response.data.can_proceed) {
                            if (typeof response.data.remaining_credits !== 'undefined') {
                                pictosound_vars.user_credits = response.data.remaining_credits;
                                if (typeof updateDurationOptionsUI === 'function') updateDurationOptionsUI();
                            }
                            if (typeof window.startMusicGeneration === 'function') {
                                window.startMusicGeneration();
                            }
                        } else {
                            if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, response.data.message || 'Impossibile procedere', "error");
                            updateProgressMessage("", false);
                            if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = false;
                            if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
                        }
                    } else {
                        if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, response.data.message || 'Errore nella verifica crediti', "error");
                        updateProgressMessage("", false);
                        if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = false;
                        if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
                    }
                },
                error: function (xhr) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.code === 'nonce_expired') {
                            handleNonceExpiredError(makeCheckRequest, data);
                            return;
                        }
                    } catch (e) { }
                    if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, 'Errore durante la verifica crediti. Riprova.', "error");
                    updateProgressMessage("", false);
                    if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = false;
                    if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
                }
            });
        }
        makeCheckRequest(requestData);
    }

    // Funzione di utilità per aggiornare il display dei crediti
    function updateCreditsDisplay(newBalance) {
        jQuery('.pictosound-saldo-display-widget').each(function () {
            const $element = jQuery(this);
            const text = $element.text();
            const newText = text.replace(/\d+/, newBalance);
            $element.text(newText);
        });
        pictosound_vars.user_credits = newBalance;
    }

    // Funzione per popolare le opzioni dei pacchetti di ricarica
    function populateCreditPackages() {
        if (typeof pictosound_vars === 'undefined' || !pictosound_vars.credit_packages) {
            console.warn("WARN: pictosound_vars.credit_packages non definito. Impossibile popolare i pacchetti di ricarica.");
            if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, "Errore: Opzioni di ricarica non disponibili.", "error");
            return;
        }

        const packagesContainer = document.getElementById('creditPackagesContainer');
        if (!packagesContainer) {
            console.warn("WARN: Elemento 'creditPackagesContainer' non trovato nel DOM per i pacchetti di ricarica.");
            return;
        }
        packagesContainer.innerHTML = ''; // Pulisci opzioni esistenti

        let firstPackage = true;
        for (const key in pictosound_vars.credit_packages) {
            if (pictosound_vars.credit_packages.hasOwnProperty(key)) {
                const packageInfo = pictosound_vars.credit_packages[key];
                const label = document.createElement('label');
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'creditPackageOption'; // Nome gruppo radio
                radio.value = key; // Il valore sarà la chiave del pacchetto (es. '20', '40')

                if (firstPackage) {
                    radio.checked = true;
                    firstPackage = false;
                }

                label.appendChild(radio);
                label.appendChild(document.createTextNode(` ${packageInfo.credits} Crediti (${packageInfo.price_simulated})`));
                packagesContainer.appendChild(label);
                packagesContainer.appendChild(document.createElement('br'));
            }
        }
        console.log("LOG: Pacchetti di ricarica crediti popolati.");
    }

    // Chiama la funzione per popolare i pacchetti se pictosound_vars è disponibile
    if (typeof pictosound_vars !== 'undefined' && pictosound_vars !== null) {
        populateCreditPackages();
    } else {
        // Se pictosound_vars non è pronto subito, attendi e riprova.
        setTimeout(() => {
            if (typeof pictosound_vars !== 'undefined' && pictosound_vars !== null) {
                populateCreditPackages();
            } else {
                console.error("ERRORE CRITICO: pictosound_vars non definito per popolare pacchetti.");
            }
        }, 600);
    }

    // Event Listener per il pulsante di ricarica con auto-recovery
    const rechargeButton = document.getElementById('rechargeCreditsButton');
    const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');

    if (rechargeButton) {
        rechargeButton.addEventListener('click', async () => {
            console.log("LOG: Pulsante 'Ricarica Crediti' cliccato (con auto-recovery).");

            if (typeof pictosound_vars === 'undefined' || pictosound_vars === null) {
                console.error("ERRORE CRITICO: pictosound_vars non definito!");
                if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, "Errore di configurazione.", "error");
                return;
            }
            if (!pictosound_vars.is_user_logged_in) {
                if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, pictosound_vars.text_login_required || "Devi effettuare il login.", "error");
                return;
            }

            const selectedPackageRadio = document.querySelector('input[name="creditPackageOption"]:checked');
            if (!selectedPackageRadio) {
                if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, "Seleziona un pacchetto di crediti.", "warn");
                return;
            }
            const packageKey = selectedPackageRadio.value;

            if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, "Processo di ricarica in corso...", "info");
            rechargeButton.disabled = true;

            // Usa la funzione rechargeCredits con auto-recovery
            rechargeCredits(packageKey);

            // Re-abilita il pulsante dopo un breve delay
            setTimeout(() => {
                rechargeButton.disabled = false;
            }, 2000);
        });
    } else {
        console.warn("WARN: Pulsante 'rechargeCreditsButton' non trovato nel DOM.");
    }

    function updateDurationOptionsUI() {
        if (typeof pictosound_vars === 'undefined' || pictosound_vars === null) {
            console.warn("WARN: pictosound_vars non è definito o è null. Impossibile aggiornare le opzioni di durata.");
            if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, "Errore: Dati di configurazione utente non caricati.", "error");

            const durationRadiosFallback = document.querySelectorAll('input[name="musicDuration"]');
            durationRadiosFallback.forEach(radio => {
                const label = document.querySelector(`label[for="${radio.id}"]`);
                if (radio.value !== "40") { // Assumendo 40s sia sempre FREE
                    radio.disabled = true;
                    if (label) {
                        label.style.opacity = '0.5';
                        label.title = "Informazioni sui costi non disponibili.";
                    }
                } else {
                    radio.disabled = false;
                    if (label) {
                        label.style.opacity = '1';
                        label.title = "Gratuito";
                    }
                }
            });
            return;
        }

        const durationRadios = document.querySelectorAll('input[name="musicDuration"]');
        durationRadios.forEach(radio => {
            const durationValue = radio.value;
            const cost = pictosound_vars.duration_costs && pictosound_vars.duration_costs[durationValue] !== undefined
                ? pictosound_vars.duration_costs[durationValue]
                : (durationValue === "40" ? 0 : 999);

            const label = document.querySelector(`label[for="${radio.id}"]`);
            let reason = '';
            let enableOption = true;

            if (cost > 0) {
                if (!pictosound_vars.is_user_logged_in) {
                    enableOption = false;
                    reason = pictosound_vars.text_login_required || "Login richiesto";
                } else if (pictosound_vars.user_credits < cost) {
                    enableOption = false;
                    reason = (pictosound_vars.text_insufficient_credits || "Crediti insufficienti") + ` (Servono ${cost}, hai ${pictosound_vars.user_credits})`;
                } else {
                    reason = `Costo: ${cost} cr.`;
                }
            } else if (cost === 0) {
                reason = 'FREE';
            } else {
                enableOption = false;
                reason = 'Opzione non disponibile';
            }

            radio.disabled = !enableOption;
            if (label) {
                const creditSpan = label.querySelector('.duration-credit');
                if (creditSpan) {
                    if (!label.dataset.originalCreditText && creditSpan.textContent) {
                        label.dataset.originalCreditText = creditSpan.textContent;
                    }

                    if (!enableOption && cost > 0) {
                        creditSpan.textContent = reason.split('(')[0].trim();
                    } else {
                        if (label.dataset.originalCreditText) creditSpan.textContent = label.dataset.originalCreditText;
                    }
                }
                label.title = reason;
                label.style.opacity = enableOption ? '1' : '0.5';
                if (!enableOption) {
                    label.classList.add('disabled-option-custom');
                } else {
                    label.classList.remove('disabled-option-custom');
                }
            }
        });

        const currentlySelectedRadio = document.querySelector('input[name="musicDuration"]:checked');
        if (currentlySelectedRadio && currentlySelectedRadio.disabled) {
            const freeOption = document.getElementById('duration40');
            if (freeOption && !freeOption.disabled) {
                freeOption.checked = true;
            }
        }
        console.log("LOG: UI opzioni durata aggiornata. Crediti utente:", pictosound_vars.user_credits, "Login:", pictosound_vars.is_user_logged_in);
    }

    // Contexts for canvases (con controllo di esistenza)
    let detectionCtx = null;
    if (domElements.detectionCanvas) {
        detectionCtx = domElements.detectionCanvas.getContext('2d');
    }

    let imageAnalysisCtx = null;
    if (domElements.imageCanvas) {
        imageAnalysisCtx = domElements.imageCanvas.getContext('2d');
    }

    // State variables
    let currentImage = null;
    let currentImageSrc = null;
    let currentStream = null;
    let currentFacingMode = "environment";
    let imageAnalysisResults = null;
    let stableAudioPromptForMusic = "";
    let cocoSsdModel = null;
    let faceApiModelLoaded = false;
    const showDetections = false;
    let initialPreselectionDoneForCurrentImage = false;

    // Italian to English translations for objects and emotions
    const objectTranslations = {
        "person": "persona", "cat": "gatto", "dog": "cane", "car": "auto", "tree": "albero",
        "book": "libro", "toothbrush": "spazzolino", "laptop": "laptop", "cell phone": "cellulare",
        "keyboard": "tastiera", "mouse": "mouse", "remote": "telecomando", "tv": "televisione",
        "bicycle": "bicicletta", "motorcycle": "motocicletta", "airplane": "aeroplano", "bus": "autobus",
        "train": "treno", "truck": "camion", "boat": "barca", "traffic light": "semaforo",
        "fire hydrant": "idrante", "stop sign": "segnale di stop", "parking meter": "parchimetro",
        "bench": "panchina", "bird": "uccello", "horse": "cavallo", "sheep": "pecora",
        "cow": "mucca", "elephant": "elefante", "bear": "orso", "zebra": "zebra", "giraffe": "giraffa",
        "backpack": "zaino", "umbrella": "ombrello", "handbag": "borsetta", "tie": "cravatta",
        "suitcase": "valigia", "frisbee": "frisbee", "skis": "sci", "snowboard": "snowboard",
        "sports ball": "palla sportiva", "kite": "aquilone", "baseball bat": "mazza da baseball",
        "baseball glove": "guanto da baseball", "skateboard": "skateboard", "surfboard": "tavola da surf",
        "tennis racket": "racchetta da tennis", "bottle": "bottiglia", "wine glass": "bicchiere da vino",
        "cup": "tazza", "fork": "forchetta", "knife": "coltello", "spoon": "cucchiaio", "bowl": "ciotola",
        "banana": "banana", "apple": "mela", "sandwich": "panino", "orange": "arancia",
        "broccoli": "broccoli", "carrot": "carota", "hot dog": "hot dog", "pizza": "pizza",
        "donut": "ciambella", "cake": "torta", "chair": "sedia", "couch": "divano",
        "potted plant": "pianta in vaso", "bed": "letto", "dining table": "tavolo da pranzo", "toilet": "toilette"
    };

    const emotionTranslations = {
        "neutral": "neutrale", "happy": "felice", "sad": "triste", "angry": "arrabbiato/a",
        "fearful": "impaurito/a", "disgusted": "disgustato/a", "surprised": "sorpreso/a"
    };

    // Cue translations for building the English prompt for the music API
    const cueTranslationsITtoEN = {
        mood: {
            "felice": "happy", "gioioso": "joyful", "triste": "sad", "malinconico": "melancholic", "riflessivo": "reflective",
            "epico": "epic", "grandioso": "grandiose", "rilassante": "relaxing", "calmo": "calm",
            "energico": "energetic", "vivace": "lively", "misterioso": "mysterious", "inquietante": "eerie",
            "sognante": "dreamy", "etereo": "ethereal", "romantico": "romantic", "drammatico": "dramatic",
            "futuristico": "futuristic", "sci-fi": "sci-fi", "nostalgico": "nostalgic", "potente": "powerful", "intenso": "intense",
            "descrittivo": "descriptive", "oscuro": "dark", "profondo": "deep", "brillante": "bright", "luminoso": "luminous",
            "tenue": "muted", "desaturato": "desaturated", "vibrante": "vibrant", "saturo": "saturated",
            "morbido": "soft", "sfumato": "blurred", "netto": "sharp", "dinamico": "dynamic",
            "atmosferico": "atmospheric", "contemplativo": "contemplative", "meravigliato": "wondrous", "anticipatorio": "anticipatory",
            "sospeso": "suspenseful"
        },
        genre: {
            "elettronica": "electronic", "dance": "dance", "edm": "EDM", "rock": "rock", "pop": "pop", "jazz": "jazz",
            "classica": "classical", "ambient": "ambient", "soundtrack": "soundtrack", "cinematografica": "cinematic",
            "folk": "folk", "acustica": "acoustic", "lo-fi": "lo-fi", "chillhop": "chillhop", "hip-hop": "hip hop",
            "funk": "funk", "soul": "soul", "metal": "metal", "reggae": "reggae", "blues": "blues",
            "world": "world music", "etnica": "ethnic music", "folk acustico": "acoustic folk", "ambient naturale": "natural ambient",
            "urban jazz": "urban jazz", "lo-fi hip hop": "lo-fi hip hop"
        },
        instrument: {
            "pianoforte": "piano", "chitarra acustica": "acoustic guitar", "chitarra elettrica": "electric guitar",
            "basso": "bass", "batteria": "drums", "percussioni": "percussion", "violino": "violin", "archi": "strings",
            "violoncello": "cello", "sassofono": "saxophone", "tromba": "trumpet", "ottoni": "brass instruments", "flauto": "flute",
            "sintetizzatore": "synthesizer", "tastiere": "keyboards", "organo": "organ", "arpa": "harp", "ukulele": "ukulele",
            "voce umana (cori o effetti)": "human voice (choir or effects)", "voce solista (da definire)": "solo voice", "voce solista": "solo voice",
            "nessuno strumento specifico": "",
            "basso profondo": "deep bass", "flauto brillante": "bright flute", "glockenspiel": "glockenspiel",
            "pad eterei": "ethereal pads", "chitarra con riverbero": "guitar with reverb",
            "chitarra elettrica con overdrive leggero": "electric guitar with light overdrive", "sassofono contralto": "alto saxophone",
            "sintetizzatore lead malinconico": "melancholic lead synthesizer", "archi lenti": "slow strings", "clarinetto": "clarinet"
        },
        rhythm: {
            "no_rhythm": "no distinct rhythm", "ambientale": "ambient rhythm",
            "slow_rhythm": "slow rhythm", "rilassato": "relaxed rhythm",
            "moderate_groove": "moderate groove", "orecchiabile": "catchy rhythm",
            "upbeat_energetic": "upbeat energetic rhythm",
            "complex_experimental_rhythm": "complex experimental rhythm"
        },
        object: {},
        tonality: {
            "maggiore": "major", "minore": "minor", "misto": ""
        },
        general: {
            "descrittivo": "descriptive",
            "lento": "slow",
            "moderato": "moderate",
            "veloce": "fast",
            "strumentale": "instrumental",
            "medium": "medium",
            "low": "low",
            "high": "high"
        }
    };

    // Populate object translations for cue system
    for (const key in objectTranslations) {
        cueTranslationsITtoEN.object[objectTranslations[key].toLowerCase()] = key.replace("_en", "");
    }

    // Helper function to translate cues to English for the API prompt
    function translateCueToEnglish(italianCue, type) {
        if (!italianCue) return "";
        const lowerCue = String(italianCue).toLowerCase();

        if (type === 'object' && cueTranslationsITtoEN.object[lowerCue]) {
            return cueTranslationsITtoEN.object[lowerCue];
        }
        if (cueTranslationsITtoEN[type] && cueTranslationsITtoEN[type][lowerCue]) {
            return cueTranslationsITtoEN[type][lowerCue];
        }
        if (cueTranslationsITtoEN.general[lowerCue]) {
            return cueTranslationsITtoEN.general[lowerCue];
        }
        console.warn(`No English translation for '${italianCue}' of type '${type}'. Using original.`);
        return italianCue;
    }

    // Data for checkbox pills (cues)
    const moodItems = [
        { value: "felice", label: "Felice / Gioioso" }, { value: "triste", label: "Triste / Malinconico" }, { value: "riflessivo", label: "Riflessivo" },
        { value: "epico", label: "Epico / Grandioso" }, { value: "rilassante", label: "Rilassante / Calmo" },
        { value: "energico", label: "Energico / Vivace" }, { value: "misterioso", label: "Misterioso / Inquietante" },
        { value: "sognante", label: "Sognante / Etereo" }, { value: "romantico", label: "Romantico" },
        { value: "drammatico", label: "Drammatico" }, { value: "futuristico", label: "Futuristico / Sci-Fi" },
        { value: "nostalgico", label: "Nostalgico" }, { value: "potente", label: "Potente / Intenso" },
        { value: "meravigliato", label: "Meravigliato" }, { value: "anticipatorio", label: "Anticipatorio" }, { value: "sospeso", label: "Sospeso" }
    ];

    const genreItems = [
        { value: "elettronica", label: "Elettronica" }, { value: "dance", label: "Dance / EDM" },
        { value: "rock", label: "Rock" }, { value: "pop", label: "Pop" }, { value: "jazz", label: "Jazz" },
        { value: "classica", label: "Classica" }, { value: "ambient", label: "Ambient" },
        { value: "soundtrack", label: "Soundtrack / Cinematografica" }, { value: "folk", label: "Folk / Acustica" }, { value: "folk acustico", label: "Folk Acustico" }, { value: "ambient naturale", label: "Ambient Naturale" },
        { value: "lo-fi", label: "Lo-fi / Chillhop" }, { value: "hip-hop", label: "Hip Hop" }, { value: "lo-fi hip hop", label: "Lo-fi Hip Hop" },
        { value: "funk", label: "Funk / Soul" }, { value: "metal", label: "Metal" },
        { value: "reggae", label: "Reggae" }, { value: "blues", label: "Blues" },
        { value: "world", label: "World Music / Etnica" }, { value: "urban jazz", label: "Urban Jazz" }
    ];

    const instrumentItems = [
        { value: "pianoforte", label: "Pianoforte" }, { value: "chitarra acustica", label: "Chitarra Acustica" },
        { value: "chitarra elettrica", label: "Chitarra Elettrica" }, { value: "basso", label: "Basso" }, { value: "basso profondo", label: "Basso Profondo" },
        { value: "batteria", label: "Batteria / Percussioni" }, { value: "violino", label: "Violino / Archi" }, { value: "archi lenti", label: "Archi Lenti" },
        { value: "violoncello", label: "Violoncello" }, { value: "sassofono", label: "Sassofono" }, { value: "sassofono contralto", label: "Sassofono Contralto" },
        { value: "tromba", label: "Tromba / Ottoni" }, { value: "flauto", label: "Flauto" }, { value: "flauto brillante", label: "Flauto Brillante" }, { value: "clarinetto", label: "Clarinetto" },
        { value: "sintetizzatore", label: "Sintetizzatore / Tastiere" }, { value: "sintetizzatore lead malinconico", label: "Synth Lead Malinconico" },
        { value: "organo", label: "Organo" }, { value: "arpa", label: "Arpa" }, { value: "ukulele", label: "Ukulele" },
        { value: "voce umana (cori o effetti)", label: "Voce umana (cori o effetti)" }, { value: "voce solista", label: "Voce Solista" },
        { value: "glockenspiel", label: "Glockenspiel" }, { value: "pad eterei", label: "Pad Eterei" },
        { value: "chitarra con riverbero", label: "Chitarra con Riverbero" }, { value: "chitarra elettrica con overdrive leggero", label: "Chitarra Elettrica Overdrive Leggero" },
        { value: "nessuno strumento specifico", label: "Nessuno strumento specifico" }
    ];

    const rhythmItems = [
        { value: "no_rhythm", label: "Nessun ritmo evidente (Ambientale)" },
        { value: "slow_rhythm", label: "Ritmo Lento e Rilassato" },
        { value: "moderate_groove", label: "Groove Moderato e Orecchiabile" },
        { value: "upbeat_energetic", label: "Ritmo Incalzante ed Energico" },
        { value: "complex_experimental_rhythm", label: "Ritmo Complesso / Sperimentale" }
    ];

    // AI Processing Simulation Text
    let simulatedProcessingInterval;
    const simulatedProcessingMessages = [
        "Analisi contorni e forme...", "Estrazione pattern visivi...", "Valutazione composizione cromatica...",
        "Identificazione elementi chiave...", "Interpretazione atmosfera generale...", "Ricerca corrispondenze emotive...",
        "Elaborazione palette sonora...", "Definizione struttura armonica...", "Sviluppo linea melodica...",
        "Costruzione del paesaggio sonoro..."
    ];
    let currentMessageIndex = 0;

    function startAISimulationText() {
        const simulationDiv = domElements.aiInsightsContent.querySelector('.ai-processing-simulation');
        if (!simulationDiv && domElements.aiInsightsContent) {
            const newSimDiv = document.createElement('div');
            newSimDiv.classList.add('ai-processing-simulation');
            newSimDiv.id = 'aiProcessingSimulation';
            domElements.aiInsightsContent.prepend(newSimDiv);
            domElements.aiProcessingSimulationDiv = newSimDiv;
        } else if (simulationDiv) {
            simulationDiv.innerHTML = '';
        }

        if (domElements.aiProcessingSimulationDiv) {
            domElements.aiProcessingSimulationDiv.style.display = 'block';
            let line1 = document.createElement('p');
            let line2 = document.createElement('p');
            domElements.aiProcessingSimulationDiv.appendChild(line1);
            domElements.aiProcessingSimulationDiv.appendChild(line2);

            currentMessageIndex = 0;
            line1.textContent = simulatedProcessingMessages[currentMessageIndex % simulatedProcessingMessages.length];
            line2.textContent = simulatedProcessingMessages[(currentMessageIndex + 1) % simulatedProcessingMessages.length];

            simulatedProcessingInterval = setInterval(() => {
                currentMessageIndex++;
                line1.textContent = simulatedProcessingMessages[currentMessageIndex % simulatedProcessingMessages.length];
                line2.textContent = simulatedProcessingMessages[(currentMessageIndex + 1) % simulatedProcessingMessages.length];
            }, 1500);
        }
    }

    function stopAISimulationText() {
        clearInterval(simulatedProcessingInterval);
        if (domElements.aiProcessingSimulationDiv) {
            domElements.aiProcessingSimulationDiv.style.display = 'none';
        }
    }

    // Function to populate checkbox pills
    function populateCheckboxPills(container, items, groupName) {
        if (!container) return;
        container.innerHTML = '';
        items.forEach(item => {
            const pillLabel = document.createElement('label');
            pillLabel.classList.add('checkbox-pill');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = groupName;
            checkbox.value = item.value;
            checkbox.addEventListener('change', function () {
                pillLabel.classList.toggle('selected', this.checked);
                initialPreselectionDoneForCurrentImage = true;
                if (currentImage && imageAnalysisResults) {
                    updateAIDisplayAndStablePrompt();
                }
            });
            pillLabel.appendChild(checkbox);
            pillLabel.appendChild(document.createTextNode(item.label));
            container.appendChild(pillLabel);
        });
    }

    // Populate all cue sections
    populateCheckboxPills(domElements.moodPillsContainer, moodItems, 'mood');
    populateCheckboxPills(domElements.genrePillsContainer, genreItems, 'genre');
    populateCheckboxPills(domElements.instrumentPillsContainer, instrumentItems, 'instrument');
    populateCheckboxPills(domElements.rhythmPillsContainer, rhythmItems, 'rhythm');

    // Helper to get selected checkbox values
    function getSelectedCheckboxValues(groupName) {
        return Array.from(document.querySelectorAll(`input[name="${groupName}"]:checked`)).map(cb => cb.value);
    }

    // Helper to convert RGB to HSL
    function rgbToHsl(r, g, b) {
        r /= 255; g /= 255; b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;
        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }
            h /= 6;
        }
        return { h: h * 360, s: s * 100, l: l * 100 };
    }

    // Helper to translate object class from English (COCO-SSD) to Italian
    function translateObject(objectClass) {
        return objectTranslations[objectClass.toLowerCase()] || objectClass;
    }

    // Helper to translate emotion from English (FaceAPI) to Italian
    function translateEmotion(emotion) {
        return emotionTranslations[emotion.toLowerCase()] || emotion;
    }

    // Toggle visibility of detection canvas
    function toggleDetectionCanvasVisibility() {
        domElements.detectionCanvas.style.display = showDetections ? 'block' : 'none';
    }

    // Core logic to determine musical cues based on analysis and user input
    function getMusicalCues(analysis, detectedObjects, detectedEmotions, creativityLevel, userInputs) {
        const cues = {
            moods: [...userInputs.selectedMoods],
            genres: [...userInputs.selectedGenres],
            instruments: [...userInputs.selectedInstruments],
            rhythms: [...userInputs.selectedRhythms],
            tempoBPM: userInputs.selectedBPM,
            tempoDescription: userInputs.selectedBPM <= 76 ? "lento" : userInputs.selectedBPM <= 120 ? "moderato" : "veloce",
            energy: "medium",
            tonality: "misto",
            keywords: [],
            vocalPresence: "strumentale",
            creativityLevel: creativityLevel
        };

        cues.keywords.push(...userInputs.selectedMoods, ...userInputs.selectedGenres, ...userInputs.selectedInstruments, ...userInputs.selectedRhythms);

        // ✅ SISTEMA SEMPLIFICATO - AI-driven suggestions if no user input for a category AND initial preselection hasn't happened
        if (!initialPreselectionDoneForCurrentImage) {
            console.log("🔍 Auto-suggestion attiva - Oggetti:", detectedObjects, "Emozioni:", detectedEmotions);

            // MOOD: Suggerimenti semplificati basati su emozioni
            if (cues.moods.length === 0 && detectedEmotions && detectedEmotions.length > 0) {
                const primaryEmotion = detectedEmotions.find(e => e !== "neutrale") || detectedEmotions[0];
                console.log("🎭 Emozione primaria rilevata:", primaryEmotion);

                if (primaryEmotion) {
                    switch (primaryEmotion) {
                        case "felice":
                            cues.moods.push("energico");
                            console.log("➡️ Aggiunto mood: energico");
                            break;
                        case "triste":
                            cues.moods.push("malinconico");
                            console.log("➡️ Aggiunto mood: malinconico");
                            break;
                        case "arrabbiato/a":
                            cues.moods.push("drammatico");
                            console.log("➡️ Aggiunto mood: drammatico");
                            break;
                        case "sorpreso/a":
                            cues.moods.push("energico");
                            console.log("➡️ Aggiunto mood: energico");
                            break;
                        case "impaurito/a":
                            cues.moods.push("misterioso");
                            console.log("➡️ Aggiunto mood: misterioso");
                            break;
                        default:
                            cues.moods.push("rilassante");
                            console.log("➡️ Aggiunto mood default: rilassante");
                            break;
                    }
                }
            }

            // MOOD ALTERNATIVO: Basato su luminosità se ancora vuoto
            if (cues.moods.length === 0 && analysis) {
                if (analysis.averageBrightness < 80) {
                    cues.moods.push("misterioso");
                    console.log("➡️ Aggiunto mood da luminosità bassa: misterioso");
                } else if (analysis.averageBrightness > 170) {
                    cues.moods.push("energico");
                    console.log("➡️ Aggiunto mood da luminosità alta: energico");
                } else {
                    cues.moods.push("rilassante");
                    console.log("➡️ Aggiunto mood da luminosità media: rilassante");
                }
            }

            // GENERI: Suggerimenti semplificati basati su oggetti
            if (cues.genres.length === 0 && detectedObjects && detectedObjects.length > 0) {
                console.log("🎼 Determinazione genere da oggetti...");

                if (detectedObjects.includes("persona")) {
                    cues.genres.push("folk");
                    console.log("➡️ Rilevata persona → genere: folk");
                } else if (detectedObjects.some(o => ["natura", "albero", "montagna", "fiore", "foresta", "lago", "animale", "uccello"].includes(o))) {
                    cues.genres.push("ambient");
                    console.log("➡️ Rilevata natura → genere: ambient");
                } else if (detectedObjects.some(o => ["auto", "città", "strada", "edificio", "semaforo", "autobus"].includes(o))) {
                    cues.genres.push("elettronica");
                    console.log("➡️ Rilevato urbano → genere: elettronica");
                } else if (detectedObjects.some(o => ["libro", "sedia", "tavolo", "divano", "letto"].includes(o))) {
                    cues.genres.push("classica");
                    console.log("➡️ Rilevato ambiente domestico → genere: classica");
                } else {
                    cues.genres.push("ambient");
                    console.log("➡️ Genere default: ambient");
                }
            }

            // STRUMENTI: Suggerimenti semplificati basati su genere
            if (cues.instruments.length === 0) {
                console.log("🎸 Determinazione strumenti da genere...");

                if (cues.genres.includes("folk")) {
                    cues.instruments.push("chitarra acustica");
                    console.log("➡️ Folk → strumento: chitarra acustica");
                } else if (cues.genres.includes("elettronica")) {
                    cues.instruments.push("sintetizzatore");
                    console.log("➡️ Elettronica → strumento: sintetizzatore");
                } else if (cues.genres.includes("classica")) {
                    cues.instruments.push("archi");
                    console.log("➡️ Classica → strumento: archi");
                } else {
                    cues.instruments.push("pianoforte");
                    console.log("➡️ Default → strumento: pianoforte");
                }
            }

            // RITMO: Suggerimento semplificato basato su mood
            if (cues.rhythms.length === 0) {
                if (cues.moods.includes("energico")) {
                    cues.rhythms.push("upbeat_energetic");
                    console.log("➡️ Energico → ritmo: upbeat_energetic");
                } else if (cues.moods.includes("rilassante")) {
                    cues.rhythms.push("slow_rhythm");
                    console.log("➡️ Rilassante → ritmo: slow_rhythm");
                } else {
                    cues.rhythms.push("moderate_groove");
                    console.log("➡️ Default → ritmo: moderate_groove");
                }
            }

            console.log("✅ Auto-suggestion completata:", {
                moods: cues.moods,
                genres: cues.genres,
                instruments: cues.instruments,
                rhythms: cues.rhythms
            });
        }

        // Determine overall energy and tonality based on selected/suggested moods
        const finalMoods = [...new Set(cues.moods)];
        if (finalMoods.some(m => ["felice", "gioioso", "energico", "ottimista"].includes(m))) {
            if (cues.energy === "medium") cues.energy = "high";
            if (cues.tonality === "misto") cues.tonality = "maggiore";
        } else if (finalMoods.some(m => ["triste", "malinconico", "riflessivo", "sospeso"].includes(m))) {
            if (cues.energy === "medium") cues.energy = "low";
            if (cues.tonality === "misto") cues.tonality = "minore";
        }

        // Suggest vocal presence if a person is detected
        if (detectedObjects.includes("persona") && cues.vocalPresence === "strumentale" && !userInputs.selectedInstruments.some(inst => inst.toLowerCase().includes("voce"))) {
            cues.vocalPresence = "voce solista";
        }

        // Consolidate keywords and ensure uniqueness
        cues.keywords.push(...cues.moods, ...cues.genres, ...cues.instruments, ...cues.rhythms, cues.tempoBPM + " BPM");
        cues.keywords = [...new Set(cues.keywords.filter(Boolean))];

        // Create a user-friendly text representation of selected cues
        cues.userTextCues = [...new Set(cues.moods)].join(", ") +
            ([...new Set(cues.genres)].length > 0 ? ", " + [...new Set(cues.genres)].join(", ") : "") +
            ([...new Set(cues.instruments)].length > 0 ? ", " + [...new Set(cues.instruments)].join(", ") : "") +
            ([...new Set(cues.rhythms)].length > 0 ? ", Ritmo: " + [...new Set(cues.rhythms)].join(", ") : "");

        // Final cleanup of cue arrays
        cues.moods = [...new Set(cues.moods)];
        cues.genres = [...new Set(cues.genres)];
        cues.instruments = [...new Set(cues.instruments)];
        cues.rhythms = [...new Set(cues.rhythms)];
        if (cues.moods.length === 0) cues.moods.push("descrittivo");
        console.log("🔍 getMusicalCues RISULTATO FINALE:", {
            moods: cues.moods,
            genres: cues.genres,
            instruments: cues.instruments,
            detectedObjects: detectedObjects,
            detectedEmotions: detectedEmotions,
            initialPreselectionDone: initialPreselectionDoneForCurrentImage
        });
        return cues;
    }

    // Generates the HTML content for the AI Insights section
    function generateAIDisplayContent(analysis, detectedObjectsList, detectedEmotionsList, userInputs, finalStablePrompt) {
        const simulationDiv = domElements.aiInsightsContent.querySelector('.ai-processing-simulation');
        // Clear previous analysis details, but keep the simulation div template if it exists
        const existingDetails = domElements.aiInsightsContent.querySelectorAll('h4, ul, #finalPromptForAI');
        existingDetails.forEach(el => {
            if (!el.classList.contains('ai-processing-simulation')) {
                el.remove();
            }
        });

        if (simulationDiv) simulationDiv.style.display = 'none';

        // Build HTML for image analysis details
        let analysisContentHTML = "<h4>Analisi Immagine:</h4><ul>";
        analysisContentHTML += `<li><strong>Oggetti Rilevati:</strong> ${detectedObjectsList && detectedObjectsList.length > 0 ? detectedObjectsList.join(", ") : "Nessuno o non significativi"}</li>`;
        analysisContentHTML += `<li><strong>Emozioni Percepite:</strong> ${detectedEmotionsList && detectedEmotionsList.length > 0 && detectedEmotionsList.some(e => e !== "neutrale" || detectedEmotionsList.length === 1) ? detectedEmotionsList.join(", ") : "Nessuna o non significativa"}</li>`;

        if (analysis) {
            let brightnessDesc = "Media";
            if (analysis.averageBrightness < 80) brightnessDesc = "Bassa (scena tendenzialmente scura)";
            else if (analysis.averageBrightness > 170) brightnessDesc = "Alta (scena molto luminosa)";
            analysisContentHTML += `<li><strong>Luminosità Generale:</strong> ${brightnessDesc}</li>`;

            let contrastDesc = "Medio";
            if (analysis.contrast < 30) contrastDesc = "Basso (immagine morbida, poco stacco)";
            else if (analysis.contrast > 70) contrastDesc = "Alto (forte stacco tra chiari e scuri)";
            analysisContentHTML += `<li><strong>Contrasto:</strong> ${contrastDesc}</li>`;

            let saturationDesc = "Media";
            if (analysis.averageSaturation < 30) saturationDesc = "Bassa (colori tenui, desaturati)";
            else if (analysis.averageSaturation > 70) saturationDesc = "Alta (colori vividi e saturi)";
            analysisContentHTML += `<li><strong>Saturazione Colori:</strong> ${saturationDesc}</li>`;

            if (analysis.dominantColors && analysis.dominantColors.length > 0) {
                analysisContentHTML += "<li><strong>Colori Dominanti:</strong><ul>";
                analysis.dominantColors.forEach(c => {
                    analysisContentHTML += `<li><span class="color-swatch-inline" style="background-color: rgb(${c.r},${c.g},${c.b});"></span>rgb(${c.r},${c.g},${c.b}) - ${c.percentage.toFixed(0)}% (H:${c.hue.toFixed(0)} S:${c.saturation.toFixed(0)} L:${c.lightness.toFixed(0)}) ~${c.pixelCount}px</li>`;
                });
                analysisContentHTML += "</ul></li>";
            }
        }
        analysisContentHTML += "</ul>";

        // Get the final musical cues based on current state for display
        const displayCues = getMusicalCues(analysis, detectedObjectsList, detectedEmotionsList, CREATIVITY_LEVEL, userInputs);

        // Build HTML for musical interpretation
        let interpretationHtml = "<h4>Interpretazione Musicale Suggerita (basata sull'analisi e tue scelte):</h4><ul>";
        if (displayCues.moods.length > 0) interpretationHtml += `<li><strong>Mood:</strong> ${displayCues.moods.join(", ")}</li>`;
        if (displayCues.genres.length > 0) interpretationHtml += `<li><strong>Generi:</strong> ${displayCues.genres.join(", ")}</li>`;
        if (displayCues.instruments.length > 0) interpretationHtml += `<li><strong>Strumenti:</strong> ${displayCues.instruments.join(", ")}</li>`;
        if (displayCues.rhythms.length > 0) interpretationHtml += `<li><strong>Ritmo:</strong> ${displayCues.rhythms.map(r => rhythmItems.find(i => i.value === r)?.label || r).join(", ")}</li>`;
        interpretationHtml += `<li><strong>Energia:</strong> ${displayCues.energy}</li>`;
        interpretationHtml += `<li><strong>Tempo:</strong> ${displayCues.tempoDescription} (${displayCues.tempoBPM} BPM)</li>`;
        if (displayCues.tonality !== "misto") interpretationHtml += `<li><strong>Tonalità:</strong> ${displayCues.tonality}</li>`;
        interpretationHtml += `<li><strong>Presenza Vocale:</strong> ${displayCues.vocalPresence}</li>`;
        interpretationHtml += "</ul>";
        interpretationHtml += `<div id="finalPromptForAI">${finalStablePrompt}</div>`;

        // Insert the generated HTML into the AI Insights content area
        if (domElements.aiProcessingSimulationDiv) {
            domElements.aiProcessingSimulationDiv.insertAdjacentHTML('afterend', analysisContentHTML + interpretationHtml);
        } else {
            domElements.aiInsightsContent.innerHTML += analysisContentHTML + interpretationHtml;
        }
    }

    // Generates the final English prompt for the music generation API
    function generateStableAudioPrompt(parsedData) {
        console.log("🎵 generateStableAudioPrompt chiamata con:", parsedData);

        if (!parsedData) {
            console.log("⚠️ parsedData non valido, uso fallback");
            return "acoustic upbeat with guitar, 120 BPM, high quality";
        }

        let parts = [];
        if (parsedData.genres?.length > 0) parts.push(...parsedData.genres.map(c => translateCueToEnglish(c, 'genre')));
        if (parsedData.moods?.length > 0) parts.push(...parsedData.moods.map(c => translateCueToEnglish(c, 'mood')));
        if (parsedData.instruments?.length > 0) parts.push(...parsedData.instruments.map(c => translateCueToEnglish(c, 'instrument')));
        if (parsedData.rhythms?.length > 0) parts.push(...parsedData.rhythms.map(c => translateCueToEnglish(c, 'rhythm')));
        if (parsedData.keywords?.length > 0) parts.push(...parsedData.keywords.map(c => translateCueToEnglish(c, 'object')));

        parts.push(parsedData.tempoBPM + " BPM");
        parts.push("high quality instrumental");

        const finalPrompt = [...new Set(parts.filter(Boolean))].join(", ");
        console.log("🎵 PROMPT FINALE:", finalPrompt);
        return finalPrompt;
    }

    // UI Update Functions
    function setStatusMessage(element, message, type = "info") {
        if (!element) {
            console.warn("setStatusMessage: elemento non trovato per messaggio:", message);
            return;
        }
        element.textContent = message;
        element.className = 'status-message';
        if (type === "error") element.classList.add("status-error");
        else if (type === "success") element.classList.add("status-success");
        element.style.display = message ? 'block' : 'none';
        if (!message && type === "info") {
            element.style.display = 'none';
        }
    }

    function updateProgressMessage(message, isLoading = false) {
        if (domElements.dynamicFeedbackArea) domElements.dynamicFeedbackArea.style.display = 'block';
        if (!message && !isLoading) {
            if (domElements.progressAndPlayerContainer) domElements.progressAndPlayerContainer.style.display = 'none';
            if (domElements.progressMessage) domElements.progressMessage.innerHTML = ''; // Clear logs too
            if (domElements.progressBarContainer) domElements.progressBarContainer.style.display = 'none';
            return;
        }
        if (domElements.progressAndPlayerContainer) domElements.progressAndPlayerContainer.style.display = 'block';
        if (domElements.progressBarContainer) domElements.progressBarContainer.style.display = isLoading ? 'block' : 'none';
        if (isLoading || message) {
            if (domElements.audioPlayerContainer) domElements.audioPlayerContainer.style.display = 'none';
        }
    }

    // Load AI Models (TensorFlow.js)
    async function loadModels() {
        console.log("LOG: Inizio caricamento modelli AI...");
        const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
        let faceApiReady = false, cocoReady = false;
        setStatusMessage(domElements.statusDiv, "Caricamento modelli AI (Oggetti e Volti)...", "info");
        domElements.dynamicFeedbackArea.style.display = 'block';

        try {
            if (typeof faceapi !== 'undefined') {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
                ]);
                faceApiModelLoaded = true;
                faceApiReady = true;
                console.log("LOG: Modelli face-api.js caricati.");
            } else {
                console.error("ERRORE CRITICO: Libreria face-api.js non trovata.");
            }
        } catch (error) {
            console.error("ERRORE caricamento face-api:", error);
        }

        try {
            if (typeof cocoSsd !== 'undefined') {
                cocoSsdModel = await cocoSsd.load();
                cocoReady = true;
                console.log("LOG: Modello COCO-SSD caricato.");
            } else {
                console.error("ERRORE CRITICO: Libreria COCO-SSD non trovata.");
            }
        } catch (error) {
            console.error("ERRORE caricamento COCO-SSD:", error);
        }

        if (faceApiReady && cocoReady) {
            setStatusMessage(domElements.statusDiv, "Tutti i modelli AI pronti. Carica un'immagine.", "success");
            console.log("LOG: Tutti i modelli AI caricati con successo.");
        } else {
            let failedModels = [];
            if (!cocoReady) failedModels.push("Rilevamento Oggetti");
            if (!faceApiReady) failedModels.push("Rilevamento Volti");
            setStatusMessage(domElements.statusDiv, `ATTENZIONE: Caricamento fallito per: ${failedModels.join(" e ")}.`, "error");
        }

        if (domElements.statusDiv.textContent.includes("pronti")) {
            setTimeout(() => {
                if (domElements.statusDiv.textContent.includes("pronti")) {
                    domElements.statusDiv.style.display = 'none';
                    domElements.dynamicFeedbackArea.style.display = 'none';
                }
            }, 3000);
        }
    }

    // Image Analysis Functions
    function analyzeImageAdvanced(imageElement, numDominantColors = 5) {
        if (!imageElement || !imageElement.complete || imageElement.naturalHeight === 0) return null;

        const aspectRatio = imageElement.naturalWidth / imageElement.naturalHeight;
        const canvasWidth = 120;
        const canvasHeight = Math.round(canvasWidth / aspectRatio);
        domElements.imageCanvas.width = canvasWidth;
        domElements.imageCanvas.height = canvasHeight;
        imageAnalysisCtx.drawImage(imageElement, 0, 0, canvasWidth, canvasHeight);

        const imageData = imageAnalysisCtx.getImageData(0, 0, canvasWidth, canvasHeight);
        const pixels = imageData.data;
        const colorCounts = {};
        let totalBrightnessSum = 0;
        let totalAnalyzedPixels = 0;
        const colorDepthReduction = 4;
        const binSize = Math.pow(2, 8 - colorDepthReduction);

        for (let i = 0; i < pixels.length; i += 4) {
            const r_orig = pixels[i], g_orig = pixels[i + 1], b_orig = pixels[i + 2], alpha = pixels[i + 3];
            if (alpha > 128) {
                const r_binned = Math.floor(r_orig / binSize) * binSize;
                const g_binned = Math.floor(g_orig / binSize) * binSize;
                const b_binned = Math.floor(b_orig / binSize) * binSize;
                const colorKey = `${r_binned},${g_binned},${b_binned}`;
                colorCounts[colorKey] = (colorCounts[colorKey] || 0) + 1;
                totalBrightnessSum += (r_orig + g_orig + b_orig) / 3;
                totalAnalyzedPixels++;
            }
        }

        if (totalAnalyzedPixels === 0) return { dominantColors: [], averageBrightness: 128, contrast: 0, averageSaturation: 50 };

        const sortedColors = Object.entries(colorCounts).sort(([, a], [, b]) => b - a).slice(0, numDominantColors).map(([k, count]) => {
            const [r, g, b] = k.split(',').map(Number);
            const hsl = rgbToHsl(r, g, b);
            return { r, g, b, percentage: (count / totalAnalyzedPixels) * 100, hue: hsl.h, saturation: hsl.s, lightness: hsl.l, pixelCount: count };
        });

        const avgB = totalBrightnessSum / totalAnalyzedPixels;
        let minL = 100, maxL = 0, totS = 0;
        sortedColors.forEach(c => { minL = Math.min(minL, c.lightness); maxL = Math.max(maxL, c.lightness); totS += c.saturation; });
        return { dominantColors: sortedColors, averageBrightness: avgB, contrast: (maxL - minL), averageSaturation: sortedColors.length > 0 ? totS / sortedColors.length : 50 };
    }

    async function detectObjectsInImage(imageElementForDetection) {
        if (!cocoSsdModel) return [];
        try {
            const predictions = await cocoSsdModel.detect(imageElementForDetection);
            return predictions.filter(p => p.score > 0.55).map(p => translateObject(p.class));
        } catch (e) {
            console.error("ERRORE COCO-SSD:", e);
            return [];
        }
    }

    async function analyzeFacesInImage(imageElementForDetection) {
        if (!faceApiModelLoaded) return [];
        try {
            const detections = await faceapi.detectAllFaces(imageElementForDetection, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceExpressions();
            return detections.map(d => {
                return translateEmotion(Object.keys(d.expressions).reduce((a, b) => d.expressions[a] > d.expressions[b] ? a : b));
            });
        } catch (e) {
            console.error("ERRORE FaceAPI:", e);
            return [];
        }
    }

    // Process new image (from upload or camera)
    function processImage(imageSrc) {
        domElements.imagePreview.src = imageSrc;
        currentImageSrc = imageSrc;
        domElements.imagePreview.style.display = 'block';
        imageAnalysisResults = null;
        initialPreselectionDoneForCurrentImage = false;

        currentImage = new Image();
        currentImage.onload = async () => {
            domElements.detectionCanvas.width = domElements.imagePreview.clientWidth;
            domElements.detectionCanvas.height = domElements.imagePreview.clientHeight;
            if (detectionCtx) detectionCtx.clearRect(0, 0, domElements.detectionCanvas.width, domElements.detectionCanvas.height);

            domElements.generateMusicButton.disabled = true;
            setStatusMessage(domElements.statusDiv, "Analisi in corso...", "info");
            updateProgressMessage("Analisi immagine in corso...", true);
            stableAudioPromptForMusic = "";

            await updateAIDisplayAndStablePrompt();

            updateProgressMessage("", false);
            domElements.dynamicFeedbackArea.style.display = 'none';
            setStatusMessage(domElements.statusDiv, "Analisi completata!", "success");
        };
        currentImage.src = imageSrc;
    }

    // Initial setup
    loadModels();
    if (typeof pictosound_vars !== 'undefined') {
        updateDurationOptionsUI();
    } else {
        setTimeout(() => { if (typeof pictosound_vars !== 'undefined') updateDurationOptionsUI(); }, 500);
    }

    // Event Listeners
    domElements.imageUpload.addEventListener('change', (event) => { if (event.target.files[0]) { processImage(URL.createObjectURL(event.target.files[0])); } });

    domElements.generateMusicButton.addEventListener('click', async () => {
        if (!currentImage) { setStatusMessage(domElements.statusDiv, "Carica un'immagine.", "error"); return; }
        if (!stableAudioPromptForMusic) { await updateAIDisplayAndStablePrompt(); }
        if (typeof pictosound_vars === 'undefined') { setStatusMessage(domElements.statusDiv, "Errore configurazione.", "error"); return; }

        domElements.generateMusicButton.disabled = true;
        domElements.musicSpinner.style.display = 'inline-block';
        updateProgressMessage("Verifica crediti...", true);
        const duration = document.querySelector('input[name="musicDuration"]:checked')?.value || 40;
        checkCredits(duration);
    });

    window.startMusicGeneration = async function () {
        domElements.progressMessage.innerHTML = '';
        updateProgressMessage("Inizio processo...", true);
        try {
            logStep("1. Avvio generazione musica.");
            if (!stableAudioPromptForMusic) {
                logStep("❌ ERRORE: Il prompt per la musica è vuoto. Rigenerazione in corso...");
                await updateAIDisplayAndStablePrompt();
                if (!stableAudioPromptForMusic) throw new Error("Impossibile generare un prompt valido.");
            }
            logStep(`2. Prompt finale per API: "${stableAudioPromptForMusic}"`);

            const selectedDurationRadio = document.querySelector('input[name="musicDuration"]:checked');
            const duration = selectedDurationRadio ? parseInt(selectedDurationRadio.value) : 40;
            logStep(`3. Durata selezionata: ${duration} secondi.`);

            logStep("4. Chiamata a 'generate_music.php' in corso...");
            const musicApiResponse = await fetch('/wp-content/pictosound/generate_music.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: stableAudioPromptForMusic, duration: duration, steps: 30 })
            });

            logStep(`5. Risposta ricevuta da PHP. Status: ${musicApiResponse.status}`);
            const responseText = await musicApiResponse.text();
            logStep(`6. Testo grezzo della risposta: <pre>${responseText.substring(0, 500)}...</pre>`);

            const musicContentType = musicApiResponse.headers.get("content-type");
            if (!musicApiResponse.ok || !musicContentType || musicContentType.indexOf("application/json") === -1) {
                throw new Error(`Errore server (PHP). Status: ${musicApiResponse.status}. Content-Type: ${musicContentType}.`);
            }

            logStep("7. Risposta è JSON valido. Analisi del contenuto...");
            const musicResult = JSON.parse(responseText);

            if (!musicResult.success || !musicResult.audioUrl) {
                throw new Error(`L'API ha restituito un errore: ${musicResult.error || 'Dettagli non disponibili'}`);
            }

            logStep(`8. ✅ Successo! URL Audio: ${musicResult.audioUrl}`);
            updateProgressMessage("", false);
            setStatusMessage(domElements.statusDiv, "Musica generata! Ora salvo la creazione...", "success");

            logStep("9. Preparazione dati per salvataggio nel database...");
            const creationDataToSave = {
                action: 'pictosound_save_creation',
                nonce: pictosound_vars.save_creation_nonce,
                title: 'Musica del ' + new Date().toLocaleString('it-IT'),
                prompt: stableAudioPromptForMusic,
                description: generateCreationDescriptionForSave(),
                image_url: currentImageSrc.length > 5000 ? 'data:image (placeholder)' : currentImageSrc,
                audio_url: musicResult.audioUrl,
                duration: duration,
                style: getSelectedGenresForSave().join(', '),
                mood: getSelectedMoodsForSave().join(', '),
                credits_used: (pictosound_vars.duration_costs || {})[duration] || 0,
                generation_data: JSON.stringify({
                    api_prompt: stableAudioPromptForMusic,
                    user_selections: { moods: getSelectedMoodsForSave(), genres: getSelectedGenresForSave(), instruments: getSelectedInstrumentsForSave(), rhythms: getSelectedRhythmsForSave() }
                })
            };

            logStep("10. Chiamata AJAX a 'pictosound_save_creation'...");
            jQuery.ajax({
                url: pictosound_vars.ajax_url,
                type: 'POST',
                data: creationDataToSave,
                success: function (response) {
                    if (response.success) {
                        logStep("11. ✅ SALVATAGGIO COMPLETATO! ID Creazione: " + response.data.creation_id);
                        showSaveNotificationPictosound("Creazione salvata con successo!", "success");
                    } else {
                        logStep(`11. ❌ ERRORE SALVATAGGIO DB: ${response.data.message || 'Errore sconosciuto'}`);
                        showSaveNotificationPictosound(`Errore salvataggio: ${response.data.message}`, "error");
                    }
                },
                error: function (xhr) {
                    logStep(`11. ❌ ERRORE AJAX CRITICO DURANTE SALVATAGGIO: ${xhr.statusText}`);
                    showSaveNotificationPictosound("Errore di connessione durante il salvataggio.", "error");
                },
                complete: function () {
                    domElements.generateMusicButton.disabled = false;
                    domElements.musicSpinner.style.display = 'none';
                }
            });

            // Aggiorna UI finale
            if (domElements.audioPlayer) domElements.audioPlayer.src = musicResult.audioUrl;
            if (domElements.audioPlayerContainer) domElements.audioPlayerContainer.style.display = 'block';

        } catch (error) {
            logStep(`❌ ERRORE CRITICO: ${error.message}`);
            updateProgressMessage("", false);
            setStatusMessage(domElements.statusDiv, `Processo interrotto. Dettagli nel log qui sopra.`, "error");
            domElements.generateMusicButton.disabled = false;
            domElements.musicSpinner.style.display = 'none';
        }
    };
});

/**
 * ⚡ NOTIFICA DI SALVATAGGIO
 */
function showSaveNotificationPictosound(message, type = 'info') {
    let notification = document.getElementById('pictosoundSaveNotification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'pictosoundSaveNotification';
        notification.style.cssText = ` position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-family: Inter, -apple-system, sans-serif; font-size: 14px; font-weight: 500; max-width: 300px; transition: all 0.3s ease; opacity: 0; transform: translateX(100%);`;
        document.body.appendChild(notification);
    }
    const styles = { success: { background: '#28a745', color: 'white' }, error: { background: '#dc3545', color: 'white' }, info: { background: '#17a2b8', color: 'white' } };
    const style = styles[type] || styles.info;
    Object.assign(notification.style, style);
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    notification.textContent = `${icons[type] || ''} ${message}`;
    notification.style.opacity = '1';
    notification.style.transform = 'translateX(0)';
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
    }, type === 'error' ? 6000 : 4000);
}

/**
 * ⚡ FUNZIONI HELPER PER RACCOGLIERE DATI (versioni sicure)
 */
function generateCreationDescriptionForSave() {
    const aiText = getAiInsightsTextForSave();
    if (aiText && aiText.length > 10) return aiText.substring(0, 200) + (aiText.length > 200 ? '...' : '');
    const moods = getSelectedMoodsForSave();
    const genres = getSelectedGenresForSave();
    if (moods.length > 0 || genres.length > 0) {
        const parts = [];
        if (moods.length > 0) parts.push(`Mood: ${moods.join(', ')}`);
        if (genres.length > 0) parts.push(`Genere: ${genres.join(', ')}`);
        return `Musica generata da immagine. ${parts.join('. ')}.`;
    }
    return 'Musica generata automaticamente da immagine con intelligenza artificiale.';
}

function getSelectedDurationForSave() { return parseInt(document.querySelector('input[name="musicDuration"]:checked')?.value || '40'); }
function getSelectedMoodsForSave() { return Array.from(document.querySelectorAll('input[name="mood"]:checked')).map(i => i.value); }
function getSelectedGenresForSave() { return Array.from(document.querySelectorAll('input[name="genre"]:checked')).map(i => i.value); }
function getSelectedInstrumentsForSave() { return Array.from(document.querySelectorAll('input[name="instrument"]:checked')).map(i => i.value); }
function getSelectedRhythmsForSave() { return Array.from(document.querySelectorAll('input[name="rhythm"]:checked')).map(i => i.value); }

function getAiInsightsTextForSave() {
    const aiElement = document.getElementById('aiInterpretationText');
    if (aiElement && aiElement.textContent) return aiElement.textContent.trim();
    const aiContent = document.getElementById('aiInsightsContent');
    if (aiContent) return (aiContent.textContent || '').replace(/\s+/g, ' ').trim().substring(0, 300);
    return '';
}

function calculateCreditsUsedForSave() {
    const duration = getSelectedDurationForSave();
    if (duration <= 40) return 0;
    if (duration <= 60) return 1;
    if (duration <= 120) return 2;
    if (duration <= 180) return 3;
    if (duration <= 240) return 4;
    if (duration <= 360) return 5;
    return Math.ceil(duration / 60);
}
/**
 * Funzione principale che orchestra l'analisi dell'immagine e l'aggiornamento dell'UI.
 * Viene chiamata quando una nuova immagine viene caricata o quando l'utente modifica i parametri.
 */
async function updateAIDisplayAndStablePrompt() {
    if (!currentImage || !currentImage.complete || currentImage.naturalHeight === 0) {
        console.warn("WARN: Tentativo di analisi su un'immagine non valida o non ancora caricata.");
        return;
    }

    logStep("🤖 Inizio analisi AI completa.");
    domElements.generateMusicButton.disabled = true;
    domElements.aiInsightsSection.style.display = 'block';
    domElements.detailsAccordionHeader.classList.add('active'); // Apri l'accordion
    domElements.aiInsightsContent.style.maxHeight = domElements.aiInsightsContent.scrollHeight + "px";


    // Mostra la simulazione di elaborazione AI
    startAISimulationText();

    // Esegui tutte le analisi in parallelo per efficienza
    const [analysis, detectedObjects, detectedEmotions] = await Promise.all([
        analyzeImageAdvanced(currentImage),
        detectObjectsInImage(currentImage),
        analyzeFacesInImage(currentImage)
    ]);
    logStep(`🎨 Analisi Colori: ${analysis ? 'Completata' : 'Fallita'}.`);
    logStep(`📦 Analisi Oggetti: Rilevati ${detectedObjects.length} oggetti.`);
    logStep(`😊 Analisi Emozioni: Rilevate ${detectedEmotions.length} emozioni.`);


    imageAnalysisResults = { analysis, detectedObjects, detectedEmotions };

    // Raccogli l'input dell'utente corrente
    const userInputs = {
        selectedMoods: getSelectedCheckboxValues('mood'),
        selectedGenres: getSelectedCheckboxValues('genre'),
        selectedInstruments: getSelectedCheckboxValues('instrument'),
        selectedRhythms: getSelectedCheckboxValues('rhythm'),
        selectedBPM: domElements.bpmSlider.value
    };

    // Ottieni i suggerimenti musicali basati sull'analisi e l'input utente
    const musicalCues = getMusicalCues(analysis, detectedObjects, detectedEmotions, CREATIVITY_LEVEL, userInputs);
    logStep("🎶 Cues musicali determinati.");

    // Se è la prima analisi per questa immagine, preseleziona le pillole suggerite
    if (!initialPreselectionDoneForCurrentImage) {
        logStep("💡 Preselezione iniziale delle pillole in corso...");
        ['moods', 'genres', 'instruments', 'rhythms'].forEach(type => {
            const container = document.getElementById(type.slice(0, -1) + 'Pills');
            if (container && musicalCues[type]) {
                musicalCues[type].forEach(value => {
                    const checkbox = container.querySelector(`input[value="${value}"]`);
                    if (checkbox && !checkbox.checked) {
                        checkbox.checked = true;
                        checkbox.dispatchEvent(new Event('change')); // Scatena l'evento per aggiornare lo stile
                    }
                });
            }
        });
        initialPreselectionDoneForCurrentImage = true; // Impedisce future preselezioni automatiche per questa immagine
    }


    // Genera il prompt stabile per l'API musicale
    stableAudioPromptForMusic = generateStableAudioPrompt(musicalCues);

    // Ferma l'animazione di testo e mostra i risultati finali
    stopAISimulationText();
    generateAIDisplayContent(analysis, detectedObjects, detectedEmotions, userInputs, stableAudioPromptForMusic);

    // Riabilita il pulsante di generazione
    domElements.generateMusicButton.disabled = false;
    logStep("✅ Analisi AI completata e UI aggiornata.");
}

// Funzioni per la gestione della fotocamera
async function startCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
    }
    try {
        const constraints = {
            video: {
                facingMode: currentFacingMode,
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        domElements.cameraFeed.srcObject = currentStream;
        domElements.cameraViewContainer.style.display = 'flex';
        logStep("📷 Fotocamera avviata.");

        // Controlla se il dispositivo ha più di una fotocamera
        navigator.mediaDevices.enumerateDevices()
            .then(devices => {
                const videoInputs = devices.filter(device => device.kind === 'videoinput');
                if (videoInputs.length > 1) {
                    domElements.switchCameraButton.style.display = 'block';
                } else {
                    domElements.switchCameraButton.style.display = 'none';
                }
            });

    } catch (err) {
        console.error("Errore accesso fotocamera:", err);
        setStatusMessage(domElements.statusDiv, "Impossibile accedere alla fotocamera. Controlla i permessi.", "error");
    }
}

function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
    }
    domElements.cameraViewContainer.style.display = 'none';
    logStep("📷 Fotocamera fermata.");
}

function captureImage() {
    const canvas = domElements.imageCanvas;
    canvas.width = domElements.cameraFeed.videoWidth;
    canvas.height = domElements.cameraFeed.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(domElements.cameraFeed, 0, 0, canvas.width, canvas.height);
    stopCamera();
    processImage(canvas.toDataURL('image/jpeg'));
    logStep("📸 Immagine catturata dalla fotocamera.");
}


// Initial setup
loadModels();
if (typeof pictosound_vars !== 'undefined') {
    updateDurationOptionsUI();
} else {
    setTimeout(() => { if (typeof pictosound_vars !== 'undefined') updateDurationOptionsUI(); }, 500);
}

// Event Listeners

// Listener per caricamento immagine
domElements.imageUpload.addEventListener('change', (event) => { if (event.target.files[0]) { processImage(URL.createObjectURL(event.target.files[0])); } });

// Listener per la fotocamera
if (domElements.takePictureButton) {
    domElements.takePictureButton.addEventListener('click', startCamera);
}
if (domElements.closeCameraButton) {
    domElements.closeCameraButton.addEventListener('click', stopCamera);
}
if (domElements.captureImageButton) {
    domElements.captureImageButton.addEventListener('click', captureImage);
}
if (domElements.switchCameraButton) {
    domElements.switchCameraButton.addEventListener('click', () => {
        currentFacingMode = currentFacingMode === "user" ? "environment" : "user";
        startCamera(); // Riavvia la fotocamera con la nuova modalità
        logStep(`🔄 Fotocamera cambiata in: ${currentFacingMode}`);
    });
}

// Listener per il pulsante Genera Musica
domElements.generateMusicButton.addEventListener('click', async () => {
    if (!currentImage) { setStatusMessage(domElements.statusDiv, "Carica o scatta un'immagine prima.", "error"); return; }
    if (!stableAudioPromptForMusic) {
        logStep("⚠️ Prompt non pronto, avvio analisi...");
        await updateAIDisplayAndStablePrompt();
    }
    if (typeof pictosound_vars === 'undefined') { setStatusMessage(domElements.statusDiv, "Errore di configurazione. Ricarica la pagina.", "error"); return; }

    domElements.generateMusicButton.disabled = true;
    domElements.musicSpinner.style.display = 'inline-block';
    updateProgressMessage("Verifica dei crediti in corso...", true);
    const duration = document.querySelector('input[name="musicDuration"]:checked')?.value || 40;
    checkCredits(duration);
});

// Listener per il BPM Slider
if (domElements.bpmSlider) {
    domElements.bpmSlider.addEventListener('input', () => {
        if (domElements.bpmValueDisplay) domElements.bpmValueDisplay.textContent = domElements.bpmSlider.value;
    });
    // Aggiorna il prompt se si cambia il BPM dopo aver analizzato un'immagine
    domElements.bpmSlider.addEventListener('change', () => {
        if (currentImage && imageAnalysisResults) {
            updateAIDisplayAndStablePrompt();
            logStep(`BPM aggiornato a: ${domElements.bpmSlider.value}`);
        }
    });
}

// Listener per l'accordion dei dettagli AI
if (domElements.detailsAccordionHeader) {
    domElements.detailsAccordionHeader.addEventListener('click', function () {
        this.classList.toggle('active');
        const content = domElements.aiInsightsContent;
        if (content.style.maxHeight) {
            content.style.maxHeight = null;
        } else {
            content.style.maxHeight = content.scrollHeight + "px";
        }
        logStep("Accordion AI attivato/disattivato.");
    });
}

// Listeners per il modal a schermo intero
if (domElements.imagePreview) {
    domElements.imagePreview.addEventListener('click', () => {
        if (domElements.fullscreenImage && domElements.fullscreenImageModal && currentImageSrc) {
            domElements.fullscreenImage.src = currentImageSrc;
            domElements.fullscreenImageModal.style.display = 'flex';
            logStep("🖼️ Immagine ingrandita.");
        }
    });
}

if (domElements.closeFullscreenButton) {
    domElements.closeFullscreenButton.addEventListener('click', () => {
        if (domElements.fullscreenImageModal) {
            domElements.fullscreenImageModal.style.display = 'none';
            logStep("🖼️ Visualizzazione ingrandita chiusa.");
        }
    });
}

// Logica di generazione musica (funzione globale chiamata da checkCredits)
window.startMusicGeneration = async function () {
    domElements.progressMessage.innerHTML = '';
    updateProgressMessage("Inizio processo di generazione musicale...", true);
    try {
        logStep("1. Avvio generazione musica.");
        if (!stableAudioPromptForMusic) {
            logStep("❌ ERRORE: Il prompt per la musica è vuoto. Tentativo di rigenerazione...");
            await updateAIDisplayAndStablePrompt();
            if (!stableAudioPromptForMusic) throw new Error("Impossibile generare un prompt valido per la musica.");
        }
        logStep(`2. Prompt finale per API: "${stableAudioPromptForMusic}"`);

        const selectedDurationRadio = document.querySelector('input[name="musicDuration"]:checked');
        const duration = selectedDurationRadio ? parseInt(selectedDurationRadio.value) : 40;
        logStep(`3. Durata selezionata: ${duration} secondi.`);

        logStep("4. Chiamata allo script 'generate_music.php' in corso...");
        const musicApiResponse = await fetch('/wp-content/pictosound/generate_music.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: stableAudioPromptForMusic, duration: duration, steps: 30, creativity: CREATIVITY_LEVEL })
        });

        logStep(`5. Risposta ricevuta da PHP. Status: ${musicApiResponse.status}`);
        const responseText = await musicApiResponse.text();
        // Logga solo l'inizio della risposta per non intasare la console
        logStep(`6. Testo grezzo della risposta: <pre>${responseText.substring(0, 500)}...</pre>`);

        const musicContentType = musicApiResponse.headers.get("content-type");
        if (!musicApiResponse.ok || !musicContentType || musicContentType.indexOf("application/json") === -1) {
            let errorDetail = responseText;
            try {
                // Prova a parsare come JSON per un messaggio di errore più pulito
                const errorJson = JSON.parse(responseText);
                if (errorJson && errorJson.error) {
                    errorDetail = errorJson.error;
                }
            } catch (e) { /* non è JSON, usa il testo grezzo */ }

            throw new Error(`Errore dal server (PHP). Status: ${musicApiResponse.status}. Dettagli: ${errorDetail}`);
        }

        logStep("7. Risposta JSON valida. Analisi del contenuto...");
        const musicResult = JSON.parse(responseText);

        if (!musicResult.success || !musicResult.audioUrl) {
            throw new Error(`L'API musicale ha restituito un errore: ${musicResult.error || 'Dettagli non disponibili'}`);
        }

        logStep(`8. ✅ Successo! URL Audio: ${musicResult.audioUrl}`);
        updateProgressMessage("", false);
        setStatusMessage(domElements.statusDiv, "Musica generata! Ora salvo la creazione nel tuo profilo...", "success");

        domElements.audioPlayerContainer.style.display = 'block';
        if (domElements.audioPlayer) {
            domElements.audioPlayer.src = musicResult.audioUrl;
            domElements.audioPlayer.load();
            domElements.audioPlayer.play();
        }
        if (domElements.downloadAudioLink) {
            domElements.downloadAudioLink.href = musicResult.audioUrl;
            domElements.downloadAudioLink.style.display = 'inline-block';
        }

        if (!pictosound_vars.is_user_logged_in) {
            logStep("Utente non loggato, salto salvataggio DB.");
            domElements.generateMusicButton.disabled = false;
            domElements.musicSpinner.style.display = 'none';
            return; // Interrompi qui se l'utente non è loggato
        }

        logStep("9. Preparazione dati per salvataggio nel database...");
        const creationDataToSave = {
            action: 'pictosound_save_creation',
            nonce: pictosound_vars.save_creation_nonce,
            title: 'Musica creata il ' + new Date().toLocaleString('it-IT'),
            prompt: stableAudioPromptForMusic,
            description: generateCreationDescriptionForSave(),
            // Se l'URL è troppo lungo (data URI), invia un placeholder
            image_url: currentImageSrc.length > 5000 ? 'data:image/jpeg;base64,...(placeholder)' : currentImageSrc,
            audio_url: musicResult.audioUrl,
            duration: duration,
            style: getSelectedGenresForSave().join(', '),
            mood: getSelectedMoodsForSave().join(', '),
            credits_used: (pictosound_vars.duration_costs || {})[String(duration)] || 0,
            generation_data: JSON.stringify({
                api_prompt: stableAudioPromptForMusic,
                user_selections: {
                    moods: getSelectedMoodsForSave(),
                    genres: getSelectedGenresForSave(),
                    instruments: getSelectedInstrumentsForSave(),
                    rhythms: getSelectedRhythmsForSave(),
                    bpm: domElements.bpmSlider.value
                },
                image_analysis: imageAnalysisResults
            })
        };

        logStep("10. Chiamata AJAX a 'pictosound_save_creation' per il salvataggio...");
        jQuery.ajax({
            url: pictosound_vars.ajax_url,
            type: 'POST',
            data: creationDataToSave,
            success: function (response) {
                if (response.success) {
                    logStep("11. ✅ SALVATAGGIO COMPLETATO! ID Creazione: " + response.data.creation_id);
                    showSaveNotificationPictosound("Creazione salvata con successo nel tuo profilo!", "success");
                    if (response.data.new_balance !== undefined) {
                        updateCreditsDisplay(response.data.new_balance);
                        updateDurationOptionsUI();
                    }
                } else {
                    logStep(`11. ❌ ERRORE SALVATAGGIO DB: ${response.data.message || 'Errore sconosciuto'}`);
                    showSaveNotificationPictosound(`Errore durante il salvataggio: ${response.data.message}`, "error");
                }
            },
            error: function (xhr) {
                logStep(`11. ❌ ERRORE AJAX CRITICO DURANTE IL SALVATAGGIO: ${xhr.statusText}`);
                showSaveNotificationPictosound("Errore di connessione durante il salvataggio della creazione.", "error");
            },
            complete: function () {
                domElements.generateMusicButton.disabled = false;
                domElements.musicSpinner.style.display = 'none';
            }
        });

    } catch (error) {
        logStep(`❌ ERRORE CRITICO nel processo di generazione: ${error.message}`);
        updateProgressMessage("", false);
        setStatusMessage(domElements.statusDiv, `Processo interrotto. Dettagli nel log di debug.`, "error");
        domElements.generateMusicButton.disabled = false;
        domElements.musicSpinner.style.display = 'none';
    }
};

});

/**
 * ⚡ NOTIFICA DI SALVATAGGIO GLOBALE
 * Mostra una notifica temporanea in stile "toast".
 */
function showSaveNotificationPictosound(message, type = 'info') {
    let notification = document.getElementById('pictosoundSaveNotification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'pictosoundSaveNotification';
        // Stili per posizionamento, aspetto e transizioni
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-family: Inter, -apple-system, sans-serif;
            font-size: 14px;
            font-weight: 500;
            max-width: 300px;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            opacity: 0;
            transform: translateX(120%);
        `;
        document.body.appendChild(notification);
    }
    // Stili per tipo di notifica (successo, errore, info)
    const styles = {
        success: { background: '#28a745', color: 'white' },
        error: { background: '#dc3545', color: 'white' },
        info: { background: '#17a2b8', color: 'white' }
    };
    const style = styles[type] || styles.info;
    Object.assign(notification.style, style);

    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    notification.textContent = `${icons[type] || ''} ${message}`;

    // Animazione di entrata
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10); // Piccolo ritardo per permettere l'applicazione degli stili iniziali

    // Animazione di uscita
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(120%)';
    }, type === 'error' ? 6000 : 4000);
}

/**
 * ⚡ FUNZIONI HELPER PER RACCOGLIERE DATI IN MODO SICURO PER IL SALVATAGGIO
 * Queste funzioni prevengono errori se gli elementi non esistono nel DOM.
 */
function generateCreationDescriptionForSave() {
    const aiText = getAiInsightsTextForSave();
    if (aiText && aiText.length > 10) return aiText.substring(0, 200) + (aiText.length > 200 ? '...' : '');

    const moods = getSelectedMoodsForSave();
    const genres = getSelectedGenresForSave();
    if (moods.length > 0 || genres.length > 0) {
        const parts = [];
        if (moods.length > 0) parts.push(`Mood: ${moods.join(', ')}`);
        if (genres.length > 0) parts.push(`Genere: ${genres.join(', ')}`);
        return `Musica generata da un'immagine. ${parts.join('. ')}.`;
    }
    return "Musica generata automaticamente da un'immagine tramite intelligenza artificiale.";
}

function getSelectedDurationForSave() {
    const radio = document.querySelector('input[name="musicDuration"]:checked');
    return radio ? parseInt(radio.value) : 40;
}

function getSelectedValuesForSave(name) {
    return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map(i => i.value);
}

function getSelectedMoodsForSave() { return getSelectedValuesForSave('mood'); }
function getSelectedGenresForSave() { return getSelectedValuesForSave('genre'); }
function getSelectedInstrumentsForSave() { return getSelectedValuesForSave('instrument'); }
function getSelectedRhythmsForSave() { return getSelectedValuesForSave('rhythm'); }


function getAiInsightsTextForSave() {
    const aiElement = document.getElementById('aiInterpretationText');
    if (aiElement && aiElement.textContent) return aiElement.textContent.trim();

    const aiContent = document.getElementById('aiInsightsContent');
    if (aiContent) {
        // Pulisce il testo da spazi multipli e ritorni a capo per una descrizione più pulita
        return (aiContent.textContent || '').replace(/\s\s+/g, ' ').trim().substring(0, 300);
    }
    return '';
}