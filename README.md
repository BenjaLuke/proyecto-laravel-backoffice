# Proyecto Laravel Backoffice

Backoffice en Laravel para gestionar catalogo, categorias, pedidos, stock,
usuarios, actividad y API con Sanctum.

Este README vive en la raiz del repositorio para que GitHub lo muestre como
documentacion principal. La guia tecnica de arranque tambien esta en
[`src/README.md`](src/README.md).

## Estado del proyecto

El proyecto esta en fase de mejora incremental. La idea es ir corrigiendo
fallos pequenos, documentando decisiones y subiendo cada bloque en commits
separados para que el historial sea facil de seguir.

## Arranque rapido

Desde la raiz del repositorio:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Aplicacion:

```text
http://localhost:8080
```

## Tests

Todos los comandos Laravel se ejecutan dentro del contenedor `app`:

```bash
docker compose exec app php artisan test
```

Ejemplo para un test concreto:

```bash
docker compose exec app php artisan test --filter=CategoryHierarchyTest
```

## Historial de cambios documentados

### Pendiente - ajusta calendario oscuro y enlace mensual

- Corregido el color de texto de las tarjetas de pedido del calendario para que sea legible en modo oscuro.
- Enlazada la metrica de pedidos del mes con el calendario filtrado al mes actual.

### e967c35 - enlaza metricas del dashboard con listados filtrados

- Convertidas en enlaces las metricas de pedidos de hoy, productos sin imagenes, productos sin tarifa activa y tarifas que caducan en 7 dias.
- Aniadido filtro de productos para tarifas que caducan en los proximos 7 dias.
- Diferenciadas visualmente las tarjetas clicables del dashboard.
- Actualizado el test de raiz para comprobar la redireccion real al dashboard.
- Aniadida cobertura del filtro de tarifas proximas a caducar.

### 7a74367 - arregla colores del dashboard

- Ajustados los indicadores de control interno para que usen color verde cuando no hay incidencias.
- Cambiados los avisos de productos sin imagenes o sin tarifa activa a color de advertencia solo cuando requieren revision.
- Alineados los iconos de esas metricas con el estado real de cada contador.

### 56a2497 - seed y desavenencias VSC/laravel

- Hacemos un seed forzado para que todos los productos tengan su stock y stock mínimo
- Arreglamos desavenencias en VSC que laravel entendía pero que VSC me gritaba.

### Pendiente - corrige tabla de productos y renueva imagenes locales

- Corregida la tabla de productos para que las columnas coincidan con la cabecera.
- Sustituidas imagenes ficticias locales por fotos reales para las rutas registradas en `product_images`.
- Normalizadas rutas de imagen de producto a `.jpg` en la base local.
- Aniadido `product-image-sources.json` con el origen usado para cada imagen local.
- Actualizados datos locales de stock: `min_stock = 0` pasa a 5-25 y `current_stock = 0` pasa a 100-1000.

### d25b505 - protege jerarquia de categorias

- Corregidos imports de rutas con namespace `Backoffice`.
- Bloqueado que una categoria pueda elegir como padre a una hija o nieta.
- Aniadido helper `Category::descendantIds()`.
- Aniadidos tests para backoffice y API.

### a244d9e - corrige fecha de servicio en pedidos no servidos

- Corregido `served_at` para que solo tenga valor en pedidos `servido`.
- Aniadida migracion correctiva para bases ya migradas.
- Ajustado factory de pedidos.
- Añadido test para pedidos API pendientes sin fecha de servicio.

### b00e616 - documenta configuracion y flujos sensibles

- Alineado Docker, `.env.example` y README tecnico.
- Comentados bloques sensibles de tokens, permisos, stock, imagenes y logs.

### ec414bd - primera fase de pequenas reparaciones

- Tokens API limitados por permisos reales del usuario.
- API de pedidos alineada con estados y movimientos de stock.
- Recurso de pedidos ampliado con `status` y `served_at`.
- Arreglado log de productos al subir una imagen nueva como principal.
- Añadidos tests de regresión.

### 302d590 - actualiza logo

- Actualizado asset `src/public/images/logo.jpg`.

## Convencion para proximos cambios

Cuando se haga una mejora nueva:

1. Crear un commit pequeño y descriptivo.
2. Añadir o actualizar tests si el cambio toca comportamiento.
3. Añadir una entrada nueva arriba del historial de cambios de este README.
