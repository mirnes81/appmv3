class StatsManager {
  constructor() {
    this.stats = null;
    this.apiUrl = 'api/stats.php';
  }

  async loadStats() {
    try {
      const response = await fetch(this.apiUrl);
      if (!response.ok) throw new Error('Erreur chargement stats');

      this.stats = await response.json();
      return this.stats;
    } catch (error) {
      console.error('Erreur stats:', error);
      return null;
    }
  }

  async getTodayStats() {
    const stats = await this.loadStats();
    if (!stats) return null;

    return {
      surface: stats.today.surface || 0,
      hours: stats.today.hours || 0,
      ratio: stats.today.surface && stats.today.hours
        ? (stats.today.surface / stats.today.hours).toFixed(2)
        : 0,
      rapports: stats.today.count || 0
    };
  }

  async getWeekStats() {
    const stats = await this.loadStats();
    if (!stats) return null;

    return {
      surface: stats.week.surface || 0,
      hours: stats.week.hours || 0,
      ratio: stats.week.surface && stats.week.hours
        ? (stats.week.surface / stats.week.hours).toFixed(2)
        : 0,
      rapports: stats.week.count || 0
    };
  }

  async getMonthStats() {
    const stats = await this.loadStats();
    if (!stats) return null;

    return {
      surface: stats.month.surface || 0,
      hours: stats.month.hours || 0,
      ratio: stats.month.surface && stats.month.hours
        ? (stats.month.surface / stats.month.hours).toFixed(2)
        : 0,
      rapports: stats.month.count || 0
    };
  }

  formatStats(stats) {
    if (!stats) return 'Stats indisponibles';

    return `
      📊 ${stats.rapports} rapport${stats.rapports > 1 ? 's' : ''} |
      ${stats.surface}m² |
      ${stats.hours}h |
      ${stats.ratio} m²/h
    `;
  }

  async displayStatsWidget(containerId) {
    const today = await this.getTodayStats();
    const week = await this.getWeekStats();

    if (!today || !week) return;

    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
      <div style="background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); color: white; padding: 16px; border-radius: 12px; margin-bottom: 16px;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">📊 Vos statistiques</div>

        <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; margin-bottom: 8px;">
          <div style="font-size: 12px; opacity: 0.9; margin-bottom: 6px;">Aujourd'hui</div>
          <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <div style="flex: 1; min-width: 70px;">
              <div style="font-size: 22px; font-weight: 700;">${today.surface}</div>
              <div style="font-size: 11px; opacity: 0.8;">m²</div>
            </div>
            <div style="flex: 1; min-width: 70px;">
              <div style="font-size: 22px; font-weight: 700;">${today.hours}</div>
              <div style="font-size: 11px; opacity: 0.8;">heures</div>
            </div>
            <div style="flex: 1; min-width: 70px;">
              <div style="font-size: 22px; font-weight: 700;">${today.ratio}</div>
              <div style="font-size: 11px; opacity: 0.8;">m²/h</div>
            </div>
          </div>
        </div>

        <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px;">
          <div style="font-size: 12px; opacity: 0.9; margin-bottom: 6px;">Cette semaine</div>
          <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <div style="flex: 1; min-width: 70px;">
              <div style="font-size: 22px; font-weight: 700;">${week.surface}</div>
              <div style="font-size: 11px; opacity: 0.8;">m²</div>
            </div>
            <div style="flex: 1; min-width: 70px;">
              <div style="font-size: 22px; font-weight: 700;">${week.hours}</div>
              <div style="font-size: 11px; opacity: 0.8;">heures</div>
            </div>
            <div style="flex: 1; min-width: 70px;">
              <div style="font-size: 22px; font-weight: 700;">${week.ratio}</div>
              <div style="font-size: 11px; opacity: 0.8;">m²/h</div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  calculateCurrentRatio(surface, heuresDebut, heuresFin) {
    if (!surface || !heuresDebut || !heuresFin) return null;

    const debut = new Date(`2000-01-01 ${heuresDebut}`);
    const fin = new Date(`2000-01-01 ${heuresFin}`);
    const hours = (fin - debut) / (1000 * 60 * 60);

    if (hours <= 0) return null;

    return {
      ratio: (surface / hours).toFixed(2),
      surface: surface,
      hours: hours.toFixed(2)
    };
  }

  getPerformanceLevel(ratio) {
    if (ratio >= 15) return { level: 'Excellent', color: '#10b981', emoji: '🏆' };
    if (ratio >= 10) return { level: 'Très bon', color: '#22c55e', emoji: '⭐' };
    if (ratio >= 7) return { level: 'Bon', color: '#84cc16', emoji: '👍' };
    if (ratio >= 5) return { level: 'Correct', color: '#eab308', emoji: '👌' };
    return { level: 'À améliorer', color: '#f59e0b', emoji: '💪' };
  }
}

window.statsManager = new StatsManager();
