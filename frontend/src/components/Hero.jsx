import { useState } from 'react';
import { Link } from 'react-router-dom';

export default function Hero({ siteName, hero, featuredRoom, rooms = [] }) {
  const featuredRooms = rooms.length ? rooms.slice(0, 5) : featuredRoom ? [featuredRoom] : [];
  const [activeIndex, setActiveIndex] = useState(0);
  const activeRoom = featuredRooms[activeIndex] || featuredRoom || null;

  const moveSlide = (direction) => {
    if (!featuredRooms.length) {
      return;
    }

    setActiveIndex((index) => (index + direction + featuredRooms.length) % featuredRooms.length);
  };

  return (
    <section className="hero-panel">
      <div className="container hero-layout">
        <div className="hero-copy-block">
          <p className="eyebrow">{hero?.eyebrow || 'Luxury Hotel Stay'}</p>
          <h1>{hero?.title || `Book premium rooms at ${siteName || 'Harbor Stay'}`}</h1>
          <p className="hero-copy">
            {hero?.description ||
              'Stay in elegant rooms, enjoy a central location, and reserve directly through a smooth booking experience.'}
          </p>
          <div className="hero-actions">
            <Link className="button button--primary" to="/rooms">
              Explore rooms
            </Link>
            <Link className="button button--ghost" to="/booking">
              Book Your Stay
            </Link>
          </div>

          <ul className="hero-list">
            <li>Modern luxury rooms</li>
            <li>Prime city location</li>
            <li>Best price guaranteed</li>
            <li>Easy online booking</li>
          </ul>
        </div>

        <aside className="hero-card hero-card--banner">
          {activeRoom?.featuredImage ? (
            <img
              className="hero-card__image"
              src={activeRoom.featuredImage}
              alt={activeRoom.title}
            />
          ) : null}
          <div className="hero-card__overlay">
            <p className="hero-card__label">Featured Suite</p>
            <h2>{activeRoom?.title || 'Executive room experience'}</h2>
            <p>
              {activeRoom?.excerpt ||
                'Choose from stylish hotel rooms designed for comfort, privacy, and memorable city stays.'}
            </p>
            {featuredRooms.length > 1 ? (
              <div className="hero-slider" aria-label="Featured suite slider">
                <div className="hero-slider__controls">
                  <button
                    aria-label="Previous featured suite"
                    className="hero-slider__arrow"
                    onClick={() => moveSlide(-1)}
                    type="button"
                  >
                    <span aria-hidden="true">←</span>
                  </button>
                  <div className="hero-slider__dots">
                    {featuredRooms.map((room, index) => (
                      <button
                        aria-label={`Show ${room.title}`}
                        className={
                          index === activeIndex
                            ? 'hero-slider__dot hero-slider__dot--active'
                            : 'hero-slider__dot'
                        }
                        key={room.id}
                        onClick={() => setActiveIndex(index)}
                        type="button"
                      />
                    ))}
                  </div>
                  <button
                    aria-label="Next featured suite"
                    className="hero-slider__arrow"
                    onClick={() => moveSlide(1)}
                    type="button"
                  >
                    <span aria-hidden="true">→</span>
                  </button>
                </div>
              </div>
            ) : null}
            <div className="hero-card__actions">
              <Link
                className="button button--primary"
                to={activeRoom ? `/booking?room=${activeRoom.id}` : '/booking'}
              >
                Book Room
              </Link>
              <Link
                className="button button--ghost hero-card__ghost"
                to={activeRoom ? `/rooms/${activeRoom.slug}` : '/rooms'}
              >
                View Details
              </Link>
            </div>
          </div>
        </aside>
      </div>
    </section>
  );
}
