class ValidationManager {
  constructor() {
    this.rules = [];
    this.warnings = [];
  }

  validate(formData) {
    this.warnings = [];

    this.checkSurfaceVsTime(formData);
    this.checkFormatWithSurface(formData);
    this.checkPhotos(formData);
    this.checkWorkDescription(formData);
    this.checkTimeLogic(formData);
    this.checkZoneCompletion(formData);

    return {
      isValid: true,
      warnings: this.warnings,
      hasWarnings: this.warnings.length > 0
    };
  }

  checkSurfaceVsTime(formData) {
    const surface = parseFloat(formData.surface_carrelee) || 0;
    const heuresDebut = formData.heures_debut;
    const heuresFin = formData.heures_fin;

    if (heuresDebut && heuresFin && surface === 0) {
      const debut = new Date(`2000-01-01 ${heuresDebut}`);
      const fin = new Date(`2000-01-01 ${heuresFin}`);
      const hours = (fin - debut) / (1000 * 60 * 60);

      if (hours > 4) {
        this.addWarning(
          'Surface anormale',
          `Vous avez travaillé ${hours.toFixed(1)}h mais la surface est à 0m². Oubli ?`,
          'warning'
        );
      }
    }

    if (surface > 0 && heuresDebut && heuresFin) {
      const debut = new Date(`2000-01-01 ${heuresDebut}`);
      const fin = new Date(`2000-01-01 ${heuresFin}`);
      const hours = (fin - debut) / (1000 * 60 * 60);
      const ratio = surface / hours;

      if (ratio > 30) {
        this.addWarning(
          'Productivité inhabituelle',
          `${ratio.toFixed(1)}m²/h semble très élevé. Vérifiez les données.`,
          'info'
        );
      }

      if (ratio < 2 && hours > 2) {
        this.addWarning(
          'Productivité faible',
          `${ratio.toFixed(1)}m²/h semble faible. Des problèmes rencontrés ?`,
          'info'
        );
      }
    }
  }

  checkFormatWithSurface(formData) {
    const surface = parseFloat(formData.surface_carrelee) || 0;
    const format = formData.format_carreaux;

    if (surface > 0 && !format) {
      this.addWarning(
        'Format manquant',
        'Vous avez indiqué une surface mais pas le format des carreaux.',
        'warning'
      );
    }
  }

  checkPhotos(formData) {
    const photoCount = formData.photoCount || 0;

    if (photoCount === 0) {
      this.addWarning(
        'Aucune photo',
        'Pensez à ajouter au moins une photo pour documenter le travail.',
        'info'
      );
    }

    if (photoCount === 1) {
      this.addWarning(
        'Une seule photo',
        'Il est recommandé d\'ajouter plusieurs photos (avant/pendant/après).',
        'info'
      );
    }
  }

  checkWorkDescription(formData) {
    const travaux = formData.travaux_realises || '';

    if (travaux.length < 10) {
      this.addWarning(
        'Description courte',
        'La description des travaux est très courte. Ajoutez plus de détails.',
        'info'
      );
    }
  }

  checkTimeLogic(formData) {
    const heuresDebut = formData.heures_debut;
    const heuresFin = formData.heures_fin;

    if (heuresDebut && heuresFin) {
      const debut = new Date(`2000-01-01 ${heuresDebut}`);
      const fin = new Date(`2000-01-01 ${heuresFin}`);

      if (fin <= debut) {
        this.addWarning(
          'Heures invalides',
          'L\'heure de fin doit être après l\'heure de début.',
          'error'
        );
      }

      const hours = (fin - debut) / (1000 * 60 * 60);
      if (hours > 12) {
        this.addWarning(
          'Durée très longue',
          `${hours.toFixed(1)}h de travail. Vérifiez les heures ou ajoutez les pauses.`,
          'warning'
        );
      }
    }
  }

  checkZoneCompletion(formData) {
    const zone = formData.zone_travail || '';
    const typeLieu = formData.type_lieu || '';

    if (!zone) {
      this.addWarning(
        'Zone manquante',
        'Veuillez sélectionner au moins une zone de travail.',
        'warning'
      );
    }

    if (!typeLieu) {
      this.addWarning(
        'Type de lieu manquant',
        'Veuillez sélectionner le type de lieu (Villa, Appartement, etc.).',
        'warning'
      );
    }
  }

  addWarning(title, message, level = 'info') {
    this.warnings.push({
      title,
      message,
      level,
      icon: this.getIcon(level)
    });
  }

  getIcon(level) {
    const icons = {
      error: '🔴',
      warning: '⚠️',
      info: 'ℹ️'
    };
    return icons[level] || 'ℹ️';
  }

  showValidationResults(result) {
    if (!result.hasWarnings) return;

    const modal = document.createElement('div');
    modal.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      padding: 20px;
    `;

    const warningsHtml = result.warnings.map(w => `
      <div style="padding: 12px; background: ${this.getLevelColor(w.level)}; border-radius: 8px; margin-bottom: 8px;">
        <div style="font-weight: 600; margin-bottom: 4px;">${w.icon} ${w.title}</div>
        <div style="font-size: 13px; opacity: 0.9;">${w.message}</div>
      </div>
    `).join('');

    modal.innerHTML = `
      <div style="background: white; border-radius: 12px; padding: 24px; max-width: 450px; width: 100%; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin: 0 0 16px 0; color: #0891b2; font-size: 18px;">⚠️ Attention</h3>
        <div style="margin-bottom: 20px;">
          ${warningsHtml}
        </div>
        <div style="display: flex; gap: 12px;">
          <button id="continueAnyway" style="flex: 1; padding: 12px; background: #0891b2; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            ✓ Continuer quand même
          </button>
          <button id="goBack" style="flex: 1; padding: 12px; background: #64748b; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            ← Corriger
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    return new Promise((resolve) => {
      document.getElementById('continueAnyway').onclick = () => {
        modal.remove();
        resolve(true);
      };

      document.getElementById('goBack').onclick = () => {
        modal.remove();
        resolve(false);
      };
    });
  }

  getLevelColor(level) {
    const colors = {
      error: '#fee2e2',
      warning: '#fef3c7',
      info: '#dbeafe'
    };
    return colors[level] || colors.info;
  }
}

window.validationManager = new ValidationManager();
