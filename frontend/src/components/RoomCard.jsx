import { Link } from 'react-router-dom';
import { formatCurrency } from '../utils/format';

export default function RoomCard({ room }) {
  return (
    <article className="room-card">
      <div className="room-card__image-wrap">
        {room.featuredImage ? (
          <img
            className="room-card__image"
            src={room.featuredImage}
            alt={room.title}
          />
        ) : (
          <div className="room-card__image room-card__image--empty">
            No image
          </div>
        )}
      </div>

      <div className="room-card__body">
        <div className="room-card__topline">
          <h3>{room.title}</h3>
          <span>{formatCurrency(room.price)}</span>
        </div>

        <p className="room-card__copy">{room.excerpt}</p>

        <div className="room-card__meta">
          <span>{room.maxGuests || 'N/A'} guests</span>
          <span>{room.beds || 'N/A'} beds</span>
          <span>{room.size || 'N/A'} sq ft</span>
        </div>

        <Link className="button button--inline" to={`/rooms/${room.slug}`}>
          View details
        </Link>
      </div>
    </article>
  );
}
