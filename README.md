# 🐳 LAMP Stack Containerization with Docker Compose

> Containerizing a LAMP application in two architectures — from a simple 2-tier setup to a production-closer 3-tier separation — using Docker Compose.

![Docker](https://img.shields.io/badge/Docker-29.2.1-2496ED?style=flat&logo=docker&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=flat&logo=apache&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)

---

## 📋 Table of Contents

- [Overview](#overview)
- [Repository Structure](#repository-structure)
- [Étape 1 — 2-Tier Architecture](#étape-1----2-tier-architecture)
- [Étape 2 — 3-Tier Architecture](#étape-2----3-tier-architecture)
- [Key Concepts](#key-concepts)
- [Quick Start](#quick-start)

---

## Overview

This project is a DevOps lab that progressively containerizes a classic **LAMP** stack (Linux, Apache, PHP, MySQL) using Docker Compose.

| | Étape 1 | Étape 2 |
|--|---------|---------|
| **Architecture** | 2-Tier | 3-Tier |
| **Containers** | 2 (web + db) | 3 (apache + phpfpm + db) |
| **PHP runtime** | `mod_php` inside Apache | Separate PHP-FPM container |
| **Protocol** | Apache handles PHP directly | Apache → FastCGI → PHP-FPM |
| **Use case** | Simple / development | Scalable / production-closer |

---

## Repository Structure

```
lamp-docker-containerization/
│
├── README.md
│
├── etape1-2tiers/                  # 2-Tier: Apache/PHP + MySQL
│   ├── Dockerfile                  # Custom php:8.3-apache image
│   ├── docker-compose.yml          # Web + DB services
│   └── index.php                   # PHP app (todo list)
│
└── etape2-3tiers/                  # 3-Tier: Apache + PHP-FPM + MySQL
    ├── docker-compose.yml          # 3 services with healthcheck chain
    ├── apache/
    │   ├── Dockerfile              # httpd:2.4 + custom config
    │   └── httpd.conf              # FastCGI proxy configuration
    ├── phpfpm/
    │   └── Dockerfile              # php:8.3-fpm + pdo_mysql
    └── www/
        └── index.php               # PHP app (todo list + MySQL)
```

---

## Étape 1 — 2-Tier Architecture

### Architecture Diagram

```
┌─────────────────────────────────────────────────────┐
│                    Docker Host                       │
│                                                      │
│   ┌──────────────────────┐   ┌───────────────────┐  │
│   │      lamp_web        │   │      lamp_db       │  │
│   │                      │   │                    │  │
│   │  php:8.3-apache      │──▶│   mysql:8.0        │  │
│   │  (mod_php built-in)  │   │                    │  │
│   │                      │   │  Port: 3306        │  │
│   │  Port: 8000:80       │   │  Volume: mysql_data│  │
│   └──────────────────────┘   └───────────────────┘  │
│              ▲                                       │
│              │                                       │
│         Port 8000                                    │
└─────────────────────────────────────────────────────┘
         │
    Browser / User
```

### Request Flow

```
User request
    │
    ▼
localhost:8000
    │
    ▼
lamp_web (Apache + mod_php)
    │  PHP executes inside Apache
    ▼
lamp_db (MySQL 8.0)
    │
    ▼
Response → User
```

### How to Run

```bash
cd etape1-2tiers/
docker compose up -d --build
```

```bash
# Verify
docker compose ps

# Open in browser
http://localhost:8000/index.php
```

### File Overview

**`Dockerfile`** — adds `pdo_mysql` to the base PHP+Apache image:
```dockerfile
FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite
```

**`docker-compose.yml`** — two services with healthcheck:
```yaml
services:
  db:
    image: mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", ...]

  web:
    build: .
    ports:
      - "8000:80"
    depends_on:
      db:
        condition: service_healthy   # ← waits for MySQL to be ready
```

### Startup Order

```
db (MySQL)
    │
    └─── healthcheck OK ───▶ web (Apache + PHP)
                                    │
                                    └─── Ready ✅
```

---

## Étape 2 — 3-Tier Architecture

### Architecture Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                          Docker Host                              │
│                                                                   │
│  ┌─────────────────┐   FastCGI   ┌──────────────────┐           │
│  │   lamp_apache   │────────────▶│   lamp_phpfpm    │           │
│  │                 │  port 9000  │                  │           │
│  │  httpd:2.4      │             │  php:8.3-fpm     │           │
│  │  mod_proxy_fcgi │             │  pdo_mysql       │           │
│  │                 │             │                  │           │
│  │  Port: 8000:80  │             │  Port: 9000      │           │
│  └─────────────────┘             └──────────────────┘           │
│          ▲                               │                        │
│          │                    MySQL      │                        │
│     Port 8000                 query      ▼                        │
│                              ┌───────────────────┐               │
│                              │     lamp_db        │               │
│                              │                   │               │
│                              │   mysql:8.0       │               │
│                              │   Port: 3306      │               │
│                              │   Volume: persisted│              │
│                              └───────────────────┘               │
└──────────────────────────────────────────────────────────────────┘
         │
    Browser / User
```

### Request Flow

```
User request
    │
    ▼
localhost:8000
    │
    ▼
lamp_apache (httpd:2.4)
    │  detects .php file
    │  forwards via FastCGI (mod_proxy_fcgi)
    ▼
lamp_phpfpm (php:8.3-fpm) :9000
    │  executes PHP code
    │  queries database
    ▼
lamp_db (mysql:8.0) :3306
    │
    ▼
Response bubbles back → lamp_phpfpm → lamp_apache → User
```

### Shared Volume — Why Both Apache and PHP-FPM Need `./www`

```
./www/index.php
    │
    ├──▶ lamp_apache   needs it to detect the .php extension
    │                  and know to forward the request
    │
    └──▶ lamp_phpfpm   needs it to actually read and execute
                       the PHP code
```

> Both containers mount `./www:/var/www/html`.  
> If either is missing the volume, the stack breaks.

### How to Run

```bash
cd etape2-3tiers/
docker compose up -d --build
```

```bash
# Verify all 3 containers
docker compose ps

# Watch startup order in real time
docker compose logs -f

# Open in browser
http://localhost:8000/index.php
```

### Startup Chain

```
db (MySQL)
    │
    └─── healthcheck OK (mysqladmin ping)
              │
              ▼
         phpfpm (PHP-FPM)
              │
              └─── healthcheck OK (php-fpm -t)
                        │
                        ▼
                   apache (httpd:2.4)
                        │
                        └─── Ready ✅
```

### File Overview

**`phpfpm/Dockerfile`** — PHP-FPM with MySQL support:
```dockerfile
FROM php:8.2-fpm
RUN docker-php-ext-install pdo pdo_mysql mysqli
```

**`apache/Dockerfile`** — Apache with custom config:
```dockerfile
FROM httpd:2.4
COPY httpd.conf /usr/local/apache2/conf/httpd.conf
```

**`apache/httpd.conf`** — the critical line that connects Apache to PHP-FPM:
```apacheconf
<FilesMatch "\.php$">
    SetHandler "proxy:fcgi://phpfpm:9000"
</FilesMatch>
```

**`docker-compose.yml`** — 3 services, 2-level healthcheck chain:
```yaml
services:
  db:       # starts first — has healthcheck
  phpfpm:   # starts after db is healthy — has healthcheck
  apache:   # starts after phpfpm is healthy
```

---

## Key Concepts

### `depends_on` vs `depends_on` + `condition: service_healthy`

```
# ❌ Only waits for container to START (not ready)
depends_on:
  - db

# ✅ Waits for container to pass its healthcheck
depends_on:
  db:
    condition: service_healthy
```

### FastCGI Protocol (Étape 2)

```
Without FastCGI (Étape 1):
  Apache reads .php → executes it internally (mod_php) → sends response

With FastCGI (Étape 2):
  Apache reads .php → sends request to PHP-FPM via TCP (port 9000)
                    → PHP-FPM executes it → returns result to Apache
                    → Apache sends response to user
```

### Docker Internal DNS

```
# Containers communicate by SERVICE NAME, not IP address
# Docker Compose creates an internal DNS on the bridge network

apache  →  can reach  →  phpfpm  (by name, port 9000)
phpfpm  →  can reach  →  db      (by name, port 3306)

# This is why index.php uses:
$host = "db";  // not an IP — the Docker service name
```

### Named Volume — Data Persistence

```
mysql_data:/var/lib/mysql

# Without this volume:
  → MySQL data is lost every time the container restarts

# With this volume:
  → Data survives container stop/start/rebuild
```

---

## Quick Start

### Clone the repo

```bash
git clone https://github.com/<your-username>/lamp-docker-containerization.git
cd lamp-docker-containerization
```

### Run Étape 1 (2-Tier)

```bash
cd etape1-2tiers
docker compose up -d --build
# → open http://localhost:8000/index.php
```

### Run Étape 2 (3-Tier)

```bash
cd etape2-3tiers
docker compose up -d --build
# → open http://localhost:8000/index.php
```

### Stop everything

```bash
docker compose down          # stop and remove containers
docker compose down -v       # also remove volumes (deletes DB data)
```

---

## Author

**Rayen Marzouk** — DevOps Lab · April 2026

---

> 📌 This project is part of a Docker containerization lab series.  
> Part 1 and Part 2 are documented as Medium articles — links in the repo description.
