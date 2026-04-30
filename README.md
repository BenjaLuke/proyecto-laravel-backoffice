# Proyecto Laravel Backoffice

Backoffice en Laravel para gestionar catálogo, categorías, pedidos, stock,
usuarios, actividad y API con Sanctum.

Este README vive en la raíz del repositorio para que GitHub lo muestre como
documentación principal. La guía técnica de arranque también está en
[`src/README.md`](src/README.md).

## Estado del proyecto

El proyecto está en fase de mejora incremental. La idea es ir corrigiendo
fallos pequeños, documentando decisiones y subiendo cada bloque en commits
separados para que el historial sea fácil de seguir.

## Arranque rápido

Desde la raíz del repositorio:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Aplicación:

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

### 7e2c31b - comenta vistas y limpia plantilla de email

- Añadidos comentarios de orientación en las vistas de backoffice para identificar cabeceras, filtros, formularios, tablas, tarjetas y estilos.
- Comentada la plantilla de email de nuevo pedido.
- Corregido el acceso a estado del pedido en el email (`$purchaseOrder->status`).
- Normalizados textos visibles del email con acentos y símbolo de euro.
- Corregidos acentos y eñes del README.

### abafe19 - resalta el día actual en calendario

- Añadido resaltado verde transparente por detrás de la tarjeta del día actual.
- Ajustado el fondo de las tarjetas de día en modo oscuro para mejorar contraste.

### b369b19 - ajusta calendario oscuro y enlace mensual

- Corregido el color de texto de las tarjetas de pedido del calendario para que sea legible en modo oscuro.
- Enlazada la métrica de pedidos del mes con el calendario filtrado al mes actual.

### e967c35 - enlaza métricas del dashboard con listados filtrados

- Convertidas en enlaces las métricas de pedidos de hoy, productos sin imágenes, productos sin tarifa activa y tarifas que caducan en 7 días.
- Añadido filtro de productos para tarifas que caducan en los próximos 7 días.
- Diferenciadas visualmente las tarjetas clicables del dashboard.
- Actualizado el test de raíz para comprobar la redirección real al dashboard.
- Añadida cobertura del filtro de tarifas próximas a caducar.

### 7a74367 - arregla colores del dashboard

- Ajustados los indicadores de control interno para que usen color verde cuando no hay incidencias.
- Cambiados los avisos de productos sin imágenes o sin tarifa activa a color de advertencia solo cuando requieren revisión.
- Alineados los iconos de esas métricas con el estado real de cada contador.

### 56a2497 - seed y desavenencias VSC/laravel

- Hacemos un seed forzado para que todos los productos tengan su stock y stock mínimo.
- Arreglamos desavenencias en VSC que Laravel entendía pero que VSC me gritaba.

### 96562d0 - corrige tabla de productos y renueva imágenes locales

- Corregida la tabla de productos para que las columnas coincidan con la cabecera.
- Sustituidas imágenes ficticias locales por fotos reales para las rutas registradas en `product_images`.
- Normalizadas rutas de imagen de producto a `.jpg` en la base local.
- Añadido `product-image-sources.json` con el origen usado para cada imagen local.
- Actualizados datos locales de stock: `min_stock = 0` pasa a 5-25 y `current_stock = 0` pasa a 100-1000.

### d25b505 - protege jerarquía de categorías

- Corregidos imports de rutas con namespace `Backoffice`.
- Bloqueado que una categoría pueda elegir como padre a una hija o nieta.
- Añadido helper `Category::descendantIds()`.
- Añadidos tests para backoffice y API.

### a244d9e - corrige fecha de servicio en pedidos no servidos

- Corregido `served_at` para que solo tenga valor en pedidos `servido`.
- Añadida migración correctiva para bases ya migradas.
- Ajustado factory de pedidos.
- Añadido test para pedidos API pendientes sin fecha de servicio.

### b00e616 - documenta configuración y flujos sensibles

- Alineado Docker, `.env.example` y README técnico.
- Comentados bloques sensibles de tokens, permisos, stock, imágenes y logs.

### ec414bd - primera fase de pequeñas reparaciones

- Tokens API limitados por permisos reales del usuario.
- API de pedidos alineada con estados y movimientos de stock.
- Recurso de pedidos ampliado con `status` y `served_at`.
- Arreglado log de productos al subir una imagen nueva como principal.
- Añadidos tests de regresión.

### 302d590 - actualiza logo

- Actualizado asset `src/public/images/logo.jpg`.

## Convención para próximos cambios

Cuando se haga una mejora nueva:

1. Crear un commit pequeño y descriptivo.
2. Añadir o actualizar tests si el cambio toca comportamiento.
3. Añadir una entrada nueva arriba del historial de cambios de este README.
