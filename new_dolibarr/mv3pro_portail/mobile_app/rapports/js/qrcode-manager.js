class QRCodeManager {
  constructor() {
    this.stream = null;
    this.scanning = false;
  }

  isSupported() {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  }

  async startScanning(videoElement, onScan, onError) {
    if (!this.isSupported()) {
      onError('Scan QR non supporté sur cet appareil');
      return;
    }

    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
      });

      videoElement.srcObject = this.stream;
      videoElement.play();

      this.scanning = true;
      this.scanFrame(videoElement, onScan);
    } catch (error) {
      onError('Impossible d\'accéder à la caméra: ' + error.message);
    }
  }

  scanFrame(videoElement, onScan) {
    if (!this.scanning) return;

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    canvas.width = videoElement.videoWidth;
    canvas.height = videoElement.videoHeight;

    context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

    try {
      const code = jsQR(imageData.data, imageData.width, imageData.height);

      if (code) {
        this.scanning = false;
        this.stopScanning();
        onScan(code.data);
        return;
      }
    } catch (error) {
      console.error('Erreur scan QR:', error);
    }

    requestAnimationFrame(() => this.scanFrame(videoElement, onScan));
  }

  stopScanning() {
    this.scanning = false;

    if (this.stream) {
      this.stream.getTracks().forEach(track => track.stop());
      this.stream = null;
    }
  }

  parseProjectQR(data) {
    try {
      const parsed = JSON.parse(data);

      if (parsed.type === 'mv3_project') {
        return {
          valid: true,
          projetId: parsed.projet_id,
          clientId: parsed.client_id,
          projetRef: parsed.projet_ref,
          typeLieu: parsed.type_lieu,
          numeroLieu: parsed.numero_lieu
        };
      }

      return { valid: false, error: 'QR Code non valide' };
    } catch (error) {
      return { valid: false, error: 'Format QR invalide' };
    }
  }

  showScanModal(onScan) {
    const modal = document.createElement('div');
    modal.id = 'qrScanModal';
    modal.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.95);
      z-index: 10000;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
    `;

    modal.innerHTML = `
      <div style="width: 100%; max-width: 400px; text-align: center;">
        <h3 style="color: white; margin-bottom: 20px; font-size: 20px;">📱 Scanner le QR Code</h3>

        <div style="position: relative; width: 100%; aspect-ratio: 1; background: #000; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
          <video id="qrVideo" style="width: 100%; height: 100%; object-fit: cover;"></video>

          <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 70%; height: 70%; border: 3px solid #0891b2; border-radius: 12px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);"></div>
        </div>

        <div style="color: white; margin-bottom: 16px; font-size: 14px;">
          Placez le QR Code du projet dans le cadre
        </div>

        <button id="cancelScan" style="padding: 12px 24px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px;">
          ✕ Annuler
        </button>
      </div>
    `;

    document.body.appendChild(modal);

    const video = document.getElementById('qrVideo');

    this.startScanning(
      video,
      (data) => {
        modal.remove();
        onScan(data);
      },
      (error) => {
        modal.remove();
        alert('Erreur: ' + error);
      }
    );

    document.getElementById('cancelScan').onclick = () => {
      this.stopScanning();
      modal.remove();
    };
  }

  generateProjectQR(projectData) {
    const data = {
      type: 'mv3_project',
      projet_id: projectData.projetId,
      client_id: projectData.clientId,
      projet_ref: projectData.projetRef,
      type_lieu: projectData.typeLieu || '',
      numero_lieu: projectData.numeroLieu || '',
      generated: Date.now()
    };

    return JSON.stringify(data);
  }
}

window.qrcodeManager = new QRCodeManager();
