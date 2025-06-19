/**
 * PICTOSOUND - Versione con ridondanze eliminate MA struttura originale mantenuta
 * Genera musica da immagini usando AI
 */

document.addEventListener('DOMContentLoaded', async () => {
    console.log("LOG: DOMContentLoaded - Pagina pronta e script principale in esecuzione.");

    const CREATIVITY_LEVEL = 50;

    // Cache DOM elements for performance and convenience - ORIGINALE
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
        moodPillsContainer: document.getElementById('moodPillsContainer'),
        genrePillsContainer: document.getElementById('genrePillsContainer'),
        instrumentPillsContainer: document.getElementById('instrumentPillsContainer'),
        rhythmPillsContainer: document.getElementById('rhythmPillsContainer'),
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

    // Contexts for canvases (con controllo di esistenza) - ORIGINALE
    let detectionCtx = null;
    if (domElements.detectionCanvas) {
        detectionCtx = domElements.detectionCanvas.getContext('2d');
    }

    let imageAnalysisCtx = null;
    if (domElements.imageCanvas) {
        imageAnalysisCtx = domElements.imageCanvas.getContext('2d');
    }

    // State variables - ORIGINALE
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

    // Italian to English translations for objects and emotions - ORIGINALE
    const objectTranslations = {
        "person": "persona", "cat": "gatto", "dog": "cane", "car": "auto", "tree": "albero",
        "book": "libro", "toothbrush": "spazzolino", "laptop": "laptop", "cell phone": "cellulare",
        "keyboard": "tastiera", "mouse": "mouse", "remote": "telecomando", "tv": "televisione",
        "bicycle": "bicicletta", "motorcycle": "motocicletta", "airplane": "aeroplano", "bus": "autobus",
        "train": "treno", "truck": "camion", "boat": "barca", "traffic light": "semaforo"
    };

    const emotionTranslations = {
        "neutral": "neutrale", "happy": "felice", "sad": "triste", "angry": "arrabbiato/a",
        "fearful": "impaurito/a", "disgusted": "disgustato/a", "surprised": "sorpreso/a"
    };

    // Data for checkbox pills (cues) - ORIGINALE
    const moodItems = [
        { value: "felice", label: "Felice / Gioioso" }, { value: "triste", label: "Triste / Malinconico" }, { value: "riflessivo", label: "Riflessivo" },
        { value: "epico", label: "Epico / Grandioso" }, { value: "rilassante", label: "Rilassante / Calmo" },
        { value: "energico", label: "Energico / Vivace" }, { value: "misterioso", label: "Misterioso / Inquietante" },
        { value: "sognante", label: "Sognante / Etereo" }, { value: "romantico", label: "Romantico" },
        { value: "drammatico", label: "Drammatico" }, { value: "futuristico", label: "Futuristico / Sci-Fi" },
        { value: "nostalgico", label: "Nostalgico" }, { value: "potente", label: "Potente / Intenso" }
    ];

    const genreItems = [
        { value: "elettronica", label: "Elettronica" }, { value: "dance", label: "Dance / EDM" },
        { value: "rock", label: "Rock" }, { value: "pop", label: "Pop" }, { value: "jazz", label: "Jazz" },
        { value: "classica", label: "Classica" }, { value: "ambient", label: "Ambient" },
        { value: "soundtrack", label: "Soundtrack / Cinematografica" }, { value: "folk", label: "Folk / Acustica" },
        { value: "lo-fi", label: "Lo-fi / Chillhop" }, { value: "hip-hop", label: "Hip Hop" },
        { value: "funk", label: "Funk / Soul" }, { value: "metal", label: "Metal" }
    ];

    const instrumentItems = [
        { value: "pianoforte", label: "Pianoforte" }, { value: "chitarra acustica", label: "Chitarra Acustica" },
        { value: "chitarra elettrica", label: "Chitarra Elettrica" }, { value: "basso", label: "Basso" },
        { value: "batteria", label: "Batteria / Percussioni" }, { value: "violino", label: "Violino / Archi" },
        { value: "violoncello", label: "Violoncello" }, { value: "sassofono", label: "Sassofono" },
        { value: "tromba", label: "Tromba / Ottoni" }, { value: "flauto", label: "Flauto" },
        { value: "sintetizzatore", label: "Sintetizzatore / Tastiere" }, { value: "organo", label: "Organo" }
    ];

    const rhythmItems = [
        { value: "no_rhythm", label: "Nessun ritmo evidente (Ambientale)" },
        { value: "slow_rhythm", label: "Ritmo Lento e Rilassato" },
        { value: "moderate_groove", label: "Groove Moderato e Orecchiabile" },
        { value: "upbeat_energetic", label: "Ritmo Incalzante ed Energico" },
        { value: "complex_experimental_rhythm", label: "Ritmo Complesso / Sperimentale" }
    ];

    // AI Processing Simulation Text - ORIGINALE
    let simulatedProcessingInterval;
    const simulatedProcessingMessages = [
        "Analisi contorni e forme...", "Estrazione pattern visivi...", "Valutazione composizione cromatica...",
        "Identificazione elementi chiave...", "Interpretazione atmosfera generale...", "Ricerca corrispondenze emotive...",
        "Elaborazione palette sonora...", "Definizione struttura armonica...", "Sviluppo linea melodica...",
        "Costruzione del paesaggio sonoro..."
    ];
    let currentMessageIndex = 0;

    // Helper functions - ORIGINALE
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

    // Load AI Models (TensorFlow.js) - FUNZIONE ORIGINALE ESATTA
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

    // Altre funzioni helper originali
    function getSelectedCheckboxValues(groupName) {
        return Array.from(document.querySelectorAll(`input[name="${groupName}"]:checked`)).map(cb => cb.value);
    }

    function translateObject(objectClass) {
        return objectTranslations[objectClass.toLowerCase()] || objectClass;
    }

    function translateEmotion(emotion) {
        return emotionTranslations[emotion.toLowerCase()] || emotion;
    }

    // Populate checkbox pills - ORIGINALE
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

    // Image Analysis Functions - ORIGINALI
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
        let totalBrightnessSum = 0;
        let totalAnalyzedPixels = 0;

        for (let i = 0; i < pixels.length; i += 4) {
            const r_orig = pixels[i], g_orig = pixels[i + 1], b_orig = pixels[i + 2], alpha = pixels[i + 3];
            if (alpha > 128) {
                totalBrightnessSum += (r_orig + g_orig + b_orig) / 3;
                totalAnalyzedPixels++;
            }
        }

        if (totalAnalyzedPixels === 0) return { dominantColors: [], averageBrightness: 128, contrast: 0, averageSaturation: 50 };

        const avgB = totalBrightnessSum / totalAnalyzedPixels;
        return { dominantColors: [], averageBrightness: avgB, contrast: 50, averageSaturation: 50 };
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

    // Generate prompt - SEMPLIFICATO
    function generateStableAudioPrompt(detectedObjects, detectedEmotions) {
        console.log("🎵 generateStableAudioPrompt chiamata con oggetti:", detectedObjects, "emozioni:", detectedEmotions);

        const parts = [];
        let genre = "acoustic";
        let mood = "";

        if (detectedObjects.includes("persona")) {
            genre = "acoustic";
        } else if (detectedObjects.some(obj => ["auto", "città", "strada"].includes(obj))) {
            genre = "electronic";
        } else {
            genre = "ambient";
        }

        if (detectedEmotions.includes("felice")) {
            mood = "upbeat";
        } else if (detectedEmotions.includes("triste")) {
            mood = "melancholic";
        }

        parts.push(genre);
        if (mood) parts.push(mood);
        parts.push("with guitar");
        parts.push("120 BPM");
        parts.push("high quality");

        const finalPrompt = parts.join(", ");
        console.log("🎵 PROMPT FINALE:", finalPrompt);
        return finalPrompt;
    }

    // Main update function - SEMPLIFICATA
    async function updateAIDisplayAndStablePrompt() {
        if (!currentImage) {
            console.warn("WARN: updateAIDisplayAndStablePrompt chiamato senza currentImage.");
            return;
        }

        if (!imageAnalysisResults) {
            domElements.dynamicFeedbackArea.style.display = 'block';
            updateProgressMessage("Analisi immagine in corso...", true);

            imageAnalysisResults = {
                colors: analyzeImageAdvanced(currentImage),
                objects: await detectObjectsInImage(currentImage),
                emotions: await analyzeFacesInImage(currentImage)
            };
            initialPreselectionDoneForCurrentImage = false;
        }

        stableAudioPromptForMusic = generateStableAudioPrompt(imageAnalysisResults.objects, imageAnalysisResults.emotions);

        // Update UI
        domElements.aiInsightsSection.style.display = 'block';
        if (domElements.aiInsightsContent) {
            domElements.aiInsightsContent.innerHTML = `
                <h4>Analisi Immagine:</h4>
                <ul>
                    <li><strong>Oggetti:</strong> ${imageAnalysisResults.objects.join(", ") || "Nessuno"}</li>
                    <li><strong>Emozioni:</strong> ${imageAnalysisResults.emotions.join(", ") || "Nessuna"}</li>
                    <li><strong>Prompt generato:</strong> ${stableAudioPromptForMusic}</li>
                </ul>
            `;
        }

        if (stableAudioPromptForMusic && !stableAudioPromptForMusic.toLowerCase().includes("errore")) {
            domElements.generateMusicButton.disabled = false;
        }

        updateProgressMessage("", false);
        setStatusMessage(domElements.statusDiv, "Analisi completata!", "success");
    }

    // Process image - ORIGINALE
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
            domElements.generateMusicButton.disabled = true;
            setStatusMessage(domElements.statusDiv, "Immagine caricata. Analisi in corso...", "info");
            
            await updateAIDisplayAndStablePrompt();
        };

        currentImage.onerror = () => {
            console.error("ERRORE: Errore durante il caricamento di currentImage.");
            setStatusMessage(domElements.statusDiv, "Errore caricamento immagine.", "error");
            domElements.generateMusicButton.disabled = true;
        };
        currentImage.src = imageSrc;
    }

    // Popola interfaccia
    populateCheckboxPills(domElements.moodPillsContainer, moodItems, 'mood');
    populateCheckboxPills(domElements.genrePillsContainer, genreItems, 'genre');
    populateCheckboxPills(domElements.instrumentPillsContainer, instrumentItems, 'instrument');
    populateCheckboxPills(domElements.rhythmPillsContainer, rhythmItems, 'rhythm');

    // Load models - CHIAMATA ORIGINALE
    loadModels();

    // Event Listeners - ORIGINALI
    domElements.imageUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                processImage(e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    if (domElements.generateMusicButton) {
        domElements.generateMusicButton.addEventListener('click', () => {
            if (!currentImage || !stableAudioPromptForMusic) {
                setStatusMessage(domElements.statusDiv, "Carica prima un'immagine", "error");
                return;
            }

            domElements.generateMusicButton.disabled = true;
            if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'inline-block';
            
            setStatusMessage(domElements.statusDiv, "Generazione musica in corso...", "info");
            updateProgressMessage("Generazione traccia audio...", true);

            // WordPress AJAX call
            jQuery.ajax({
                url: pictosound_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'pictosound_generate_music',
                    prompt: stableAudioPromptForMusic,
                    duration: document.querySelector('input[name="musicDuration"]:checked')?.value || 40,
                    image_url: currentImageSrc,
                    nonce: pictosound_vars.nonce_check_credits
                },
                success: (response) => {
                    if (response.success && response.data.audioUrl) {
                        setStatusMessage(domElements.statusDiv, "Musica generata con successo!", "success");
                        
                        if (domElements.audioPlayer) domElements.audioPlayer.src = response.data.audioUrl;
                        if (domElements.downloadAudioLink) {
                            domElements.downloadAudioLink.href = response.data.downloadUrl || response.data.audioUrl;
                            domElements.downloadAudioLink.style.display = 'inline-flex';
                        }
                        if (domElements.audioPlayerContainer) domElements.audioPlayerContainer.style.display = 'block';
                        
                        updateProgressMessage("", false);
                    } else {
                        setStatusMessage(domElements.statusDiv, response.data?.message || "Errore generazione musica", "error");
                        updateProgressMessage("", false);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Errore generazione musica:', error);
                    setStatusMessage(domElements.statusDiv, "Errore durante la generazione musica", "error");
                    updateProgressMessage("", false);
                },
                complete: () => {
                    domElements.generateMusicButton.disabled = false;
                    if (domElements.musicSpinner) domElements.musicSpinner.style.display = 'none';
                }
            });
        });
    }

    // BPM slider
    if (domElements.bpmSlider && domElements.bpmValueDisplay) {
        domElements.bpmSlider.addEventListener('input', () => {
            domElements.bpmValueDisplay.textContent = domElements.bpmSlider.value;
        });
    }

    console.log("LOG: Script principale completato con successo");
});