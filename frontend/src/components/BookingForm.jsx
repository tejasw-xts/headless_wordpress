import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

const initialState = {
  name: '',
  email: '',
  phone: '',
  check_in: '',
  check_out: '',
  guests: '2',
  room_id: '',
};

function shortenRoomLabel(room) {
  const maxLength = 20;
  const title =
    room.title.length > maxLength
      ? `${room.title.slice(0, maxLength).trim()}...`
      : room.title;

  return title;
}

export default function BookingForm({ rooms = [], defaultRoomId = '' }) {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    ...initialState,
    room_id: defaultRoomId,
  });

  const roomOptions = rooms.map((room) => ({
    value: String(room.id),
    label: shortenRoomLabel(room),
  }));

  function handleChange(event) {
    const { name, value } = event.target;

    setFormData((current) => ({
      ...current,
      [name]: value,
    }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const selectedRoom = rooms.find((room) => String(room.id) === String(formData.room_id));

    navigate('/checkout', {
      state: {
        bookingDraft: {
          ...formData,
          guests: Number(formData.guests),
          room_id: Number(formData.room_id),
          room_title: selectedRoom?.title || '',
          room_price: selectedRoom?.price || '',
          room_image: selectedRoom?.featuredImage || '',
          hotel_name: selectedRoom?.hotelName || '',
        },
      },
    });
  }

  return (
    <form className="booking-form" onSubmit={handleSubmit}>
      <div className="booking-form__header">
        <p className="eyebrow">Reservation form</p>
        <h2>Request your room</h2>
        <p>Complete the details below and continue to checkout to confirm your stay request.</p>
      </div>

      <div className="form-grid">
        <label>
          Full name
          <input
            name="name"
            onChange={handleChange}
            required
            type="text"
            value={formData.name}
          />
        </label>

        <label>
          Email
          <input
            name="email"
            onChange={handleChange}
            required
            type="email"
            value={formData.email}
          />
        </label>

        <label>
          Phone
          <input
            name="phone"
            onChange={handleChange}
            type="tel"
            value={formData.phone}
          />
        </label>

        <label>
          Room
          <select
            name="room_id"
            onChange={handleChange}
            required
            value={formData.room_id}
          >
            <option value="">Select a room</option>
            {roomOptions.map((room) => (
              <option key={room.value} value={room.value}>
                {room.label}
              </option>
            ))}
          </select>
        </label>

        <label>
          Check in
          <input
            name="check_in"
            onChange={handleChange}
            required
            type="date"
            value={formData.check_in}
          />
        </label>

        <label>
          Check out
          <input
            name="check_out"
            onChange={handleChange}
            required
            type="date"
            value={formData.check_out}
          />
        </label>

        <label>
          Guests
          <select name="guests" onChange={handleChange} value={formData.guests}>
            {[1, 2, 3, 4, 5].map((count) => (
              <option key={count} value={count}>
                {count}
              </option>
            ))}
          </select>
        </label>
      </div>

      <div className="booking-form__footer">
        <p>Secure your stay with direct hotel support and a fast checkout experience.</p>
        <button className="button button--primary">Continue to checkout</button>
      </div>
    </form>
  );
}
