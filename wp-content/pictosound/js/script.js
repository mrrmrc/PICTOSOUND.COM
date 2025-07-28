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
        { value: "felice", label: "Felice" },
        { value: "triste", label: "Triste" },
        { value: "riflessivo", label: "Riflessivo" },
        { value: "epico", label: "Epico" },
        { value: "rilassante", label: "Rilassante" },
        { value: "energico", label: "Energico" },
        { value: "misterioso", label: "Misterioso" },
        { value: "sognante", label: "Sognante" },
        { value: "romantico", label: "Romantico" },
        { value: "drammatico", label: "Drammatico" },
        { value: "futuristico", label: "Futuristico" },
        { value: "nostalgico", label: "Nostalgico" },
        { value: "potente", label: "Potente" }
    ];

    const genreItems = [
        { value: "elettronica", label: "Elettronica" },
        { value: "rock", label: "Rock" },
        { value: "pop", label: "Pop" },
        { value: "jazz", label: "Jazz" },
        { value: "classica", label: "Classica" },
        { value: "ambient", label: "Ambient" },
        { value: "soundtrack", label: "Soundtrack" },
        { value: "folk", label: "Folk" },
        { value: "lo-fi", label: "Lo-fi" },
        { value: "hip-hop", label: "Hip Hop" }
    ];

    const instrumentItems = [
        { value: "pianoforte", label: "Pianoforte" },
        { value: "chitarra acustica", label: "Chitarra" },
        { value: "chitarra elettrica", label: "Chitarra Elettrica" },
        { value: "basso", label: "Basso" },
        { value: "batteria", label: "Batteria" },
        { value: "violino", label: "Violino" },
        { value: "sintetizzatore", label: "Sintetizzatore" }
    ];

    const rhythmItems = [
        { value: "no_rhythm", label: "Nessun ritmo" },
        { value: "slow_rhythm", label: "Ritmo Lento" },
        { value: "moderate_groove", label: "Ritmo Moderato" },
        { value: "upbeat_energetic", label: "Ritmo Energico" }
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

    // ========== SISTEMA DI ATTESA AMICHEVOLE ==========
    let waitingInterval = null;
    let waitingStartTime = null;
    let currentWaitingStep = 0;

    const waitingSteps = [
        {
            message: "🎵 Inizializzazione AI musicale...",
            tip: "L'intelligenza artificiale sta analizzando la tua immagine per capire l'atmosfera perfetta!",
            duration: 3000
        },
        {
            message: "🎨 Conversione visiva in suoni...",
            tip: "Stiamo trasformando i colori, le forme e le emozioni in note musicali!",
            duration: 4000
        },
        {
            message: "🎹 Composizione melodica in corso...",
            tip: "L'AI sta scegliendo gli strumenti e creando la struttura del brano!",
            duration: 5000
        },
        {
            message: "🎼 Arrangiamento e produzione...",
            tip: "Stiamo aggiungendo armonie, ritmi e effetti per rendere unico il tuo brano!",
            duration: 4000
        },
        {
            message: "🎧 Finalizzazione audio...",
            tip: "Ultimi ritocchi per garantire la massima qualità audio!",
            duration: 3000
        },
        {
            message: "✨ Creazione quasi completata...",
            tip: "Il tuo brano personalizzato è quasi pronto per essere ascoltato!",
            duration: 2000
        }
    ];

    const musicalTips = [
        "💡 Ogni colore ha una sua 'temperatura' musicale: i colori caldi tendono a generare melodie più energiche!",
        "🎯 L'AI analizza anche le forme: linee curve creano melodie fluide, forme geometriche ritmi più definiti!",
        "🌈 La saturazione dei colori influenza l'intensità degli strumenti nella tua composizione!",
        "🎨 Le immagini con molti dettagli spesso producono arrangiamenti più complessi e ricchi!",
        "🎵 La posizione degli elementi nell'immagine può influenzare la progressione musicale!",
        "🔊 L'AI può riconoscere oltre 80 oggetti diversi e ognuno contribuisce al mood musicale!",
        "⏱️ La generazione di musica AI richiede circa 20-40 secondi per creare un brano unico!",
        "🎼 Ogni brano generato è completamente originale e non esiste da nessun'altra parte!"
    ];

    function startFriendlyWaiting() {
        waitingStartTime = Date.now();
        currentWaitingStep = 0;

        // Crea il container dell'attesa amichevole
        const waitingContainer = document.createElement('div');
        waitingContainer.id = 'friendly-waiting-container';
        waitingContainer.style.cssText = `
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin: 20px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        `;

        // Pattern di sfondo animato
        const pattern = document.createElement('div');
        pattern.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/><circle cx="20" cy="20" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="30" r="1.5" fill="white" opacity="0.1"/><circle cx="30" cy="80" r="1" fill="white" opacity="0.1"/><circle cx="70" cy="70" r="1.5" fill="white" opacity="0.1"/></svg>') repeat;
            animation: patternMove 20s linear infinite;
            opacity: 0.3;
        `;
        waitingContainer.appendChild(pattern);

        // Contenuto principale
        const content = document.createElement('div');
        content.style.cssText = `
            position: relative;
            z-index: 2;
        `;

        // Icona musicale animata
        const musicIcon = document.createElement('div');
        musicIcon.id = 'music-icon';
        musicIcon.style.cssText = `
            font-size: 4rem;
            margin-bottom: 20px;
            animation: musicBounce 2s ease-in-out infinite;
        `;
        musicIcon.textContent = '🎵';
        content.appendChild(musicIcon);

        // Messaggio principale
        const mainMessage = document.createElement('div');
        mainMessage.id = 'waiting-main-message';
        mainMessage.style.cssText = `
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            min-height: 2em;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        content.appendChild(mainMessage);

        // Tip educativo
        const tipMessage = document.createElement('div');
        tipMessage.id = 'waiting-tip-message';
        tipMessage.style.cssText = `
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 25px;
            line-height: 1.5;
            min-height: 3em;
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        `;
        content.appendChild(tipMessage);

        // Progress bar creativa
        const progressContainer = document.createElement('div');
        progressContainer.style.cssText = `
            background: rgba(255, 255, 255, 0.2);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
        `;

        const progressBar = document.createElement('div');
        progressBar.id = 'creative-progress-bar';
        progressBar.style.cssText = `
            background: linear-gradient(90deg, #ffd700, #ff6b6b, #4ecdc4, #45b7d1);
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: width 0.3s ease;
            background-size: 200% 100%;
            animation: progressGradient 3s ease-in-out infinite;
        `;
        progressContainer.appendChild(progressBar);
        content.appendChild(progressContainer);

        // Timer e stima
        const timeInfo = document.createElement('div');
        timeInfo.id = 'waiting-time-info';
        timeInfo.style.cssText = `
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 15px;
        `;
        content.appendChild(timeInfo);

        // Curiosità musicale
        const musicalCuriosity = document.createElement('div');
        musicalCuriosity.id = 'musical-curiosity';
        musicalCuriosity.style.cssText = `
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            line-height: 1.4;
            border-left: 4px solid #ffd700;
            margin-top: 20px;
        `;
        content.appendChild(musicalCuriosity);

        waitingContainer.appendChild(content);

        // Sostituisci il contenuto del feedback area
        domElements.dynamicFeedbackArea.innerHTML = '';
        domElements.dynamicFeedbackArea.appendChild(waitingContainer);

        // Aggiungi CSS per le animazioni
        addWaitingAnimations();

        // Avvia il ciclo di aggiornamento
        updateWaitingContent();
        waitingInterval = setInterval(updateWaitingContent, 1000);
    }

    function updateWaitingContent() {
        const elapsedTime = Date.now() - waitingStartTime;
        const elapsedSeconds = Math.floor(elapsedTime / 1000);

        // Aggiorna il contenuto basato sul tempo trascorso
        const mainMessage = document.getElementById('waiting-main-message');
        const tipMessage = document.getElementById('waiting-tip-message');
        const timeInfo = document.getElementById('waiting-time-info');
        const progressBar = document.getElementById('creative-progress-bar');
        const curiosity = document.getElementById('musical-curiosity');

        if (!mainMessage) return;

        // Calcola quale step mostrare
        let totalDuration = 0;
        let currentStep = 0;

        for (let i = 0; i < waitingSteps.length; i++) {
            if (elapsedTime >= totalDuration && elapsedTime < totalDuration + waitingSteps[i].duration) {
                currentStep = i;
                break;
            }
            totalDuration += waitingSteps[i].duration;
            if (i === waitingSteps.length - 1) {
                currentStep = i; // Resta sull'ultimo step
            }
        }

        // Aggiorna contenuto
        const step = waitingSteps[currentStep];
        mainMessage.textContent = step.message;
        tipMessage.textContent = step.tip;

        // Aggiorna progress bar (stima 30 secondi totali)
        const estimatedTotal = 30000; // 30 secondi
        const progress = Math.min((elapsedTime / estimatedTotal) * 100, 95);
        progressBar.style.width = progress + '%';

        // Aggiorna timer
        const estimatedRemaining = Math.max(0, Math.ceil((estimatedTotal - elapsedTime) / 1000));
        if (estimatedRemaining > 0) {
            timeInfo.textContent = `⏱️ Tempo trascorso: ${elapsedSeconds}s | Stima completamento: ~${estimatedRemaining}s`;
        } else {
            timeInfo.textContent = `⏱️ Tempo trascorso: ${elapsedSeconds}s | Finalizzazione in corso...`;
        }

        // Mostra curiosità ogni 8 secondi
        if (elapsedSeconds > 0 && elapsedSeconds % 8 === 0) {
            const randomTip = musicalTips[Math.floor(Math.random() * musicalTips.length)];
            curiosity.textContent = randomTip;
            curiosity.style.animation = 'fadeInUp 0.5s ease-out';
        }
    }

    function stopFriendlyWaiting() {
        if (waitingInterval) {
            clearInterval(waitingInterval);
            waitingInterval = null;
        }

        // Mostra messaggio di completamento
        const container = document.getElementById('friendly-waiting-container');
        if (container) {
            container.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            container.innerHTML = `
                <div style="position: relative; z-index: 2;">
                    <div style="font-size: 3rem; margin-bottom: 15px; animation: successPulse 1s ease-out;">🎉</div>
                    <div style="font-size: 1.4rem; font-weight: 600; margin-bottom: 10px;">🎵 Creazione Completata!</div>
                    <div style="font-size: 1rem; opacity: 0.9;">Il tuo brano personalizzato è pronto per essere ascoltato!</div>
                </div>
            `;

            // Rimuovi dopo 2 secondi
            setTimeout(() => {
                if (container.parentNode) {
                    container.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(() => {
                        container.remove();
                    }, 500);
                }
            }, 2000);
        }
    }

    function addWaitingAnimations() {
        if (document.getElementById('waiting-animations')) return;

        const style = document.createElement('style');
        style.id = 'waiting-animations';
        style.textContent = `
            @keyframes musicBounce {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                25% { transform: translateY(-10px) rotate(-5deg); }
                50% { transform: translateY(-5px) rotate(0deg); }
                75% { transform: translateY(-8px) rotate(5deg); }
            }
            
            @keyframes progressGradient {
                0%, 100% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
            }
            
            @keyframes patternMove {
                0% { transform: translateX(0px) translateY(0px); }
                100% { transform: translateX(100px) translateY(100px); }
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes successPulse {
                0% { transform: scale(0.8); opacity: 0; }
                50% { transform: scale(1.1); opacity: 1; }
                100% { transform: scale(1); opacity: 1; }
            }
            
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
        document.head.appendChild(style);
    }
    function updateGenerationsArchive() {
        const archiveContainer = document.getElementById('pictosound-archive-container');

        if (!archiveContainer) {
            console.log("📁 Archivio non trovato in questa pagina, reindirizzo alla home");

            // Se non c'è l'archivio, reindirizza alla home
            const archiveUrl = '/'; // Home page

            // Mostra notifica di successo prima del reindirizzamento
            showSuccessNotification("🎵 Musica generata con successo! Ti stiamo portando alla home...");

            setTimeout(() => {
                window.location.href = archiveUrl;
            }, 2000);

            return;
        }

        console.log("📁 Archivio trovato, aggiorno dinamicamente...");

        // Mostra notifica di aggiornamento
        showSuccessNotification("🎵 Musica generata! Aggiornamento archivio in corso...");

        // Trova il wrapper dell'archivio
        const archiveWrapper = archiveContainer.querySelector('.pictosound-generations-archive-wrapper');
        if (!archiveWrapper) {
            console.warn("⚠️ Wrapper archivio non trovato, ricarico la pagina");
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            return;
        }

        // Aggiungi indicatore di caricamento
        const loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'ps-loading-update';
        loadingIndicator.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            text-align: center;
            font-family: system-ui, sans-serif;
            font-size: 1rem;
            color: #667eea;
            font-weight: 500;
        `;
        loadingIndicator.innerHTML = `
            <div style="margin-bottom: 10px;">
                <div class="spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            </div>
            <div>📁 Aggiornamento archivio...</div>
        `;
        document.body.appendChild(loadingIndicator);

        // Ricarica il contenuto dell'archivio via AJAX
        const currentUrl = window.location.href;

        fetch(currentUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.text())
            .then(html => {
                // Crea un elemento temporaneo per parsare l'HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;

                // Trova il nuovo contenuto dell'archivio
                const newArchiveContainer = tempDiv.querySelector('#pictosound-archive-container');

                if (newArchiveContainer) {
                    // Sostituisci il contenuto dell'archivio
                    archiveContainer.innerHTML = newArchiveContainer.innerHTML;

                    console.log("✅ Archivio aggiornato con successo!");

                    // Rimuovi indicatore di caricamento
                    setTimeout(() => {
                        const loader = document.getElementById('ps-loading-update');
                        if (loader) loader.remove();
                    }, 500);

                    // Mostra notifica di successo
                    setTimeout(() => {
                        showSuccessNotification("🎵 Nuovo brano aggiunto al tuo archivio!");
                    }, 800);

                    // Scorri verso l'archivio per mostrare il nuovo brano
                    setTimeout(() => {
                        archiveContainer.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 1000);

                } else {
                    console.error("❌ Nuovo contenuto archivio non trovato, ricarico la pagina");
                    // Fallback: ricarica la pagina
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error("❌ Errore aggiornamento archivio:", error);

                // Rimuovi indicatore di caricamento
                const loader = document.getElementById('ps-loading-update');
                if (loader) loader.remove();

                // Fallback: ricarica la pagina
                showSuccessNotification("🎵 Musica generata! Ricaricamento pagina...");
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            });
    }

    // Funzione per mostrare notifiche di successo
    function showSuccessNotification(message) {
        // Rimuovi notifiche esistenti
        const existingNotification = document.querySelector('.ps-success-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = 'ps-success-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            z-index: 10000;
            font-family: system-ui, sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            max-width: 350px;
            animation: ps-notification-slide 0.4s ease-out;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Rimuovi dopo 4 secondi
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'ps-notification-slide 0.3s ease-in reverse';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 4000);
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

    // 🔧 FIX BRUTALE: Intercetta OGNI modifica all'immagine preview
    // Controlla ogni 500ms se c'è una nuova immagine da processare
    let lastProcessedImage = null;

    function checkForNewImage() {
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview && imagePreview.src && !imagePreview.src.includes('#')) {
            const currentSrc = imagePreview.src;

            // Se è una nuova immagine diversa dall'ultima processata
            if (currentSrc !== lastProcessedImage && currentSrc.length > 100) {
                console.log("📸 NUOVA IMMAGINE RILEVATA! Avvio processImage()");
                lastProcessedImage = currentSrc;

                // Processa l'immagine
                processImage(currentSrc);
            }
        }
    }

    // Controlla ogni 500ms
    setInterval(checkForNewImage, 500);

    // Controlla anche subito
    setTimeout(checkForNewImage, 1000);

    console.log("📸 Sistema di rilevamento immagini ATTIVATO (controllo ogni 500ms)");

    // ========== GENERATE MUSIC BUTTON ==========
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

        // 🎵 NUOVO: Sistema di attesa amichevole
        startFriendlyWaiting();
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
                // 🎵 Stop sistema di attesa amichevole
                stopFriendlyWaiting();

                domElements.audioPlayer.src = response.data.audioUrl;
                domElements.audioPlayerContainer.style.display = 'block';
                domElements.progressAndPlayerContainer.style.display = 'block';

                if (domElements.downloadAudioLink) {
                    domElements.downloadAudioLink.href = response.data.downloadUrl || response.data.audioUrl;
                    domElements.downloadAudioLink.style.display = 'inline-flex';
                }

                console.log("✅ Musica generata e player attivato");

                // 🎵 NUOVO: Aggiorna archivio generazioni dopo successo
                setTimeout(() => {
                    updateGenerationsArchive();
                }, 2000); // Aspetta 2 secondi per dare tempo al server di salvare

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

    // BPM slider event listener
    if (domElements.bpmSlider && domElements.bpmValueDisplay) {
        domElements.bpmSlider.addEventListener('input', () => {
            domElements.bpmValueDisplay.textContent = domElements.bpmSlider.value;
            initialPreselectionDoneForCurrentImage = true;
            if (currentImage && imageAnalysisResults) {
                updateAIDisplayAndStablePrompt();
            }
        });
    }

    // Accordion event listener
    if (domElements.detailsAccordionHeader && domElements.aiInsightsContent) {
        domElements.detailsAccordionHeader.addEventListener('click', () => {
            const isOpen = domElements.detailsAccordionHeader.classList.toggle('open');
            domElements.aiInsightsContent.style.display = isOpen ? 'block' : 'none';
        });
    }

    // Collapsible sections event listeners
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

    // Mantieni compatibilità con CameraHandler esistente
    if (window.CameraHandler) {
        // ...
    } else {
        console.warn("WARN: ⚠️ CameraHandler non trovato - usando sistema fotocamera integrato");
    }

    window.addEventListener('beforeunload', () => {
        if (window.CameraHandler) {
            CameraHandler.cleanup();
        }
    });

    // Load AI models
    loadModels();

    // Check if user is logged in
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