import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import * as db from '../utils/db';
import * as api from '../utils/api';

interface OfflineContextType {
  isOnline: boolean;
  isSyncing: boolean;
  syncProgress: number;
  pendingActions: number;
  sync: () => Promise<void>;
}

const OfflineContext = createContext<OfflineContextType | undefined>(undefined);

export function OfflineProvider({ children }: { children: ReactNode }) {
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [isSyncing, setIsSyncing] = useState(false);
  const [syncProgress, setSyncProgress] = useState(0);
  const [pendingActions, setPendingActions] = useState(0);

  useEffect(() => {
    const handleOnline = () => {
      setIsOnline(true);
      sync();
    };

    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    updatePendingCount();

    const interval = setInterval(() => {
      if (navigator.onLine && !isSyncing) {
        sync();
      }
    }, 60000);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      clearInterval(interval);
    };
  }, []);

  const updatePendingCount = async () => {
    const queue = await db.getSyncQueue();
    setPendingActions(queue.length);
  };

  const sync = async () => {
    if (isSyncing || !navigator.onLine) return;

    try {
      setIsSyncing(true);
      setSyncProgress(0);

      const queue = await db.getSyncQueue();
      if (queue.length === 0) {
        return;
      }

      const sortedQueue = queue.sort((a, b) => a.priority - b.priority);

      for (let i = 0; i < sortedQueue.length; i++) {
        const item = sortedQueue[i];

        try {
          switch (item.action_type) {
            case 'create_report':
              await api.createReport(item.payload);
              break;
            case 'create_regie':
              await api.createRegie(item.payload);
              break;
            case 'create_sens_pose':
              await api.createSensPose(item.payload);
              break;
            case 'upload_photo':
              if (item.payload.reportId && item.payload.photo) {
                await api.uploadPhoto(item.payload.reportId, item.payload.photo);
              }
              break;
            default:
              console.warn('Unknown action type:', item.action_type);
          }

          await db.removeSyncQueueItem(item.id);
        } catch (error) {
          console.error('Sync item failed:', error);
          await db.updateSyncQueueItem(item.id, {
            retry_count: item.retry_count + 1,
            error_message: error instanceof Error ? error.message : 'Unknown error'
          });
        }

        setSyncProgress(((i + 1) / sortedQueue.length) * 100);
      }

      await updatePendingCount();
    } catch (error) {
      console.error('Sync failed:', error);
    } finally {
      setIsSyncing(false);
      setSyncProgress(0);
    }
  };

  return (
    <OfflineContext.Provider
      value={{
        isOnline,
        isSyncing,
        syncProgress,
        pendingActions,
        sync
      }}
    >
      {children}
    </OfflineContext.Provider>
  );
}

export function useOffline() {
  const context = useContext(OfflineContext);
  if (context === undefined) {
    throw new Error('useOffline must be used within OfflineProvider');
  }
  return context;
}
