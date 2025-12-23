import { Report, Regie, SensPose, ReportTemplate, SyncQueueItem, CacheEntry, Photo } from '../types';

const DB_NAME = 'MV3ProDB';
const DB_VERSION = 1;

let db: IDBDatabase | null = null;

export async function initDB(): Promise<IDBDatabase> {
  if (db) return db;

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => {
      db = request.result;
      resolve(db);
    };

    request.onupgradeneeded = (event) => {
      const database = (event.target as IDBOpenDBRequest).result;

      if (!database.objectStoreNames.contains('reports')) {
        const reportStore = database.createObjectStore('reports', { keyPath: 'id' });
        reportStore.createIndex('user_id', 'user_id', { unique: false });
        reportStore.createIndex('status', 'status', { unique: false });
        reportStore.createIndex('date', 'date', { unique: false });
      }

      if (!database.objectStoreNames.contains('regie')) {
        const regieStore = database.createObjectStore('regie', { keyPath: 'id' });
        regieStore.createIndex('user_id', 'user_id', { unique: false });
        regieStore.createIndex('status', 'status', { unique: false });
        regieStore.createIndex('date', 'date', { unique: false });
      }

      if (!database.objectStoreNames.contains('sens_pose')) {
        const sensPoseStore = database.createObjectStore('sens_pose', { keyPath: 'id' });
        sensPoseStore.createIndex('user_id', 'user_id', { unique: false });
        sensPoseStore.createIndex('status', 'status', { unique: false });
        sensPoseStore.createIndex('date', 'date', { unique: false });
      }

      if (!database.objectStoreNames.contains('templates')) {
        const templateStore = database.createObjectStore('templates', { keyPath: 'id' });
        templateStore.createIndex('user_id', 'user_id', { unique: false });
        templateStore.createIndex('report_type', 'report_type', { unique: false });
      }

      if (!database.objectStoreNames.contains('sync_queue')) {
        const syncStore = database.createObjectStore('sync_queue', { keyPath: 'id' });
        syncStore.createIndex('user_id', 'user_id', { unique: false });
        syncStore.createIndex('status', 'status', { unique: false });
        syncStore.createIndex('priority', 'priority', { unique: false });
      }

      if (!database.objectStoreNames.contains('cache')) {
        const cacheStore = database.createObjectStore('cache', { keyPath: 'id' });
        cacheStore.createIndex('cache_key', 'cache_key', { unique: true });
        cacheStore.createIndex('cache_type', 'cache_type', { unique: false });
        cacheStore.createIndex('expires_at', 'expires_at', { unique: false });
      }

      if (!database.objectStoreNames.contains('photos')) {
        const photoStore = database.createObjectStore('photos', { keyPath: 'id' });
        photoStore.createIndex('uploaded', 'uploaded', { unique: false });
      }
    };
  });
}

async function getStore(storeName: string, mode: IDBTransactionMode = 'readonly'): Promise<IDBObjectStore> {
  const database = await initDB();
  const transaction = database.transaction(storeName, mode);
  return transaction.objectStore(storeName);
}

export async function saveReport(report: Report): Promise<void> {
  const store = await getStore('reports', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.put(report);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function getReports(userId: string, status?: string): Promise<Report[]> {
  const store = await getStore('reports');
  const index = status ? store.index('status') : store.index('user_id');
  const key = status || userId;

  return new Promise((resolve, reject) => {
    const request = index.getAll(key);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

export async function getReport(id: string): Promise<Report | undefined> {
  const store = await getStore('reports');
  return new Promise((resolve, reject) => {
    const request = store.get(id);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

export async function deleteReport(id: string): Promise<void> {
  const store = await getStore('reports', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.delete(id);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function saveRegie(regie: Regie): Promise<void> {
  const store = await getStore('regie', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.put(regie);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function getRegies(userId: string): Promise<Regie[]> {
  const store = await getStore('regie');
  const index = store.index('user_id');

  return new Promise((resolve, reject) => {
    const request = index.getAll(userId);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

export async function saveSensPose(sensPose: SensPose): Promise<void> {
  const store = await getStore('sens_pose', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.put(sensPose);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function getSensPoses(userId: string): Promise<SensPose[]> {
  const store = await getStore('sens_pose');
  const index = store.index('user_id');

  return new Promise((resolve, reject) => {
    const request = index.getAll(userId);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

export async function saveTemplate(template: ReportTemplate): Promise<void> {
  const store = await getStore('templates', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.put(template);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function getTemplates(userId: string, reportType?: string): Promise<ReportTemplate[]> {
  const store = await getStore('templates');
  const index = reportType ? store.index('report_type') : store.index('user_id');
  const key = reportType || userId;

  return new Promise((resolve, reject) => {
    const request = index.getAll(key);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

export async function addToSyncQueue(item: SyncQueueItem): Promise<void> {
  const store = await getStore('sync_queue', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.add(item);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function getSyncQueue(): Promise<SyncQueueItem[]> {
  const store = await getStore('sync_queue');
  const index = store.index('status');

  return new Promise((resolve, reject) => {
    const request = index.getAll('pending');
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

export async function removeSyncQueueItem(id: string): Promise<void> {
  const store = await getStore('sync_queue', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.delete(id);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function updateSyncQueueItem(id: string, updates: Partial<SyncQueueItem>): Promise<void> {
  const store = await getStore('sync_queue', 'readwrite');
  const item = await new Promise<SyncQueueItem>((resolve, reject) => {
    const request = store.get(id);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });

  if (item) {
    const updated = { ...item, ...updates };
    await new Promise((resolve, reject) => {
      const request = store.put(updated);
      request.onsuccess = () => resolve(undefined);
      request.onerror = () => reject(request.error);
    });
  }
}

export async function saveCache(entry: CacheEntry): Promise<void> {
  const store = await getStore('cache', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.put(entry);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function getCache(key: string): Promise<CacheEntry | undefined> {
  const store = await getStore('cache');
  const index = store.index('cache_key');

  return new Promise((resolve, reject) => {
    const request = index.get(key);
    request.onsuccess = () => {
      const result = request.result;
      if (result && new Date(result.expires_at) > new Date()) {
        resolve(result);
      } else {
        resolve(undefined);
      }
    };
    request.onerror = () => reject(request.error);
  });
}

export async function savePhoto(photo: Photo): Promise<void> {
  const store = await getStore('photos', 'readwrite');
  await new Promise((resolve, reject) => {
    const request = store.put(photo);
    request.onsuccess = () => resolve(undefined);
    request.onerror = () => reject(request.error);
  });
}

export async function getUnuploadedPhotos(): Promise<Photo[]> {
  const store = await getStore('photos');
  const index = store.index('uploaded');

  return new Promise((resolve, reject) => {
    const request = index.getAll(false);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}
