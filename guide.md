# 🚀 Deployment Guide (ApiGateway v1.0.0)

This section explains how to deploy the **multi‑tenant ApiGateway v1.0.0** to a production environment, including Apache, Nginx, and VPS hosting.

---

## ⚙️ Apache Setup

1. **Enable required modules**:
   ```bash
   a2enmod rewrite
   a2enmod headers
   systemctl restart apache2
   ```

2. **VirtualHost configuration** (`/etc/apache2/sites-available/api_gateway.conf`):
   ```apache
   <VirtualHost *:80>
       ServerName api.example.com
       DocumentRoot /var/www/api_gateway

       <Directory /var/www/api_gateway>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/api_gateway_error.log
       CustomLog ${APACHE_LOG_DIR}/api_gateway_access.log combined
   </VirtualHost>
   ```

3. **Enable site and reload Apache**:
   ```bash
   a2ensite api_gateway.conf
   systemctl reload apache2
   ```

---

## ⚙️ Nginx Setup

1. **Server block configuration** (`/etc/nginx/sites-available/api_gateway`):
   ```nginx
   server {
       listen 80;
       server_name api.example.com;

       root /var/www/api_gateway;
       index index.php;

       location / {
           try_files $uri /index.php?$query_string;
       }

       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }

       access_log /var/log/nginx/api_gateway_access.log;
       error_log /var/log/nginx/api_gateway_error.log;
   }
   ```

2. **Enable site and reload Nginx**:
   ```bash
   ln -s /etc/nginx/sites-available/api_gateway /etc/nginx/sites-enabled/
   nginx -t
   systemctl reload nginx
   ```

---

## ⚙️ `.htaccess` Rewrite Rules (Apache)

Place in `/var/www/api_gateway/.htaccess`:

```apache
RewriteEngine On

# Route all requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

This ensures clean URLs like `/users` or `/orders` are routed through `index.php`.

---

## ⚙️ VPS Setup (Hostman / Hetzner / DigitalOcean)

1. **Provision VPS**:
   - Ubuntu 22.04 LTS recommended.
   - Minimum: 1 vCPU, 1GB RAM, 20GB SSD.

2. **Install stack**:
   ```bash
   sudo apt update
   sudo apt install apache2 php8.2 php8.2-sqlite3 libapache2-mod-php8.2 -y
   ```

   Or for Nginx + PHP‑FPM:
   ```bash
   sudo apt install nginx php8.2-fpm php8.2-sqlite3 -y
   ```

3. **Deploy code**:
   - Upload `index.php` and supporting files to `/var/www/api_gateway`.
   - Ensure permissions:
     ```bash
     chown -R www-data:www-data /var/www/api_gateway
     chmod -R 755 /var/www/api_gateway
     ```

4. **Firewall**:
   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```

5. **SSL (Let’s Encrypt)**:
   ```bash
   sudo apt install certbot python3-certbot-apache -y
   sudo certbot --apache -d api.example.com
   ```
   Or for Nginx:
   ```bash
   sudo apt install certbot python3-certbot-nginx -y
   sudo certbot --nginx -d api.example.com
   ```

---

## ✅ Verification

- Test routes:
  ```bash
  curl -X GET http://api.example.com/users \
    -H "Authorization: Basic YWRtaW46c2VjcmV0MTIz" \
    -H "X-Client-ID: client123"
  ```

- Check metrics:
  ```bash
  curl -X GET http://api.example.com/metrics \
    -H "Authorization: Basic YWRtaW46c2VjcmV0MTIz" \
    -H "X-Client-ID: client123"
  ```

---

## 🏷️ Production Notes
- Use **systemd service monitoring** (`systemctl status apache2` or `nginx`).
- Regularly back up `gateway.db`.
- Consider **reverse proxy caching** (e.g., Nginx `proxy_cache`) for heavy traffic.
- Monitor logs (`/var/log/api_gateway_error.log`, `/var/log/api_gateway_access.log`).

---

Andrew, this guide makes your gateway **production‑ready** on VPS providers like Hostman or Hetzner (I noticed you’ve been exploring those).  

Would you like me to also add a **Docker deployment section** so you can containerize the gateway and run it on Kubernetes or CoreOS (since you’ve been looking at Fedora CoreOS and Kubernetes videos)?