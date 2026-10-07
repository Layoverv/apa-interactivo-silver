# Pregunta 3 — Componentes en React

Tienda académica construida con React y Vite. La página muestra seis productos con una tarjeta reutilizable y CSS Grid de dos columnas en escritorio; en pantallas pequeñas cambia a una columna.

## Ejecutar

Abre esta carpeta en CodeSandbox como proyecto React/Vite. En un entorno con Node.js y npm también puedes ejecutar:

```bash
npm install
npm run dev
```

## Componentes

- `src/components/ProductCard.jsx`: presenta nombre, precio, categoría, imagen y descripción mediante cinco props.
- `src/components/ProductList.jsx`: define los seis productos y recorre el array con `.map()` para crear cada tarjeta.
- `src/App.jsx`: compone la cabecera, introducción, colección y pie de página.
- `src/App.css`: define la cuadrícula, las tarjetas, la paleta y los ajustes responsive.

El botón Comprar es visual; no se implementan carrito, pagos ni servicios externos. Las fotografías se cargan desde Unsplash.
