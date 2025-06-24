document.addEventListener('DOMContentLoaded', async () => {
    console.log("LOG: DOMContentLoaded - PictoSound con AI reale inizializzato.");

    // ===================================================================
    // 🎯 CACHE DOM ELEMENTS (COMPLETO)
    // ===================================================================
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
    };

    // ===================================================================
    // 🔧 STATE VARIABLES
    // ===================================================================

    // Immagine e analisi
    let currentImage = null;
    let currentImageSrc = null;
    let imageAnalysisResults = null;
    let stableAudioPromptForMusic = "";
    let initialPreselectionDoneForCurrentImage = false;

    // Camera e mobile
    let currentStream = null;
    let currentFacingMode = "environment";
    let zoomCapabilities = null;
    let currentZoom = 1;
    let initialPinchDistance = 0;
    let lastTap = 0;
    const DOUBLE_TAP_DELAY = 300; // ms

    // AI Models
    let cocoSsdModel = null;
    let faceApiModelsLoaded = false;

    // Contexts
    const imageAnalysisCtx = domElements.imageCanvas ? domElements.imageCanvas.getContext('2d') : null;

    // ===================================================================
    // 📸 FUNZIONI AVANZATE FOTOCAMERA MOBILE (DAL TUO FILE JS)
    // ===================================================================

    /**
     * Applica stili CSS per un'esperienza a schermo intero su mobile.
     */
    function applyCameraStyles() {
        if (document.getElementById('pictosound-camera-styles')) return;
        const style = document.createElement('style');
        style.id = 'pictosound-camera-styles';
        style.innerHTML = `
            body.camera-active {
                overflow: hidden;
            }
            #cameraViewContainer {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: #000;
                z-index: 1000;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            #cameraFeed {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .camera-controls {
                position: absolute;
                bottom: 20px;
                width: 100%;
                display: flex;
                justify-content: space-around;
                align-items: center;
                z-index: 1001;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Apre la visualizzazione della fotocamera e avvia lo stream video.
     */
    async function openCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatusMessage("Il tuo browser non supporta l'accesso alla fotocamera.", "error");
            return;
        }

        applyCameraStyles();
        document.body.classList.add('camera-active');

        if (currentStream) closeCamera();

        const constraints = {
            video: {
                facingMode: currentFacingMode,
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        };

        try {
            currentStream = await navigator.mediaDevices.getUserMedia(constraints);
            domElements.cameraFeed.srcObject = currentStream;
            domElements.cameraViewContainer.style.display = 'flex';

            // Gestione Zoom
            const [track] = currentStream.getVideoTracks();
            const capabilities = track.getCapabilities();
            if (capabilities.zoom) {
                zoomCapabilities = capabilities.zoom;
                currentZoom = zoomCapabilities.min;
                addTouchListeners();
                console.log("LOG: Zoom supportato. Range:", zoomCapabilities.min, "-", zoomCapabilities.max);
            } else {
                console.log("LOG: Zoom non supportato da questa fotocamera.");
            }

        } catch (error) {
            handleCameraError(error);
        }
    }

    /**
     * Gestisce gli errori di accesso alla fotocamera.
     */
    function handleCameraError(error) {
        console.error("Errore accesso fotocamera:", error);
        let errorMessage = "Impossibile accedere alla fotocamera.";
        if (error.name === "NotAllowedError" || error.name === "PermissionDeniedError") {
            errorMessage = "Hai negato il permesso di usare la fotocamera. Abilitalo nelle impostazioni del browser.";
        } else if (error.name === "NotFoundError" || error.name === "DevicesNotFoundError") {
            errorMessage = "Nessuna fotocamera trovata sul tuo dispositivo.";
        }
        setStatusMessage(errorMessage, "error");
        closeCamera();
    }

    /**
     * Chiude la fotocamera.
     */
    function closeCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
        removeTouchListeners();
        domElements.cameraViewContainer.style.display = 'none';
        document.body.classList.remove('camera-active');
        setStatusMessage("", "info");
    }

    /**
     * Cattura un frame dal video e lo processa.
     */
    function captureImage() {
        if (!currentStream) return;
        const video = domElements.cameraFeed;
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = video.videoWidth;
        tempCanvas.height = video.videoHeight;
        const tempCtx = tempCanvas.getContext('2d');
        tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);
        const imageDataUrl = tempCanvas.toDataURL('image/jpeg');
        processNewImage(imageDataUrl);
        closeCamera();
    }

    /**
     * Cambia fotocamera (frontale/posteriore).
     */
    function switchCamera() {
        currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
        openCamera();
    }

    // --- Funzioni per Gesti Touch (DAL TUO FILE JS) ---

    function addTouchListeners() {
        domElements.cameraFeed.addEventListener('touchstart', handleTouchStart, { passive: false });
        domElements.cameraFeed.addEventListener('touchmove', handleTouchMove, { passive: false });
        domElements.cameraFeed.addEventListener('touchend', handleTouchEnd);
    }

    function removeTouchListeners() {
        domElements.cameraFeed.removeEventListener('touchstart', handleTouchStart);
        domElements.cameraFeed.removeEventListener('touchmove', handleTouchMove);
        domElements.cameraFeed.removeEventListener('touchend', handleTouchEnd);
    }

    function handleTouchStart(event) {
        event.preventDefault();
        if (event.touches.length === 2) {
            initialPinchDistance = getDistance(event.touches);
        }
    }

    function handleTouchMove(event) {
        event.preventDefault();
        if (event.touches.length === 2 && zoomCapabilities) {
            const newDistance = getDistance(event.touches);
            const zoomFactor = newDistance / initialPinchDistance;

            // Calcola il nuovo livello di zoom
            let newZoom = currentZoom * zoomFactor;

            // Limita lo zoom entro i valori min/max supportati
            newZoom = Math.max(zoomCapabilities.min, Math.min(newZoom, zoomCapabilities.max));

            // Applica lo zoom
            const [track] = currentStream.getVideoTracks();
            track.applyConstraints({ advanced: [{ zoom: newZoom }] });
        }
    }

    function handleTouchEnd(event) {
        if (event.touches.length > 0) return; // Se ci sono ancora dita sullo schermo, non fare nulla

        const currentTime = new Date().getTime();
        const timeSinceLastTap = currentTime - lastTap;

        if (timeSinceLastTap < DOUBLE_TAP_DELAY && timeSinceLastTap > 0) {
            // È un doppio tocco!
            console.log("LOG: Doppio tocco rilevato, scatto la foto.");
            captureImage();
        }

        lastTap = currentTime;
        initialPinchDistance = 0; // Resetta la distanza del pinch

        // Salva il livello di zoom corrente dopo il pinch
        if (zoomCapabilities) {
            const [track] = currentStream.getVideoTracks();
            currentZoom = track.getSettings().zoom || 1;
        }
    }

    function getDistance(touches) {
        const [touch1, touch2] = touches;
        return Math.sqrt(
            Math.pow(touch2.pageX - touch1.pageX, 2) +
            Math.pow(touch2.pageY - touch1.pageY, 2)
        );
    }

    // ===================================================================
    // 🧠 FUNZIONI REALI DI ANALISI AI
    // ===================================================================

    /**
     * Carica tutti i modelli AI necessari
     */
    async function loadModels() {
        try {
            setStatusMessage("🤖 Caricamento modelli AI in corso...", "info");

            // Carica il modello COCO-SSD per object detection
            console.log("🔄 Caricamento COCO-SSD...");
            cocoSsdModel = await cocoSsd.load();
            console.log("✅ COCO-SSD caricato con successo");

            // Carica i modelli Face-API
            console.log("🔄 Caricamento Face-API models...");
            const baseUrl = '/wp-content/pictosound/js/models';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(baseUrl),
                faceapi.nets.faceExpressionNet.loadFromUri(baseUrl),
                faceapi.nets.ageGenderNet.loadFromUri(baseUrl)
            ]);
            faceApiModelsLoaded = true;
            console.log("✅ Face-API models caricati con successo");

            setStatusMessage("🎉 Modelli AI pronti! Carica un'immagine per iniziare.", "success");

        } catch (error) {
            console.error("❌ Errore nel caricamento dei modelli:", error);
            setStatusMessage("⚠️ Alcuni modelli AI non sono disponibili. L'analisi potrebbe essere limitata.", "error");
        }
    }

    /**
     * Funzione helper per i messaggi di stato
     */
    function setStatusMessage(message, type = "info") {
        if (!domElements.statusDiv) return;
        domElements.statusDiv.textContent = message;
        domElements.statusDiv.className = 'status-message';
        if (type === "error") domElements.statusDiv.classList.add("status-error");
        else if (type === "success") domElements.statusDiv.classList.add("status-success");
        domElements.statusDiv.style.display = message ? 'block' : 'none';
    }

    /**
     * Analisi avanzata dei colori dell'immagine
     */
    function analyzeImageAdvanced(imageElement) {
        console.log("🎨 Analisi avanzata colori in corso...");

        // Crea un canvas temporaneo per l'analisi dei colori
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        // Ridimensiona per performance (max 300px)
        const maxSize = 300;
        const scale = Math.min(maxSize / imageElement.width, maxSize / imageElement.height);
        canvas.width = imageElement.width * scale;
        canvas.height = imageElement.height * scale;

        ctx.drawImage(imageElement, 0, 0, canvas.width, canvas.height);

        // Ottieni i pixel
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const pixels = imageData.data;

        // Analizza i colori
        const colorCounts = {};
        let totalBrightness = 0;
        let totalSaturation = 0;
        let pixelCount = 0;

        for (let i = 0; i < pixels.length; i += 4) {
            const r = pixels[i];
            const g = pixels[i + 1];
            const b = pixels[i + 2];
            const a = pixels[i + 3];

            if (a < 128) continue; // Salta pixel trasparenti

            // Calcola luminosità
            const brightness = (r * 0.299 + g * 0.587 + b * 0.114);
            totalBrightness += brightness;

            // Calcola saturazione (HSV)
            const max = Math.max(r, g, b);
            const min = Math.min(r, g, b);
            const saturation = max === 0 ? 0 : (max - min) / max * 100;
            totalSaturation += saturation;

            // Raggruppa colori simili (riduce precisione per clustering)
            const colorKey = `${Math.floor(r / 20) * 20},${Math.floor(g / 20) * 20},${Math.floor(b / 20) * 20}`;
            colorCounts[colorKey] = (colorCounts[colorKey] || 0) + 1;

            pixelCount++;
        }

        // Trova i colori dominanti
        const sortedColors = Object.entries(colorCounts)
            .sort(([, a], [, b]) => b - a)
            .slice(0, 5);

        const dominantColors = sortedColors.map(([colorKey, count]) => {
            const [r, g, b] = colorKey.split(',').map(Number);
            const percentage = Math.round((count / pixelCount) * 100);

            // Calcola HSL
            const max = Math.max(r, g, b) / 255;
            const min = Math.min(r, g, b) / 255;
            const lightness = (max + min) / 2;
            const delta = max - min;
            const saturation = delta === 0 ? 0 : delta / (1 - Math.abs(2 * lightness - 1));

            let hue = 0;
            if (delta !== 0) {
                if (max === r / 255) hue = ((g / 255 - b / 255) / delta) % 6;
                else if (max === g / 255) hue = (b / 255 - r / 255) / delta + 2;
                else hue = (r / 255 - g / 255) / delta + 4;
            }
            hue = Math.round(hue * 60);
            if (hue < 0) hue += 360;

            return {
                r, g, b,
                percentage,
                hue: hue,
                saturation: Math.round(saturation * 100),
                lightness: Math.round(lightness * 100)
            };
        });

        const averageBrightness = Math.round(totalBrightness / pixelCount);
        const averageSaturation = Math.round(totalSaturation / pixelCount);

        // Calcola contrasto
        const maxBrightness = Math.max(...dominantColors.map(c => c.lightness));
        const minBrightness = Math.min(...dominantColors.map(c => c.lightness));
        const contrast = maxBrightness - minBrightness;

        const result = {
            dominantColors,
            averageBrightness,
            contrast,
            averageSaturation
        };

        console.log("✅ Analisi colori completata:", result);
        return result;
    }

    /**
     * Detecta oggetti nell'immagine usando COCO-SSD
     */
    async function detectObjectsInImage(imageElement) {
        if (!cocoSsdModel) {
            console.warn("⚠️ Modello COCO-SSD non ancora caricato");
            return ["oggetto_generico"];
        }

        try {
            console.log("🔍 Rilevamento oggetti in corso...");

            // Esegui la predizione
            const predictions = await cocoSsdModel.detect(imageElement);

            // Estrai le classi rilevate (con confidenza > 0.3)
            const detectedObjects = predictions
                .filter(prediction => prediction.score > 0.3)
                .map(prediction => prediction.class)
                .filter((value, index, self) => self.indexOf(value) === index); // Rimuovi duplicati

            console.log("✅ Oggetti rilevati:", detectedObjects);

            // Se non trova nulla, ritorna una categoria generica
            if (detectedObjects.length === 0) {
                return ["scena_generica"];
            }

            return detectedObjects;

        } catch (error) {
            console.error("❌ Errore nel rilevamento oggetti:", error);
            return ["oggetto_generico"];
        }
    }

    /**
     * Analizza le espressioni facciali usando Face-API
     */
    async function analyzeFacesInImage(imageElement) {
        if (!faceApiModelsLoaded) {
            console.warn("⚠️ Modelli Face-API non ancora caricati");
            return ["neutral"];
        }

        try {
            console.log("😊 Analisi espressioni facciali in corso...");

            // Rileva volti con espressioni
            const detections = await faceapi
                .detectAllFaces(imageElement, new faceapi.TinyFaceDetectorOptions())
                .withFaceExpressions();

            if (detections.length === 0) {
                console.log("ℹ️ Nessun volto rilevato");
                return ["no_face"];
            }

            // Estrai le emozioni dominanti
            const emotions = [];
            detections.forEach(detection => {
                const expressions = detection.expressions;

                // Trova l'espressione con confidenza maggiore
                let maxExpression = 'neutral';
                let maxScore = 0;

                Object.entries(expressions).forEach(([emotion, score]) => {
                    if (score > maxScore && score > 0.3) { // Soglia di confidenza
                        maxScore = score;
                        maxExpression = emotion;
                    }
                });

                emotions.push(maxExpression);
            });

            console.log("✅ Emozioni rilevate:", emotions);

            // Rimuovi duplicati
            return [...new Set(emotions)];

        } catch (error) {
            console.error("❌ Errore nell'analisi delle espressioni:", error);
            return ["neutral"];
        }
    }

    // ===================================================================
    // 🎵 LOGICA MUSICALE (DAL TUO HTML ORIGINALE)
    // ===================================================================

    // Data for checkbox pills (mantieni i tuoi dati originali)
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

    function setupCollapsibleSections() {
        if (domElements.detailsAccordionHeader) {
            domElements.detailsAccordionHeader.addEventListener('click', () => {
                const isOpen = domElements.detailsAccordionHeader.classList.toggle('open');
                domElements.aiInsightsContent.style.display = isOpen ? 'block' : 'none';
            });
        }
        document.querySelectorAll('.collapsible-cue-header').forEach(header => {
            header.addEventListener('click', () => {
                header.classList.toggle('open');
                const pillsGroup = header.nextElementSibling;
                if (pillsGroup && pillsGroup.classList.contains('checkbox-pills-group')) {
                    pillsGroup.classList.toggle('open');
                }
                const bpmSliderContainer = header.parentElement.querySelector('.bpm-slider-container');
                if (bpmSliderContainer) {
                    bpmSliderContainer.style.display = header.classList.contains('open') ? 'block' : 'none';
                }
            });
        });
    }

    function getSelectedCheckboxValues(groupName) {
        return Array.from(document.querySelectorAll(`input[name="${groupName}"]:checked`)).map(cb => cb.value);
    }

    // LOGICA MUSICALE MIGLIORATA CON AI REALE
    function getMusicalCues(analysis, userInputs) {
        const { colors, objects, emotions } = analysis;
        const cues = {
            moods: [...userInputs.selectedMoods],
            genres: [...userInputs.selectedGenres],
            instruments: [...userInputs.selectedInstruments],
            rhythms: [...userInputs.selectedRhythms],
            tempoBPM: userInputs.selectedBPM
        };

        // SUGGERIMENTI AI BASATI SU ANALISI REALE
        if (userInputs.selectedMoods.length === 0) {
            // Basato su emozioni rilevate
            if (emotions.includes("happy")) cues.moods.push("felice", "energico");
            if (emotions.includes("sad")) cues.moods.push("triste", "malinconico");
            if (emotions.includes("angry")) cues.moods.push("potente", "intenso");
            if (emotions.includes("surprised")) cues.moods.push("meravigliato");
            if (emotions.includes("fearful")) cues.moods.push("misterioso", "drammatico");
            if (emotions.includes("neutral") || emotions.includes("no_face")) {
                // Basato su colori se non ci sono emozioni
                if (colors && colors.averageBrightness > 180) cues.moods.push("felice", "energico");
                if (colors && colors.averageBrightness < 70) cues.moods.push("misterioso", "drammatico");
                if (colors && colors.averageSaturation > 70) cues.moods.push("energico");
                if (colors && colors.averageSaturation < 30) cues.moods.push("rilassante", "riflessivo");
            }
        }

        if (userInputs.selectedGenres.length === 0) {
            // Basato su oggetti rilevati
            if (objects.some(obj => ['bird', 'tree', 'grass', 'mountain', 'sky'].includes(obj))) {
                cues.genres.push("ambient", "folk", "soundtrack");
            }
            if (objects.includes("person")) {
                if (emotions.includes("happy")) cues.genres.push("pop", "dance");
                else cues.genres.push("folk", "jazz");
            }
            if (objects.some(obj => ['car', 'bus', 'motorbike'].includes(obj))) {
                cues.genres.push("rock", "elettronica");
            }
            if (objects.some(obj => ['bottle', 'wine glass', 'cake'].includes(obj))) {
                cues.genres.push("jazz");
            }
        }

        if (userInputs.selectedInstruments.length === 0) {
            // Basato su mood suggerito
            if (cues.moods.some(m => ['triste', 'malinconico', 'riflessivo'].includes(m))) {
                cues.instruments.push("pianoforte", "violoncello", "archi lenti");
            }
            if (cues.moods.some(m => ['felice', 'energico'].includes(m))) {
                cues.instruments.push("chitarra elettrica", "batteria", "sintetizzatore");
            }
            if (cues.genres.includes("jazz")) {
                cues.instruments.push("sassofono", "pianoforte", "basso");
            }
            if (cues.genres.includes("folk")) {
                cues.instruments.push("chitarra acustica", "violino");
            }
        }

        // Remove duplicates
        for (const key in cues) {
            if (Array.isArray(cues[key])) {
                cues[key] = [...new Set(cues[key])];
            }
        }

        return cues;
    }

    function preselectCuesFromAnalysis(musicalCues) {
        if (!musicalCues) return;

        const preselectGroup = (groupItems, cueValues, groupName) => {
            const container = domElements[groupName + 'PillsContainer'];
            if (!container) return;

            let hasSelection = false;
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');

            checkboxes.forEach(checkbox => {
                const pillLabel = checkbox.closest('.checkbox-pill');
                const shouldBeChecked = cueValues.map(cv => cv.toLowerCase()).includes(checkbox.value.toLowerCase());

                checkbox.checked = shouldBeChecked;
                pillLabel.classList.toggle('selected', shouldBeChecked);
                if (shouldBeChecked) hasSelection = true;
            });

            // Automatically open the accordion if a selection was made
            const header = container.previousElementSibling;
            if (hasSelection && header && header.classList.contains('collapsible-cue-header') && !header.classList.contains('open')) {
                header.click();
            }
        };

        preselectGroup(moodItems, musicalCues.moods || [], 'mood');
        preselectGroup(genreItems, musicalCues.genres || [], 'genre');
        preselectGroup(instrumentItems, musicalCues.instruments || [], 'instrument');
        preselectGroup(rhythmItems, musicalCues.rhythms || [], 'rhythm');
    }

    function generateStableAudioPrompt(cues) {
        const parts = [];
        if (cues.moods.length > 0) parts.push(...cues.moods);
        if (cues.genres.length > 0) parts.push(...cues.genres);
        if (cues.instruments.length > 0) parts.push(...cues.instruments);
        if (cues.rhythms.length > 0) parts.push(...cues.rhythms);  // ← QUESTA RIGA MANCAVA!
        parts.push(`${cues.tempoBPM} BPM`);
        return [...new Set(parts)].join(', ') + ", high quality audio";
    }

    function generateAIDisplayContent(analysis, finalPrompt) {
        const { colors, objects, emotions } = analysis;
        const existingDetails = domElements.aiInsightsContent.querySelectorAll('h4, ul, #finalPromptForAI');
        existingDetails.forEach(el => el.remove());

        let html = "<h4>🔍 Analisi Immagine Reale:</h4><ul>";
        html += `<li><strong>Oggetti rilevati:</strong> ${objects.join(', ')}</li>`;
        html += `<li><strong>Emozioni facciali:</strong> ${emotions.join(', ')}</li>`;
        if (colors && colors.dominantColors) {
            html += "<li><strong>Colori dominanti:</strong><ul>";
            colors.dominantColors.forEach(c => {
                html += `<li><span class="color-swatch-inline" style="background-color: rgb(${c.r},${c.g},${c.b});"></span>rgb(${c.r},${c.g},${c.b}) - ${c.percentage}%</li>`;
            });
            html += "</ul></li>";
            html += `<li><strong>Luminosità media:</strong> ${colors.averageBrightness}/255</li>`;
            html += `<li><strong>Saturazione media:</strong> ${colors.averageSaturation}%</li>`;
            html += `<li><strong>Contrasto:</strong> ${colors.contrast}%</li>`;
        }
        html += "</ul>";
        html += `<div id="finalPromptForAI"><strong>🎵 Prompt Musicale Finale:</strong><br>${finalPrompt}</div>`;

        domElements.aiInsightsContent.insertAdjacentHTML('beforeend', html);
    }

    async function updateAIDisplayAndStablePrompt() {
        if (!currentImage) return;

        // 1. Run analysis if it hasn't been done yet for this image
        if (!imageAnalysisResults) {
            domElements.aiProcessingSimulationDiv.style.display = 'block';
            domElements.aiProcessingSimulationDiv.innerHTML = '<p>🧠 Analisi AI in corso...</p>';

            try {
                imageAnalysisResults = {
                    colors: analyzeImageAdvanced(currentImage),
                    objects: await detectObjectsInImage(currentImage),
                    emotions: await analyzeFacesInImage(currentImage)
                };
                console.log("🎯 Analisi completata:", imageAnalysisResults);
            } catch (error) {
                console.error("❌ Errore durante l'analisi:", error);
                imageAnalysisResults = {
                    colors: { dominantColors: [], averageBrightness: 128, contrast: 50, averageSaturation: 50 },
                    objects: ["oggetto_generico"],
                    emotions: ["neutral"]
                };
            }

            domElements.aiProcessingSimulationDiv.style.display = 'none';
            initialPreselectionDoneForCurrentImage = false;
        }

        // 2. Get current user inputs
        const userInputs = {
            selectedMoods: getSelectedCheckboxValues('mood'),
            selectedGenres: getSelectedCheckboxValues('genre'),
            selectedInstruments: getSelectedCheckboxValues('instrument'),
            selectedRhythms: getSelectedCheckboxValues('rhythm'),
            selectedBPM: domElements.bpmSlider.value,
        };

        // 3. Get musical cues based on analysis AND user inputs
        const musicalCues = getMusicalCues(imageAnalysisResults, userInputs);

        // 4. Pre-select pills if it's the first run for this image
        if (!initialPreselectionDoneForCurrentImage) {
            preselectCuesFromAnalysis(musicalCues);
        }

        // 5. Generate final prompt based on the combined (AI + User) cues
        const finalCues = {
            moods: getSelectedCheckboxValues('mood'),
            genres: getSelectedCheckboxValues('genre'),
            instruments: getSelectedCheckboxValues('instrument'),
            rhythms: getSelectedCheckboxValues('rhythm'),
            tempoBPM: domElements.bpmSlider.value,
        };
        stableAudioPromptForMusic = generateStableAudioPrompt(finalCues);

        // 6. Update the "What AI Sees" display
        generateAIDisplayContent(imageAnalysisResults, stableAudioPromptForMusic);
        domElements.aiInsightsSection.style.display = 'block';
        domElements.generateMusicButton.disabled = false;
    }

    async function processNewImage(imageSrc) {
        domElements.imagePreview.src = imageSrc;
        domElements.imagePreview.style.display = 'block';
        currentImage = new Image();

        currentImage.onload = async () => {
            imageAnalysisResults = null; // Reset for new image
            initialPreselectionDoneForCurrentImage = false; // Allow pre-selection again

            // Reset UI
            document.querySelectorAll('.checkbox-pill.selected').forEach(pill => pill.classList.remove('selected'));
            document.querySelectorAll('.checkbox-pills-group input').forEach(cb => cb.checked = false);
            document.querySelectorAll('.collapsible-cue-header.open').forEach(header => header.click());

            setStatusMessage("🎨 Immagine caricata! Analisi AI in corso...", "info");
            await updateAIDisplayAndStablePrompt();
            setStatusMessage("✅ Analisi completata! Ora puoi generare la musica.", "success");
        };
        currentImage.src = imageSrc;
    }

    // ===================================================================
    // 🔌 COLLEGAMENTO EVENTI
    // ===================================================================

    // Eventi fotocamera
    if (domElements.takePictureButton) domElements.takePictureButton.addEventListener('click', openCamera);
    if (domElements.closeCameraButton) domElements.closeCameraButton.addEventListener('click', closeCamera);
    if (domElements.captureImageButton) domElements.captureImageButton.addEventListener('click', captureImage);
    if (domElements.switchCameraButton) domElements.switchCameraButton.addEventListener('click', switchCamera);

    // Eventi caricamento immagine
    domElements.imageUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => processNewImage(e.target.result);
            reader.readAsDataURL(file);
        }
    });

    // Eventi musicali
    domElements.generateMusicButton.addEventListener('click', async () => {
        console.log("🎵 Click su 'Genera Musica' con prompt:", stableAudioPromptForMusic);
        // La tua logica originale di generazione musicale va qui
        // ... chiamata AJAX etc.
    });

    // ===================================================================
    // 🚀 INIZIALIZZAZIONE
    // ===================================================================

    // Setup UI
    populateCheckboxPills(domElements.moodPillsContainer, moodItems, 'mood');
    populateCheckboxPills(domElements.genrePillsContainer, genreItems, 'genre');
    populateCheckboxPills(domElements.instrumentPillsContainer, instrumentItems, 'instrument');
    populateCheckboxPills(domElements.rhythmPillsContainer, rhythmItems, 'rhythm');
    setupCollapsibleSections();

    domElements.bpmSlider.addEventListener('input', () => {
        domElements.bpmValueDisplay.textContent = domElements.bpmSlider.value;
        if (currentImage) updateAIDisplayAndStablePrompt(); // Update prompt on BPM change
    });

    // Carica i modelli all'avvio
    await loadModels();

    console.log("🎉 PictoSound completamente inizializzato con AI reale e funzioni camera avanzate!");
});