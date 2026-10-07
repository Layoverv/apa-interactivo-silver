import ProductList from './components/ProductList.jsx';

function App() {
  return (
    <div className="site-shell">
      <header className="site-header">
        <a className="wordmark" href="#inicio" aria-label="Norte, inicio">
          norte<span>.</span>
        </a>
        <nav className="main-nav" aria-label="Navegación principal">
          <a href="#coleccion">Colección</a>
          <a href="#nosotros">Nuestra mirada</a>
        </nav>
        <a className="header-note" href="#coleccion">Objetos seleccionados</a>
      </header>

      <main id="inicio">
        <section className="hero" aria-labelledby="hero-title">
          <div className="hero-copy">
            <p className="eyebrow">DISEÑO PARA LA VIDA DIARIA</p>
            <h1 id="hero-title">Menos, pero mejor.</h1>
            <p className="hero-description">
              Una colección de objetos funcionales, hechos para acompañar tus días con calma y propósito.
            </p>
            <a className="text-link" href="#coleccion">Descubre la colección <span aria-hidden="true">→</span></a>
          </div>
          <div className="hero-visual" role="img" aria-label="Audífonos de diseño minimalista sobre un fondo neutro">
            <img
              src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1400&q=85"
              alt="Audífonos inalámbricos en tonos neutros"
              fetchPriority="high"
            />
            <div className="hero-caption"><span>01 / 06</span><span>Forma y función</span></div>
          </div>
          <div className="hero-index" aria-hidden="true">01</div>
        </section>

        <section className="collection" id="coleccion" aria-labelledby="collection-title">
          <div className="section-heading">
            <div>
              <p className="eyebrow">LA SELECCIÓN</p>
              <h2 id="collection-title">Piezas para cada día</h2>
            </div>
            <p className="section-note">Seis objetos. Una misma atención al detalle.</p>
          </div>
          <ProductList />
        </section>

        <section className="closing-note" id="nosotros">
          <p className="eyebrow">UNA ELECCIÓN CONSCIENTE</p>
          <p>Diseño honesto, materiales cuidados y belleza en lo esencial.</p>
        </section>
      </main>

      <footer className="site-footer">
        <a className="wordmark" href="#inicio">norte<span>.</span></a>
        <p>Objetos para todos los días</p>
        <span>© 2026 Norte</span>
      </footer>
    </div>
  );
}

export default App;
