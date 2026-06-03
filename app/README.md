# NAICS Search API

## Prerequisites

- Docker Desktop
- Postman
- Ports available: `8000`, `3306`, `6379`, `8080`

## Run with Docker

From the project root (`naicsSearch`):

```bash
docker compose up -d --build
```

Install PHP dependencies inside the app container:

```bash
docker compose exec app composer install
```

If `.env` does not exist yet, create it from example:

```bash
copy app\.env.example app\.env
```

Generate Laravel app key:

```bash
docker compose exec app php artisan key:generate
```

Run migrations:

```bash
docker compose exec app php artisan migrate
```

## Optional: Import NAICS dataset

This project has a custom command `naics:import`.

Before running it, make sure these files exist:

- `app/storage/app/data/all-naics.json`
- `app/storage/app/data/naics_index_data.json`

Then run:

```bash
docker compose exec app php artisan naics:import
```

## Access URLs

- API base: `http://localhost:8000`
- phpMyAdmin: `http://localhost:8080`
- MySQL host/port from machine: `127.0.0.1:3306`

## Test in Postman

API route:

- `POST /api/search`

### Request

- Method: `POST`
- URL: `http://localhost:8000/api/search`
- Headers:
  - `Content-Type: application/json`
  - `Accept: application/json`
- Body (raw JSON):

```json
{
  "search": "restaurant"
}
```

`search` must be a string with minimum 2 characters.

### Expected response shape

```json
{
  "NAICS": 5,
  "names": [
    "722511 : Full-Service Restaurants - ...",
    "..."
  ],
  "search": "restaurant",
  "image_path": null
}
```

## Stop project

```bash
docker compose down
```

To also remove DB volume data:

```bash
docker compose down -v
``` 
