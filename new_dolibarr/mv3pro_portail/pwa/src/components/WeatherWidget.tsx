import { useEffect, useState } from 'react';

interface WeatherData {
  temp: number;
  condition: string;
  icon: string;
}

interface ForecastDay {
  day: string;
  temp: number;
  icon: string;
}

export function WeatherWidget() {
  const [weather, setWeather] = useState<WeatherData | null>(null);
  const [forecast, setForecast] = useState<ForecastDay[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          try {
            const { latitude, longitude } = position.coords;
            const response = await fetch(
              `https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&daily=temperature_2m_max,weathercode&current_weather=true&timezone=Europe/Zurich`
            );
            const data = await response.json();

            const weatherIcons: Record<number, string> = {
              0: '☀️', 1: '🌤️', 2: '⛅', 3: '☁️',
              45: '🌫️', 48: '🌫️',
              51: '🌦️', 53: '🌦️', 55: '🌧️',
              61: '🌧️', 63: '🌧️', 65: '⛈️',
              71: '🌨️', 73: '🌨️', 75: '❄️',
              80: '🌦️', 81: '🌧️', 82: '⛈️',
              95: '⛈️', 96: '⛈️', 99: '⛈️'
            };

            const getWeatherLabel = (code: number): string => {
              if (code === 0) return 'Ensoleillé';
              if (code <= 3) return 'Nuageux';
              if (code <= 48) return 'Brouillard';
              if (code <= 55) return 'Bruine';
              if (code <= 67) return 'Pluie';
              if (code <= 77) return 'Neige';
              if (code <= 82) return 'Averses';
              return 'Orage';
            };

            setWeather({
              temp: Math.round(data.current_weather.temperature),
              condition: getWeatherLabel(data.current_weather.weathercode),
              icon: weatherIcons[data.current_weather.weathercode] || '🌤️',
            });

            const days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            const forecastData = data.daily.time.slice(1, 6).map((date: string, idx: number) => ({
              day: days[new Date(date).getDay()],
              temp: Math.round(data.daily.temperature_2m_max[idx + 1]),
              icon: weatherIcons[data.daily.weathercode[idx + 1]] || '🌤️',
            }));

            setForecast(forecastData);
          } catch (error) {
            console.error('Météo indisponible', error);
          } finally {
            setLoading(false);
          }
        },
        () => setLoading(false)
      );
    } else {
      setLoading(false);
    }
  }, []);

  if (loading || !weather) return null;

  return (
    <div
      style={{
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        borderRadius: '12px',
        padding: '16px',
        color: 'white',
        marginBottom: '16px',
      }}
    >
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
        <div>
          <div style={{ fontSize: '14px', opacity: 0.9, marginBottom: '4px' }}>Maintenant</div>
          <div style={{ fontSize: '32px', fontWeight: '700' }}>{weather.temp}°</div>
          <div style={{ fontSize: '13px', opacity: 0.9 }}>{weather.condition}</div>
        </div>
        <div style={{ fontSize: '48px' }}>{weather.icon}</div>
      </div>

      {forecast.length > 0 && (
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(5, 1fr)',
            gap: '8px',
            borderTop: '1px solid rgba(255, 255, 255, 0.2)',
            paddingTop: '12px',
          }}
        >
          {forecast.map((day, idx) => (
            <div key={idx} style={{ textAlign: 'center' }}>
              <div style={{ fontSize: '11px', opacity: 0.8, marginBottom: '4px' }}>{day.day}</div>
              <div style={{ fontSize: '20px', marginBottom: '4px' }}>{day.icon}</div>
              <div style={{ fontSize: '13px', fontWeight: '600' }}>{day.temp}°</div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
