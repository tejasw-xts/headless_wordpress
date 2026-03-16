import { Link, useLocation } from 'react-router-dom';

export default function ThankYouPage() {
  const location = useLocation();
  const bookingId = location.state?.bookingId;
  const bookingDraft = location.state?.bookingDraft;
  const totalNights = location.state?.totalNights || 1;

  if (!bookingId || !bookingDraft) {
    return (
      <section className="content-section">
        <div className="container status-card">
          <h1>No confirmed booking found</h1>
          <p>Complete the checkout step first so we can show the booking summary here.</p>
          <Link className="button button--primary" to="/booking">
            Back to booking
          </Link>
        </div>
      </section>
    );
  }

  return (
    <section className="content-section">
      <div className="container page-shell">
        <div className="thankyou-hero">
          <div className="thankyou-hero__content">
            <p className="eyebrow">Reservation confirmed</p>
            <h1>Thank you for choosing Mukta Boutique Hotel</h1>
            <p className="page-lead">
              {location.state?.message || 'Your luxury stay at Mukta Boutique Hotel has been successfully reserved.'}
            </p>
            <div className="hero-actions">
              <Link className="button button--primary" to="/rooms">
                Explore more rooms
              </Link>
              <a className="button button--ghost" href="tel:+919876543210">
                Call reservations
              </a>
            </div>
          </div>

          <aside className="thankyou-hero__panel">
            <p className="eyebrow">Booking reference</p>
            <h2>#{bookingId}</h2>
            <p className="thankyou-hero__panel-copy">
              Keep this reference handy. Our team may request it while confirming arrival details and special preferences.
            </p>
            <div className="thankyou-badges">
              <span>{totalNights} night{totalNights > 1 ? 's' : ''}</span>
              <span>{bookingDraft.guests} guest{Number(bookingDraft.guests) > 1 ? 's' : ''}</span>
              <span>{bookingDraft.room_title || `Room #${bookingDraft.room_id}`}</span>
            </div>
          </aside>
        </div>

        <div className="thankyou-layout">
          <div className="thankyou-summary">
            <div className="section-heading">
              <p className="eyebrow">Stay summary</p>
              <h2>Your reservation details are ready</h2>
              <p>Review your submitted information below. If anything needs adjustment, contact reservations with your booking reference.</p>
            </div>

            <div className="thankyou-grid">
              <div className="summary-card thankyou-card">
                <p className="eyebrow">Guest details</p>
                <div className="checkout-list">
                  <div className="checkout-list__row">
                    <span>Name</span>
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

              <div className="summary-card thankyou-card">
                <p className="eyebrow">Reservation details</p>
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
                </div>
              </div>
            </div>

            <div className="thankyou-next">
              <div className="info-card">
                <h3>What happens next?</h3>
                <p>Our reservation team reviews your request and confirms availability, stay details, and arrival support based on your selected room and dates.</p>
              </div>
              <div className="info-card">
                <h3>Need immediate help?</h3>
                <p>For urgent updates, call our reservation desk with your booking reference and we will assist you directly.</p>
              </div>
            </div>
          </div>

          <aside className="thankyou-panel">
            {bookingDraft.room_image ? (
              <img alt={bookingDraft.room_title || 'Selected room'} className="thankyou-room-image" src={bookingDraft.room_image} />
            ) : null}
            <div className="thankyou-panel__actions">
              <Link className="button button--primary" to="/">
                Return home
              </Link>
              <Link className="button button--ghost" to="/booking">
                Create new booking
              </Link>
            </div>
          </aside>
        </div>

      </div>
    </section>
  );
}
