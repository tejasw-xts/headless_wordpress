import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { explainFetchError, getHomepage } from '../api/wordpress';
import ErrorState from '../components/ErrorState';
import Hero from '../components/Hero';
import LoadingState from '../components/LoadingState';
import RoomCard from '../components/RoomCard';
import SectionCards from '../components/SectionCards';

export default function HomePage() {
  const roomsSliderRef = useRef(null);
  const [state, setState] = useState({
    loading: true,
    error: '',
    data: null,
  });

  const scrollRooms = (direction) => {
    const slider = roomsSliderRef.current;

    if (!slider) {
      return;
    }

    const amount = Math.max(slider.clientWidth * 0.75, 320);
    slider.scrollBy({
      left: direction * amount,
      behavior: 'smooth',
    });
  };

  useEffect(() => {
    let active = true;

    getHomepage()
      .then((data) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: '',
          data,
        });
      })
      .catch((error) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: explainFetchError(error),
          data: null,
        });
      });

    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    if (!state.data) {
      return;
    }

    document.title = `${state.data.site?.name || 'Harbor Stay'} | Luxury Hotel Rooms & Direct Booking`;
  }, [state.data]);

  if (state.loading) {
    return <LoadingState label="Loading homepage from WordPress..." />;
  }

  if (state.error) {
    return <ErrorState message={state.error} />;
  }

  const { data } = state;
  const featuredRoom = data.rooms?.[0] || null;

  return (
    <>
      <Hero
        hero={data.hero}
        rooms={data.rooms}
        siteName={data.site?.name}
        featuredRoom={featuredRoom}
      />

      <section className="content-section">
        <div className="container">
          <div className="section-heading section-heading--inline rooms-section__heading">
            <div className="rooms-section__intro">
              <p className="eyebrow">Luxury Rooms</p>
              <h2>Discover Luxury Hotel Rooms Designed for Comfort</h2>
              <p>Choose from our carefully designed hotel rooms that combine comfort, elegance,
                and modern amenities. Each room is thoughtfully created to provide guests with
                a relaxing and memorable stay experience.</p>
            </div>
            <Link className="button button--ghost rooms-section__cta" to="/rooms">
              View all rooms
            </Link>
          </div>

          <div className="rooms-slider">
            <div className="rooms-slider__controls" aria-label="Luxury rooms slider controls">
              <button
                aria-label="Previous rooms"
                className="rooms-slider__control"
                onClick={() => scrollRooms(-1)}
                type="button"
              >
                <span aria-hidden="true">←</span>
              </button>
              <button
                aria-label="Next rooms"
                className="rooms-slider__control"
                onClick={() => scrollRooms(1)}
                type="button"
              >
                <span aria-hidden="true">→</span>
              </button>
            </div>
            <div className="room-grid room-grid--slider" ref={roomsSliderRef}>
              {data.rooms.map((room) => (
                <RoomCard key={room.id} room={room} />
              ))}
            </div>
            <div className="rooms-slider__hint" aria-hidden="true">
              Explore the collection with the arrows or by swiping across the cards
            </div>
          </div>
        </div>
      </section>

      <SectionCards
        sectionId="offers"
        title={data.sections?.offer_title || 'Exclusive Hotel Offers'}
        description={
          data.sections?.offer_description ||
          'Enjoy exclusive offers and premium hospitality services designed to make your stay at Harbor Stay unforgettable.'
        }
        cards={data.sections?.offer_cards}
      />
      <SectionCards
        sectionId="services"
        title={data.sections?.service_title || 'Premium Guest Services'}
        description={
          data.sections?.service_description ||
          'Our professional hospitality team ensures that every guest receives exceptional service from arrival to departure.'
        }
        cards={data.sections?.service_cards}
      />
      <SectionCards
        sectionId="experiences"
        title={data.sections?.experience_title || 'Unique Stay Experiences'}
        description={
          data.sections?.experience_description ||
          'Make your stay memorable with curated experiences designed for relaxation, exploration, and luxury living.'
        }
        cards={data.sections?.experience_cards}
      />
    </>
  );
}
