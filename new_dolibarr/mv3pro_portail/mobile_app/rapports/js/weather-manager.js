class WeatherManager {
  constructor() {
    this.currentWeather = null;
    this.cacheKey = 'weather_cache';
    this.cacheDuration = 3600000;
  }

  async getWeather(lat = null, lon = null) {
    const cached = this.getCachedWeather();
    if (cached) {
      this.currentWeather = cached;
      return cached;
    }

    try {
      let coords = { lat, lon };

      if (!lat || !lon) {
        if (window.gpsManager && window.gpsManager.currentPosition) {
          coords = {
            lat: window.gpsManager.currentPosition.latitude,
            lon: window.gpsManager.currentPosition.longitude
          };
        } else {
          coords = { lat: 46.8182, lon: 8.2275 };
        }
      }

      const response = await fetch(
        `https://api.open-meteo.com/v1/forecast?latitude=${coords.lat}&longitude=${coords.lon}&current_weather=true&timezone=Europe/Zurich`
      );

      if (!response.ok) throw new Error('Erreur API météo');

      const data = await response.json();
      const weather = this.parseWeather(data.current_weather);

      this.cacheWeather(weather);
      this.currentWeather = weather;

      return weather;
    } catch (error) {
      console.error('Erreur météo:', error);
      return this.getDefaultWeather();
    }
  }

  parseWeather(data) {
    const temp = Math.round(data.temperature);
    const windSpeed = Math.round(data.windspeed);
    const weatherCode = data.weathercode;

    return {
      temperature: temp,
      windSpeed: windSpeed,
      condition: this.getWeatherCondition(weatherCode),
      icon: this.getWeatherIcon(weatherCode),
      description: this.getWeatherDescription(weatherCode, temp)
    };
  }

  getWeatherCondition(code) {
    const conditions = {
      0: 'Dégagé',
      1: 'Peu nuageux',
      2: 'Partiellement nuageux',
      3: 'Couvert',
      45: 'Brouillard',
      48: 'Brouillard givrant',
      51: 'Bruine légère',
      53: 'Bruine modérée',
      55: 'Bruine forte',
      61: 'Pluie légère',
      63: 'Pluie modérée',
      65: 'Pluie forte',
      71: 'Neige légère',
      73: 'Neige modérée',
      75: 'Neige forte',
      77: 'Grêle',
      80: 'Averses légères',
      81: 'Averses modérées',
      82: 'Averses fortes',
      85: 'Averses de neige légères',
      86: 'Averses de neige fortes',
      95: 'Orage',
      96: 'Orage avec grêle légère',
      99: 'Orage avec grêle forte'
    };

    return conditions[code] || 'Inconnu';
  }

  getWeatherIcon(code) {
    if (code === 0) return '☀️';
    if (code <= 3) return '⛅';
    if (code >= 45 && code <= 48) return '🌫️';
    if (code >= 51 && code <= 55) return '🌦️';
    if (code >= 61 && code <= 65) return '🌧️';
    if (code >= 71 && code <= 77) return '❄️';
    if (code >= 80 && code <= 82) return '🌧️';
    if (code >= 85 && code <= 86) return '🌨️';
    if (code >= 95) return '⛈️';
    return '🌤️';
  }

  getWeatherDescription(code, temp) {
    const condition = this.getWeatherCondition(code);
    return `${condition}, ${temp}°C`;
  }

  cacheWeather(weather) {
    const cache = {
      data: weather,
      timestamp: Date.now()
    };
    localStorage.setItem(this.cacheKey, JSON.stringify(cache));
  }

  getCachedWeather() {
    const cached = localStorage.getItem(this.cacheKey);
    if (!cached) return null;

    try {
      const cache = JSON.parse(cached);
      const age = Date.now() - cache.timestamp;

      if (age < this.cacheDuration) {
        return cache.data;
      }
    } catch (error) {
      return null;
    }

    return null;
  }

  getDefaultWeather() {
    return {
      temperature: '--',
      windSpeed: '--',
      condition: 'Non disponible',
      icon: '🌤️',
      description: 'Météo non disponible'
    };
  }

  async displayWeather(containerId) {
    const weather = await this.getWeather();
    const container = document.getElementById(containerId);

    if (!container) return;

    container.innerHTML = `
      <div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #eff6ff; border-radius: 8px; font-size: 14px;">
        <span style="font-size: 24px;">${weather.icon}</span>
        <div>
          <div style="font-weight: 600; color: #0891b2;">${weather.description}</div>
          <div style="font-size: 12px; color: #64748b;">Vent: ${weather.windSpeed} km/h</div>
        </div>
      </div>
    `;
  }

  getWeatherForReport() {
    if (!this.currentWeather) return null;

    return {
      temperature: this.currentWeather.temperature,
      condition: this.currentWeather.condition,
      description: this.currentWeather.description,
      icon: this.currentWeather.icon
    };
  }
}

window.weatherManager = new WeatherManager();
