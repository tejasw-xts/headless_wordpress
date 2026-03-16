import { Link } from 'react-router-dom';

export default function NotFoundPage() {
  return (
    <section className="content-section">
      <div className="container status-card">
        <h1>Page not found</h1>
        <p>The route does not exist in the React app.</p>
        <Link className="button button--primary" to="/">
          Return home
        </Link>
      </div>
    </section>
  );
}
