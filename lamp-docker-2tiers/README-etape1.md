# Étape 1 — 2-Tier Architecture (Apache/PHP + MySQL)

> LAMP stack in 2 containers: one for Apache+PHP, one for MySQL.

---

## Architecture

```
Browser
   │
   ▼
lamp_web (php:8.3-apache) :8000
   │  mod_php — PHP runs inside Apache
   ▼
lamp_db (mysql:8.0) :3306
```

## Structure

```
etape1-2tiers/
├── Dockerfile          # php:8.3-apache + pdo_mysql
├── docker-compose.yml  # web + db services
└── index.php           # PHP todo app
```

## Run

```bash
docker compose up -d --build
# → http://localhost:8000/index.php
```

## Key Points

- `pdo_mysql` is not in `php:8.3-apache` by default — installed via `Dockerfile`
- `depends_on` + `condition: service_healthy` prevents PHP from starting before MySQL is ready
- `mysql_data` named volume keeps data after container restart

## Startup Order

```
db → healthcheck OK → web ✅
```