# CET Dashboard Server Setup Guide (Oracle Linux 8.5)

## 📌 Overview

This document captures the full setup process for deploying the **CET Dashboard (Laravel + Vue)** on an Oracle Linux 8.5 server, including **issues encountered and their solutions**. It is intended as a reusable reference for future server setups.

---

# 🖥️ 1. Base System Preparation

## Update system

```bash
sudo dnf update -y
```

## Install utilities

```bash
sudo dnf install epel-release -y
sudo dnf install git unzip curl wget -y
```

---

# 🐘 2. PHP Installation (PHP 8.3 via REMI)

## Problem

Default Oracle Linux repos do not provide modern PHP versions.

## Solution

Use REMI repository:

```bash
sudo dnf install https://rpms.remirepo.net/enterprise/remi-release-8.rpm -y
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.3 -y

sudo dnf install php php-cli php-fpm php-mysqlnd php-xml php-mbstring php-json php-opcache php-gd php-curl php-zip -y
```

## Start PHP-FPM

```bash
sudo systemctl enable php-fpm
sudo systemctl start php-fpm
```

---

# 🌐 3. Nginx Installation

```bash
sudo dnf install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

# 🟢 4. Node.js Installation (Node 24)

## Observation

Oracle Linux AppStream includes Node 24.

## Install

```bash
sudo dnf module reset nodejs -y
sudo dnf module enable nodejs:24 -y
sudo dnf install nodejs -y
```

---

# 🗄️ 5. Oracle Instant Client (Remote DB Setup)

## Context

Database is hosted on a separate server → Instant Client required.

## Problem

Generic package names not found.

## Solution

Use version-specific packages:

```bash
sudo dnf install oracle-release-el8 -y
sudo dnf config-manager --enable ol8_oracle_instantclient

sudo dnf install oracle-instantclient19.30-basic oracle-instantclient19.30-devel -y
```

---

# ⚙️ 6. Install OCI8 Extension

## Problem 1

```bash
pecl: command not found
```

## Solution

```bash
sudo dnf install php-pear php-devel gcc make -y
```

---

## Problem 2 (Build Error)

```text
fatal error: oci8_dtrace_gen.h: No such file or directory
```

## Cause

OCI8 + PHP 8.3 DTrace incompatibility

## Solution

Disable DTrace:

```bash
export PHP_DTRACE=no
sudo pecl install oci8
```

When prompted:

```text
instantclient,/usr/lib/oracle/19.30/client64/lib
```

---

## Enable OCI8

```bash
echo "extension=oci8.so" | sudo tee /etc/php.d/oci8.ini
sudo systemctl restart php-fpm
```

## Verify

```bash
php -m | grep oci8
```

---

# 📦 7. Laravel Setup

## Install dependencies

```bash
composer install --optimize-autoloader --no-dev
```

## Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

## Oracle DB config

```env
DB_CONNECTION=oracle
DB_HOST=<db-host>
DB_PORT=1521
DB_SERVICE_NAME=<service_name>
DB_USERNAME=<username>
DB_PASSWORD=<password>
```

## Install Oracle driver

```bash
composer require yajra/laravel-oci8
```

---

# ⚡ 8. Vue Build

```bash
npm install
npm run build
```

---

# 🌍 9. Nginx Configuration

```nginx
server {
    listen 80;
    server_name cetdashboardtest.zesco.co.zm;

    root /var/www/cet-dashboard/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Restart:

```bash
sudo systemctl restart nginx
```

---

# 🔐 10. SSL Setup

## Problem (Certbot Failure)

```text
NXDOMAIN looking up A record
```

## Cause

DNS not pointing to server.

---

## Solution Options

### Option A: Public Server (Let’s Encrypt)

1. Create DNS A record:

   * `cetdashboardtest.zesco.co.zm → server IP`

2. Run:

```bash
sudo certbot --nginx -d cetdashboardtest.zesco.co.zm
```

---

### Option B: Internal Server (Recommended)

Use DigiCert or internal CA.

## Required files:

* `.crt`
* `.key`
* CA bundle

## Nginx SSL config

```nginx
server {
    listen 443 ssl;
    server_name cetdashboardtest.zesco.co.zm;

    ssl_certificate /etc/nginx/ssl/server.crt;
    ssl_certificate_key /etc/nginx/ssl/server.key;
    ssl_trusted_certificate /etc/nginx/ssl/ca_bundle.crt;
}
```

---

# 🔑 11. DigiCert Usage Notes

## Key Points

* Certificates can be reused across servers ✅
* Private key is required ❗
* `.pfx` file is preferred

## Convert `.pfx`

```bash
openssl pkcs12 -in certificate.pfx -clcerts -nokeys -out server.crt
openssl pkcs12 -in certificate.pfx -nocerts -nodes -out server.key
openssl pkcs12 -in certificate.pfx -cacerts -nokeys -out ca_bundle.crt
```

---

# 🚨 Common Issues & Fixes

| Issue                      | Cause               | Solution               |
| -------------------------- | ------------------- | ---------------------- |
| PHP 8.1 module missing     | Not in default repo | Use REMI               |
| Node 24 not installing     | Wrong method        | Use DNF module         |
| OCI8 build fails           | DTrace issue        | Disable DTrace         |
| `pecl` not found           | Missing packages    | Install php-pear       |
| Instant client not found   | Wrong package name  | Use versioned packages |
| Certbot NXDOMAIN           | DNS not configured  | Add A record           |
| SSL not working internally | No public access    | Use internal cert      |

---

# ✅ Final Stack

* Oracle Linux 8.5
* Nginx
* PHP 8.3
* Node 24
* Laravel + Vue
* Oracle Instant Client 19.30
* OCI8 extension
* Remote Oracle DB

---

# 🚀 Notes for Future Setups

* Always confirm:

  * Laravel version → PHP compatibility
  * Node version → Vue compatibility
  * DB connectivity before Laravel config
* Prefer:

  * Modular configs (`/etc/php.d`)
  * DNF-based installs for maintainability
* Coordinate early with:

  * Infrastructure team (SSL, DNS, DB access)

---

# 📌 End of Document
