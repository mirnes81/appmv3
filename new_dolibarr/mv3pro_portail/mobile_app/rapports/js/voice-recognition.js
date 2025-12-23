class VoiceRecognition {
  constructor() {
    this.recognition = null;
    this.isListening = false;
    this.init();
  }

  init() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      console.warn('Reconnaissance vocale non supportée');
      return;
    }

    this.recognition = new SpeechRecognition();
    this.recognition.lang = 'fr-FR';
    this.recognition.continuous = false;
    this.recognition.interimResults = false;
    this.recognition.maxAlternatives = 1;
  }

  isSupported() {
    return this.recognition !== null;
  }

  async startListening(onResult, onError) {
    if (!this.recognition) {
      onError('Reconnaissance vocale non supportée');
      return;
    }

    this.isListening = true;

    this.recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      const confidence = event.results[0][0].confidence;
      onResult(transcript, confidence);
    };

    this.recognition.onerror = (event) => {
      this.isListening = false;
      onError(this.getErrorMessage(event.error));
    };

    this.recognition.onend = () => {
      this.isListening = false;
    };

    try {
      this.recognition.start();
    } catch (error) {
      this.isListening = false;
      onError('Erreur démarrage reconnaissance vocale');
    }
  }

  stopListening() {
    if (this.recognition && this.isListening) {
      this.recognition.stop();
      this.isListening = false;
    }
  }

  getErrorMessage(error) {
    const messages = {
      'no-speech': 'Aucune parole détectée',
      'audio-capture': 'Microphone non accessible',
      'not-allowed': 'Permission microphone refusée',
      'network': 'Erreur réseau',
      'aborted': 'Reconnaissance annulée'
    };
    return messages[error] || 'Erreur reconnaissance vocale';
  }

  parseWorkDescription(transcript) {
    const text = transcript.toLowerCase();
    const result = {
      surface: null,
      format: null,
      zone: null,
      type_pose: null,
      description: transcript
    };

    const surfaceMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:m[²2]|mètre)/i);
    if (surfaceMatch) {
      result.surface = parseFloat(surfaceMatch[1].replace(',', '.'));
    }

    const formatMatch = text.match(/(\d+)\s*[x×]\s*(\d+)/);
    if (formatMatch) {
      result.format = `${formatMatch[1]}×${formatMatch[2]}`;
    }

    const zones = {
      'salon': 'Salon',
      'cuisine': 'Cuisine',
      'chambre': 'Chambre',
      'salle de bain': 'Salle de bain',
      'douche': 'Douche',
      'wc': 'WC',
      'toilette': 'WC',
      'couloir': 'Couloir',
      'entrée': 'Entrée',
      'terrasse': 'Terrasse',
      'balcon': 'Balcon',
      'garage': 'Garage',
      'escalier': 'Escalier'
    };

    for (const [key, value] of Object.entries(zones)) {
      if (text.includes(key)) {
        result.zone = value;
        break;
      }
    }

    const poses = {
      'droite': 'Droite',
      'décalée': 'Droite décalée',
      'diagonale': 'Diagonale',
      'chevron': 'Chevron',
      'opus': 'Opus'
    };

    for (const [key, value] of Object.entries(poses)) {
      if (text.includes(key)) {
        result.type_pose = value;
        break;
      }
    }

    return result;
  }
}

window.voiceRecognition = new VoiceRecognition();
