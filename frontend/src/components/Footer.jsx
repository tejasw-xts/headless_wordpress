import { Link } from 'react-router-dom';
import Logo from './Logo';

export default function Footer() {
  return (
    <footer className="site-footer">
      <div className="container footer-shell">
        <div className="footer-top">
          <div>
            <div className="footer-brand">
              <Logo />
            </div>
            <h2>Luxury rooms, thoughtful service, and direct booking made simple.</h2>
            <p className="footer-copy">
              Subscribe for exclusive hotel offers, seasonal packages, and travel inspiration for your next city stay.
            </p>
          </div>

          <form className="footer-newsletter__form">
            <input placeholder="Enter your email address" type="email" />
            <button className="button button--primary" type="submit">
              Subscribe
            </button>
          </form>
        </div>

        <div className="footer-grid">
          <div>
            <p className="footer-label">Stay</p>
            <Link className="footer-link" to="/rooms">Rooms & Suites</Link>
            <a className="footer-link" href="/#offers">Hotel Offers</a>
            <a className="footer-link" href="/#experiences">Guest Experiences</a>
          </div>

          <div>
            <p className="footer-label">Contact</p>
            <a className="footer-link" href="https://maps.google.com/?q=Mumbai%2C%20India" rel="noreferrer" target="_blank">Mumbai, India</a>
            <a className="footer-link" href="tel:+919876543210">+91 98765 43210</a>
            <a className="footer-link" href="mailto:stay@harborstay.com">stay@harborstay.com</a>
          </div>

          <div>
            <p className="footer-label">Service</p>
            <a className="footer-link" href="/#services">Airport Pickup</a>
            <a className="footer-link" href="/#services">Private Dining</a>
            <a className="footer-link" href="/#services">Front Desk Support</a>
          </div>
        </div>

        <div className="footer-bottom">
          <p className="footer-copy">© 2026 Harbor Stay. All rights reserved.</p>
          <p className="footer-copy">Luxury hotel website experience with direct booking.</p>
        </div>
      </div>
    </footer>
  );
}
