# Mukta Boutique Hotel — Headless WordPress + React (Vite)

A full-stack demo hotel booking website built with **WordPress as a headless CMS** (rooms + bookings + admin workflow) and a **React (Vite) frontend**.

This project was created as a client-ready demo showing a modern hotel UI with a real booking workflow: saving bookings, managing status from the WordPress dashboard, and sending email notifications.

---

## Highlights

- **Headless rooms CMS** in WordPress (rooms are managed in WP Dashboard)
- **React UI (Vite)** for fast, modern frontend
- **Booking workflow**
  - Create booking (New)
  - Admin can **Confirm / Cancel** from WP
  - Status emails to admin + customer
- **Customer account flow (frontend)**
  - Login popup on *Book this room*
  - **My bookings** page
  - Customer can **cancel** or **request date change** (admin gets email)
- **Dedicated bookings DB table** synced with Booking CPT
- **SMTP support** for reliable email delivery

---

## Repo Structure

- `frontend/` — React + Vite app
- `server/` — WordPress installation
  - `server/wp-content/plugins/hotel-booking/` — custom booking plugin (status, emails, auth API)
  - `server/wp-content/plugins/smart_broken/` — broken-link utility plugin
  - `server/wp-content/themes/hotel-booking-theme-child/` — child theme for Elementor support + REST routes + admin booking columns

---

## Tech Stack

**Backend**
- WordPress (custom post types + REST API)
- Custom plugin: `hotel-booking`
  - booking status (`new`, `confirmed`, `cancelled`)
  - customer/admin email notifications
  - SMTP via `phpmailer_init`
  - custom DB table: `wp_hotel_bookings`
  - REST auth endpoints (token-based)
  - customer self-service endpoints (my bookings, cancel, date change)

**Frontend**
- React
- Vite
- Tailwind utilities (kept design via existing CSS; Tailwind preflight disabled)

---

## Features (Detailed)

### Rooms
- Rooms are created/managed in WordPress.
- Frontend fetches rooms using the WP REST API.

### Bookings
- Booking data includes: room, dates, guests, customer info, status.
- Saved as a `booking` custom post type + synced into a dedicated table.

### Admin workflow
- Admin receives a **New booking** email with full booking details.
- Admin can confirm/cancel the booking in WordPress.
- On confirm/cancel, both **admin + customer** receive status emails.

### Customer actions
- Login popup shown when user clicks **Book this room**.
- Customers can:
  - view all bookings from **My bookings** page
  - cancel a booking
  - request a date change (request is saved in booking meta + emailed to admin)

---

## Environment Setup

### Requirements
- PHP + MySQL + Apache/Nginx (for WordPress)
- Node.js 18+ (Node 20 recommended) for frontend

### WordPress configuration
`server/wp-config.php` is intentionally **not committed** (secrets).

1) Copy sample:
- Copy `server/wp-config-sample.php` → `server/wp-config.php`

2) Set DB credentials.

3) (Optional but recommended) Force site URL:
```php
define('WP_HOME', 'http://YOUR_SERVER_IP');
define('WP_SITEURL', 'http://YOUR_SERVER_IP');
```

---

## SMTP (Email Delivery)

Add these constants in `server/wp-config.php` (values depend on your provider):

```php
define('HOTEL_BOOKING_SMTP_HOST', 'smtp.example.com');
define('HOTEL_BOOKING_SMTP_PORT', 587);
define('HOTEL_BOOKING_SMTP_ENCRYPTION', 'tls'); // tls | ssl | none
define('HOTEL_BOOKING_SMTP_USERNAME', 'you@example.com');
define('HOTEL_BOOKING_SMTP_PASSWORD', 'YOUR_APP_PASSWORD');
define('HOTEL_BOOKING_SMTP_FROM_ADDRESS', 'you@example.com');
define('HOTEL_BOOKING_SMTP_FROM_NAME', 'Mukta Boutique Hotel');
```

---

## Frontend Setup (React)

From `frontend/`:

```bash
npm install
npm run dev
```

Environment variables (`frontend/.env`):
- `WORDPRESS_BACKEND_URL` — where WordPress is running (example: `http://172.16.60.17`)
- `VITE_WORDPRESS_API_BASE` — usually `/wp-json`

---

## API Endpoints

### Public
- `GET /wp-json/hotel-booking/v1/homepage`
- `POST /wp-json/hotel-booking/v1/booking`

### Auth (token-based)
- `POST /wp-json/hotel-booking/v1/auth/register`
- `POST /wp-json/hotel-booking/v1/auth/login`
- `GET /wp-json/hotel-booking/v1/auth/me`

### Customer bookings
- `GET /wp-json/hotel-booking/v1/my-bookings`
- `POST /wp-json/hotel-booking/v1/bookings/{id}/cancel`
- `POST /wp-json/hotel-booking/v1/bookings/{id}/change-dates`

---

## LAN Demo Notes

If you want to demo on the same network:

- WordPress: `http://YOUR_SERVER_IP/`
- Frontend dev server: `http://YOUR_SERVER_IP:5173/`

Run Vite with host:
```bash
npm run dev -- --host 0.0.0.0 --port 5173
```

Make sure firewall allows the port (Ubuntu example):
```bash
sudo ufw allow 5173/tcp
```

---

## Security Notes (Important)

- Do **not** commit `wp-config.php` (contains secrets).
- Use **HTTPS** in production.
- For production-grade auth, consider a standard approach (JWT plugin/OAuth) and stronger rate limiting.

---

## LinkedIn post (short summary)

Built a client-ready hotel booking demo using **WordPress (headless CMS) + React (Vite)**.

- WordPress manages rooms + bookings
- React delivers a modern UI
- Booking workflow with status (New/Confirmed/Cancelled)
- Email notifications via SMTP
- Customer login + “My Bookings” + cancel/date-change requests

