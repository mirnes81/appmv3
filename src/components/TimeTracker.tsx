import { useState, useEffect } from 'react';
import { Play, Pause, Square, Clock } from 'lucide-react';

interface TimeEntry {
  start: Date;
  end?: Date;
  duration: number;
}

interface TimeTrackerProps {
  fichinterId?: number;
  onTimeUpdate?: (totalSeconds: number) => void;
}

export default function TimeTracker({ fichinterId, onTimeUpdate }: TimeTrackerProps) {
  const [isRunning, setIsRunning] = useState(false);
  const [isPaused, setIsPaused] = useState(false);
  const [currentStart, setCurrentStart] = useState<Date | null>(null);
  const [pauseStart, setPauseStart] = useState<Date | null>(null);
  const [totalSeconds, setTotalSeconds] = useState(0);
  const [displaySeconds, setDisplaySeconds] = useState(0);
  const [entries, setEntries] = useState<TimeEntry[]>([]);

  useEffect(() => {
    let interval: number | undefined;

    if (isRunning && !isPaused && currentStart) {
      interval = window.setInterval(() => {
        const now = new Date();
        const elapsed = Math.floor((now.getTime() - currentStart.getTime()) / 1000);
        setDisplaySeconds(totalSeconds + elapsed);
      }, 1000);
    }

    return () => {
      if (interval) clearInterval(interval);
    };
  }, [isRunning, isPaused, currentStart, totalSeconds]);

  useEffect(() => {
    if (onTimeUpdate) {
      onTimeUpdate(displaySeconds);
    }
  }, [displaySeconds, onTimeUpdate]);

  useEffect(() => {
    if (fichinterId) {
      const savedData = localStorage.getItem(`time_tracker_${fichinterId}`);
      if (savedData) {
        const data = JSON.parse(savedData);
        setTotalSeconds(data.totalSeconds || 0);
        setDisplaySeconds(data.totalSeconds || 0);
        setEntries(data.entries || []);
      }
    }
  }, [fichinterId]);

  const saveToStorage = (data: { totalSeconds: number; entries: TimeEntry[] }) => {
    if (fichinterId) {
      localStorage.setItem(`time_tracker_${fichinterId}`, JSON.stringify(data));
    }
  };

  const handleStart = () => {
    const now = new Date();
    setCurrentStart(now);
    setIsRunning(true);
    setIsPaused(false);
  };

  const handlePause = () => {
    if (!isPaused && currentStart) {
      setPauseStart(new Date());
      setIsPaused(true);

      const now = new Date();
      const elapsed = Math.floor((now.getTime() - currentStart.getTime()) / 1000);
      const newTotal = totalSeconds + elapsed;
      setTotalSeconds(newTotal);
      setDisplaySeconds(newTotal);

      saveToStorage({ totalSeconds: newTotal, entries });
    }
  };

  const handleResume = () => {
    if (isPaused && pauseStart) {
      const now = new Date();
      setCurrentStart(now);
      setPauseStart(null);
      setIsPaused(false);
    }
  };

  const handleStop = () => {
    if (currentStart) {
      const now = new Date();
      const elapsed = Math.floor((now.getTime() - currentStart.getTime()) / 1000);
      const newTotal = totalSeconds + elapsed;

      const entry: TimeEntry = {
        start: currentStart,
        end: now,
        duration: elapsed
      };

      const newEntries = [...entries, entry];
      setEntries(newEntries);
      setTotalSeconds(newTotal);
      setDisplaySeconds(newTotal);

      saveToStorage({ totalSeconds: newTotal, entries: newEntries });

      setIsRunning(false);
      setIsPaused(false);
      setCurrentStart(null);
      setPauseStart(null);
    }
  };

  const formatTime = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <Clock className="w-5 h-5 text-blue-600" />
          <span className="font-medium text-gray-900">Suivi du temps</span>
        </div>
        <div className="text-2xl font-bold text-blue-600 tabular-nums">
          {formatTime(displaySeconds)}
        </div>
      </div>

      <div className="flex gap-2">
        {!isRunning && !isPaused && (
          <button
            onClick={handleStart}
            className="flex-1 btn-primary flex items-center justify-center gap-2"
          >
            <Play className="w-4 h-4" />
            Démarrer
          </button>
        )}

        {isRunning && !isPaused && (
          <>
            <button
              onClick={handlePause}
              className="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg px-4 py-2 font-medium transition-colors flex items-center justify-center gap-2"
            >
              <Pause className="w-4 h-4" />
              Pause
            </button>
            <button
              onClick={handleStop}
              className="flex-1 bg-red-500 hover:bg-red-600 text-white rounded-lg px-4 py-2 font-medium transition-colors flex items-center justify-center gap-2"
            >
              <Square className="w-4 h-4" />
              Stop
            </button>
          </>
        )}

        {isPaused && (
          <>
            <button
              onClick={handleResume}
              className="flex-1 btn-primary flex items-center justify-center gap-2"
            >
              <Play className="w-4 h-4" />
              Reprendre
            </button>
            <button
              onClick={handleStop}
              className="flex-1 bg-red-500 hover:bg-red-600 text-white rounded-lg px-4 py-2 font-medium transition-colors flex items-center justify-center gap-2"
            >
              <Square className="w-4 h-4" />
              Stop
            </button>
          </>
        )}
      </div>

      {entries.length > 0 && (
        <div className="mt-4 pt-4 border-t border-gray-200">
          <p className="text-sm text-gray-600 mb-2">Historique</p>
          <div className="space-y-1">
            {entries.map((entry, index) => (
              <div key={index} className="text-xs text-gray-500 flex justify-between">
                <span>
                  {new Date(entry.start).toLocaleTimeString('fr-FR')} - {entry.end ? new Date(entry.end).toLocaleTimeString('fr-FR') : 'En cours'}
                </span>
                <span className="font-medium">{formatTime(entry.duration)}</span>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
