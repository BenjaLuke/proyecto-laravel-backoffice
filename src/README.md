# Laravel Backoffice

Backoffice Laravel para gestionar catalogo, categorias, pedidos, stock, usuarios, actividad y API con Sanctum.

## Requisitos

- Docker Desktop
- Docker Compose

## Arranque local

Desde la raiz del repositorio:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

La aplicacion queda disponible en:

```text
http://localhost:8080
```

## Credenciales locales

El seeder crea usuarios de desarrollo:

```text
admin / admin1234
testuser / password
```

No uses estas credenciales en produccion.

## Comandos utiles

Ejecutar tests:

```bash
docker compose exec app php artisan test
```

Ejecutar un test concreto:

```bash
docker compose exec app php artisan test --filter=ApiAuthTokenTest
```

Limpiar cache de Laravel:

```bash
docker compose exec app php artisan optimize:clear
```

Abrir Tinker:

```bash
docker compose exec app php artisan tinker
```

## Base de datos

El entorno Docker usa MySQL:

```text
host: db
database: laravel
user: laravel
password: secret
```

Desde el host, MySQL queda expuesto en el puerto `3307`.
