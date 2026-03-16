const API_BASE = import.meta.env.VITE_WORDPRESS_API_BASE || '/wp-json';
const WP_AJAX_URL = '/wp-admin/admin-ajax.php';

const AUTH_TOKEN_KEY = 'hotelBookingAuthToken';

export function getAuthToken() {
  try {
    return window.localStorage.getItem(AUTH_TOKEN_KEY) || '';
  } catch (error) {
    return '';
  }
}

export function setAuthToken(token) {
  try {
    if (token) {
      window.localStorage.setItem(AUTH_TOKEN_KEY, token);
    } else {
      window.localStorage.removeItem(AUTH_TOKEN_KEY);
    }
  } catch (error) {
    // Ignore storage failures.
  }
}

async function fetchJson(path, options = {}) {
  const headers = {
    ...(options.headers || {}),
  };

  if (options.body) {
    headers['Content-Type'] = 'application/json';
  }

  const token = getAuthToken();
  if (token && !headers.Authorization) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    let message = 'Request failed.';

    try {
      const errorBody = await response.json();
      message = errorBody.message || message;
    } catch (error) {
      message = response.statusText || message;
    }

    throw new Error(message);
  }

  return response.json();
}

function isNetworkStyleError(error) {
  return (
    error instanceof TypeError ||
    error.message === 'Failed to fetch' ||
    error.message === 'Load failed'
  );
}

function decodeHtml(value) {
  const textarea = document.createElement('textarea');
  textarea.innerHTML = value || '';
  return textarea.value;
}

function stripHtml(value) {
  const div = document.createElement('div');
  div.innerHTML = value || '';
  return (div.textContent || div.innerText || '').trim();
}

function normalizeWpRoom(room) {
  const gallery = Array.isArray(room.room_gallery) ? room.room_gallery : [];

  return {
    id: room.id,
    slug: room.slug,
    title: decodeHtml(room.title?.rendered || 'Room'),
    excerpt: stripHtml(room.excerpt?.rendered || ''),
    content: room.content?.rendered || '',
    link: room.link,
    featuredImage:
      gallery[0]?.large || room._embedded?.['wp:featuredmedia']?.[0]?.source_url || '',
    gallery,
    price: room.room_price || room.meta?._room_price || '',
    maxGuests: room.room_max_guests || room.meta?._room_max_guests || '',
    size: room.room_size || room.meta?._room_size || '',
    beds: room.room_beds || room.meta?._room_beds || '',
    hotelName: room.room_hotel_name || room.meta?._room_hotel_name || '',
    location: room.room_location || room.meta?._room_location || '',
  };
}

export async function getHomepage() {
  try {
    return await fetchJson('/hotel-booking/v1/homepage');
  } catch (error) {
    const [frontPages, offerPages, servicePages, experiencePages, rooms] = await Promise.all([
      fetchJson('/wp/v2/pages?slug=home'),
      fetchJson('/wp/v2/pages?slug=home-offers'),
      fetchJson('/wp/v2/pages?slug=home-services'),
      fetchJson('/wp/v2/pages?slug=home-experiences'),
      getRooms(),
    ]);

    const homePage = frontPages[0] || null;
    const offerPage = offerPages[0] || null;
    const servicePage = servicePages[0] || null;
    const experiencePage = experiencePages[0] || null;

    return {
      site: {
        name: document.title.replace(/\s*\|.*$/, '') || 'Harbor Stay',
        description: 'WordPress managed hotel website',
        url: window.location.origin,
      },
      hero: {
        eyebrow: 'Luxury Hotel Stay',
        title: homePage
          ? decodeHtml(homePage.title?.rendered || 'Book your stay at Harbor Stay')
          : 'Book your stay at Harbor Stay',
        description: homePage
          ? decodeHtml(homePage.excerpt?.rendered || '')
          : 'Discover elegant hotel rooms, a welcoming city location, and a smooth direct booking experience for your next stay.',
      },
      sections: {
        offer_title: decodeHtml(offerPage?.title?.rendered || 'Exclusive Hotel Offers'),
        offer_description:
          decodeHtml(offerPage?.excerpt?.rendered || '') ||
          'Enjoy exclusive offers and premium hospitality services designed to make your stay at Harbor Stay unforgettable.',
        offer_cards: [],
        service_title: decodeHtml(servicePage?.title?.rendered || 'Premium Guest Services'),
        service_description:
          decodeHtml(servicePage?.excerpt?.rendered || '') ||
          'Our professional hospitality team ensures that every guest receives exceptional service from arrival to departure.',
        service_cards: [],
        experience_title: decodeHtml(experiencePage?.title?.rendered || 'Unique Stay Experiences'),
        experience_description:
          decodeHtml(experiencePage?.excerpt?.rendered || '') ||
          'Make your stay memorable with curated experiences designed for relaxation, exploration, and luxury living.',
        experience_cards: [],
      },
      rooms: rooms.slice(0, 6),
      fallback: true,
      fallbackReason: error.message,
    };
  }
}

export async function getRooms() {
  const rooms = await fetchJson('/wp/v2/rooms?per_page=12&_embed');
  return rooms.map(normalizeWpRoom);
}

export async function getRoomBySlug(slug) {
  const rooms = await fetchJson(
    `/wp/v2/rooms?slug=${encodeURIComponent(slug)}&_embed`,
  );

  if (!rooms.length) {
    throw new Error('Room not found.');
  }

  return normalizeWpRoom(rooms[0]);
}

export async function createBooking(payload) {
  try {
    return await fetchJson('/hotel-booking/v1/booking', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  } catch (error) {
    const formData = new URLSearchParams();
    formData.set('action', 'process_booking');
    formData.set('nonce', window.hotelBooking?.nonce || '');
    formData.set('check_in', payload.check_in || '');
    formData.set('check_out', payload.check_out || '');
    formData.set('guests', String(payload.guests || ''));
    formData.set('room_id', String(payload.room_id || ''));
    formData.set('name', payload.name || '');
    formData.set('email', payload.email || '');
    formData.set('phone', payload.phone || '');

    const response = await fetch(WP_AJAX_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: formData.toString(),
    });

    if (!response.ok) {
      throw error;
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.data?.message || 'Booking failed.');
    }

    return {
      success: true,
      booking_id: null,
      message: result.data?.message || 'Booking successful.',
      fallback: true,
    };
  }
}

export async function registerUser({ name = '', email = '', password = '' }) {
  const result = await fetchJson('/hotel-booking/v1/auth/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password }),
  });

  if (result?.token) {
    setAuthToken(result.token);
  }

  return result;
}

export async function loginUser({ identifier = '', email = '', password = '' }) {
  const result = await fetchJson('/hotel-booking/v1/auth/login', {
    method: 'POST',
    body: JSON.stringify({ identifier, email, password }),
  });

  if (result?.token) {
    setAuthToken(result.token);
  }

  return result;
}

export async function logoutUser() {
  setAuthToken('');
  return { success: true };
}

export async function getMe() {
  return fetchJson('/hotel-booking/v1/auth/me');
}

export async function getMyBookings() {
  return fetchJson('/hotel-booking/v1/my-bookings');
}

export async function cancelMyBooking(bookingId) {
  const id = Number(bookingId);
  if (!id) throw new Error('Invalid booking.');
  return fetchJson(`/hotel-booking/v1/bookings/${id}/cancel`, { method: 'POST' });
}

export async function requestMyBookingDateChange(bookingId, { new_check_in, new_check_out }) {
  const id = Number(bookingId);
  if (!id) throw new Error('Invalid booking.');
  return fetchJson(`/hotel-booking/v1/bookings/${id}/change-dates`, {
    method: 'POST',
    body: JSON.stringify({ new_check_in, new_check_out }),
  });
}

export async function getPageBySlug(slug) {
  const pages = await fetchJson(`/wp/v2/pages?slug=${encodeURIComponent(slug)}`);

  if (!pages.length) {
    return null;
  }

  const page = pages[0];

  return {
    id: page.id,
    slug: page.slug,
    title: decodeHtml(page.title?.rendered || slug),
    content: page.content?.rendered || '',
    excerpt: decodeHtml(page.excerpt?.rendered || ''),
  };
}

export function explainFetchError(error) {
  if (isNetworkStyleError(error)) {
    return 'React could not reach the WordPress API. Check that Vite is running on port 5173 and WordPress is reachable from the proxy target.';
  }

  if (error.message === 'Not Found') {
    return 'The requested WordPress endpoint does not exist. Your standard REST API is working, but the custom headless route is missing.';
  }

  return error.message || 'Request failed.';
}
