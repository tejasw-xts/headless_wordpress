import { useEffect, useState } from 'react';
import { NavLink } from 'react-router-dom';
import Logo from './Logo';
import AuthModal from '../auth/AuthModal';
import { useAuth } from '../auth/AuthContext';

const links = [
  { to: '/', label: 'Home' },
  { to: '/about', label: 'About' },
  { to: '/contact', label: 'Contact' },
  { to: '/rooms', label: 'Rooms' },
  { to: '/booking', label: 'Booking' },
];

export default function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { isAuthenticated, logout } = useAuth();
  const [authOpen, setAuthOpen] = useState(false);
  const [authMode, setAuthMode] = useState('login');

  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth > 768) {
        setIsMenuOpen(false);
      }
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  return (
    <header className="site-header">
      <div className="container header-row">
        <NavLink className="brand-mark" to="/">
          <Logo compact />
        </NavLink>

        <button
          aria-controls="primary-navigation"
          aria-expanded={isMenuOpen}
          aria-label={isMenuOpen ? 'Close menu' : 'Open menu'}
          className={`menu-toggle${isMenuOpen ? ' menu-toggle--open' : ''}`}
          onClick={() => setIsMenuOpen((open) => !open)}
          type="button"
        >
          <span />
          <span />
          <span />
        </button>

        <nav
          aria-label="Primary navigation"
          className={`main-nav${isMenuOpen ? ' main-nav--open' : ''}`}
          id="primary-navigation"
        >
          <AuthModal
            open={authOpen}
            initialMode={authMode}
            imageUrl="http://172.16.60.17/wp-content/uploads/2026/03/hotelss.jpg"
            onClose={() => setAuthOpen(false)}
            onSuccess={() => {
              setAuthOpen(false);
              setIsMenuOpen(false);
            }}
          />

          {links.map((link) => (
            <NavLink
              key={link.to}
              className={({ isActive }) =>
                isActive ? 'nav-link nav-link--active' : 'nav-link'
              }
              onClick={() => setIsMenuOpen(false)}
              to={link.to}
            >
              {link.label}
            </NavLink>
          ))}

          {isAuthenticated ? (
            <>
              <NavLink
                className={({ isActive }) =>
                  isActive ? 'nav-link nav-link--active' : 'nav-link'
                }
                onClick={() => setIsMenuOpen(false)}
                to="/my-bookings"
              >
                My bookings
              </NavLink>
              <button
                className="nav-link nav-link--button"
                onClick={async () => {
                  await logout();
                  setIsMenuOpen(false);
                }}
                type="button"
              >
                Logout
              </button>
            </>
          ) : (
            <>
              <button
                className="nav-link nav-link--button"
                onClick={() => {
                  setAuthMode('login');
                  setAuthOpen(true);
                }}
                type="button"
              >
                Login
              </button>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}
