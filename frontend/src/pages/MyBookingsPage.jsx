import { useEffect, useMemo, useState } from 'react';
import { cancelMyBooking, getMyBookings, requestMyBookingDateChange } from '../api/wordpress';
import { useAuth } from '../auth/AuthContext';

export default function MyBookingsPage() {
  const { isAuthenticated, loading: authLoading, user, logout } = useAuth();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [bookings, setBookings] = useState([]);
  const [workingId, setWorkingId] = useState(null);

  const title = useMemo(() => user?.name || user?.email || 'Guest', [user]);

  async function load() {
    setLoading(true);
    setError('');
    try {
      const result = await getMyBookings();
      setBookings(Array.isArray(result?.bookings) ? result.bookings : []);
    } catch (e) {
      setError(e?.message || 'Failed to load bookings.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (!authLoading && isAuthenticated) load();
    if (!authLoading && !isAuthenticated) setLoading(false);
  }, [authLoading, isAuthenticated]);

  async function handleCancel(id) {
    setWorkingId(id);
    setError('');
    try {
      await cancelMyBooking(id);
      await load();
    } catch (e) {
      setError(e?.message || 'Cancel failed.');
    } finally {
      setWorkingId(null);
    }
  }

  async function handleChangeDates(id, new_check_in, new_check_out) {
    setWorkingId(id);
    setError('');
    try {
      await requestMyBookingDateChange(id, { new_check_in, new_check_out });
      await load();
    } catch (e) {
      setError(e?.message || 'Request failed.');
    } finally {
      setWorkingId(null);
    }
  }

  if (authLoading) {
    return (
      <section className="section">
        <div className="container">
          <h1>My bookings</h1>
          <p>Loading…</p>
        </div>
      </section>
    );
  }

  if (!isAuthenticated) {
    return (
      <section className="section">
        <div className="container">
          <h1>My bookings</h1>
          <p>Please log in from the booking flow to view your bookings.</p>
        </div>
      </section>
    );
  }

  return (
    <section className="section">
      <div className="container">
        <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
          <div>
            <p className="eyebrow">Account</p>
            <h1 style={{ marginTop: 0 }}>My bookings</h1>
            <p style={{ marginTop: 0, color: '#555' }}>Signed in as {title}</p>
          </div>
          <button className="button button--secondary" onClick={logout}>
            Logout
          </button>
        </div>

        {error ? (
          <div style={{ marginTop: 14, padding: 12, borderRadius: 12, border: '1px solid #ffc9c9', background: '#fff5f5', color: '#7a0b0b' }}>
            {error}
          </div>
        ) : null}

        {loading ? (
          <p style={{ marginTop: 16 }}>Loading bookings…</p>
        ) : bookings.length ? (
          <div style={{ marginTop: 16, display: 'grid', gap: 14 }}>
            {bookings.map((b) => (
              <article key={b.id} className="card" style={{ padding: 16 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
                  <div>
                    <p className="eyebrow" style={{ marginBottom: 6 }}>
                      Booking #{b.id} • {b.status}
                    </p>
                    <h3 style={{ margin: 0 }}>{b.room_title || 'Room'}</h3>
                    <p style={{ margin: '8px 0 0', color: '#555' }}>
                      {b.check_in || '—'} → {b.check_out || '—'} • Guests: {b.guests || '—'}
                    </p>
                  </div>
                  <div style={{ display: 'flex', gap: 10, alignItems: 'flex-start', flexWrap: 'wrap' }}>
                    <button
                      className="button button--secondary"
                      onClick={() => handleCancel(b.id)}
                      disabled={workingId === b.id || b.status === 'cancelled'}
                    >
                      {b.status === 'cancelled' ? 'Cancelled' : workingId === b.id ? 'Working…' : 'Cancel'}
                    </button>
                  </div>
                </div>

                <div style={{ marginTop: 12, display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'flex-end' }}>
                  <label style={{ display: 'grid', gap: 6 }}>
                    <span style={{ fontSize: 13, color: '#555' }}>New check-in</span>
                    <input type="date" id={`ci-${b.id}`} style={{ padding: '9px 10px', border: '1px solid #ddd', borderRadius: 12 }} />
                  </label>
                  <label style={{ display: 'grid', gap: 6 }}>
                    <span style={{ fontSize: 13, color: '#555' }}>New check-out</span>
                    <input type="date" id={`co-${b.id}`} style={{ padding: '9px 10px', border: '1px solid #ddd', borderRadius: 12 }} />
                  </label>
                  <button
                    className="button button--primary"
                    disabled={workingId === b.id}
                    onClick={() => {
                      const ci = document.getElementById(`ci-${b.id}`)?.value || '';
                      const co = document.getElementById(`co-${b.id}`)?.value || '';
                      handleChangeDates(b.id, ci, co);
                    }}
                  >
                    {workingId === b.id ? 'Working…' : 'Request date change'}
                  </button>
                </div>
                <p style={{ margin: '10px 0 0', color: '#666', fontSize: 13 }}>
                  Date change requests are reviewed by the hotel and confirmed by email.
                </p>
              </article>
            ))}
          </div>
        ) : (
          <p style={{ marginTop: 16 }}>No bookings found yet.</p>
        )}
      </div>
    </section>
  );
}

