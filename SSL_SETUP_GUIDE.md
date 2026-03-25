# SSL Setup Guide for Nginx (Using DigiCert Wildcard Certificate)

This guide documents the process of setting up SSL on an nginx server using a DigiCert wildcard certificate (`*.zesco.co.zm`) copied from an existing server.

---

## Prerequisites

- Root/sudo access on both the source server (with existing cert) and the target server (nginx)
- The target server must have nginx installed and running
- The domain must have a DNS A record pointing to the server's IP

---

## Step 1: Verify DNS A Record

Confirm that a DNS A record exists for your domain:

```bash
dig yourdomain.zesco.co.zm A +short
```

This should return an IP address. If it returns nothing, contact your DNS administrator to create an A record.

---

## Step 2: Locate SSL Certificate Files on an Existing Server

### For an Nginx source server:

```bash
grep -r "ssl_certificate" /etc/nginx/ 2>/dev/null
```

### For an Apache source server:

```bash
grep -r "SSLCertificateFile\|SSLCertificateKeyFile\|SSLCertificateChainFile" /etc/httpd/ 2>/dev/null
```

You need to identify 3 files:
- **Certificate file** (e.g., `star_zesco_co_zm.crt`)
- **Private key file** (e.g., `myserver.key`)
- **CA chain file** (e.g., `DigiCertCA.crt`)

---

## Step 3: Verify the Certificate is Still Valid

On the source server, check the certificate's expiry date:

```bash
openssl x509 -in /etc/ssl/star_zesco_co_zm.crt -noout -issuer -subject -dates
```

Confirm `notAfter` is in the future. If expired, obtain a renewed certificate before proceeding.

---

## Step 4: Copy Certificate Files to the Target Server

From the **target server** (your nginx server), pull the files:

```bash
sudo mkdir -p /etc/ssl
scp root@<source-server-ip>:/etc/ssl/star_zesco_co_zm.crt /etc/ssl/
scp root@<source-server-ip>:/etc/ssl/myserver.key /etc/ssl/
scp root@<source-server-ip>:/etc/ssl/DigiCertCA.crt /etc/ssl/
```

Or from the **source server**, push them:

```bash
scp /etc/ssl/star_zesco_co_zm.crt /etc/ssl/myserver.key /etc/ssl/DigiCertCA.crt root@<target-server-ip>:/etc/ssl/
```

> **Note:** Copying files does not affect the source server's SSL in any way.

---

## Step 5: Create Chained Certificate and Secure Key

Nginx requires the server certificate and CA certificate combined into a single chained file:

```bash
sudo cat /etc/ssl/star_zesco_co_zm.crt /etc/ssl/DigiCertCA.crt > /etc/ssl/star_zesco_co_zm_chained.crt
```

Secure the private key permissions:

```bash
sudo chmod 600 /etc/ssl/myserver.key
```

---

## Step 6: Configure Nginx

Create or replace the nginx site config (e.g., `/etc/nginx/conf.d/cet-dashboard.conf`):

```nginx
server {
    listen 80;
    server_name yourdomain.zesco.co.zm;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.zesco.co.zm;

    ssl_certificate /etc/ssl/star_zesco_co_zm_chained.crt;
    ssl_certificate_key /etc/ssl/myserver.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    root /var/www/your-project/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Replace `yourdomain.zesco.co.zm` and `/var/www/your-project/public` with your actual values.

---

## Step 7: Test and Restart Nginx

Test the configuration for syntax errors:

```bash
sudo nginx -t
```

If successful, restart nginx:

```bash
sudo systemctl restart nginx
```

---

## Step 8: Ensure Firewall Allows HTTPS

```bash
sudo firewall-cmd --list-services
```

If `http` and `https` are not listed:

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

## Step 9: Update Laravel .env

Update the `APP_URL` in your Laravel `.env` file:

```
APP_URL=https://yourdomain.zesco.co.zm
```

---

## Certificate Renewal

The wildcard certificate expires periodically. To check the expiry date:

```bash
openssl x509 -in /etc/ssl/star_zesco_co_zm_chained.crt -noout -enddate
```

When the certificate is renewed:

1. Obtain the new `star_zesco_co_zm.crt`, `myserver.key`, and `DigiCertCA.crt` files
2. Copy them to `/etc/ssl/` (overwriting the old files)
3. Recreate the chained certificate:
   ```bash
   sudo cat /etc/ssl/star_zesco_co_zm.crt /etc/ssl/DigiCertCA.crt > /etc/ssl/star_zesco_co_zm_chained.crt
   ```
4. Restart nginx:
   ```bash
   sudo systemctl restart nginx
   ```

---

## Removing SSL Later

To revert to HTTP only:

1. Delete the certificate files:
   ```bash
   sudo rm /etc/ssl/star_zesco_co_zm.crt /etc/ssl/myserver.key /etc/ssl/DigiCertCA.crt /etc/ssl/star_zesco_co_zm_chained.crt
   ```
2. Replace the nginx config with an HTTP-only version (remove the port 443 server block and the redirect)
3. Restart nginx:
   ```bash
   sudo systemctl restart nginx
   ```
4. Update `APP_URL` in `.env` back to `http://`

---

## Quick Reference: Useful Commands

| Task | Command |
|------|---------|
| Check DNS A record | `dig yourdomain.zesco.co.zm A +short` |
| Check server public IP | `curl -4 ifconfig.me` |
| Check cert expiry | `openssl x509 -in /etc/ssl/star_zesco_co_zm_chained.crt -noout -enddate` |
| Check cert details | `openssl x509 -in /etc/ssl/star_zesco_co_zm_chained.crt -noout -issuer -subject -dates` |
| Test nginx config | `sudo nginx -t` |
| Restart nginx | `sudo systemctl restart nginx` |
| Check nginx status | `sudo systemctl status nginx` |
| Check what's listening on 443 | `sudo ss -tlnp \| grep ':443'` |
| Check firewall services | `sudo firewall-cmd --list-services` |
