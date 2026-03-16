export default function Logo({ compact = false }) {
  return (
    <div className={compact ? 'logo-lockup logo-lockup--compact' : 'logo-lockup'}>
      <svg
        aria-hidden="true"
        className="logo-emblem"
        viewBox="0 0 120 120"
      >
        <circle className="logo-emblem__ring" cx="60" cy="60" r="41" />
        <path
          className="logo-emblem__arc"
          d="M28 28a48 48 0 1 0 58 67"
        />
        <path
          className="logo-emblem__lotus"
          d="M60 77c-5-13-3-24 0-33 3 9 5 20 0 33Zm-18-2c1-11 7-20 16-26-1 9-4 18-10 26h-6Zm42 0h-6c-6-8-9-17-10-26 9 6 15 15 16 26Zm-30-5c-7-2-14-7-19-14 10-1 19 3 25 10-2 2-4 3-6 4Zm42 0c-2-1-4-2-6-4 6-7 15-11 25-10-5 7-12 12-19 14ZM60 44c-5 6-8 13-8 21 0 4 1 8 2 12-7-8-11-18-11-29 6 1 12 4 17 10Zm0 0c5 6 8 13 8 21 0 4-1 8-2 12 7-8 11-18 11-29-6 1-12 4-17 10Z"
        />
        <path
          className="logo-emblem__spark"
          d="M60 10l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6Z"
        />
      </svg>

      <div className="logo-copy">
        <span className="logo-wordmark">Mukta</span>
        <span className="logo-tagline">Boutique Hotel &amp; Spa</span>
      </div>
    </div>
  );
}
