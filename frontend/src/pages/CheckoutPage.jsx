import { useMemo, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { createBooking } from '../api/wordpress';
import { formatCurrency } from '../utils/format';

export default function CheckoutPage() {
  const location = useLocation();
  const navigate = useNavigate();
  const bookingDraft = location.state?.bookingDraft;
  const [status, setStatus] = useState({
    type: 'idle',
    message: '',
  });

  const totalNights = useMemo(() => {
    if (!bookingDraft?.check_in || !bookingDraft?.check_out) {
      return 1;
    }

    const checkInDate = new Date(bookingDraft.check_in);
    const checkOutDate = new Date(bookingDraft.check_out);
    const diff = Math.round((checkOutDate - checkInDate) / 86400000);

    return diff > 0 ? diff : 1;
  }, [bookingDraft]);

  const estimatedTotal = useMemo(() => {
    const roomRate = Number(bookingDraft?.room_price || 0);
    return roomRate > 0 ? roomRate * totalNights : 0;
  }, [bookingDraft?.room_price, totalNights]);

  if (!bookingDraft) {
    return (
      <section className="content-section">
        <div className="container status-card">
          <h1>Checkout data missing</h1>
          <p>Start from the booking page so the customer details can be reviewed here.</p>
          <Link className="button button--primary" to="/booking">
            Go to booking
          </Link>
        </div>
      </section>
    );
  }

  async function handleConfirmBooking() {
    setStatus({
      type: 'loading',
      message: 'Confirming booking in WordPress...',
    });

    try {
      const result = await createBooking(bookingDraft);

      navigate('/thankyou', {
        replace: true,
        state: {
          bookingId: result.booking_id,
          bookingDraft,
          message: result.message,
          totalNights,
        },
      });
    } catch (error) {
      setStatus({
        type: 'error',
        message: error.message || 'Booking confirmation failed.',
      });
    }
  }

  return (
    <section className="content-section">
      <div className="container page-shell">
        <div className="checkout-hero">
          <div className="checkout-hero__content">
            <p className="eyebrow">Final reservation review</p>
            <h1>Confirm your boutique hotel stay with confidence</h1>
            <p className="page-lead">
              Review your selected room, guest details, and stay dates before sending the final reservation request to our hotel team.
            </p>
            <div className="hero-actions">
              <Link className="button button--primary" to={`/booking?room=${bookingDraft.room_id || ''}`}>
                Edit booking details
              </Link>
              <a className="button button--ghost" href="tel:+919876543210">
                Speak to reservations
              </a>
            </div>
          </div>

          <aside className="checkout-hero__panel">
            <div className="checkout-hero__feature">
              <p className="eyebrow">Stay assurance</p>
              <h3>Every reservation request is reviewed directly by our hotel team</h3>
              <p>
                Confirm with confidence knowing your room choice, guest details, and special preferences are sent directly to the reservation desk.
              </p>
            </div>
            <div className="checkout-hero__facts">
              <div className="info-card">
                <h3>{totalNights}</h3>
                <p>Night stay</p>
              </div>
              <div className="info-card">
                <h3>{bookingDraft.guests}</h3>
                <p>Guest{Number(bookingDraft.guests) > 1 ? 's' : ''}</p>
              </div>
            </div>
          </aside>
        </div>

        <div className="checkout-layout">
          <div className="checkout-summary">
            <div className="section-heading">
              <p className="eyebrow">Checkout overview</p>
              <h2>Review each part of your reservation before confirmation</h2>
              <p>
                This final step lets you double-check guest details, stay dates, and your selected room so the reservation is submitted correctly.
              </p>
            </div>

            <div className="checkout-grid">
              <div className="summary-card checkout-card">
                <p className="eyebrow">Guest details</p>
                <div className="checkout-list">
                  <div className="checkout-list__row">
                    <span>Guest name</span>
                    <strong>{bookingDraft.name}</strong>
                  </div>
                  <div className="checkout-list__row">
                    <span>Email</span>
                    <strong>{bookingDraft.email}</strong>
                  </div>
                  <div className="checkout-list__row">
                    <span>Phone</span>
                    <strong>{bookingDraft.phone || 'Not provided'}</strong>
                  </div>
                </div>
              </div>

              <div className="summary-card checkout-card">
                <p className="eyebrow">Stay summary</p>
                <div className="checkout-list">
                  <div className="checkout-list__row">
                    <span>Room</span>
                    <strong>{bookingDraft.room_title || `Room #${bookingDraft.room_id}`}</strong>
                  </div>
                  <div className="checkout-list__row">
                    <span>Check in</span>
                    <strong>{bookingDraft.check_in}</strong>
                  </div>
                  <div className="checkout-list__row">
                    <span>Check out</span>
                    <strong>{bookingDraft.check_out}</strong>
                  </div>
                  <div className="checkout-list__row">
                    <span>Guests</span>
                    <strong>{bookingDraft.guests}</strong>
                  </div>
                  <div className="checkout-list__row">
                    <span>Nights</span>
                    <strong>{totalNights}</strong>
                  </div>
                </div>
              </div>
            </div>

            <div className="checkout-support">
              <div className="info-card">
                <h3>Need to adjust your reservation?</h3>
                <p>Return to booking to change room selection, dates, or guest details before sending the confirmation request.</p>
              </div>
              <div className="info-card">
                <h3>Hotel team support</h3>
                <p>After confirming, our reservations team can assist with arrival timing, family stay requests, and special notes.</p>
              </div>
            </div>
          </div>

          <aside className="checkout-panel">
            <p className="eyebrow">Reservation total</p>
            <h2>Ready to confirm your booking</h2>
            <p className="checkout-panel__copy">
              Submit this stay request and continue to the confirmation page with your booking reference and reservation summary.
            </p>
            {bookingDraft.room_image ? (
              <img alt={bookingDraft.room_title || 'Selected room'} className="checkout-room-image" src={bookingDraft.room_image} />
            ) : null}
            <div className="checkout-rate">
              <span>Estimated stay total</span>
              <strong className="checkout-price">{formatCurrency(estimatedTotal || bookingDraft.room_price || '0')}</strong>
            </div>
            <div className="checkout-meta">
              <span>{totalNights} night{totalNights > 1 ? 's' : ''}</span>
              <span>{bookingDraft.guests} guest{Number(bookingDraft.guests) > 1 ? 's' : ''}</span>
            </div>
            <button
              className="button button--primary"
              disabled={status.type === 'loading'}
              onClick={handleConfirmBooking}
              type="button"
            >
              {status.type === 'loading' ? 'Confirming...' : 'Confirm booking'}
            </button>
            {status.message ? (
              <p
                className={
                  status.type === 'error' ? 'form-message form-message--error' : 'form-message'
                }
              >
                {status.message}
              </p>
            ) : null}
          </aside>
        </div>
      </div>
    </section>
  );
}
