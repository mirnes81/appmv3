class CameraManager {
  constructor() {
    this.maxPhotos = 10;
    this.photos = [];
    this.compressionQuality = 0.8;
  }

  async capturePhoto(categoryOverlay = null) {
    return new Promise((resolve, reject) => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.capture = 'environment';

      input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) {
          reject('Aucune photo sélectionnée');
          return;
        }

        try {
          const processedPhoto = await this.processPhoto(file, categoryOverlay);
          resolve(processedPhoto);
        } catch (error) {
          reject(error);
        }
      };

      input.click();
    });
  }

  async processPhoto(file, categoryOverlay = null) {
    const compressedFile = await this.compressImage(file);

    let watermarkedFile = compressedFile;
    if (categoryOverlay) {
      watermarkedFile = await this.addWatermark(compressedFile, categoryOverlay);
    }

    return {
      file: watermarkedFile,
      preview: await this.getPreviewUrl(watermarkedFile),
      size: watermarkedFile.size,
      originalSize: file.size,
      compressionRatio: Math.round((1 - watermarkedFile.size / file.size) * 100)
    };
  }

  async compressImage(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();

      reader.onload = (e) => {
        const img = new Image();

        img.onload = () => {
          const canvas = document.createElement('canvas');
          let width = img.width;
          let height = img.height;

          const maxDimension = 1920;
          if (width > maxDimension || height > maxDimension) {
            if (width > height) {
              height = (height / width) * maxDimension;
              width = maxDimension;
            } else {
              width = (width / height) * maxDimension;
              height = maxDimension;
            }
          }

          canvas.width = width;
          canvas.height = height;

          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, width, height);

          canvas.toBlob(
            (blob) => {
              const compressedFile = new File([blob], file.name, {
                type: 'image/jpeg',
                lastModified: Date.now()
              });
              resolve(compressedFile);
            },
            'image/jpeg',
            this.compressionQuality
          );
        };

        img.onerror = () => reject('Erreur chargement image');
        img.src = e.target.result;
      };

      reader.onerror = () => reject('Erreur lecture fichier');
      reader.readAsDataURL(file);
    });
  }

  async addWatermark(file, overlayText) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();

      reader.onload = (e) => {
        const img = new Image();

        img.onload = () => {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;

          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);

          const now = new Date();
          const dateStr = now.toLocaleDateString('fr-FR');
          const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

          ctx.font = 'bold 32px Arial';
          ctx.textAlign = 'left';
          ctx.textBaseline = 'top';

          const padding = 20;
          const lineHeight = 40;
          let y = padding;

          const drawText = (text, color, bgColor) => {
            const metrics = ctx.measureText(text);
            const textWidth = metrics.width;

            ctx.fillStyle = bgColor;
            ctx.fillRect(padding - 10, y - 5, textWidth + 20, lineHeight);

            ctx.fillStyle = color;
            ctx.fillText(text, padding, y);

            y += lineHeight + 5;
          };

          drawText(overlayText, '#fff', 'rgba(8, 145, 178, 0.9)');
          drawText(`📅 ${dateStr} ${timeStr}`, '#fff', 'rgba(0, 0, 0, 0.7)');

          const projetInfo = document.querySelector('[name="fk_projet"] option:checked');
          if (projetInfo && projetInfo.value) {
            drawText(`🏗️ ${projetInfo.textContent.substring(0, 30)}`, '#fff', 'rgba(0, 0, 0, 0.7)');
          }

          canvas.toBlob(
            (blob) => {
              const watermarkedFile = new File([blob], file.name, {
                type: 'image/jpeg',
                lastModified: Date.now()
              });
              resolve(watermarkedFile);
            },
            'image/jpeg',
            0.9
          );
        };

        img.onerror = () => reject('Erreur chargement image');
        img.src = e.target.result;
      };

      reader.onerror = () => reject('Erreur lecture fichier');
      reader.readAsDataURL(file);
    });
  }

  getPreviewUrl(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = (e) => resolve(e.target.result);
      reader.onerror = () => reject('Erreur génération preview');
      reader.readAsDataURL(file);
    });
  }

  async captureWithCategory(category) {
    const categoryLabels = {
      'avant': '🔵 AVANT',
      'pendant': '🟡 PENDANT',
      'apres': '🟢 APRÈS'
    };

    const overlay = categoryLabels[category] || categoryLabels['pendant'];

    try {
      const photo = await this.capturePhoto(overlay);
      photo.category = category;
      return photo;
    } catch (error) {
      throw error;
    }
  }

  formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }
}

window.cameraManager = new CameraManager();
