class DraftManager {
  constructor() {
    this.autoSaveInterval = null;
    this.autoSaveDelay = 30000;
    this.formElement = null;
  }

  init(formElement) {
    this.formElement = formElement;
    this.startAutoSave();
    this.checkExistingDraft();
  }

  startAutoSave() {
    if (this.autoSaveInterval) return;

    this.autoSaveInterval = setInterval(() => {
      this.saveDraft();
    }, this.autoSaveDelay);

    this.formElement.addEventListener('input', () => {
      clearInterval(this.autoSaveInterval);
      this.autoSaveInterval = setInterval(() => {
        this.saveDraft();
      }, this.autoSaveDelay);
    });
  }

  stopAutoSave() {
    if (this.autoSaveInterval) {
      clearInterval(this.autoSaveInterval);
      this.autoSaveInterval = null;
    }
  }

  async saveDraft() {
    if (!this.formElement) return;

    const formData = this.getFormData();

    if (Object.keys(formData).length === 0) return;

    if (window.offlineManager) {
      await window.offlineManager.saveDraft(formData);
      this.showAutoSaveIndicator();
    }
  }

  getFormData() {
    const data = {};
    const formData = new FormData(this.formElement);

    for (const [key, value] of formData.entries()) {
      if (value && key !== 'action' && key !== 'token') {
        data[key] = value;
      }
    }

    if (window.timerManager) {
      const timerState = window.timerManager.getState();
      data.timer = timerState;
    }

    if (window.gpsManager && window.gpsManager.currentPosition) {
      data.gps = window.gpsManager.currentPosition;
    }

    return data;
  }

  async checkExistingDraft() {
    if (!window.offlineManager) return;

    const draft = await window.offlineManager.loadDraft();

    if (draft && draft.data) {
      const age = Date.now() - draft.timestamp;
      const ageMinutes = Math.round(age / 60000);

      if (ageMinutes < 1440) {
        this.showDraftRestorePrompt(draft.data, ageMinutes);
      }
    }
  }

  showDraftRestorePrompt(data, ageMinutes) {
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

    modal.innerHTML = `
      <div style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; width: 100%;">
        <h3 style="margin: 0 0 12px 0; color: #0891b2; font-size: 18px;">📝 Brouillon trouvé</h3>
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 14px;">
          Un brouillon a été sauvegardé il y a ${ageMinutes} minute${ageMinutes > 1 ? 's' : ''}.
          Voulez-vous le restaurer ?
        </p>
        <div style="display: flex; gap: 12px;">
          <button id="restoreDraft" style="flex: 1; padding: 12px; background: #0891b2; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            ✅ Restaurer
          </button>
          <button id="discardDraft" style="flex: 1; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            ❌ Nouveau
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('restoreDraft').onclick = () => {
      this.restoreDraft(data);
      modal.remove();
    };

    document.getElementById('discardDraft').onclick = async () => {
      await window.offlineManager.clearDraft();
      modal.remove();
    };
  }

  restoreDraft(data) {
    for (const [key, value] of Object.entries(data)) {
      if (key === 'timer' || key === 'gps') continue;

      const input = this.formElement.querySelector(`[name="${key}"]`);
      if (input) {
        if (input.type === 'checkbox') {
          input.checked = value === 'on' || value === true;
        } else {
          input.value = value;
        }

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    if (data.timer && window.timerManager) {
      if (data.timer.startTime) {
        window.timerManager.startTime = data.timer.startTime;
        window.timerManager.isRunning = data.timer.isRunning;
        window.timerManager.isPaused = data.timer.isPaused;
        if (data.timer.isRunning) {
          window.timerManager.startInterval();
        }
      }
    }

    this.showNotification('Brouillon restauré avec succès', 'success');
  }

  async clearDraft() {
    if (window.offlineManager) {
      await window.offlineManager.clearDraft();
    }
  }

  showAutoSaveIndicator() {
    const indicator = document.getElementById('autoSaveIndicator');
    if (!indicator) return;

    indicator.textContent = '💾 Sauvegardé';
    indicator.style.opacity = '1';

    setTimeout(() => {
      indicator.style.opacity = '0';
    }, 2000);
  }

  showNotification(message, type = 'info') {
    const colors = {
      success: '#10b981',
      info: '#0891b2'
    };

    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 70px;
      left: 50%;
      transform: translateX(-50%);
      background: ${colors[type]};
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      z-index: 10000;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), 2000);
  }
}

window.draftManager = new DraftManager();
