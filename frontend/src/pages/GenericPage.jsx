import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getPageBySlug } from '../api/wordpress';
import ErrorState from '../components/ErrorState';
import LoadingState from '../components/LoadingState';

export default function GenericPage({
  pageType = 'page',
  slug,
  fallbackTitle,
  fallbackContent,
}) {
  const [state, setState] = useState({
    loading: true,
    error: '',
    page: null,
  });

  useEffect(() => {
    let active = true;

    getPageBySlug(slug)
      .then((page) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: '',
          page,
        });
      })
      .catch((error) => {
        if (!active) {
          return;
        }

        setState({
          loading: false,
          error: error.message || 'Failed to load page.',
          page: null,
        });
      });

    return () => {
      active = false;
    };
  }, [slug]);

  if (state.loading) {
    return <LoadingState label={`Loading ${fallbackTitle} page...`} />;
  }

  if (state.error) {
    return <ErrorState message={state.error} />;
  }

  const title = state.page?.title || fallbackTitle;
  const content = state.page?.content || fallbackContent;
  const excerpt = state.page?.excerpt || '';

  const pageMeta = {
    about: {
      eyebrow: 'Boutique hospitality',
      lead:
        excerpt ||
        'Discover a refined stay experience shaped by elegant rooms, calm wellness touches, and attentive service in the heart of the city.',
      sideTitle: 'Why guests choose Mukta',
      sideItems: [
        'Boutique rooms with modern comforts',
        'Warm service and direct booking support',
        'Relaxed city location for leisure and business travel',
      ],
    },
    contact: {
      eyebrow: 'Guest assistance',
      lead:
        excerpt ||
        'Speak with our team for reservations, special stay requests, airport pickup planning, and direct booking assistance.',
      sideTitle: 'Contact details',
      sideItems: [
        'Mumbai, India',
        '+91 98765 43210',
        'stay@harborstay.com',
      ],
    },
    page: {
      eyebrow: 'Hotel page',
      lead: excerpt || '',
      sideTitle: 'Stay information',
      sideItems: [
        'Direct reservations available',
        'Curated room experiences',
        'Guest support before arrival',
      ],
    },
  };

  const config = pageMeta[pageType] || pageMeta.page;

  if (pageType === 'about') {
    return (
      <section className="content-section">
        <div className="container page-shell">
          <div className="about-hero">
            <div className="about-hero__content">
              <p className="eyebrow">{config.eyebrow}</p>
              <h1>{title}</h1>
              <p className="page-lead">{config.lead}</p>
              <div className="hero-actions">
                <Link className="button button--primary" to="/rooms">
                  Explore rooms
                </Link>
                <Link className="button button--ghost" to="/contact">
                  Contact hotel
                </Link>
              </div>
            </div>

            <aside className="about-hero__visual">
              <div className="about-hero__badge">
                <p className="eyebrow">Boutique stay</p>
                <h3>Elegant hospitality in the heart of the city</h3>
                <p>Designed for leisure escapes, family stays, and calm business travel.</p>
              </div>
              <div className="about-hero__stats">
                <div className="info-card">
                  <h3>24/7</h3>
                  <p>Guest assistance</p>
                </div>
                <div className="info-card">
                  <h3>Direct</h3>
                  <p>Hotel booking support</p>
                </div>
              </div>
            </aside>
          </div>

          <div className="about-layout">
            <article
              className="page-content"
              dangerouslySetInnerHTML={{ __html: content }}
            />

            <aside className="about-side">
              <div className="page-sidecard">
                <p className="eyebrow">Our promise</p>
                <div className="page-sidecard__list">
                  <p>Personalized guest care from reservation to departure.</p>
                  <p>Comfort-focused rooms with refined boutique styling.</p>
                  <p>Direct communication for requests, upgrades, and special stays.</p>
                </div>
              </div>
              <div className="page-sidecard">
                <p className="eyebrow">Guest experience</p>
                <div className="page-sidecard__list">
                  <p>Relaxed room ambiance</p>
                  <p>Warm hospitality team</p>
                  <p>City convenience with a boutique atmosphere</p>
                </div>
              </div>
            </aside>
          </div>

          <div className="about-values">
            <div className="info-card">
              <p className="eyebrow">Comfort first</p>
              <h3>Spaces designed to feel calm, warm, and welcoming.</h3>
            </div>
            <div className="info-card">
              <p className="eyebrow">Thoughtful service</p>
              <h3>Every stay is supported by attentive and practical guest care.</h3>
            </div>
            <div className="info-card">
              <p className="eyebrow">Boutique character</p>
              <h3>A more personal hotel experience with elegant details throughout.</h3>
            </div>
          </div>
        </div>
      </section>
    );
  }

  if (pageType === 'contact') {
    const faqs = [
      {
        question: 'What time are check-in and check-out?',
        answer: 'Standard check-in is from 2:00 PM and check-out is by 11:00 AM. Early arrival and late departure requests can be shared with our reservations team.',
      },
      {
        question: 'Can I request airport pickup or local transport?',
        answer: 'Yes. We can help arrange airport transfers and local travel support based on availability and your stay schedule.',
      },
      {
        question: 'Do you assist with family stays and special room requests?',
        answer: 'Yes. Our team can guide you toward suitable room categories for families, couples, or business travelers and help with special stay preferences.',
      },
      {
        question: 'How can I confirm room availability quickly?',
        answer: 'The fastest option is to contact our reservations team with your dates, number of guests, and preferred room category so we can assist directly.',
      },
    ];

    return (
      <section className="content-section">
        <div className="container page-shell">
          <div className="contact-hero">
            <div className="contact-hero__content">
              <p className="eyebrow">{config.eyebrow}</p>
              <h1>{title}</h1>
              <p className="page-lead">{config.lead}</p>
              <div className="hero-actions">
                <Link className="button button--primary" to="/booking">
                  Reserve your stay
                </Link>
                <a className="button button--ghost" href="tel:+919876543210">
                  Call hotel
                </a>
              </div>
            </div>

            <aside className="contact-hero__panel">
              <div className="page-sidecard">
                <p className="eyebrow">Reservations</p>
                <h3>Speak with our hotel team directly</h3>
                <p>We are available to assist with room guidance, special requests, airport pickup planning, and direct booking support.</p>
              </div>
            </aside>
          </div>

          <div className="contact-grid">
            <div className="info-card">
              <p className="eyebrow">Visit us</p>
              <h3>Mumbai, India</h3>
              <p>Conveniently located for city stays, business travel, and relaxing boutique hotel experiences.</p>
            </div>
            <div className="info-card">
              <p className="eyebrow">Call us</p>
              <h3>+91 98765 43210</h3>
              <p>Speak with our team for reservation support, booking guidance, and stay assistance.</p>
            </div>
            <div className="info-card">
              <p className="eyebrow">Email us</p>
              <h3>stay@harborstay.com</h3>
              <p>Share your travel dates and room preferences for quicker booking assistance.</p>
            </div>
          </div>

          <div className="contact-layout">
            <article
              className="page-content"
              dangerouslySetInnerHTML={{ __html: content }}
            />

            <aside className="contact-side">
              <div className="page-sidecard">
                <p className="eyebrow">For reservations</p>
                <div className="page-sidecard__list">
                  <p>Share check-in and check-out dates</p>
                  <p>Let us know the number of guests</p>
                  <p>Tell us your preferred room type or special requests</p>
                </div>
              </div>
              <div className="page-sidecard">
                <p className="eyebrow">Need quick help?</p>
                <div className="page-sidecard__list">
                  <p>Room availability assistance</p>
                  <p>Airport pickup coordination</p>
                  <p>Group and family stay support</p>
                </div>
              </div>
            </aside>
          </div>

          <div className="faq-section">
            <div className="section-heading">
              <p className="eyebrow">Frequently asked questions</p>
              <h2>Helpful answers before you book or contact us</h2>
            </div>

            <div className="faq-list">
              {faqs.map((faq) => (
                <details className="faq-card" key={faq.question}>
                  <summary className="faq-card__summary">
                    <h3>{faq.question}</h3>
                    <span className="faq-card__icon" aria-hidden="true">+</span>
                  </summary>
                  <div className="faq-card__answer">
                    <p>{faq.answer}</p>
                  </div>
                </details>
              ))}
            </div>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="content-section">
      <div className="container page-shell">
        <div className="page-hero">
          <div className="section-heading">
            <p className="eyebrow">{config.eyebrow}</p>
            <h1>{title}</h1>
            {config.lead ? <p className="page-lead">{config.lead}</p> : null}
          </div>

          <aside className="page-sidecard">
            <p className="eyebrow">{config.sideTitle}</p>
            <div className="page-sidecard__list">
              {config.sideItems.map((item) => (
                <p key={item}>{item}</p>
              ))}
            </div>
          </aside>
        </div>

        <article
          className="page-content"
          dangerouslySetInnerHTML={{ __html: content }}
        />
      </div>
    </section>
  );
}
