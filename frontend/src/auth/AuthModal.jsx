import React, { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { useAuth } from './AuthContext';

export default function AuthModal({
  open,
  onClose,
  onSuccess,
  defaultEmail = '',
  defaultName = '',
  initialMode = 'login',
  imageUrl = '',
}) {
  const { loading, login, register } = useAuth();
  const [mode, setMode] = useState(initialMode === 'register' ? 'register' : 'login');
  const [name, setName] = useState(defaultName);
  const [email, setEmail] = useState(defaultEmail);
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    if (open) {
      setMode(initialMode === 'register' ? 'register' : 'login');
      setName(defaultName);
      setEmail(defaultEmail);
      setPassword('');
      setError('');
    }
  }, [open, initialMode, defaultEmail, defaultName]);

  if (!open) return null;

  if (typeof document === 'undefined') return null;

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');

    try {
      if (mode === 'register') {
        await register({ name, email, password });
      } else {
        await login({ email, password });
      }
      onSuccess?.();
    } catch (e) {
      setError(e?.message || 'Login failed.');
    }
  }

  return createPortal(
    <div
      className="auth-modal"
      onMouseDown={(e) => {
        if (e.target === e.currentTarget) onClose?.();
      }}
      role="dialog"
      aria-modal="true"
    >
      <div className="auth-modal__panel">
        <button type="button" className="auth-modal__close" onClick={onClose} aria-label="Close">
          ×
        </button>

        <div
          className="auth-modal__image"
          style={
            imageUrl
              ? { backgroundImage: `url(${imageUrl})` }
              : undefined
          }
        >
          <div className="auth-modal__image-overlay">
            <p className="eyebrow auth-modal__kicker">Welcome back</p>
            <h3 className="auth-modal__image-title">Mukta Boutique Hotel</h3>
            <p className="auth-modal__image-copy">
              Sign in to book your room, view your bookings, and request date changes.
            </p>
          </div>
        </div>

        <div className="auth-modal__content">
          <div className="auth-modal__header">
            <p className="eyebrow">{mode === 'register' ? 'Register' : 'Login'}</p>
            <h3 style={{ margin: 0 }}>Continue to checkout to book this room</h3>
          </div>

          <form onSubmit={handleSubmit} className="auth-modal__form">
            {mode === 'register' ? (
              <label className="auth-modal__field">
                Full name
                <input
                  className="auth-modal__input"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="Your name"
                />
              </label>
            ) : null}

            <label className="auth-modal__field">
              Email
              <input
                className="auth-modal__input"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="you@example.com"
                type="email"
                required
              />
            </label>

            <label className="auth-modal__field">
              Password
              <input
                className="auth-modal__input"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Password"
                type="password"
                required
                minLength={6}
              />
            </label>

            <div className="auth-modal__actions">
              <button type="submit" className="button button--primary" disabled={loading}>
                {loading
                  ? 'Please wait…'
                  : mode === 'register'
                    ? 'Create account & continue'
                    : 'Login & continue'}
              </button>
              <p className="auth-modal__hint">
                {mode === 'register'
                  ? 'We will create your account and then continue checkout.'
                  : 'Use your account email + password to continue.'}
              </p>
            </div>

            <div className="auth-modal__switch">
              {mode === 'login' ? (
                <button
                  type="button"
                  className="auth-modal__link"
                  onClick={() => setMode('register')}
                >
                  New here? Create an account
                </button>
              ) : (
                <button
                  type="button"
                  className="auth-modal__link"
                  onClick={() => setMode('login')}
                >
                  Already have an account? Login
                </button>
              )}
            </div>

            {error ? <div className="auth-modal__error">{error}</div> : null}
          </form>
        </div>
      </div>
    </div>
    ,
    document.body,
  );
}
