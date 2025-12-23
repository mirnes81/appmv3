class TimerManager {
  constructor() {
    this.startTime = null;
    this.endTime = null;
    this.pauses = [];
    this.currentPauseStart = null;
    this.isRunning = false;
    this.isPaused = false;
    this.interval = null;
    this.loadState();
  }

  start() {
    if (this.isRunning) return;

    this.startTime = Date.now();
    this.endTime = null;
    this.pauses = [];
    this.currentPauseStart = null;
    this.isRunning = true;
    this.isPaused = false;

    this.startInterval();
    this.saveState();

    return this.formatTime(this.startTime);
  }

  pause() {
    if (!this.isRunning || this.isPaused) return;

    this.currentPauseStart = Date.now();
    this.isPaused = true;
    this.saveState();
  }

  resume() {
    if (!this.isRunning || !this.isPaused) return;

    const pauseDuration = Date.now() - this.currentPauseStart;
    this.pauses.push({
      start: this.currentPauseStart,
      end: Date.now(),
      duration: pauseDuration
    });

    this.currentPauseStart = null;
    this.isPaused = false;
    this.saveState();
  }

  stop() {
    if (!this.isRunning) return;

    if (this.isPaused) {
      this.resume();
    }

    this.endTime = Date.now();
    this.isRunning = false;
    this.stopInterval();
    this.saveState();

    return this.formatTime(this.endTime);
  }

  getTotalDuration() {
    if (!this.startTime) return 0;

    const end = this.endTime || Date.now();
    const totalMs = end - this.startTime;

    const pauseMs = this.pauses.reduce((sum, pause) => sum + pause.duration, 0);

    const currentPauseMs = this.isPaused ? (Date.now() - this.currentPauseStart) : 0;

    return totalMs - pauseMs - currentPauseMs;
  }

  getTotalDurationHours() {
    return this.getTotalDuration() / (1000 * 60 * 60);
  }

  formatDuration(ms) {
    const seconds = Math.floor(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    const displayHours = hours;
    const displayMinutes = minutes % 60;
    const displaySeconds = seconds % 60;

    return {
      hours: displayHours,
      minutes: displayMinutes,
      seconds: displaySeconds,
      display: `${displayHours}h${String(displayMinutes).padStart(2, '0')}:${String(displaySeconds).padStart(2, '0')}`
    };
  }

  formatTime(timestamp) {
    const date = new Date(timestamp);
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
  }

  startInterval() {
    if (this.interval) return;

    this.interval = setInterval(() => {
      if (this.isRunning && !this.isPaused) {
        this.updateDisplay();
      }
    }, 1000);
  }

  stopInterval() {
    if (this.interval) {
      clearInterval(this.interval);
      this.interval = null;
    }
  }

  updateDisplay() {
    const duration = this.getTotalDuration();
    const formatted = this.formatDuration(duration);

    const timerDisplay = document.getElementById('timerDisplay');
    if (timerDisplay) {
      timerDisplay.textContent = formatted.display;
    }
  }

  saveState() {
    const state = {
      startTime: this.startTime,
      endTime: this.endTime,
      pauses: this.pauses,
      currentPauseStart: this.currentPauseStart,
      isRunning: this.isRunning,
      isPaused: this.isPaused
    };
    localStorage.setItem('timerState', JSON.stringify(state));
  }

  loadState() {
    const saved = localStorage.getItem('timerState');
    if (!saved) return;

    try {
      const state = JSON.parse(saved);
      this.startTime = state.startTime;
      this.endTime = state.endTime;
      this.pauses = state.pauses || [];
      this.currentPauseStart = state.currentPauseStart;
      this.isRunning = state.isRunning;
      this.isPaused = state.isPaused;

      if (this.isRunning) {
        this.startInterval();
      }
    } catch (error) {
      console.error('Erreur chargement timer:', error);
    }
  }

  reset() {
    this.stopInterval();
    this.startTime = null;
    this.endTime = null;
    this.pauses = [];
    this.currentPauseStart = null;
    this.isRunning = false;
    this.isPaused = false;
    localStorage.removeItem('timerState');
  }

  getState() {
    return {
      isRunning: this.isRunning,
      isPaused: this.isPaused,
      startTime: this.startTime,
      endTime: this.endTime,
      duration: this.getTotalDuration(),
      formatted: this.formatDuration(this.getTotalDuration())
    };
  }
}

window.timerManager = new TimerManager();
