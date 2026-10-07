import ProductCard from './ProductCard.jsx';

const products = [
  {
    nombre: 'Auriculares Studio',
    precio: '$ 489.000',
    categoria: 'Audio',
    imagen: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1000&q=85',
    descripcion: 'Sonido envolvente y comodidad para acompañarte durante horas.',
  },
  {
    nombre: 'Reloj Meridian',
    precio: '$ 359.000',
    categoria: 'Accesorios',
    imagen: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=1000&q=85',
    descripcion: 'Una silueta atemporal con lectura clara y materiales duraderos.',
  },
  {
    nombre: 'Cámara Compacta',
    precio: '$ 1.290.000',
    categoria: 'Tecnología',
    imagen: 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=1000&q=85',
    descripcion: 'Captura momentos con controles intuitivos y un cuerpo ligero.',
  },
  {
    nombre: 'Tenis Urbano',
    precio: '$ 329.000',
    categoria: 'Calzado',
    imagen: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1000&q=85',
    descripcion: 'Líneas limpias y una suela cómoda para moverte a tu ritmo.',
  },
  {
    nombre: 'Altavoz portátil',
    precio: '$ 279.000',
    categoria: 'Audio',
    imagen: 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=1000&q=85',
    descripcion: 'Un sonido cálido en un formato compacto para cualquier espacio.',
  },
  {
    nombre: 'Lentes de sol Forma',
    precio: '$ 219.000',
    categoria: 'Accesorios',
    imagen: 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=1000&q=85',
    descripcion: 'Protección diaria y un perfil sobrio que combina con todo.',
  },
];

function ProductList() {
  return (
    <div className="product-grid">
      {products.map((product) => (
        <ProductCard
          key={product.nombre}
          nombre={product.nombre}
          precio={product.precio}
          categoria={product.categoria}
          imagen={product.imagen}
          descripcion={product.descripcion}
        />
      ))}
    </div>
  );
}

export default ProductList;
