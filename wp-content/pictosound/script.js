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
    // Sostituisci la funzione rechargeCredits esistente con questa versione PayPal
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
                        // Controlla se è un redirect PayPal
                        if (response.data.is_redirect && response.data.redirect_url) {
                            console.log('PayPal redirect URL:', response.data.redirect_url);

                            // Mostra messaggio e reindirizza a PayPal
                            const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');
                            if (rechargeStatusDiv) {
                                setStatusMessage(rechargeStatusDiv, 'Reindirizzamento a PayPal...', "info");
                            }

                            // Reindirizza a PayPal dopo un breve delay
                            setTimeout(() => {
                                window.location.href = response.data.redirect_url;
                            }, 1000);

                        } else {
                            // Gestisci risposta normale (non dovrebbe succedere con PayPal)
                            console.log('Ricarica completata:', response.data);
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
                        console.error('Errore ricarica:', response.data);
                        const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');
                        if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, response.data.message || 'Errore durante la ricarica', "error");
                    }
                },
                error: function (xhr, status, error) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.code === 'nonce_expired') {
                            // Gestisci automaticamente il nonce scaduto
                            handleNonceExpiredError(makeRechargeRequest, data);
                            return;
                        }
                    } catch (e) {
                        // Ignora errori di parsing
                    }

                    console.error('ERRORE: Risposta JSON dal server (WP AJAX Ricarica) indica fallimento:', xhr.responseText);
                    const rechargeStatusDiv = document.getElementById('rechargeStatusMessage');
                    if (rechargeStatusDiv) setStatusMessage(rechargeStatusDiv, 'Errore durante la ricarica. Riprova.', "error");
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
                        console.log('Check crediti completato:', response.data);
                        // Gestisci il successo
                        if (response.data.can_proceed) {
                            // Aggiorna crediti rimanenti se forniti
                            if (typeof response.data.remaining_credits !== 'undefined') {
                                pictosound_vars.user_credits = response.data.remaining_credits;
                                if (typeof updateDurationOptionsUI === 'function') updateDurationOptionsUI();
                            }
                            // Procedi con la generazione
                            if (typeof window.startMusicGeneration === 'function') {
                                window.startMusicGeneration();
                            }
                        } else {
                            // Crediti insufficienti o altro errore
                            if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, response.data.message || 'Impossibile procedere', "error");
                            updateProgressMessage("", false);
                            if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = false;
                            if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
                        }
                    } else {
                        console.error('Errore check crediti:', response.data);
                        if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, response.data.message || 'Errore nella verifica crediti', "error");
                        updateProgressMessage("", false);
                        if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = false;
                        if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
                    }
                },
                error: function (xhr, status, error) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.code === 'nonce_expired') {
                            // Gestisci automaticamente il nonce scaduto
                            handleNonceExpiredError(makeCheckRequest, data);
                            return;
                        }
                    } catch (e) {
                        // Ignora errori di parsing
                    }

                    console.error('ERRORE: Risposta JSON dal server (WP AJAX Check) indica fallimento:', xhr.responseText);
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
        // Aggiorna tutti gli elementi che mostrano il saldo crediti
        jQuery('.pictosound-saldo-display-widget').each(function () {
            const $element = jQuery(this);
            const text = $element.text();
            const newText = text.replace(/\d+/, newBalance);
            $element.text(newText);
        });

        // Aggiorna anche le variabili globali
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
        }, 600); // Un leggero ritardo maggiore rispetto all'altro timeout
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
    // ✅ NUOVA FUNZIONE SEMPLIFICATA
    function generateStableAudioPrompt(parsedData) {
        console.log("🎵 generateStableAudioPrompt chiamata con:", parsedData);

        // Fallback se parsedData non è valido
        if (!parsedData) {
            console.log("⚠️ parsedData non valido, uso fallback");
            return "acoustic upbeat with guitar, 120 BPM, high quality";
        }

        // Estrai dati dall'analisi immagine GLOBALE
        const detectedObjects = imageAnalysisResults?.objects || [];
        const detectedEmotions = imageAnalysisResults?.emotions || [];

        console.log("🎯 Oggetti rilevati:", detectedObjects);
        console.log("😊 Emozioni rilevate:", detectedEmotions);
        console.log("🎭 Mood parsedData:", parsedData.moods);
        console.log("🎼 Generi parsedData:", parsedData.genres);

        // 1. DETERMINA GENERE PRINCIPALE
        let genre = "atmospheric"; // Default

        if (detectedObjects.includes("persona")) {
            genre = "acoustic";
            console.log("👤 Persona rilevata → genere: acoustic");
        } else if (detectedObjects.includes("cane") && detectedObjects.includes("persona")) {
            genre = "acoustic";
            console.log("👤🐕 Persona + cane → genere: acoustic");
        } else if (detectedObjects.some(obj => ["auto", "città", "strada"].includes(obj))) {
            genre = "electronic";
            console.log("🏙️ Urbano rilevato → genere: electronic");
        } else if (detectedObjects.some(obj => ["natura", "albero", "montagna"].includes(obj))) {
            genre = "ambient";
            console.log("🌲 Natura rilevata → genere: ambient");
        }

        // 2. DETERMINA MOOD
        let mood = "";

        // Prima controlla emozioni rilevate DIRETTAMENTE
        if (detectedEmotions.includes("felice")) {
            mood = "upbeat";
            console.log("😊 Emozione felice → mood: upbeat");
        } else if (detectedEmotions.includes("triste")) {
            mood = "melancholic";
            console.log("😢 Emozione triste → mood: melancholic");
        } else if (parsedData.moods && parsedData.moods.length > 0 && parsedData.moods[0] !== "descrittivo") {
            // Usa mood dall'analisi se non è il fallback
            const firstMood = parsedData.moods[0];
            if (firstMood === "energico") mood = "upbeat";
            else if (firstMood === "malinconico") mood = "melancholic";
            else if (firstMood === "rilassante") mood = "calm";
            console.log("🎭 Mood da parsedData:", firstMood, "→", mood);
        }

        // 3. DETERMINA STRUMENTO
        let instrument = "guitar"; // Default per acoustic

        if (genre === "acoustic") instrument = "guitar";
        else if (genre === "electronic") instrument = "synthesizer";
        else if (genre === "ambient") instrument = "piano";

        console.log("🎸 Strumento scelto:", instrument);

        // 4. DETERMINA BPM
        let bpm = "120"; // Default
        if (mood === "upbeat") bpm = "130";
        else if (mood === "melancholic" || mood === "calm") bpm = "80";

        console.log("⏱️ BPM scelto:", bpm);

        // 5. COSTRUISCI PROMPT SEMPLICE
        const parts = [];

        // Aggiungi genere
        parts.push(genre);

        // Aggiungi mood se significativo
        if (mood && mood !== genre) {
            parts.push(mood);
        }

        // Aggiungi strumento
        parts.push("with " + instrument);

        // Aggiungi BPM
        parts.push(bpm + " BPM");

        // Aggiungi qualità
        parts.push("high quality");

        const finalPrompt = parts.join(", ");

        console.log("🎵 PROMPT FINALE SEMPLIFICATO:", finalPrompt);

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
        domElements.dynamicFeedbackArea.style.display = 'block';
        if (!message && !isLoading) {
            domElements.progressAndPlayerContainer.style.display = 'none';
            domElements.progressMessage.textContent = '';
            domElements.progressBarContainer.style.display = 'none';
            return;
        }
        domElements.progressAndPlayerContainer.style.display = 'block';
        domElements.progressMessage.textContent = message;
        domElements.progressBarContainer.style.display = isLoading ? 'block' : 'none';
        if (isLoading || message) {
            domElements.audioPlayerContainer.style.display = 'none';
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
                console.error("ERRORE CRITICO: Libreria face-api.js non trovata. Assicurati che '/wp-content/pictosound/js/face-api.min.js' sia caricato.");
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
                console.error("ERRORE CRITICO: Libreria COCO-SSD non trovata. Assicurati che '/wp-content/pictosound/js/coco-ssd.min.js' sia caricato.");
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
            setStatusMessage(domElements.statusDiv, `ATTENZIONE: Caricamento fallito per: ${failedModels.join(" e ")}. Funzionalità limitate.`, "error");
            console.error("ERRORE: Caricamento fallito per:", failedModels.join(" e "));
        }

        if (domElements.statusDiv.textContent === "Tutti i modelli AI pronti. Carica un'immagine." && !domElements.progressMessage.textContent && domElements.audioPlayerContainer.style.display === 'none') {
            setTimeout(() => {
                if (domElements.statusDiv.textContent === "Tutti i modelli AI pronti. Carica un'immagine.") {
                    domElements.statusDiv.style.display = 'none';
                    domElements.dynamicFeedbackArea.style.display = 'none';
                }
            }, 3000);
        }
    }

    // Image Analysis Functions
    function analyzeImageAdvanced(imageElement, numDominantColors = 5) {
        if (!imageElement || !imageElement.complete || imageElement.naturalHeight === 0) {
            console.warn("WARN: analyzeImageAdvanced - Immagine non valida o non caricata.");
            return null;
        }

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
        if (!cocoSsdModel) {
            console.warn("WARN: Modello COCO-SSD non pronto per rilevamento oggetti.");
            return [];
        }
        try {
            const predictions = await cocoSsdModel.detect(imageElementForDetection);
            const filtered = predictions.filter(p => p.score > 0.55);
            console.log("LOG: Oggetti rilevati (originale EN):", filtered.map(p => p.class));
            return filtered.map(p => translateObject(p.class));
        } catch (e) {
            console.error("ERRORE COCO-SSD:", e);
            return [];
        }
    }

    async function analyzeFacesInImage(imageElementForDetection) {
        if (!faceApiModelLoaded) {
            console.warn("WARN: Modello FaceAPI non pronto per analisi volti.");
            return [];
        }
        try {
            const detections = await faceapi.detectAllFaces(imageElementForDetection, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceExpressions();
            return detections.map(d => {
                let dominantEmotionKey = "neutral";
                if (d.expressions && Object.keys(d.expressions).length > 0) {
                    dominantEmotionKey = Object.keys(d.expressions).reduce((a, b) => d.expressions[a] > d.expressions[b] ? a : b);
                }
                return translateEmotion(dominantEmotionKey);
            });
        } catch (e) {
            console.error("ERRORE FaceAPI:", e);
            return [];
        }
    }

    // QR Code and Composite Image Functions
    async function generateQrCodeToCanvas(canvasElement, text, size = 120) {
        console.log(`LOG DEBUG QR: Chiamata a generateQrCodeToCanvas con testo: "${text}", dimensione: ${size}`);
        return new Promise((resolve, reject) => {
            if (typeof QRCode === 'undefined') {
                console.error("ERRORE CRITICO: Libreria QRCode non è definita! Assicurati che '/wp-content/pictosound/js/qrcode.min.js' sia caricato.");
                return reject(new Error("Libreria QRCode non definita"));
            }
            if (typeof QRCode.toCanvas !== 'function') {
                console.error("ERRORE CRITICO: QRCode.toCanvas non è una funzione! La libreria potrebbe essere errata o non caricata.");
                return reject(new TypeError("QRCode.toCanvas is not a function"));
            }

            const options = {
                width: size,
                margin: 1,
                errorCorrectionLevel: 'H'
            };
            QRCode.toCanvas(canvasElement, text, options, function (error) {
                if (error) {
                    console.error("ERRORE DEBUG QR: Errore generazione QR Code:", error);
                    reject(error);
                } else {
                    console.log(`LOG DEBUG QR: QR Code generato su canvas (${canvasElement.width}x${canvasElement.height}) per:`, text);
                    resolve(canvasElement);
                }
            });
        });
    }

    async function createCompositeImage(originalImageElement, qrCodeCanvasElement, targetCanvasElement) {
        console.log("LOG DEBUG QR: Inizio createCompositeImage.");
        const ctx = targetCanvasElement.getContext('2d');
        const PADDING_FACTOR = 0.03;

        const PADDING = originalImageElement.naturalWidth * PADDING_FACTOR;

        const QR_DRAW_WIDTH = qrCodeCanvasElement.width;
        const QR_DRAW_HEIGHT = qrCodeCanvasElement.height;
        console.log(`LOG DEBUG QR: Dimensioni QR canvas per disegno: ${QR_DRAW_WIDTH}x${QR_DRAW_HEIGHT}`);

        targetCanvasElement.width = originalImageElement.naturalWidth;
        targetCanvasElement.height = originalImageElement.naturalHeight;
        console.log(`LOG DEBUG QR: Dimensioni canvas composito: ${targetCanvasElement.width}x${targetCanvasElement.height}`);

        ctx.drawImage(originalImageElement, 0, 0);
        console.log("LOG DEBUG QR: Immagine originale disegnata su canvas composito.");

        const qrX = targetCanvasElement.width - QR_DRAW_WIDTH - PADDING;
        const qrY = targetCanvasElement.height - QR_DRAW_HEIGHT - PADDING;
        console.log(`LOG DEBUG QR: Posizione QR (X,Y): ${qrX}, ${qrY}`);

        ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
        ctx.fillRect(qrX - PADDING / 2, qrY - PADDING / 2, QR_DRAW_WIDTH + PADDING, QR_DRAW_HEIGHT + PADDING);

        ctx.strokeStyle = 'rgba(0, 0, 0, 0.3)';
        ctx.lineWidth = 1;
        ctx.strokeRect(qrX - PADDING / 2, qrY - PADDING / 2, QR_DRAW_WIDTH + PADDING, QR_DRAW_HEIGHT + PADDING);
        console.log("LOG DEBUG QR: Sfondo e bordo per QR disegnati.");

        ctx.drawImage(qrCodeCanvasElement, qrX, qrY, QR_DRAW_WIDTH, QR_DRAW_HEIGHT);
        console.log("LOG DEBUG QR: Canvas QR disegnato su canvas composito.");

        const dataUrl = targetCanvasElement.toDataURL('image/png');
        console.log("LOG DEBUG QR: Immagine composita creata come Data URL.");
        return dataUrl;
    }

    // Preselects cue checkboxes based on AI analysis
    function preselectCuesFromAnalysis(musicalCues) {
        if (!musicalCues) return;

        const preselectGroup = (groupItems, cueValues, groupName) => {
            groupItems.forEach(item => {
                const checkbox = document.querySelector(`input[name="${groupName}"][value="${item.value}"]`);
                if (checkbox) {
                    const pillLabel = checkbox.closest('.checkbox-pill');
                    if (cueValues.map(cv => cv.toLowerCase()).includes(item.value.toLowerCase())) {
                        checkbox.checked = true;
                        pillLabel.classList.add('selected');
                    } else {
                        checkbox.checked = false;
                        pillLabel.classList.remove('selected');
                    }
                }
            });
        };

        preselectGroup(moodItems, musicalCues.moods || [], 'mood');
        preselectGroup(genreItems, musicalCues.genres || [], 'genre');
        preselectGroup(instrumentItems, musicalCues.instruments || [], 'instrument');
        preselectGroup(rhythmItems, musicalCues.rhythms || [], 'rhythm');

        // Automatically open sections that have preselected cues
        document.querySelectorAll('.cues-selection-container').forEach(container => {
            const header = container.querySelector('label.group-label.collapsible-cue-header');
            const pillsGroup = container.querySelector('.checkbox-pills-group');
            if (header && pillsGroup) {
                const hasSelectedPill = Array.from(pillsGroup.querySelectorAll('.checkbox-pill.selected')).length > 0;
                if (hasSelectedPill && !header.classList.contains('open')) {
                    header.classList.add('open');
                    pillsGroup.classList.add('open');
                    const bpmSliderContainer = header.parentElement.querySelector('.bpm-slider-container');
                    if (bpmSliderContainer) {
                        bpmSliderContainer.style.display = 'block';
                    }
                }
            }
        });
    }

    // Main function to update AI display and the stable audio prompt
    async function updateAIDisplayAndStablePrompt() {
        if (!currentImage) {
            console.warn("WARN: updateAIDisplayAndStablePrompt chiamato senza currentImage.");
            return;
        }

        // Perform analysis if not already done for the current image
        if (!imageAnalysisResults) {
            domElements.dynamicFeedbackArea.style.display = 'block';
            domElements.statusDiv.classList.add('hidden');
            updateProgressMessage("Analisi immagine in corso...", true);
            startAISimulationText();

            imageAnalysisResults = {
                colors: analyzeImageAdvanced(currentImage),
                objects: await detectObjectsInImage(currentImage),
                emotions: await analyzeFacesInImage(currentImage)
            };
            stopAISimulationText();
            initialPreselectionDoneForCurrentImage = false;
        }

        // Get current user selections from checkboxes and BPM slider
        const selectedMoods = getSelectedCheckboxValues('mood');
        const selectedGenres = getSelectedCheckboxValues('genre');
        const selectedInstruments = getSelectedCheckboxValues('instrument');
        const selectedRhythms = getSelectedCheckboxValues('rhythm');
        const selectedBPM = domElements.bpmSlider.value;
        const userInputs = { selectedMoods, selectedGenres, selectedInstruments, selectedRhythms, selectedBPM };

        // Get musical cues based on analysis and user inputs
        const tempParsedCues = getMusicalCues(imageAnalysisResults.colors, imageAnalysisResults.objects, imageAnalysisResults.emotions, CREATIVITY_LEVEL, userInputs);

        // If AI preselection hasn't been done, do it now
        if (!initialPreselectionDoneForCurrentImage) {
            preselectCuesFromAnalysis(tempParsedCues);
            initialPreselectionDoneForCurrentImage = true;
        }

        // Generate the final English prompt for the music API
        stableAudioPromptForMusic = generateStableAudioPrompt(tempParsedCues);

        // Update the AI Insights display section
        generateAIDisplayContent(imageAnalysisResults.colors, imageAnalysisResults.objects, imageAnalysisResults.emotions, userInputs, stableAudioPromptForMusic);
        domElements.aiInsightsSection.style.display = 'block';

        // Flash accordion header if it's closed but has new content
        if (imageAnalysisResults && !domElements.detailsAccordionHeader.classList.contains('open') && domElements.aiInsightsContent.innerHTML.includes('<h4>')) {
            domElements.detailsAccordionHeader.classList.add('new-content-flash');
            setTimeout(() => { domElements.detailsAccordionHeader.classList.remove('new-content-flash'); }, 1800);
        }
        console.log("LOG: Prompt per Stability AI (Inglese):", stableAudioPromptForMusic);

        // Enable or disable the generate music button based on prompt validity
        if (stableAudioPromptForMusic && !stableAudioPromptForMusic.toLowerCase().includes("errore") && !stableAudioPromptForMusic.toLowerCase().includes("data analysis not available")) {
            domElements.generateMusicButton.disabled = false;
        } else {
            domElements.generateMusicButton.disabled = true;
        }
    }

    // Process new image (from upload or camera)
    function processImage(imageSrc) {
        console.log("LOG: Inizio processImage con src:", imageSrc ? "presente" : "mancante");
        domElements.imagePreview.src = imageSrc;
        currentImageSrc = imageSrc;
        domElements.imagePreview.style.display = 'block';
        imageAnalysisResults = null;
        initialPreselectionDoneForCurrentImage = false;

        currentImage = new Image();
        currentImage.onload = async () => {
            console.log("LOG: currentImage caricata. Dimensioni:", currentImage.naturalWidth, "x", currentImage.naturalHeight);

            domElements.detectionCanvas.width = domElements.imagePreview.clientWidth;
            domElements.detectionCanvas.height = domElements.imagePreview.clientHeight;
            detectionCtx.clearRect(0, 0, domElements.detectionCanvas.width, domElements.detectionCanvas.height);
            toggleDetectionCanvasVisibility();

            // Reset UI for new analysis
            domElements.generateMusicButton.disabled = true;
            setStatusMessage(domElements.statusDiv, "Immagine caricata. Analisi in corso...", "info");
            domElements.dynamicFeedbackArea.style.display = 'block';
            updateProgressMessage("Analisi immagine in corso...", true);
            stableAudioPromptForMusic = "";

            // Hide AI insights and clear old content
            domElements.aiInsightsSection.style.display = 'none';
            if (domElements.detailsAccordionHeader) domElements.detailsAccordionHeader.classList.remove('open');
            if (domElements.aiInsightsContent) {
                domElements.aiInsightsContent.style.display = 'none';
                const simDiv = domElements.aiInsightsContent.querySelector('.ai-processing-simulation');
                const existingDetails = domElements.aiInsightsContent.querySelectorAll('h4, ul, #finalPromptForAI');
                existingDetails.forEach(el => {
                    if (!el.classList.contains('ai-processing-simulation')) el.remove();
                });
                if (simDiv) simDiv.innerHTML = '<p>In attesa di analisi immagine...</p>';
            }

            // Hide player and download links
            domElements.progressAndPlayerContainer.style.display = 'none';
            domElements.audioPlayerContainer.style.display = 'none';
            domElements.downloadAudioLink.style.display = 'none';
            domElements.downloadCompositeImageLink.style.display = 'none';
            domElements.downloadQrOnlyLink.style.display = 'none';

            // Clear all selected cue pills and close cue sections
            document.querySelectorAll('.checkbox-pill input[type="checkbox"]:checked').forEach(cb => {
                cb.checked = false;
                cb.closest('.checkbox-pill').classList.remove('selected');
            });
            document.querySelectorAll('.cues-selection-container label.group-label.open').forEach(header => {
                header.classList.remove('open');
                const content = header.nextElementSibling;
                if (content && content.classList.contains('checkbox-pills-group')) {
                    content.classList.remove('open');
                }
                const bpmSliderContainer = header.parentElement.querySelector('.bpm-slider-container');
                if (bpmSliderContainer) bpmSliderContainer.style.display = 'none';
            });
            domElements.bpmSlider.value = 120;
            domElements.bpmValueDisplay.textContent = domElements.bpmSlider.value;

            // Start the analysis and update UI
            await updateAIDisplayAndStablePrompt();

            // Update UI after analysis is complete
            updateProgressMessage("", false);
            domElements.dynamicFeedbackArea.style.display = 'none';
            setStatusMessage(domElements.statusDiv, "Analisi completata. Scegli spunti o genera direttamente!", "success");
        };

        currentImage.onerror = () => {
            console.error("ERRORE: Errore durante il caricamento di currentImage.");
            setStatusMessage(domElements.statusDiv, "Errore caricamento immagine.", "error");
            updateProgressMessage("Errore caricamento immagine.", false);
            domElements.generateMusicButton.disabled = true;
        };
        currentImage.src = imageSrc;
    }

    // Initial model loading
    loadModels();

    // Inizializza il sistema di crediti
    if (typeof pictosound_vars !== 'undefined' && pictosound_vars !== null) {
        console.log("LOG: pictosound_vars trovato (dopo loadModels), chiamo updateDurationOptionsUI().");
        updateDurationOptionsUI();
    } else {
        console.warn("WARN: pictosound_vars non immediatamente disponibile (dopo loadModels), imposto un timeout per updateDurationOptionsUI().");
        setTimeout(() => {
            if (typeof pictosound_vars !== 'undefined' && pictosound_vars !== null) {
                console.log("LOG: pictosound_vars trovato dopo timeout (post-loadModels), chiamo updateDurationOptionsUI().");
                updateDurationOptionsUI();
            } else {
                console.error("ERRORE CRITICO: pictosound_vars non definito dopo il timeout (post-loadModels). Il sistema di crediti non funzionerà correttamente.");
                if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, "Errore configurazione crediti: dati server mancanti.", "error");
            }
        }, 500);
    }

    // Function to start and manage camera stream
    async function startCamera(requestedFacingMode) {
        domElements.imagePreview.style.display = 'none';
        domElements.imagePreview.src = '#';
        currentImage = null;
        imageAnalysisResults = null;
        domElements.aiInsightsSection.style.display = 'none';
        domElements.cameraViewContainer.style.display = 'block';
        domElements.generateMusicButton.disabled = true;

        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
        }

        let streamAcquired = false;
        const primaryAttemptConstraints = { video: { facingMode: requestedFacingMode }, audio: false };
        const alternateFacingMode = requestedFacingMode === "environment" ? "user" : "environment";
        const alternateAttemptConstraints = { video: { facingMode: alternateFacingMode }, audio: false };
        const genericAttemptConstraints = { video: true, audio: false };

        try {
            console.log(`Tentativo primario con facingMode: ${requestedFacingMode}`);
            currentStream = await navigator.mediaDevices.getUserMedia(primaryAttemptConstraints);
            setStatusMessage(domElements.statusDiv, `Fotocamera (${requestedFacingMode === 'environment' ? 'posteriore' : 'frontale'}) attivata.`, "info");
            currentFacingMode = requestedFacingMode;
            streamAcquired = true;
        } catch (errPrimary) {
            console.warn(`Errore con facingMode primario (${requestedFacingMode}): ${errPrimary.name} - ${errPrimary.message}`);
            try {
                console.log(`Tentativo alternativo con facingMode: ${alternateFacingMode}`);
                currentStream = await navigator.mediaDevices.getUserMedia(alternateAttemptConstraints);
                setStatusMessage(domElements.statusDiv, `Fotocamera (${alternateFacingMode === 'environment' ? 'posteriore' : 'frontale'}) attivata (fallback).`, "info");
                currentFacingMode = alternateFacingMode;
                streamAcquired = true;
            } catch (errAlternate) {
                console.warn(`Errore con facingMode alternativo (${alternateFacingMode}): ${errAlternate.name} - ${errAlternate.message}`);
                try {
                    console.log("Tentativo generico (video: true)...");
                    currentStream = await navigator.mediaDevices.getUserMedia(genericAttemptConstraints);
                    const settings = currentStream.getVideoTracks()[0].getSettings();
                    const actualFacingMode = settings.facingMode || "sconosciuto";
                    currentFacingMode = (actualFacingMode && actualFacingMode !== "unknown") ? actualFacingMode : currentFacingMode;
                    setStatusMessage(domElements.statusDiv, `Fotocamera generica (${currentFacingMode}) attivata (fallback).`, "info");
                    streamAcquired = true;
                } catch (errGeneric) {
                    console.error("Errore con fotocamera generica:", errGeneric);
                    setStatusMessage(domElements.statusDiv, "Impossibile accedere alla fotocamera. Controlla i permessi.", "error");
                    domElements.cameraViewContainer.style.display = 'none';
                    currentFacingMode = "environment";
                }
            }
        }

        if (streamAcquired && currentStream) {
            domElements.cameraFeed.srcObject = currentStream;
            console.log("Stream fotocamera acquisito e assegnato.");
        } else {
            console.log("Impossibile acquisire stream fotocamera dopo tutti i tentativi.");
            domElements.cameraViewContainer.style.display = 'none';
        }
    }

    // Event Listeners
    domElements.imageUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                processImage(e.target.result);
                domElements.cameraViewContainer.style.display = 'none';
                if (currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                    currentStream = null;
                }
            }
            reader.readAsDataURL(file);
        }
    });

    domElements.takePictureButton.addEventListener('click', async () => {
        await startCamera(currentFacingMode);
    });

    if (domElements.switchCameraButton) {
        domElements.switchCameraButton.addEventListener('click', async () => {
            currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
            console.log(`Cambiando a facingMode: ${currentFacingMode}`);
            await startCamera(currentFacingMode);
        });
    }

    domElements.captureImageButton.addEventListener('click', () => {
        if (currentStream && domElements.cameraFeed.readyState >= 2) {
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = domElements.cameraFeed.videoWidth;
            tempCanvas.height = domElements.cameraFeed.videoHeight;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(domElements.cameraFeed, 0, 0, tempCanvas.width, tempCanvas.height);
            const imageDataUrl = tempCanvas.toDataURL('image/jpeg');
            processImage(imageDataUrl);

            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
            domElements.cameraViewContainer.style.display = 'none';
        } else {
            setStatusMessage(domElements.statusDiv, "Feed fotocamera non pronto. Riprova.", "warn");
        }
    });

    domElements.closeCameraButton.addEventListener('click', () => {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
        domElements.cameraViewContainer.style.display = 'none';
        setStatusMessage(domElements.statusDiv, "Fotocamera chiusa.", "info");
    });

    if (domElements.bpmSlider && domElements.bpmValueDisplay) {
        domElements.bpmSlider.addEventListener('input', () => {
            domElements.bpmValueDisplay.textContent = domElements.bpmSlider.value;
            initialPreselectionDoneForCurrentImage = true;
            if (currentImage && imageAnalysisResults) {
                updateAIDisplayAndStablePrompt();
            }
        });
    }

    // GENERATE MUSIC BUTTON con auto-recovery
    if (domElements.generateMusicButton) {
        domElements.generateMusicButton.addEventListener('click', async () => {
            console.log("LOG: Pulsante 'Avvia generazione musica' cliccato (con auto-recovery).");

            if (!currentImage) {
                console.warn("WARN: Tentativo di generare musica senza currentImage.");
                if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, "Carica o scatta prima un'immagine.", "error");
                updateProgressMessage("", false);
                if (domElements.dynamicFeedbackArea) domElements.dynamicFeedbackArea.style.display = 'none';
                return;
            }

            if (!stableAudioPromptForMusic || stableAudioPromptForMusic.toLowerCase().includes("errore") || stableAudioPromptForMusic.toLowerCase().includes("data analysis not available")) {
                console.log("LOG: Prompt non pronto, richiamo updateAIDisplayAndStablePrompt per finalizzarlo.");
                if (typeof updateAIDisplayAndStablePrompt === 'function') {
                    await updateAIDisplayAndStablePrompt();
                } else {
                    console.error("ERRORE: updateAIDisplayAndStablePrompt non è definita!");
                    if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, "Errore interno (prompt).", "error");
                    return;
                }
                if (!stableAudioPromptForMusic || stableAudioPromptForMusic.toLowerCase().includes("errore") || stableAudioPromptForMusic.toLowerCase().includes("data analysis not available")) {
                    console.error("ERRORE: Prompt ancora non valido dopo il tentativo di finalizzazione.");
                    if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, "Errore preparazione prompt. Riprova o seleziona nuova immagine.", "error");
                    updateProgressMessage("Errore nella preparazione del prompt.", false);
                    return;
                }
            }

            if (typeof pictosound_vars === 'undefined' || pictosound_vars === null) {
                console.error("ERRORE CRITICO: pictosound_vars non è definito! Impossibile procedere.");
                if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, "Errore di configurazione (mancano variabili server).", "error");
                return;
            }

            if (domElements.statusDiv) setStatusMessage(domElements.statusDiv, pictosound_vars.text_checking_credits || "Verifica crediti...", "info");
            if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = true;
            if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'inline-block';
            if (domElements.audioPlayerContainer) domElements.audioPlayerContainer.style.display = 'none';
            if (domElements.downloadAudioLink) domElements.downloadAudioLink.style.display = 'none';
            if (domElements.downloadCompositeImageLink) domElements.downloadCompositeImageLink.style.display = 'none';
            if (domElements.downloadQrOnlyLink) domElements.downloadQrOnlyLink.style.display = 'none';
            if (domElements.dynamicFeedbackArea) domElements.dynamicFeedbackArea.style.display = 'block';
            updateProgressMessage(pictosound_vars.text_checking_credits || "Verifica crediti...", true);

            const selectedDurationRadio = document.querySelector('input[name="musicDuration"]:checked');
            const duration = selectedDurationRadio ? Math.max(30, Math.min(180, parseInt(selectedDurationRadio.value))) : 45; // Default 45s

            // Usa checkCredits con auto-recovery
            checkCredits(duration);
        });

        // Funzione per procedere con la generazione dopo il check crediti
        window.startMusicGeneration = async function () {
            try {
                if (domElements.dynamicFeedbackArea) domElements.dynamicFeedbackArea.style.display = 'block';
                updateProgressMessage(pictosound_vars.text_generating_music || 'Generazione traccia audio in corso...', true);

                const promptForMusic = stableAudioPromptForMusic;
                const selectedDurationRadio = document.querySelector('input[name="musicDuration"]:checked');
                const duration = selectedDurationRadio ? parseInt(selectedDurationRadio.value) : 40;

                console.log(`LOG: Parametri per generazione (AJAX unificato): prompt="${promptForMusic}", duration=${duration}`);

                // ⚡ CHIAMATA AJAX UNIFICATA CHE INVIA TUTTI I DATI
                jQuery.ajax({
                    url: pictosound_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pictosound_generate_music', // L'azione corretta per la tua funzione PHP
                        prompt: promptForMusic,
                        duration: duration,
                        image_data: currentImageSrc, // <-- ECCO LA MODIFICA CHIAVE! Inviamo l'immagine.
                        nonce: pictosound_vars.nonce_generate // Aggiungeremo questo nonce al Passaggio 2
                    },
                    timeout: 180000, // Timeout di 3 minuti per la generazione
                    success: async function (response) {
                        console.log("LOG: Risposta da AJAX unificato (pictosound_generate_music):", response);

                        if (response.success && response.data.audioUrl) {
                            updateProgressMessage("", false);
                            setStatusMessage(domElements.statusDiv, "Musica generata con successo!", "success");

                            // Nascondi messaggio di successo dopo qualche secondo
                            setTimeout(() => {
                                if (domElements.statusDiv && domElements.statusDiv.textContent === "Musica generata con successo!") {
                                    domElements.dynamicFeedbackArea.style.display = 'none';
                                    domElements.statusDiv.style.display = 'none';
                                }
                            }, 4000);

                            // Mostra il player audio
                            if (domElements.audioPlayer) domElements.audioPlayer.src = response.data.audioUrl;
                            if (domElements.audioInfo) domElements.audioInfo.textContent = `Traccia: ${response.data.fileName || 'audio_generato.mp3'}`;
                            if (domElements.audioPlayerContainer) domElements.audioPlayerContainer.style.display = 'block';
                            if (domElements.progressAndPlayerContainer) domElements.progressAndPlayerContainer.style.display = 'block';

                            // Mostra i pulsanti di download
                            if (domElements.downloadAudioLink && response.data.downloadUrl) {
                                domElements.downloadAudioLink.href = response.data.downloadUrl;
                                domElements.downloadAudioLink.download = response.data.fileName || `generated_audio_${Date.now()}.mp3`;
                                domElements.downloadAudioLink.style.display = 'inline-flex';
                            }

                            // Gestione QR Code e immagine composita (invariato)
                            if (currentImage && currentImage.complete && typeof QRCode !== 'undefined' && response.data.audioUrl && domElements.compositeImageCanvas) {
                                try {
                                    const qrCanvasForComposite = document.createElement('canvas');
                                    const qrCanvasForSoloDownload = document.createElement('canvas');
                                    let desiredQrPixelSize = Math.max(50, Math.min(currentImage.naturalWidth * 0.25, currentImage.naturalHeight * 0.25, 150));

                                    await generateQrCodeToCanvas(qrCanvasForComposite, response.data.audioUrl, desiredQrPixelSize);
                                    const compositeImageDataUrl = await createCompositeImage(currentImage, qrCanvasForComposite, domElements.compositeImageCanvas);
                                    if (domElements.downloadCompositeImageLink) {
                                        domElements.downloadCompositeImageLink.href = compositeImageDataUrl;
                                        domElements.downloadCompositeImageLink.download = `pictosound_img_qr_${Date.now()}.png`;
                                        domElements.downloadCompositeImageLink.style.display = 'inline-flex';
                                    }

                                    await generateQrCodeToCanvas(qrCanvasForSoloDownload, response.data.audioUrl, 150);
                                    const soloQrDataUrl = qrCanvasForSoloDownload.toDataURL('image/png');
                                    if (domElements.downloadQrOnlyLink) {
                                        domElements.downloadQrOnlyLink.href = soloQrDataUrl;
                                        domElements.downloadQrOnlyLink.download = `pictosound_qrcode_${Date.now()}.png`;
                                        domElements.downloadQrOnlyLink.style.display = 'inline-flex';
                                    }
                                } catch (qrError) {
                                    console.error("ERRORE QR:", qrError);
                                }
                            }

                        } else {
                            const errorMessage = response.data.error || response.data.message || "Errore sconosciuto durante la generazione.";
                            updateProgressMessage(errorMessage, false);
                            setStatusMessage(domElements.statusDiv, errorMessage, "error");
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("ERRORE durante la generazione musica:", error, status, xhr.responseText);
                        const errorMessage = `Errore di connessione o del server (${status}).`;
                        updateProgressMessage(errorMessage, false);
                        setStatusMessage(domElements.statusDiv, errorMessage, "error");
                    },
                    complete: function () {
                        // Assicurati che il pulsante e lo spinner tornino normali
                        if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = false;
                        if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
                    }
                });

            } catch (error) {
                console.error("ERRORE grave in startMusicGeneration:", error);
                updateProgressMessage(`Errore: ${error.message}`, false);
                setStatusMessage(domElements.statusDiv, "Errore critico durante l'avvio della generazione", "error");
                if (domElements.generateMusicButton) domElements.generateMusicButton.disabled = false;
                if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
            }
        };
    }

    // Accordion for "Dettagli" in AI Insights
    if (domElements.detailsAccordionHeader && domElements.aiInsightsContent) {
        domElements.detailsAccordionHeader.addEventListener('click', () => {
            const isOpen = domElements.detailsAccordionHeader.classList.toggle('open');
            domElements.aiInsightsContent.style.display = isOpen ? 'block' : 'none';
        });
    }

    // Accordions for Cue Selection Groups
    document.querySelectorAll('.cues-selection-container label.group-label.collapsible-cue-header').forEach(header => {
        header.addEventListener('click', () => {
            header.classList.toggle('open');
            const content = header.nextElementSibling;
            if (content && content.classList.contains('checkbox-pills-group')) {
                content.classList.toggle('open');
                const bpmSliderContainer = header.parentElement.querySelector('.bpm-slider-container');
                if (bpmSliderContainer) {
                    bpmSliderContainer.style.display = header.classList.contains('open') ? 'block' : 'none';
                }
            }
        });
    });

    // Fullscreen image on audio play
    if (domElements.audioPlayer && domElements.fullscreenImageModal && domElements.fullscreenImage && domElements.closeFullscreenButton) {
        domElements.audioPlayer.onplay = () => {
            if (currentImageSrc) {
                domElements.fullscreenImage.src = currentImageSrc;
                domElements.fullscreenImageModal.style.display = 'flex';
            }
        };
        domElements.closeFullscreenButton.onclick = () => {
            domElements.fullscreenImageModal.style.display = 'none';
        };
        window.onclick = (event) => {
            if (event.target == domElements.fullscreenImageModal) {
                domElements.fullscreenImageModal.style.display = 'none';
            }
        };
        document.addEventListener('keydown', function (event) {
            if (event.key === "Escape") {
                domElements.fullscreenImageModal.style.display = 'none';
            }
        });
    }
});