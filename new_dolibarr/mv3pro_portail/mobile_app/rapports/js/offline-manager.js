class OfflineManager {
  constructor() {
    this.dbName = 'mv3_rapports_db';
    this.dbVersion = 1;
    this.db = null;
    this.init();
  }

  async init() {
    this.db = await this.openDB();
    this.checkPendingRapports();
    this.setupOnlineListener();
  }

  openDB() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result);

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        if (!db.objectStoreNames.contains('pending_rapports')) {
          db.createObjectStore('pending_rapports', { keyPath: 'id', autoIncrement: true });
        }

        if (!db.objectStoreNames.contains('draft_rapports')) {
          db.createObjectStore('draft_rapports', { keyPath: 'id' });
        }
      };
    });
  }

  async saveDraft(formData) {
    const tx = this.db.transaction('draft_rapports', 'readwrite');
    const store = tx.objectStore('draft_rapports');

    const draft = {
      id: 'current',
      data: formData,
      timestamp: Date.now()
    };

    await store.put(draft);
  }

  async loadDraft() {
    const tx = this.db.transaction('draft_rapports', 'readonly');
    const store = tx.objectStore('draft_rapports');
    return await store.get('current');
  }

  async clearDraft() {
    const tx = this.db.transaction('draft_rapports', 'readwrite');
    const store = tx.objectStore('draft_rapports');
    await store.delete('current');
  }

  async saveOfflineRapport(formData) {
    const tx = this.db.transaction('pending_rapports', 'readwrite');
    const store = tx.objectStore('pending_rapports');

    const rapport = {
      data: formData,
      timestamp: Date.now()
    };

    await store.add(rapport);
    this.showNotification('Rapport enregistré hors-ligne', 'success');
  }

  async checkPendingRapports() {
    const tx = this.db.transaction('pending_rapports', 'readonly');
    const store = tx.objectStore('pending_rapports');
    const count = await store.count();

    if (count > 0) {
      this.showNotification(`${count} rapport(s) en attente de synchronisation`, 'warning');
    }
  }

  setupOnlineListener() {
    window.addEventListener('online', async () => {
      this.showNotification('Connexion rétablie. Synchronisation...', 'info');
      await this.syncPendingRapports();
    });

    window.addEventListener('offline', () => {
      this.showNotification('Mode hors-ligne activé', 'warning');
    });
  }

  async syncPendingRapports() {
    if (!navigator.onLine) return;

    const tx = this.db.transaction('pending_rapports', 'readwrite');
    const store = tx.objectStore('pending_rapports');
    const rapports = await store.getAll();

    for (const rapport of rapports) {
      try {
        const formData = new FormData();
        for (const [key, value] of Object.entries(rapport.data)) {
          formData.append(key, value);
        }
        formData.append('action', 'create');

        const response = await fetch('new.php', {
          method: 'POST',
          body: formData
        });

        if (response.ok) {
          await store.delete(rapport.id);
          this.showNotification('Rapport synchronisé avec succès', 'success');
        }
      } catch (error) {
        console.error('Erreur sync:', error);
      }
    }
  }

  showNotification(message, type = 'info') {
    const colors = {
      success: '#10b981',
      error: '#ef4444',
      warning: '#f59e0b',
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

    setTimeout(() => notification.remove(), 3000);
  }

  isOnline() {
    return navigator.onLine;
  }
}

window.offlineManager = new OfflineManager();
