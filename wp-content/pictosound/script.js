document.addEventListener('DOMContentLoaded', async () => {
    console.log("LOG: DOMContentLoaded - Pagina pronta e script principale in esecuzione.");

    const CREATIVITY_LEVEL = 50;
    // 🔧 CONFIGURAZIONE STANDALONE - Versione CORRETTA
    if (typeof pictosound_vars === 'undefined') {
        console.log("🔧 Modalità standalone attiva");
        window.pictosound_vars = {
            ajax_url: '/wp-content/pictosound/generate_music.php', // ⭐ CORRETTO: usa direttamente la tua API
            nonce_generate: 'standalone_demo',
            is_user_logged_in: false,
            user_credits: 0
        };
    }

    // 🔧 OVERRIDE jQuery.ajax per evitare chiamate WordPress
    if (typeof jQuery !== 'undefined') {
        const originalAjax = jQuery.ajax;
        jQuery.ajax = function (options) {
            // Se è una chiamata WordPress, reindirizza alla tua API
            if (options.url && options.url.includes('admin-ajax.php')) {
                console.log("🔄 Reindirizzamento chiamata da WordPress a API diretta");
                return fetch('/wp-content/pictosound/generate_music.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prompt: options.data.prompt,
                        duration: parseInt(options.data.duration),
                        steps: 30
                    })
                }).then(response => response.json());
            }
            // Altrimenti usa jQuery normale
            return originalAjax.call(this, options);
        };
    }
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

    // Debug - verifica elementi accordion
    console.log("LOG: Accordion header trovato:", !!domElements.detailsAccordionHeader);
    console.log("LOG: AI insights content trovato:", !!domElements.aiInsightsContent);

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

    // AI Processing messages
    const simulatedProcessingMessages = [
        "Analisi contorni e forme...", "Estrazione pattern visivi...",
        "Valutazione composizione cromatica...", "Identificazione elementi chiave...",
        "Interpretazione atmosfera generale...", "Ricerca corrispondenze emotive...",
        "Elaborazione palette sonora...", "Definizione struttura armonica...",
        "Sviluppo linea melodica...", "Costruzione del paesaggio sonoro..."
    ];
    let simulatedProcessingInterval;
    let currentMessageIndex = 0;

    // Helper functions
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

    // Translate object/emotion
    function translateObject(objectClass) {
        return objectTranslations[objectClass.toLowerCase()] || objectClass;
    }

    function translateEmotion(emotion) {
        return emotionTranslations[emotion.toLowerCase()] || emotion;
    }

    // Get selected checkbox values
    function getSelectedCheckboxValues(groupName) {
        return Array.from(document.querySelectorAll(`input[name="${groupName}"]:checked`)).map(cb => cb.value);
    }

    // Translate Italian cue to English
    function translateCueToEnglish(italianCue, type) {
        if (!italianCue) return "";
        const lowerCue = String(italianCue).toLowerCase();

        if (cueTranslationsITtoEN[type] && cueTranslationsITtoEN[type][lowerCue]) {
            return cueTranslationsITtoEN[type][lowerCue];
        }

        console.warn(`No English translation for '${italianCue}' of type '${type}'`);
        return italianCue;
    }

    // Start AI simulation animation
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

    // Populate checkbox pills
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

    // Analyze image colors and properties
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

    // Load AI Models
    async function loadModels() {
        console.log("LOG: Inizio caricamento modelli AI...");
        setStatusMessage(domElements.statusDiv, "Caricamento modelli AI...", "info");

        try {
            // Verifica che le librerie siano caricate
            if (typeof cocoSsd === 'undefined') {
                throw new Error("Libreria COCO-SSD non trovata");
            }
            if (typeof faceapi === 'undefined') {
                throw new Error("Libreria Face-API non trovata");
            }

            // Carica i modelli
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

            // Nascondi il messaggio dopo 3 secondi
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

    // Process new image
    function processImage(imageSrc) {
        console.log("LOG: Inizio processImage");
        domElements.imagePreview.src = imageSrc;
        currentImageSrc = imageSrc;
        domElements.imagePreview.style.display = 'block';
        imageAnalysisResults = null;
        initialPreselectionDoneForCurrentImage = false;

        // Reset UI
        domElements.generateMusicButton.disabled = true;
        domElements.aiInsightsSection.style.display = 'none';
        if (domElements.detailsAccordionHeader) domElements.detailsAccordionHeader.classList.remove('open');
        if (domElements.aiInsightsContent) domElements.aiInsightsContent.style.display = 'none';

        // Clear selected pills
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
                // Analisi completa
                const colorAnalysis = analyzeImageAdvanced(currentImage);
                const objects = await detectObjectsInImage(currentImage);
                const emotions = await analyzeFacesInImage(currentImage);

                imageAnalysisResults = {
                    colors: colorAnalysis,
                    objects: objects,
                    emotions: emotions
                };

                console.log("LOG: Analisi completata", imageAnalysisResults);

                // Aggiorna display e genera prompt
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

    // Musical cues logic
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

            // Mood based on emotions
            if (cues.moods.length === 0 && detectedEmotions && detectedEmotions.length > 0) {
                const primaryEmotion = detectedEmotions.find(e => e !== "neutrale") || detectedEmotions[0];
                if (primaryEmotion === "felice") cues.moods.push("energico");
                else if (primaryEmotion === "triste") cues.moods.push("malinconico");
                else if (primaryEmotion === "arrabbiato/a") cues.moods.push("drammatico");
                else cues.moods.push("rilassante");
            }

            // Genre based on objects
            if (cues.genres.length === 0 && detectedObjects && detectedObjects.length > 0) {
                if (detectedObjects.includes("persona")) cues.genres.push("folk");
                else if (detectedObjects.some(o => ["albero", "natura", "animale"].includes(o))) cues.genres.push("ambient");
                else if (detectedObjects.some(o => ["auto", "città", "strada"].includes(o))) cues.genres.push("elettronica");
                else cues.genres.push("ambient");
            }

            // Instruments based on genre
            if (cues.instruments.length === 0) {
                if (cues.genres.includes("folk")) cues.instruments.push("chitarra acustica");
                else if (cues.genres.includes("elettronica")) cues.instruments.push("sintetizzatore");
                else cues.instruments.push("pianoforte");
            }

            // Rhythm based on mood
            if (cues.rhythms.length === 0) {
                if (cues.moods.includes("energico")) cues.rhythms.push("upbeat_energetic");
                else if (cues.moods.includes("rilassante")) cues.rhythms.push("slow_rhythm");
                else cues.rhythms.push("moderate_groove");
            }
        }

        return cues;
    }

    // Generate AI display content
    function generateAIDisplayContent(analysis, detectedObjectsList, detectedEmotionsList, userInputs, finalStablePrompt) {
        // Clear previous content
        const existingDetails = domElements.aiInsightsContent.querySelectorAll('h4, ul, #finalPromptForAI');
        existingDetails.forEach(el => el.remove());

        let analysisContentHTML = "<h4>Analisi Immagine:</h4><ul>";
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

        // Musical interpretation
        const displayCues = getMusicalCues(analysis, detectedObjectsList, detectedEmotionsList, CREATIVITY_LEVEL, userInputs);

        let interpretationHtml = "<h4>Interpretazione Musicale:</h4><ul>";
        if (displayCues.moods.length > 0) interpretationHtml += `<li><strong>Mood:</strong> ${displayCues.moods.join(", ")}</li>`;
        if (displayCues.genres.length > 0) interpretationHtml += `<li><strong>Generi:</strong> ${displayCues.genres.join(", ")}</li>`;
        if (displayCues.instruments.length > 0) interpretationHtml += `<li><strong>Strumenti:</strong> ${displayCues.instruments.join(", ")}</li>`;
        interpretationHtml += `<li><strong>Tempo:</strong> ${displayCues.tempoDescription} (${displayCues.tempoBPM} BPM)</li>`;
        interpretationHtml += "</ul>";

        interpretationHtml += `<div id="finalPromptForAI" style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 5px;">
            <strong>Prompt finale per AI:</strong><br>${finalStablePrompt}
        </div>`;

        // Insert HTML
        if (domElements.aiProcessingSimulationDiv) {
            domElements.aiProcessingSimulationDiv.insertAdjacentHTML('afterend', analysisContentHTML + interpretationHtml);
        } else {
            domElements.aiInsightsContent.innerHTML += analysisContentHTML + interpretationHtml;
        }
    }

    // Generate stable audio prompt
    function generateStableAudioPrompt(parsedData) {
        console.log("🎵 Generazione prompt per AI");

        let parts = [];

        // Add moods (translated to English)
        if (parsedData.moods.length > 0) {
            parsedData.moods.forEach(mood => {
                const englishMood = cueTranslationsITtoEN.mood[mood] || mood;
                if (englishMood) parts.push(englishMood);
            });
        }

        // Add genre
        if (parsedData.genres.length > 0) {
            const genre = parsedData.genres[0];
            const englishGenre = cueTranslationsITtoEN.genre[genre] || genre;
            if (englishGenre) parts.push(englishGenre);
        }

        // Add instrument
        if (parsedData.instruments.length > 0) {
            const instrument = parsedData.instruments[0];
            const englishInstrument = cueTranslationsITtoEN.instrument[instrument] || instrument;
            if (englishInstrument) parts.push("with " + englishInstrument);
        }

        // Add BPM
        parts.push(parsedData.tempoBPM + " BPM");

        // Add quality
        parts.push("high quality");

        const finalPrompt = parts.join(", ");
        console.log("🎵 Prompt finale:", finalPrompt);

        return finalPrompt;
    }

    // Update AI display and prompt
    async function updateAIDisplayAndStablePrompt() {
        if (!currentImage || !imageAnalysisResults) return;

        // Get user selections
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

        // Get musical cues
        const tempParsedCues = getMusicalCues(
            imageAnalysisResults.colors,
            imageAnalysisResults.objects,
            imageAnalysisResults.emotions,
            CREATIVITY_LEVEL,
            userInputs
        );

        // Generate prompt
        stableAudioPromptForMusic = generateStableAudioPrompt(tempParsedCues);

        // Update display
        generateAIDisplayContent(
            imageAnalysisResults.colors,
            imageAnalysisResults.objects,
            imageAnalysisResults.emotions,
            userInputs,
            stableAudioPromptForMusic
        );

        // Show AI insights section
        domElements.aiInsightsSection.style.display = 'block';
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

    // Event Listeners
    domElements.imageUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => processImage(e.target.result);
            reader.readAsDataURL(file);
        }
    });

    // Generate Music Button
    domElements.generateMusicButton.addEventListener('click', async () => {
        console.log("LOG: Click genera musica");

        if (!currentImage) {
            setStatusMessage(domElements.statusDiv, "Carica prima un'immagine!", "error");
            return;
        }

        // Se il prompt non è pronto, generalo ora
        if (!stableAudioPromptForMusic || stableAudioPromptForMusic === "") {
            await updateAIDisplayAndStablePrompt();
        }

        domElements.generateMusicButton.disabled = true;
        domElements.musicSpinner.style.display = 'inline-block';
        setStatusMessage(domElements.statusDiv, "Generazione musica in corso...", "info");
        updateProgressMessage("Generazione traccia audio in corso...", true);

        try {
            const duration = document.querySelector('input[name="musicDuration"]:checked')?.value || "40";

            console.log("LOG: Invio richiesta con prompt:", stableAudioPromptForMusic);

            // Chiamata AJAX WordPress
            const response = await jQuery.ajax({
                url: pictosound_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'pictosound_generate_music',
                    prompt: stableAudioPromptForMusic,
                    duration: duration,
                    image_data: currentImageSrc, // <-- Modificato da 'image_url' a 'image_data'
                    nonce: pictosound_vars.nonce_generate || ''
                }
            });

            console.log("LOG: Risposta ricevuta:", response);

            if (response.success && response.data.audioUrl) {
                // Successo!
                updateProgressMessage("", false);
                setStatusMessage(domElements.statusDiv, "Musica generata con successo!", "success");

                // Mostra player audio
                domElements.audioPlayer.src = response.data.audioUrl;
                domElements.audioPlayerContainer.style.display = 'block';
                domElements.progressAndPlayerContainer.style.display = 'block';

                // Setup download
                if (domElements.downloadAudioLink) {
                    domElements.downloadAudioLink.href = response.data.downloadUrl || response.data.audioUrl;
                    domElements.downloadAudioLink.style.display = 'inline-flex';
                }

                // Nascondi status dopo 3 secondi
                setTimeout(() => {
                    domElements.statusDiv.style.display = 'none';
                    domElements.dynamicFeedbackArea.style.display = 'none';
                }, 3000);
            } else {
                throw new Error(response.data?.error || 'Errore generazione');
            }

        } catch (error) {
            console.error("ERRORE generazione:", error);
            updateProgressMessage("", false);
            setStatusMessage(domElements.statusDiv, `Errore: ${error.message}`, "error");
        } finally {
            domElements.generateMusicButton.disabled = false;
            domElements.musicSpinner.style.display = 'none';
        }
    });

    // BPM Slider
    if (domElements.bpmSlider && domElements.bpmValueDisplay) {
        domElements.bpmSlider.addEventListener('input', () => {
            domElements.bpmValueDisplay.textContent = domElements.bpmSlider.value;
            initialPreselectionDoneForCurrentImage = true;
            if (currentImage && imageAnalysisResults) {
                updateAIDisplayAndStablePrompt();
            }
        });
    }

    // Accordion for details
    if (domElements.detailsAccordionHeader && domElements.aiInsightsContent) {
        domElements.detailsAccordionHeader.addEventListener('click', () => {
            const isOpen = domElements.detailsAccordionHeader.classList.toggle('open');
            domElements.aiInsightsContent.style.display = isOpen ? 'block' : 'none';
        });
    }

    // Accordion for cue groups (Mood, Genere, etc.)
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

    // Initialize pills
    populateCheckboxPills(domElements.moodPillsContainer, moodItems, 'mood');
    populateCheckboxPills(domElements.genrePillsContainer, genreItems, 'genre');
    populateCheckboxPills(domElements.instrumentPillsContainer, instrumentItems, 'instrument');
    populateCheckboxPills(domElements.rhythmPillsContainer, rhythmItems, 'rhythm');

    // Inizializza subito i modelli
    loadModels();

    // Popola le opzioni di durata/crediti se disponibili
    if (typeof pictosound_vars !== 'undefined') {
        console.log("LOG: pictosound_vars disponibile:", pictosound_vars);
        // Qui puoi aggiungere la logica per i crediti se necessaria
    }
});