function ProductCard({ nombre, precio, categoria, imagen, descripcion }) {
  return (
    <article className="product-card">
      <div className="product-image-wrap">
        <img className="product-image" src={imagen} alt={nombre} loading="lazy" />
        <span className="product-category">{categoria}</span>
      </div>
      <div className="product-information">
        <div className="product-title-row">
          <h3>{nombre}</h3>
          <p className="product-price">{precio}</p>
        </div>
        <p className="product-description">{descripcion}</p>
        <button className="buy-button" type="button">Comprar <span aria-hidden="true">↗</span></button>
      </div>
    </article>
  );
}

export default ProductCard;
