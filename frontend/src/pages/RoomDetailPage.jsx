import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { getRoomBySlug } from '../api/wordpress';
import AuthModal from '../auth/AuthModal';
import { useAuth } from '../auth/AuthContext';
import ErrorState from '../components/ErrorState';
import LoadingState from '../components/LoadingState';
import { formatCurrency } from '../utils/format';

export default function RoomDetailPage() {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();
  const [state, setState] = useState({
    loading: true,
    error: '',
    room: null,
  });
  const [activeImage, setActiveImage] = useState('');
  const [authOpen, setAuthOpen] = useState(false);

  useEffect(() => {
    let active = true;

    getRoomBySlug(slug)
      .then((room) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: '',
          room,
        });
        setActiveImage(room.gallery?.[0]?.large || room.featuredImage || '');
      })
      .catch((error) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: error.message || 'Failed to load room.',
          room: null,
        });
      });

    return () => {
      active = false;
    };
  }, [slug]);

  if (state.loading) {
    return <LoadingState label="Loading room details..." />;
  }

  if (state.error) {
    return <ErrorState message={state.error} />;
  }

  const { room } = state;
  const gallery = room.gallery || [];
  const currentImage = activeImage || room.featuredImage;

  return (
    <section className="content-section">
      <div className="container room-detail">
        <div>
          {currentImage ? (
            <img className="room-detail__image" src={currentImage} alt={room.title} />
          ) : null}

          {gallery.length > 1 ? (
            <div className="room-detail__thumbs">
              {gallery.map((image) => (
                <button
                  key={image.id}
                  className={
                    currentImage === image.large
                      ? 'room-detail__thumb room-detail__thumb--active'
                      : 'room-detail__thumb'
                  }
                  onClick={() => setActiveImage(image.large)}
                  type="button"
                >
                  <img src={image.thumb} alt={image.alt || room.title} />
                </button>
              ))}
            </div>
          ) : null}
        </div>

        <div className="room-detail__content">
          <p className="eyebrow">Room detail</p>
          {room.hotelName ? <p className="room-detail__hotel">{room.hotelName}</p> : null}
          <h1 className="room-detail__title">{room.title}</h1>

          {room.location ? <p className="room-detail__location">{room.location}</p> : null}
          <span className="price-pill room-detail__price">{formatCurrency(room.price)}</span>

          <div className="room-detail__meta">
            <span>{room.maxGuests || 'N/A'} guests</span>
            <span>{room.beds || 'N/A'} beds</span>
            <span>{room.size || 'N/A'} sq ft</span>
          </div>

          <div
            className="rich-copy"
            dangerouslySetInnerHTML={{ __html: room.content }}
          />

          <div className="room-detail__support">
            <div className="info-card">
              <p className="eyebrow">Stay highlights</p>
              <p>Elegant interiors, direct hotel booking, and a comfortable stay experience designed for leisure and business guests.</p>
            </div>
            <div className="info-card">
              <p className="eyebrow">Book direct</p>
              <p>Reserve through the hotel for quicker assistance, personalized support, and easier communication before arrival.</p>
            </div>
          </div>

          <AuthModal
            open={authOpen}
            onClose={() => setAuthOpen(false)}
            imageUrl={currentImage || room.featuredImage || ''}
            onSuccess={() => {
              setAuthOpen(false);
              navigate(`/booking?room=${room.id}`);
            }}
          />

          <Link
            className="button button--primary"
            to={`/booking?room=${room.id}`}
            onClick={(event) => {
              if (!isAuthenticated) {
                event.preventDefault();
                setAuthOpen(true);
              }
            }}
          >
            Book this room
          </Link>
        </div>
      </div>
    </section>
  );
}
