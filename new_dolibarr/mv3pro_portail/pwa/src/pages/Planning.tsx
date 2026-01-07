import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { api, PlanningEvent } from '../lib/api';

export function Planning() {
  const [events, setEvents] = useState<PlanningEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [dateFrom, setDateFrom] = useState(new Date().toISOString().split('T')[0]);
  const [dateTo, setDateTo] = useState(
    new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  );

  const loadPlanning = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await api.planning(dateFrom, dateTo);
      setEvents(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadPlanning();
  }, [dateFrom, dateTo]);

  return (
    <Layout title="Planning">
      <div style={{ padding: '20px' }}>
        <div className="card" style={{ marginBottom: '20px' }}>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label htmlFor="dateFrom" className="form-label" style={{ fontSize: '14px' }}>
                Du
              </label>
              <input
                id="dateFrom"
                type="date"
                className="form-input"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                style={{ padding: '10px' }}
              />
            </div>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label htmlFor="dateTo" className="form-label" style={{ fontSize: '14px' }}>
                Au
              </label>
              <input
                id="dateTo"
                type="date"
                className="form-input"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                style={{ padding: '10px' }}
              />
            </div>
          </div>
        </div>

        {loading && <LoadingSpinner />}

        {error && (
          <div className="alert alert-error">
            {error}
          </div>
        )}

        {!loading && !error && events.length === 0 && (
          <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>📅</div>
            <div style={{ color: '#6b7280', fontSize: '16px' }}>
              Aucune affectation pour cette période
            </div>
          </div>
        )}

        {!loading && events.length > 0 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {events.map((event) => (
              <Link
                key={event.id}
                to={`/planning/${event.id}`}
                className="card"
                style={{
                  display: 'block',
                  textDecoration: 'none',
                  color: 'inherit',
                  transition: 'all 200ms ease',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'start', gap: '12px' }}>
                  <div
                    style={{
                      width: '48px',
                      height: '48px',
                      borderRadius: '12px',
                      background: '#dbeafe',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontSize: '24px',
                      flexShrink: 0,
                    }}
                  >
                    📅
                  </div>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontWeight: '600', marginBottom: '4px', fontSize: '15px' }}>
                      {event.label}
                    </div>
                    {event.client_nom && (
                      <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '4px' }}>
                        Client: {event.client_nom}
                      </div>
                    )}
                    <div style={{ fontSize: '14px', color: '#6b7280' }}>
                      📍 {event.location || 'Non spécifié'}
                    </div>
                    <div style={{ fontSize: '14px', color: '#0891b2', marginTop: '4px' }}>
                      🕐 {new Date(event.datep).toLocaleString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </div>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
}
