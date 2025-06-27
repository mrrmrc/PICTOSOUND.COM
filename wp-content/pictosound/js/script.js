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
    // =======================================================================
    // ⚡ INIZIO BLOCCO LOGICA UX PER UTENTI NON AUTENTICATI
    // =======================================================================

    // DEFINIZIONE DELLA FUNZIONE DI GESTIONE INTERFACCIA
    // ========== VERSIONE MODIFICATA - SEMPRE LOGIN RICHIESTO ==========
    function updateUserAccessUI() {
        const loginOrRegisterPrompt = document.getElementById('loginOrRegisterPrompt');
        if (!loginOrRegisterPrompt) {
            console.error("Elemento 'loginOrRegisterPrompt' non trovato nel DOM.");
            return;
        }

        const isUserLoggedIn = pictosound_vars.is_user_logged_in;
        const generateButton = document.getElementById('generateMusicButton');

        // ⚡ NUOVO: Se l'utente NON è autenticato, SEMPRE blocca
        if (!isUserLoggedIn) {
            const promptHTML = `
            <strong style="font-size: 16px; color: #0056b3;">🔒 Accesso Richiesto</strong>
            <p style="margin: 8px 0 0;">Per utilizzare Pictosound devi essere registrato.</p>
            <p style="margin: 8px 0 0;">La registrazione è <strong>gratuita</strong> e ti premia con <strong>6 crediti in omaggio</strong>!</p>
            <div style="margin-top: 15px;">
                <a href="/login/" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; margin-right: 10px; font-weight: 600;">Accedi Ora</a>
                <a href="/registrazione/" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">Registrati Gratis</a>
            </div>
        `;

            loginOrRegisterPrompt.innerHTML = promptHTML;
            loginOrRegisterPrompt.style.display = 'block';

            if (generateButton) {
                generateButton.disabled = true; // ⚡ SEMPRE disabilitato se non loggati
            }

            return;
        }

        // CASO: L'utente È autenticato - nasconde il prompt e abilita il pulsante
        loginOrRegisterPrompt.style.display = 'none';
        if (generateButton) {
            const currentImage = document.getElementById('imagePreview');
            generateButton.disabled = !currentImage || currentImage.src.includes("#");
        }
    }

    // ATTIVAZIONE DELLA LOGICA
    // Si assicuri che queste righe siano presenti nel suo script, preferibilmente verso la fine 
    // del listener 'DOMContentLoaded', per garantire che tutti gli elementi siano caricati.

    document.querySelectorAll('input[name="musicDuration"]').forEach(radio => {
        radio.addEventListener('change', updateUserAccessUI);
    });

    // Eseguiamo un controllo iniziale al caricamento della pagina per impostare lo stato corretto.
    // Lo eseguiamo con un piccolo ritardo per assicurarci che tutte le variabili siano pronte.
    setTimeout(updateUserAccessUI, 100);

    // =======================================================================
    // ⚡ FINE BLOCCO LOGICA UX
    // =======================================================================
    // Debug - verifica elementi accordion
    console.log("LOG: Accordion header trovato:", !!domElements.detailsAccordionHeader);
    console.log("LOG: AI insights content trovato:", !!domElements.aiInsightsContent);
    console.log("LOG: AI processing simulation trovato:", !!domElements.aiProcessingSimulationDiv);

    // State variables
    let currentImage = null;
    let currentImageSrc = null;
    let imageAnalysisResults = null;
    let stableAudioPromptForMusic = "";
    let cocoSsdModel = null;
    let faceApiModelLoaded = false;
    const showDetections = false;
    let initialPreselectionDoneForCurrentImage = false;

    // Contexts for canvases
    let detectionCtx = null;
    if (domElements.detectionCanvas) {
        detectionCtx = domElements.detectionCanvas.getContext('2d');
    }

    let imageAnalysisCtx = null;
    if (domElements.imageCanvas) {
        imageAnalysisCtx = domElements.imageCanvas.getContext('2d');
    }

    // Italian to English translations
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

    // Cue translations for building English prompt
    const cueTranslationsITtoEN = {
        mood: {
            "felice": "happy", "gioioso": "joyful", "triste": "sad", "malinconico": "melancholic",
            "riflessivo": "reflective", "epico": "epic", "grandioso": "grandiose",
            "rilassante": "relaxing", "calmo": "calm", "energico": "energetic",
            "vivace": "lively", "misterioso": "mysterious", "inquietante": "eerie",
            "sognante": "dreamy", "etereo": "ethereal", "romantico": "romantic",
            "drammatico": "dramatic", "futuristico": "futuristic", "sci-fi": "sci-fi",
            "nostalgico": "nostalgic", "potente": "powerful", "intenso": "intense"
        },
        genre: {
            "elettronica": "electronic", "dance": "dance", "rock": "rock", "pop": "pop",
            "jazz": "jazz", "classica": "classical", "ambient": "ambient",
            "soundtrack": "soundtrack", "cinematografica": "cinematic", "folk": "folk",
            "acustica": "acoustic", "lo-fi": "lo-fi", "hip-hop": "hip hop"
        },
        instrument: {
            "pianoforte": "piano", "chitarra acustica": "acoustic guitar",
            "chitarra elettrica": "electric guitar", "basso": "bass", "batteria": "drums",
            "violino": "violin", "archi": "strings", "sintetizzatore": "synthesizer"
        },
        rhythm: {
            "no_rhythm": "no distinct rhythm", "slow_rhythm": "slow rhythm",
            "moderate_groove": "moderate groove", "upbeat_energetic": "upbeat energetic rhythm"
        }
    };

    // Pills data
    const moodItems = [
        { value: "felice", label: "Felice / Gioioso" },
        { value: "triste", label: "Triste / Malinconico" },
        { value: "riflessivo", label: "Riflessivo" },
        { value: "epico", label: "Epico / Grandioso" },
        { value: "rilassante", label: "Rilassante / Calmo" },
        { value: "energico", label: "Energico / Vivace" },
        { value: "misterioso", label: "Misterioso / Inquietante" },
        { value: "sognante", label: "Sognante / Etereo" },
        { value: "romantico", label: "Romantico" },
        { value: "drammatico", label: "Drammatico" },
        { value: "futuristico", label: "Futuristico / Sci-Fi" },
        { value: "nostalgico", label: "Nostalgico" },
        { value: "potente", label: "Potente / Intenso" }
    ];

    const genreItems = [
        { value: "elettronica", label: "Elettronica" },
        { value: "rock", label: "Rock" },
        { value: "pop", label: "Pop" },
        { value: "jazz", label: "Jazz" },
        { value: "classica", label: "Classica" },
        { value: "ambient", label: "Ambient" },
        { value: "soundtrack", label: "Soundtrack / Cinematografica" },
        { value: "folk", label: "Folk / Acustica" },
        { value: "lo-fi", label: "Lo-fi / Chillhop" },
        { value: "hip-hop", label: "Hip Hop" }
    ];

    const instrumentItems = [
        { value: "pianoforte", label: "Pianoforte" },
        { value: "chitarra acustica", label: "Chitarra Acustica" },
        { value: "chitarra elettrica", label: "Chitarra Elettrica" },
        { value: "basso", label: "Basso" },
        { value: "batteria", label: "Batteria / Percussioni" },
        { value: "violino", label: "Violino / Archi" },
        { value: "sintetizzatore", label: "Sintetizzatore / Tastiere" }
    ];

    const rhythmItems = [
        { value: "no_rhythm", label: "Nessun ritmo evidente (Ambientale)" },
        { value: "slow_rhythm", label: "Ritmo Lento e Rilassato" },
        { value: "moderate_groove", label: "Groove Moderato e Orecchiabile" },
        { value: "upbeat_energetic", label: "Ritmo Incalzante ed Energico" }
    ];

    // ========== HELPER FUNCTIONS ==========
    function setStatusMessage(element, message, type = "info") {
        if (!element) return;
        element.textContent = message;
        element.className = 'status-message';
        if (type === "error") element.classList.add("status-error");
        else if (type === "success") element.classList.add("status-success");
        element.style.display = message ? 'block' : 'none';
    }

    // RGB to HSL conversion
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

    function translateObject(objectClass) {
        return objectTranslations[objectClass.toLowerCase()] || objectClass;
    }

    function translateEmotion(emotion) {
        return emotionTranslations[emotion.toLowerCase()] || emotion;
    }

    function getSelectedCheckboxValues(groupName) {
        return Array.from(document.querySelectorAll(`input[name="${groupName}"]:checked`)).map(cb => cb.value);
    }

    function translateCueToEnglish(italianCue, type) {
        if (!italianCue) return "";
        const lowerCue = String(italianCue).toLowerCase();
        if (cueTranslationsITtoEN[type] && cueTranslationsITtoEN[type][lowerCue]) {
            return cueTranslationsITtoEN[type][lowerCue];
        }
        console.warn(`No English translation for '${italianCue}' of type '${type}'`);
        return italianCue;
    }

    // ========== DEFINITA PRIMA DI ESSERE USATA ==========
    function forceUpdatePrompt() {
        console.log("🔄 DEBUG: Aggiornamento forzato del prompt");
        if (currentImage && imageAnalysisResults) {
            updateAIDisplayAndStablePrompt();
        } else {
            console.log("⚠️ DEBUG: Aggiornamento prompt saltato - mancano dati immagine");
        }
    }

    // ========== FUNZIONI MODIFICATE PER SEPARARE I CONTENUTI ==========

    // Start AI simulation animation - SEMPLIFICATA
    function startAISimulationText() {
        if (domElements.aiProcessingSimulationDiv) {
            domElements.aiProcessingSimulationDiv.innerHTML = '<p style="margin: 10px 0; color: #666; font-style: italic;">🔍 Analisi immagine in corso...</p>';
            domElements.aiProcessingSimulationDiv.style.display = 'block';
            console.log("✅ DEBUG: Messaggio di attesa impostato nell'elemento sempre visibile");
        }
    }

    function stopAISimulationText() {
        // Non nascondere più l'elemento, sarà sostituito dall'interpretazione
        console.log("✅ DEBUG: Stop simulazione - l'elemento rimarrà visibile per l'interpretazione");
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
    }

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
                console.log(`🎛️ DEBUG: Cambiato ${groupName}: ${item.value} = ${this.checked}`);
                pillLabel.classList.toggle('selected', this.checked);
                initialPreselectionDoneForCurrentImage = true;

                setTimeout(() => {
                    forceUpdatePrompt();
                }, 100);
            });

            pillLabel.appendChild(checkbox);
            pillLabel.appendChild(document.createTextNode(item.label));
            container.appendChild(pillLabel);
        });
    }

    function analyzeImageAdvanced(imageElement, numDominantColors = 5) {
        if (!imageElement || !imageElement.complete || imageElement.naturalHeight === 0) {
            console.warn("WARN: analyzeImageAdvanced - Immagine non valida");
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

    // ========== AI MODELS ==========
    async function loadModels() {
        console.log("LOG: Inizio caricamento modelli AI...");
        setStatusMessage(domElements.statusDiv, "Caricamento modelli AI...", "info");

        try {
            if (typeof cocoSsd === 'undefined') {
                throw new Error("Libreria COCO-SSD non trovata");
            }
            if (typeof faceapi === 'undefined') {
                throw new Error("Libreria Face-API non trovata");
            }

            const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';

            const [cocoModel] = await Promise.all([
                cocoSsd.load(),
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
            ]);

            cocoSsdModel = cocoModel;
            faceApiModelLoaded = true;

            setStatusMessage(domElements.statusDiv, "Modelli AI pronti! Carica un'immagine.", "success");
            console.log("LOG: Tutti i modelli AI caricati con successo.");

            setTimeout(() => {
                if (domElements.statusDiv.textContent === "Modelli AI pronti! Carica un'immagine.") {
                    domElements.statusDiv.style.display = 'none';
                    domElements.dynamicFeedbackArea.style.display = 'none';
                }
            }, 3000);

        } catch (error) {
            console.error("ERRORE caricamento modelli:", error);
            setStatusMessage(domElements.statusDiv, `Errore: ${error.message}. Ricarica la pagina.`, "error");
        }
    }

    async function detectObjectsInImage(imageElement) {
        if (!cocoSsdModel) return [];
        try {
            const predictions = await cocoSsdModel.detect(imageElement);
            return predictions.filter(p => p.score > 0.55).map(p => objectTranslations[p.class] || p.class);
        } catch (e) {
            console.error("ERRORE COCO-SSD:", e);
            return [];
        }
    }

    async function analyzeFacesInImage(imageElement) {
        if (!faceApiModelLoaded) return [];
        try {
            const detections = await faceapi.detectAllFaces(imageElement, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceExpressions();

            return detections.map(d => {
                let dominantEmotion = "neutral";
                if (d.expressions) {
                    dominantEmotion = Object.keys(d.expressions).reduce((a, b) =>
                        d.expressions[a] > d.expressions[b] ? a : b
                    );
                }
                return emotionTranslations[dominantEmotion] || dominantEmotion;
            });
        } catch (e) {
            console.error("ERRORE FaceAPI:", e);
            return [];
        }
    }

    // ========== AI SUGGESTIONS ==========
    function applyAISuggestionsToUI() {
        console.log("🤖 DEBUG: Inizio applicazione suggerimenti AI");
        console.log("🤖 DEBUG: initialPreselectionDoneForCurrentImage =", initialPreselectionDoneForCurrentImage);
        console.log("🤖 DEBUG: imageAnalysisResults =", imageAnalysisResults);

        if (!imageAnalysisResults) {
            console.log("❌ DEBUG: Nessun risultato analisi disponibile");
            return;
        }

        // RESET di tutte le pill
        document.querySelectorAll('.checkbox-pill input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
            cb.closest('.checkbox-pill').classList.remove('selected');
        });
        console.log("🧹 DEBUG: Reset di tutte le pill completato");

        const aiSuggestions = {
            moods: [],
            genres: [],
            instruments: [],
            rhythms: [],
            bpm: 120
        };

        // LOGICA MOOD basata su emozioni
        if (imageAnalysisResults.emotions && imageAnalysisResults.emotions.length > 0) {
            const primaryEmotion = imageAnalysisResults.emotions.find(e => e !== "neutrale") || imageAnalysisResults.emotions[0];
            console.log("😊 DEBUG: Emozione primaria rilevata:", primaryEmotion);

            if (primaryEmotion === "felice") {
                aiSuggestions.moods.push("energico");
                aiSuggestions.bpm = 140;
            } else if (primaryEmotion === "triste") {
                aiSuggestions.moods.push("malinconico");
                aiSuggestions.bpm = 80;
            } else if (primaryEmotion === "arrabbiato/a") {
                aiSuggestions.moods.push("drammatico");
                aiSuggestions.bpm = 160;
            } else {
                aiSuggestions.moods.push("rilassante");
                aiSuggestions.bpm = 100;
            }
        } else {
            if (imageAnalysisResults.colors) {
                if (imageAnalysisResults.colors.averageBrightness > 170) {
                    aiSuggestions.moods.push("energico");
                    aiSuggestions.bpm = 130;
                } else if (imageAnalysisResults.colors.averageBrightness < 80) {
                    aiSuggestions.moods.push("misterioso");
                    aiSuggestions.bpm = 90;
                } else {
                    aiSuggestions.moods.push("rilassante");
                    aiSuggestions.bpm = 110;
                }
            } else {
                aiSuggestions.moods.push("rilassante");
            }
        }

        // LOGICA GENERE basata su oggetti
        if (imageAnalysisResults.objects && imageAnalysisResults.objects.length > 0) {
            console.log("🔍 DEBUG: Oggetti rilevati:", imageAnalysisResults.objects);

            if (imageAnalysisResults.objects.includes("persona")) {
                aiSuggestions.genres.push("folk");
            } else if (imageAnalysisResults.objects.some(o => ["auto", "città", "strada"].includes(o))) {
                aiSuggestions.genres.push("elettronica");
            } else if (imageAnalysisResults.objects.some(o => ["albero", "natura", "animale", "uccello"].includes(o))) {
                aiSuggestions.genres.push("ambient");
            } else {
                aiSuggestions.genres.push("pop");
            }
        } else {
            aiSuggestions.genres.push("ambient");
        }

        // LOGICA STRUMENTI basata su genere
        if (aiSuggestions.genres.includes("folk")) {
            aiSuggestions.instruments.push("chitarra acustica");
        } else if (aiSuggestions.genres.includes("elettronica")) {
            aiSuggestions.instruments.push("sintetizzatore");
        } else if (aiSuggestions.genres.includes("ambient")) {
            aiSuggestions.instruments.push("sintetizzatore");
        } else {
            aiSuggestions.instruments.push("pianoforte");
        }

        // LOGICA RITMO basata su BPM
        if (aiSuggestions.bpm >= 140) {
            aiSuggestions.rhythms.push("upbeat_energetic");
        } else if (aiSuggestions.bpm <= 90) {
            aiSuggestions.rhythms.push("slow_rhythm");
        } else {
            aiSuggestions.rhythms.push("moderate_groove");
        }

        console.log("✨ DEBUG: Suggerimenti AI generati:", aiSuggestions);

        // APPLICA I SUGGERIMENTI ALLE PILL
        let appliedCount = 0;

        aiSuggestions.moods.forEach(mood => {
            const checkbox = document.querySelector(`input[name="mood"][value="${mood}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.closest('.checkbox-pill').classList.add('selected');
                appliedCount++;
                console.log(`✅ DEBUG: Applicato mood: ${mood}`);
            }
        });

        aiSuggestions.genres.forEach(genre => {
            const checkbox = document.querySelector(`input[name="genre"][value="${genre}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.closest('.checkbox-pill').classList.add('selected');
                appliedCount++;
                console.log(`✅ DEBUG: Applicato genere: ${genre}`);
            }
        });

        aiSuggestions.instruments.forEach(instrument => {
            const checkbox = document.querySelector(`input[name="instrument"][value="${instrument}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.closest('.checkbox-pill').classList.add('selected');
                appliedCount++;
                console.log(`✅ DEBUG: Applicato strumento: ${instrument}`);
            }
        });

        aiSuggestions.rhythms.forEach(rhythm => {
            const checkbox = document.querySelector(`input[name="rhythm"][value="${rhythm}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.closest('.checkbox-pill').classList.add('selected');
                appliedCount++;
                console.log(`✅ DEBUG: Applicato ritmo: ${rhythm}`);
            }
        });

        // Applica BPM
        if (domElements.bpmSlider && aiSuggestions.bpm !== 120) {
            domElements.bpmSlider.value = aiSuggestions.bpm;
            if (domElements.bpmValueDisplay) {
                domElements.bpmValueDisplay.textContent = aiSuggestions.bpm;
            }
            console.log(`✅ DEBUG: Applicato BPM: ${aiSuggestions.bpm}`);
        }

        initialPreselectionDoneForCurrentImage = true;
        console.log(`🎯 DEBUG: Applicati ${appliedCount} suggerimenti in totale`);
        console.log("✅ DEBUG: Pre-selezioni AI completate");
    }

    // ========== PROMPT GENERATION FUNCTIONS ==========
    function generateStableAudioPrompt(parsedData) {
        console.log("🎵 Generazione prompt per AI con selezioni multiple");
        console.log("📊 DEBUG: Dati ricevuti:", parsedData);

        let parts = [];

        // ✅ GESTIONE MULTIPLE MOODS
        if (parsedData.moods && parsedData.moods.length > 0) {
            console.log("😊 DEBUG: Moods selezionati:", parsedData.moods);
            parsedData.moods.forEach(mood => {
                const englishMood = cueTranslationsITtoEN.mood[mood] || mood;
                if (englishMood) {
                    parts.push(englishMood);
                    console.log(`  ✅ Aggiunto mood: ${mood} -> ${englishMood}`);
                }
            });
        }

        // ✅ GESTIONE MULTIPLE GENRES  
        if (parsedData.genres && parsedData.genres.length > 0) {
            console.log("🎼 DEBUG: Generi selezionati:", parsedData.genres);
            const englishGenres = [];
            parsedData.genres.forEach(genre => {
                const englishGenre = cueTranslationsITtoEN.genre[genre] || genre;
                if (englishGenre) {
                    englishGenres.push(englishGenre);
                    console.log(`  ✅ Aggiunto genere: ${genre} -> ${englishGenre}`);
                }
            });

            if (englishGenres.length === 1) {
                parts.push(englishGenres[0]);
            } else if (englishGenres.length === 2) {
                parts.push(englishGenres.join("-") + " fusion");
            } else if (englishGenres.length > 2) {
                parts.push("multi-genre featuring " + englishGenres.slice(0, 3).join(", "));
            }
        }

        // ✅ GESTIONE MULTIPLE INSTRUMENTS
        if (parsedData.instruments && parsedData.instruments.length > 0) {
            console.log("🎸 DEBUG: Strumenti selezionati:", parsedData.instruments);
            const englishInstruments = [];
            parsedData.instruments.forEach(instrument => {
                const englishInstrument = cueTranslationsITtoEN.instrument[instrument] || instrument;
                if (englishInstrument) {
                    englishInstruments.push(englishInstrument);
                    console.log(`  ✅ Aggiunto strumento: ${instrument} -> ${englishInstrument}`);
                }
            });

            if (englishInstruments.length === 1) {
                parts.push("with " + englishInstruments[0]);
            } else if (englishInstruments.length === 2) {
                parts.push("featuring " + englishInstruments.join(" and "));
            } else if (englishInstruments.length >= 3) {
                const lastInstrument = englishInstruments.pop();
                parts.push("featuring " + englishInstruments.join(", ") + " and " + lastInstrument);
            }
        }

        // ✅ GESTIONE MULTIPLE RHYTHMS
        if (parsedData.rhythms && parsedData.rhythms.length > 0) {
            console.log("🥁 DEBUG: Ritmi selezionati:", parsedData.rhythms);
            const rhythm = parsedData.rhythms[parsedData.rhythms.length - 1];
            const englishRhythm = cueTranslationsITtoEN.rhythm[rhythm] || rhythm;
            if (englishRhythm) {
                parts.push(englishRhythm);
                console.log(`  ✅ Aggiunto ritmo: ${rhythm} -> ${englishRhythm}`);
            }
        }

        // ✅ BPM
        if (parsedData.tempoBPM) {
            parts.push(parsedData.tempoBPM + " BPM");
            console.log(`  ✅ Aggiunto BPM: ${parsedData.tempoBPM}`);
        }

        parts.push("high quality");

        const finalPrompt = parts.join(", ");
        console.log("🎵 DEBUG: Prompt finale completo:", finalPrompt);

        return finalPrompt;
    }

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
            keywords: []
        };

        // Auto-suggestion if no user input
        if (!initialPreselectionDoneForCurrentImage) {
            console.log("🔍 Auto-suggestion attiva");

            if (cues.moods.length === 0 && detectedEmotions && detectedEmotions.length > 0) {
                const primaryEmotion = detectedEmotions.find(e => e !== "neutrale") || detectedEmotions[0];
                if (primaryEmotion === "felice") cues.moods.push("energico");
                else if (primaryEmotion === "triste") cues.moods.push("malinconico");
                else if (primaryEmotion === "arrabbiato/a") cues.moods.push("drammatico");
                else cues.moods.push("rilassante");
            }

            if (cues.genres.length === 0 && detectedObjects && detectedObjects.length > 0) {
                if (detectedObjects.includes("persona")) cues.genres.push("folk");
                else if (detectedObjects.some(o => ["albero", "natura", "animale"].includes(o))) cues.genres.push("ambient");
                else if (detectedObjects.some(o => ["auto", "città", "strada"].includes(o))) cues.genres.push("elettronica");
                else cues.genres.push("ambient");
            }

            if (cues.instruments.length === 0) {
                if (cues.genres.includes("folk")) cues.instruments.push("chitarra acustica");
                else if (cues.genres.includes("elettronica")) cues.instruments.push("sintetizzatore");
                else cues.instruments.push("pianoforte");
            }

            if (cues.rhythms.length === 0) {
                if (cues.moods.includes("energico")) cues.rhythms.push("upbeat_energetic");
                else if (cues.moods.includes("rilassante")) cues.rhythms.push("slow_rhythm");
                else cues.rhythms.push("moderate_groove");
            }
        }

        return cues;
    }

    // ========== FUNZIONE CHIAVE MODIFICATA ==========
    function generateAIDisplayContent(analysis, detectedObjectsList, detectedEmotionsList, userInputs, finalStablePrompt) {
        console.log("🔄 DEBUG: Aggiornamento contenuto AI separato");

        // ========== PARTE 1: INTERPRETAZIONE MUSICALE (SEMPRE VISIBILE) ==========
        let interpretationHtml = `
        <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
                    border-radius: 12px; 
                    padding: 20px; 
                    margin: 15px 0; 
                    border-left: 5px solid #007cba;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h4 style="color: #007cba; margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px;">
                🎵 <span>Interpretazione Musicale</span>
            </h4>
            <div style="display: grid; gap: 10px;">`;

        if (userInputs.selectedMoods && userInputs.selectedMoods.length > 0) {
            interpretationHtml += `<div><strong>🎭 Mood:</strong> <span style="color: #6c757d;">${userInputs.selectedMoods.join(", ")}</span></div>`;
        }
        if (userInputs.selectedGenres && userInputs.selectedGenres.length > 0) {
            interpretationHtml += `<div><strong>🎼 Generi:</strong> <span style="color: #6c757d;">${userInputs.selectedGenres.join(", ")}</span></div>`;
        }
        if (userInputs.selectedInstruments && userInputs.selectedInstruments.length > 0) {
            interpretationHtml += `<div><strong>🎸 Strumenti:</strong> <span style="color: #6c757d;">${userInputs.selectedInstruments.join(", ")}</span></div>`;
        }
        if (userInputs.selectedRhythms && userInputs.selectedRhythms.length > 0) {
            interpretationHtml += `<div><strong>🥁 Ritmo:</strong> <span style="color: #6c757d;">${userInputs.selectedRhythms.join(", ")}</span></div>`;
        }

        interpretationHtml += `<div><strong>⏱️ Tempo:</strong> <span style="color: #6c757d;">${userInputs.selectedBPM} BPM</span></div>`;
        interpretationHtml += `</div></div>`;

        // INSERISCI L'INTERPRETAZIONE NELL'ELEMENTO SEMPRE VISIBILE
        if (domElements.aiProcessingSimulationDiv) {
            domElements.aiProcessingSimulationDiv.innerHTML = interpretationHtml;
            domElements.aiProcessingSimulationDiv.style.display = 'block';
            console.log("✅ DEBUG: Interpretazione musicale inserita (sempre visibile)");
        }

        // ========== PARTE 2: DETTAGLI TECNICI (NELL'ACCORDION) ==========
        let analysisContentHTML = "<h4>Analisi Tecnica Immagine:</h4><ul>";
        analysisContentHTML += `<li><strong>Oggetti Rilevati:</strong> ${detectedObjectsList && detectedObjectsList.length > 0 ? detectedObjectsList.join(", ") : "Nessuno"}</li>`;
        analysisContentHTML += `<li><strong>Emozioni Percepite:</strong> ${detectedEmotionsList && detectedEmotionsList.length > 0 ? detectedEmotionsList.join(", ") : "Nessuna"}</li>`;

        if (analysis) {
            let brightnessDesc = "Media";
            if (analysis.averageBrightness < 80) brightnessDesc = "Bassa (scena scura)";
            else if (analysis.averageBrightness > 170) brightnessDesc = "Alta (scena luminosa)";
            analysisContentHTML += `<li><strong>Luminosità:</strong> ${brightnessDesc}</li>`;

            let contrastDesc = "Medio";
            if (analysis.contrast < 30) contrastDesc = "Basso";
            else if (analysis.contrast > 70) contrastDesc = "Alto";
            analysisContentHTML += `<li><strong>Contrasto:</strong> ${contrastDesc}</li>`;

            if (analysis.dominantColors && analysis.dominantColors.length > 0) {
                analysisContentHTML += "<li><strong>Colori Dominanti:</strong><ul>";
                analysis.dominantColors.forEach(c => {
                    analysisContentHTML += `<li><span class="color-swatch-inline" style="background-color: rgb(${c.r},${c.g},${c.b});"></span>RGB(${c.r},${c.g},${c.b}) - ${c.percentage.toFixed(0)}%</li>`;
                });
                analysisContentHTML += "</ul></li>";
            }
        }
        analysisContentHTML += "</ul>";

        analysisContentHTML += `<div id="finalPromptForAI" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; font-family: monospace;">
        <strong style="color: #495057; font-size: 14px;">🤖 Prompt finale per AI:</strong><br>
        <span style="color: #28a745; font-weight: 500; font-size: 13px; line-height: 1.4; display: block; margin-top: 8px;">${finalStablePrompt}</span>
        </div>`;

        // INSERISCI I DETTAGLI TECNICI NELL'ACCORDION
        domElements.aiInsightsContent.innerHTML = analysisContentHTML;
        console.log("✅ DEBUG: Dettagli tecnici inseriti (accordion)");
    }

    async function updateAIDisplayAndStablePrompt() {
        if (!currentImage || !imageAnalysisResults) return;

        const selectedMoods = getSelectedCheckboxValues('mood');
        const selectedGenres = getSelectedCheckboxValues('genre');
        const selectedInstruments = getSelectedCheckboxValues('instrument');
        const selectedRhythms = getSelectedCheckboxValues('rhythm');
        const selectedBPM = domElements.bpmSlider?.value || 120;

        const userInputs = {
            selectedMoods,
            selectedGenres,
            selectedInstruments,
            selectedRhythms,
            selectedBPM
        };

        const tempParsedCues = getMusicalCues(
            imageAnalysisResults.colors,
            imageAnalysisResults.objects,
            imageAnalysisResults.emotions,
            CREATIVITY_LEVEL,
            userInputs
        );

        stableAudioPromptForMusic = generateStableAudioPrompt(tempParsedCues);

        generateAIDisplayContent(
            imageAnalysisResults.colors,
            imageAnalysisResults.objects,
            imageAnalysisResults.emotions,
            userInputs,
            stableAudioPromptForMusic
        );

        domElements.aiInsightsSection.style.display = 'block';
    }

    // ========== IMAGE PROCESSING ==========
    function processImage(imageSrc) {
        console.log("LOG: Inizio processImage");
        domElements.imagePreview.src = imageSrc;
        currentImageSrc = imageSrc;
        domElements.imagePreview.style.display = 'block';
        imageAnalysisResults = null;
        initialPreselectionDoneForCurrentImage = false;

        domElements.generateMusicButton.disabled = true;
        domElements.aiInsightsSection.style.display = 'none';
        if (domElements.detailsAccordionHeader) domElements.detailsAccordionHeader.classList.remove('open');
        if (domElements.aiInsightsContent) domElements.aiInsightsContent.style.display = 'none';

        document.querySelectorAll('.checkbox-pill input[type="checkbox"]:checked').forEach(cb => {
            cb.checked = false;
            cb.closest('.checkbox-pill').classList.remove('selected');
        });

        currentImage = new Image();
        currentImage.onload = async () => {
            console.log("LOG: Immagine caricata, inizio analisi completa");

            setStatusMessage(domElements.statusDiv, "Analisi immagine in corso...", "info");
            updateProgressMessage("Analisi immagine in corso...", true);
            startAISimulationText();

            try {
                const colorAnalysis = analyzeImageAdvanced(currentImage);
                const objects = await detectObjectsInImage(currentImage);
                const emotions = await analyzeFacesInImage(currentImage);

                imageAnalysisResults = {
                    colors: colorAnalysis,
                    objects: objects,
                    emotions: emotions
                };

                console.log("LOG: Analisi completata", imageAnalysisResults);

                applyAISuggestionsToUI();
                await updateAIDisplayAndStablePrompt();

                stopAISimulationText();
                updateProgressMessage("", false);
                setStatusMessage(domElements.statusDiv, "Analisi completata! Personalizza e genera.", "success");
                domElements.generateMusicButton.disabled = false;

            } catch (error) {
                console.error("Errore analisi:", error);
                stopAISimulationText();
                updateProgressMessage("", false);
                setStatusMessage(domElements.statusDiv, "Errore analisi. Puoi comunque generare.", "warn");
                domElements.generateMusicButton.disabled = false;
            }
        };

        currentImage.onerror = () => {
            console.error("ERRORE: Errore caricamento immagine");
            setStatusMessage(domElements.statusDiv, "Errore caricamento immagine.", "error");
            domElements.generateMusicButton.disabled = true;
        };

        currentImage.src = imageSrc;
    }

    // ========== EVENT LISTENERS ==========
    domElements.imageUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => processImage(e.target.result);
            reader.readAsDataURL(file);
        }
    });

    // ========== MODIFICA ALLA FUNZIONE DEL PULSANTE GENERA MUSICA ==========
    // Sostituisci la parte del click del generateMusicButton con questa:

    domElements.generateMusicButton.addEventListener('click', async () => {
        console.log("LOG: Click genera musica");

        if (!currentImage) {
            setStatusMessage(domElements.statusDiv, "Carica prima un'immagine!", "error");
            return;
        }

        if (!stableAudioPromptForMusic || stableAudioPromptForMusic === "") {
            applyAISuggestionsToUI();
            await updateAIDisplayAndStablePrompt();
        }

        // ========== PULIZIA MESSAGGI E PLAYER PRECEDENTI ==========
        domElements.generateMusicButton.disabled = true;
        domElements.musicSpinner.style.display = 'inline-block';

        // NASCONDI AUDIO PLAYER E DOWNLOAD PRECEDENTI
        if (domElements.audioPlayerContainer) domElements.audioPlayerContainer.style.display = 'none';
        if (domElements.downloadAudioLink) domElements.downloadAudioLink.style.display = 'none';
        if (domElements.downloadCompositeImageLink) domElements.downloadCompositeImageLink.style.display = 'none';
        if (domElements.downloadQrOnlyLink) domElements.downloadQrOnlyLink.style.display = 'none';

        // MOSTRA SOLO IL MESSAGGIO DI STATO DELLA GENERAZIONE
        setStatusMessage(domElements.statusDiv, "Generazione musica in corso...", "info");
        updateProgressMessage("Generazione traccia audio in corso...", true);
        domElements.dynamicFeedbackArea.style.display = 'block';

        try {
            const duration = document.querySelector('input[name="musicDuration"]:checked')?.value || "40";
            const trackTitle = document.getElementById('trackName')?.value || '';

            console.log("LOG: Invio richiesta con prompt:", stableAudioPromptForMusic);
            console.log("LOG: Invio titolo brano:", trackTitle);

            const response = await jQuery.ajax({
                url: pictosound_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'pictosound_generate_music',
                    prompt: stableAudioPromptForMusic,
                    duration: duration,
                    image_data: currentImageSrc,
                    nonce: pictosound_vars.nonce_generate || '',
                    title: trackTitle
                }
            });

            console.log("LOG: Risposta ricevuta:", response);

            if (response.success && response.data.audioUrl) {
                updateProgressMessage("", false);
                setStatusMessage(domElements.statusDiv, "", "info");
                domElements.dynamicFeedbackArea.style.display = 'none';

                domElements.audioPlayer.src = response.data.audioUrl;
                domElements.audioPlayerContainer.style.display = 'block';
                domElements.progressAndPlayerContainer.style.display = 'block';

                if (domElements.downloadAudioLink) {
                    domElements.downloadAudioLink.href = response.data.downloadUrl || response.data.audioUrl;
                    domElements.downloadAudioLink.style.display = 'inline-flex';
                }

                console.log("✅ Musica generata e player attivato");

            } else {
                throw new Error(response.data?.error || 'Errore nella generazione musicale.');
            }

        } catch (error) {
            console.error("ERRORE generazione:", error);
            updateProgressMessage("", false);

            // ⚡ GESTIONE SPECIFICA PER ERRORE 403 (NON LOGGATO)
            if (error.status === 403 || (error.responseJSON && error.responseJSON.data && error.responseJSON.data.error && error.responseJSON.data.error.includes('registrato'))) {

                // Nascondi tutti i messaggi di errore
                setStatusMessage(domElements.statusDiv, "", "info");
                domElements.dynamicFeedbackArea.style.display = 'none';

                // Forza la visualizzazione del prompt di login
                console.log("👤 Utente non loggato rilevato, mostro prompt di registrazione");
                updateUserAccessUI(); // Questa funzione mostrerà il prompt

                // Scroll verso il prompt per assicurarsi che sia visibile
                setTimeout(() => {
                    const loginPrompt = document.getElementById('loginOrRegisterPrompt');
                    if (loginPrompt && loginPrompt.style.display !== 'none') {
                        loginPrompt.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 500);

            } else {
                // Gestione errori normali
                let errorMessage = error.message || 'Errore sconosciuto.';
                if (error.responseText) {
                    errorMessage = "Errore del server. Controlla la console del browser per i dettagli.";
                }

                setStatusMessage(domElements.statusDiv, `Errore: ${errorMessage}`, "error");

                setTimeout(() => {
                    if (domElements.statusDiv.textContent.includes("Errore:")) {
                        setStatusMessage(domElements.statusDiv, "", "info");
                        domElements.dynamicFeedbackArea.style.display = 'none';
                    }
                }, 5000);
            }
        } finally {
            domElements.generateMusicButton.disabled = false;
            domElements.musicSpinner.style.display = 'none';
        }
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

    if (domElements.detailsAccordionHeader && domElements.aiInsightsContent) {
        domElements.detailsAccordionHeader.addEventListener('click', () => {
            const isOpen = domElements.detailsAccordionHeader.classList.toggle('open');
            domElements.aiInsightsContent.style.display = isOpen ? 'block' : 'none';
        });
    }

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

    // ========== INITIALIZATION ==========
    populateCheckboxPills(domElements.moodPillsContainer, moodItems, 'mood');
    populateCheckboxPills(domElements.genrePillsContainer, genreItems, 'genre');
    populateCheckboxPills(domElements.instrumentPillsContainer, instrumentItems, 'instrument');
    populateCheckboxPills(domElements.rhythmPillsContainer, rhythmItems, 'rhythm');

    if (window.CameraHandler) {
        console.log("LOG: Inizializzazione CameraHandler...");
        const cameraInitSuccess = CameraHandler.init(
            domElements,
            processImage,
            (message, type = "info") => setStatusMessage(domElements.statusDiv, message, type)
        );

        if (cameraInitSuccess) {
            console.log("LOG: ✅ CameraHandler inizializzato con successo");
        } else {
            console.error("ERRORE: ❌ Inizializzazione CameraHandler fallita");
        }
    } else {
        console.warn("WARN: ⚠️ CameraHandler non trovato - verifica che camera-handler.js sia caricato");
    }

    window.addEventListener('beforeunload', () => {
        if (window.CameraHandler) {
            CameraHandler.cleanup();
        }
    });

    loadModels();

    if (typeof pictosound_vars !== 'undefined') {
        console.log("LOG: pictosound_vars disponibile:", pictosound_vars);
    }
    if (typeof pictosound_vars !== 'undefined' && !pictosound_vars.is_user_logged_in) {
        console.log("👤 Utente non loggato rilevato al caricamento pagina");

        // Forza la visualizzazione del prompt immediatamente
        setTimeout(() => {
            updateUserAccessUI();

            // Disabilita il pulsante genera musica
            const generateButton = document.getElementById('generateMusicButton');
            if (generateButton) {
                generateButton.disabled = true;
            }

            // Aggiungi event listener per mostrare sempre il prompt quando cambia durata
            document.querySelectorAll('input[name="musicDuration"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    console.log("👤 Utente non loggato ha cambiato durata, mostro prompt");
                    updateUserAccessUI();
                });
            });

        }, 200);
    }
});