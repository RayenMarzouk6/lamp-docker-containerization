# Étape 2 — 3-Tier Architecture (Apache + PHP-FPM + MySQL)

> LAMP stack in 3 containers: Apache and PHP-FPM are separated and communicate via FastCGI.

---

## Architecture

```
Browser
   │
   ▼
lamp_apache (httpd:2.4) :8000
   │  detects .php → forwards via FastCGI (mod_proxy_fcgi)
   ▼
lamp_phpfpm (php:8.3-fpm) :9000
   │  executes PHP → queries database
   ▼
lamp_db (mysql:8.0) :3306
```

## Structure

```
etape2-3tiers/
├── docker-compose.yml      # 3 services + healthcheck chain
├── apache/
│   ├── Dockerfile          # httpd:2.4 + custom config
│   └── httpd.conf          # FastCGI proxy setup
├── phpfpm/
│   └── Dockerfile          # php:8.3-fpm + pdo_mysql
└── www/
    └── index.php           # PHP todo app
```

## Run

```bash
docker compose up -d --build
# → http://localhost:8000/index.php
```

## Key Points

- `httpd:2.4` is pure Apache — no PHP. Requests are proxied via `SetHandler "proxy:fcgi://phpfpm:9000"`
- Both `apache` and `phpfpm` mount `./www` — Apache needs the file path, PHP-FPM needs to execute it
- Double healthcheck chain ensures correct startup order

## Startup Order

```
db → healthcheck OK → phpfpm → healthcheck OK → apache ✅
```