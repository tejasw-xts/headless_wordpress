import { useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { getRooms } from '../api/wordpress';
import BookingForm from '../components/BookingForm';
import ErrorState from '../components/ErrorState';
import LoadingState from '../components/LoadingState';

export default function BookingPage() {
  const location = useLocation();
  const roomId = new URLSearchParams(location.search).get('room') || '';
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
          error: error.message || 'Failed to load booking form.',
          rooms: [],
        });
      });

    return () => {
      active = false;
    };
  }, []);

  if (state.loading) {
    return <LoadingState label="Loading booking form..." />;
  }

  if (state.error) {
    return <ErrorState message={state.error} />;
  }

  const selectedRoom = state.rooms.find((room) => String(room.id) === String(roomId));

  return (
    <section className="content-section">
      <div className="container page-shell">
        <div className="booking-hero">
          <div className="booking-hero__content">
            <p className="eyebrow">Direct reservations</p>
            <h1>Plan your stay with a smooth boutique hotel booking experience</h1>
            <p className="page-lead">
              Choose your dates, select the right room, and continue to checkout with direct hotel support for requests, preferences, and stay planning.
            </p>
            <div className="hero-actions">
              <a className="button button--primary" href="tel:+919876543210">
                Call reservations
              </a>
              <a className="button button--ghost" href="mailto:stay@harborstay.com">
                Email our team
              </a>
            </div>
          </div>

          <aside className="booking-hero__panel">
            <div className="booking-hero__feature">
              <p className="eyebrow">Reservation support</p>
              <h3>Direct guidance before you confirm your room</h3>
              <p>Our team can help with room selection, early check-in requests, family stay preferences, airport pickup coordination, and other special requirements.</p>
            </div>
          </aside>
        </div>

        <div className="booking-layout">
          <div className="booking-copy">
            <div className="section-heading">
              <p className="eyebrow">Reserve your stay</p>
              <h2>Complete your room request in a few simple steps</h2>
              <p>
                Choose your stay dates, confirm guest details, and continue to checkout for a direct hotel booking experience.
              </p>
            </div>
            <div className="booking-highlights">
              <div className="info-card">
                <h3>Direct booking benefits</h3>
                <p>Best available room guidance, personalized assistance, and faster reservation support from our hotel team.</p>
              </div>
              <div className="info-card">
                <h3>Guest support</h3>
                <p>Need airport pickup, an early check-in request, or help choosing the right room? Contact us after submitting your stay details.</p>
              </div>
            </div>
            {selectedRoom ? (
              <div className="booking-room-card">
                {selectedRoom.featuredImage ? (
                  <img alt={selectedRoom.title} className="booking-room-card__image" src={selectedRoom.featuredImage} />
                ) : null}
                <div>
                  <p className="eyebrow">Selected room</p>
                  <h3>{selectedRoom.title}</h3>
                  <p>{selectedRoom.excerpt}</p>
                </div>
              </div>
            ) : (
              <div className="page-sidecard">
                <p className="eyebrow">Before you book</p>
                <div className="page-sidecard__list">
                  <p>Select your preferred room category</p>
                  <p>Choose accurate check-in and check-out dates</p>
                  <p>Add guest details so checkout is faster</p>
                </div>
              </div>
            )}
            {location.state?.message ? (
              <p className="form-message">{location.state.message}</p>
            ) : null}
          </div>

          <BookingForm defaultRoomId={roomId} rooms={state.rooms} />
        </div>
      </div>
    </section>
  );
}
