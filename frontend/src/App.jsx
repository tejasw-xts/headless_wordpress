import { Navigate, Route, Routes } from 'react-router-dom';
import Footer from './components/Footer';
import Header from './components/Header';
import BookingPage from './pages/BookingPage';
import HomePage from './pages/HomePage';
import NotFoundPage from './pages/NotFoundPage';
import GenericPage from './pages/GenericPage';
import CheckoutPage from './pages/CheckoutPage';
import RoomDetailPage from './pages/RoomDetailPage';
import RoomsPage from './pages/RoomsPage';
import ThankYouPage from './pages/ThankYouPage';
import MyBookingsPage from './pages/MyBookingsPage';

export default function App() {
  return (
    <div className="app-shell">
      <Header />
      <main className="app-main">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route
            path="/about"
            element={
              <GenericPage
                pageType="about"
                slug="about-us"
                fallbackTitle="About Us"
                fallbackContent="<p>Mukta Boutique Hotel &amp; Spa is designed for travelers who value warm hospitality, elegant interiors, and a calm city retreat. From thoughtfully appointed rooms to attentive guest service, every part of the stay is shaped around comfort and ease.</p><p>Our hotel blends modern convenience with a refined boutique atmosphere, making it ideal for family stays, business visits, weekend escapes, and special celebrations.</p><p>Whether you are arriving for a short city break or a longer relaxing stay, our team is focused on creating a seamless experience from check-in to departure.</p>"
              />
            }
          />
          <Route
            path="/contact"
            element={
              <GenericPage
                pageType="contact"
                slug="contact"
                fallbackTitle="Contact"
                fallbackContent="<p>Our reservations team is available to help with room bookings, stay details, group inquiries, and special requests. Reach out anytime and we will guide you through the best room options for your visit.</p><p>For faster assistance, include your travel dates, number of guests, and preferred room type when contacting us.</p>"
              />
            }
          />
          <Route path="/rooms" element={<RoomsPage />} />
          <Route path="/rooms/:slug" element={<RoomDetailPage />} />
          <Route path="/booking" element={<BookingPage />} />
          <Route path="/checkout" element={<CheckoutPage />} />
          <Route path="/my-bookings" element={<MyBookingsPage />} />
          <Route path="/thankyou" element={<ThankYouPage />} />
          <Route path="/home" element={<Navigate to="/" replace />} />
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </main>
      <Footer />
    </div>
  );
}
