# Headless WordPress React App

This app treats WordPress as a CMS and uses React as the frontend.

## Folder structure

```text
headless-react-app/
├── index.html
├── package.json
├── vite.config.js
├── .env.example
└── src/
    ├── api/
    ├── components/
    ├── pages/
    ├── styles/
    ├── utils/
    ├── App.jsx
    └── main.jsx
```

## What WordPress provides

- `/wp-json/wp/v2/rooms` for room content
- `/wp-json/hotel-booking/v1/homepage` for homepage sections
- `/wp-json/hotel-booking/v1/booking` for booking submissions

The child theme in this project has already been updated to expose those endpoints.

## Local setup

1. Copy `.env.example` to `.env`.
2. Set `WORDPRESS_BACKEND_URL` to your WordPress site URL.
3. Set `VITE_WORDPRESS_API_BASE` to your WordPress REST base URL.
4. Run `npm install`.
5. Run `npm run dev`.

## Production build

```bash
npm run build
```

You can deploy the generated `dist/` folder to any static hosting provider.
