import { useEffect, useState } from 'react';
import { getRooms } from '../api/wordpress';
import ErrorState from '../components/ErrorState';
import LoadingState from '../components/LoadingState';
import RoomCard from '../components/RoomCard';

function CountUpValue({ value }) {
  const rawValue = String(value || '0');
  const match = rawValue.match(/\d+/);
  const target = match ? Number(match[0]) : 0;
  const suffix = rawValue.replace(String(target), '');
  const [displayValue, setDisplayValue] = useState(0);

  useEffect(() => {
    const duration = 700;
    const startTime = performance.now();

    let frameId = 0;

    const tick = (now) => {
      const progress = Math.min((now - startTime) / duration, 1);
      setDisplayValue(Math.round(target * progress));

      if (progress < 1) {
        frameId = window.requestAnimationFrame(tick);
      }
    };

    frameId = window.requestAnimationFrame(tick);

    return () => {
      window.cancelAnimationFrame(frameId);
    };
  }, [target]);

  return `${displayValue}${suffix}`;
}

export default function RoomsPage() {
  const [state, setState] = useState({
    loading: true,
    error: '',
    rooms: [],
  });

  useEffect(() => {
    let active = true;

    getRooms()
      .then((rooms) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: '',
          rooms,
        });
      })
      .catch((error) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: error.message || 'Failed to load rooms.',
          rooms: [],
        });
      });

    return () => {
      active = false;
    };
  }, []);

  if (state.loading) {
    return <LoadingState label="Loading rooms..." />;
  }

  if (state.error) {
    return <ErrorState message={state.error} />;
  }

  const featuredRooms = state.rooms.slice(0, 3);

  return (
    <section className="content-section">
      <div className="container page-shell">
        <div className="rooms-hero">
          <div className="rooms-hero__content">
            <p className="eyebrow">Luxury rooms</p>
            <h1>Discover refined rooms and restful stays designed around comfort</h1>
            <p className="page-lead">
              Explore boutique room experiences crafted for business travel, family visits,
              and relaxing city breaks. Each stay is shaped by elegant interiors, practical
              amenities, and direct hotel support.
            </p>
            <div className="hero-actions">
              <a className="button button--primary" href="tel:+919876543210">
                Call for reservations
              </a>
              <a className="button button--ghost" href="mailto:stay@harborstay.com">
                Email our team
              </a>
            </div>
          </div>

          <aside className="rooms-hero__panel">
            <div className="rooms-hero__feature">
              <p className="eyebrow">Stay collection</p>
              <h3>{state.rooms.length} room options available</h3>
              <p>From spacious suites to practical premium rooms, each category is designed to deliver a calm and comfortable hotel experience.</p>
            </div>
            <div className="rooms-hero__stats">
              {featuredRooms.map((room) => (
                <article className="rooms-hero-stat" key={room.id}>
                  <div className="rooms-hero-stat__value">
                    <CountUpValue value={room.maxGuests || '2+'} />
                  </div>
                  <div className="rooms-hero-stat__content">
                    <p className="eyebrow">Guests</p>
                    <h3>{room.title}</h3>
                  </div>
                </article>
              ))}
            </div>
          </aside>
        </div>

        <div className="rooms-archive-intro">
          <div className="section-heading">
            <p className="eyebrow">Room collection</p>
            <h2>Choose the stay that best fits your travel plans</h2>
            <p>
              Compare our rooms by style, guest capacity, and layout. Whether you are planning a quick business trip or a longer leisure stay, our hotel rooms are tailored for comfort and ease.
            </p>
          </div>
          <div className="page-sidecard">
            <p className="eyebrow">Booking support</p>
            <div className="page-sidecard__list">
              <p>Direct booking assistance from the hotel team</p>
              <p>Room guidance for families, couples, and business guests</p>
              <p>Help with special requests before arrival</p>
            </div>
          </div>
        </div>

        <div className="room-grid">
          {state.rooms.map((room) => (
            <RoomCard key={room.id} room={room} />
          ))}
        </div>
      </div>
    </section>
  );
}
