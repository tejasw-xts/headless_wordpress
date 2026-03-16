export default function SectionCards({ title, description = '', cards = [], sectionId = '' }) {
  if (!cards.length) {
    return null;
  }

  return (
    <section className="content-section" id={sectionId || undefined}>
      <div className="container">
        <div className="section-heading">
          <h2>{title}</h2>
          {description ? <p>{description}</p> : null}
        </div>

        <div className="info-grid">
          {cards.map((card) => (
            <article className="info-card" key={`${title}-${card.title}`}>
              {card.icon ? <span className="info-card__icon">{card.icon}</span> : null}
              <h3>{card.title}</h3>
              <p>{card.copy}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
