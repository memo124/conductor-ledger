# Despliegue en producción

Guía para publicar ConductorLedger en **Apache**, **Nginx** o **XAMPP** sin usar `php artisan serve`.

## Requisitos del servidor

- PHP 8.2+ con extensiones: `pdo_pgsql`, `openssl`, `mbstring`, `json`, `sodium`
- PostgreSQL 14+ (cliente `pg_dump` instalado en el servidor)
- Composer 2.x
- Para respaldos ZIP: extensión PHP `zip` recomendada (`extension=zip` en `php.ini`); en Windows sin `zip`, se usa PowerShell; en Linux, el paquete `zip` del sistema

## Variables de entorno críticas

Copia `.env.example` a `.env` y configura al menos:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=conductor_ledger
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@tudominio.com
RESEND_API_KEY=re_...

MASTER_ENCRYPTION_KEY=base64:...
REGISTRATION_MODE=approval
SECURITY_ADMIN_EMAIL=admin@tudominio.com

# Respaldos (v1.2.1+)
PG_DUMP_BINARY=pg_dump
BACKUP_DISK=backup_local
BACKUP_RETENTION_MONTHS=12
BACKUP_NOTIFY_EMAIL=admin@tudominio.com
BACKUP_DOWNLOAD_TOKEN_TTL=15
```

Generar claves:

```bash
php artisan key:generate
# MASTER_ENCRYPTION_KEY: base64 de 32 bytes aleatorios
php -r "echo 'MASTER_ENCRYPTION_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

## Instalación

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force   # solo primera vez o entornos de prueba
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Permisos de escritura:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Apache

El **DocumentRoot** debe apuntar a la carpeta `public/`, no a la raíz del proyecto.

```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot /var/www/conductor-ledger/public

    <Directory /var/www/conductor-ledger/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

En XAMPP (Windows), equivalente en `httpd-vhosts.conf`:

```apache
DocumentRoot "D:/xampp/htdocs/conductor-ledger/public"
<Directory "D:/xampp/htdocs/conductor-ledger/public">
    AllowOverride All
    Require all granted
</Directory>
```

El archivo `public/.htaccess` ya incluye las reglas de reescritura de Laravel.

---

## Nginx

```nginx
server {
    listen 80;
    server_name tudominio.com;
    root /var/www/conductor-ledger/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Cola de trabajos (respaldos)

Con `QUEUE_CONNECTION=database` los jobs en background (p. ej. `DatabaseBackupJob`) requieren un **worker** en segundo plano.

> **Nota (v1.1.1):** las notificaciones formales por correo (registro, creación/activación de usuario) se envían de forma síncrona y **no** dependen del worker.

### Opción recomendada: Supervisor (Linux)

`/etc/supervisor/conf.d/conductor-ledger-worker.conf`:

```ini
[program:conductor-ledger-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/conductor-ledger/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/conductor-ledger/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start conductor-ledger-worker:*
```

Tras cada deploy: `php artisan queue:restart`

### Opción simple: cron

```cron
* * * * * cd /var/www/conductor-ledger && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

### Opción sin worker (sitios pequeños)

```env
QUEUE_CONNECTION=sync
```

Los jobs en cola se ejecutan en la misma petición HTTP (sin proceso en background). Los correos de notificación ya se envían así desde v1.1.1.

### Windows + XAMPP

Usar **NSSM** o el **Programador de tareas** para ejecutar al inicio:

```text
php artisan queue:work database --sleep=3 --tries=3
```

---

## Respaldos de base de datos (v1.2.1+)

### Requisitos

| Plataforma | `pg_dump` | ZIP |
|------------|-----------|-----|
| **Linux / Docker** | `postgresql-client` o imagen con `pg_dump` en PATH | `apt install zip` o PHP `zip` |
| **Windows (XAMPP)** | Instalar [PostgreSQL](https://www.postgresql.org/download/windows/) o indicar ruta en `.env` | `extension=zip` en `php.ini` o PowerShell (incluido en Windows) |

Ejemplo Windows cuando `pg_dump` no está en PATH:

```env
PG_DUMP_BINARY="C:\Program Files\PostgreSQL\17\bin\pg_dump.exe"
```

### Permisos de carpeta

El usuario del servidor web (Apache/`www-data`) debe poder escribir en:

```text
storage/app/backups/
```

En XAMPP, la carpeta del proyecto debe ser escribible por el usuario que ejecuta Apache.

### Generación manual vs programada

| Modo | Cómo | Cola |
|------|------|------|
| **Manual** | Administración → Respaldos → «Generar respaldo ahora» | No requiere worker |
| **Programado** | `DatabaseBackupJob` vía `schedule:run` | Requiere `queue:work` o `QUEUE_CONNECTION=sync` |

### Restaurar

```bash
unzip conductorledger_YYYYMMDD_HHMMSS.zip
psql -h 127.0.0.1 -U postgres -d conductor_ledger -f conductorledger_YYYYMMDD_HHMMSS.sql
```

---

## Tareas programadas

Backups mensuales y particiones de tablas se definen en `routes/console.php`. Agregar **un cron**:

```cron
* * * * * cd /var/www/conductor-ledger && php artisan schedule:run >> /dev/null 2>&1
```

---

## Resend (correo)

| Entorno | `MAIL_FROM_ADDRESS` | Destinatarios |
|---------|---------------------|---------------|
| Desarrollo | `onboarding@resend.dev` | Solo el correo de tu cuenta Resend |
| Producción | `noreply@tudominio.com` | Cualquiera, tras verificar dominio en [resend.com/domains](https://resend.com/domains) |

---

## Checklist post-deploy

- [ ] `APP_DEBUG=false`
- [ ] HTTPS configurado
- [ ] Worker de cola activo (o `QUEUE_CONNECTION=sync`)
- [ ] Cron de `schedule:run` activo
- [ ] Dominio verificado en Resend
- [ ] `MASTER_ENCRYPTION_KEY` respaldada de forma segura (fuera del repo)
- [ ] Probar login, registro, activación de usuario y envío de correo
- [ ] Probar respaldo manual (Administración → Respaldos) y descarga del ZIP
- [ ] `pg_dump` accesible desde PHP (`PG_DUMP_BINARY` si hace falta)
- [ ] Carpeta `storage/app/backups` escribible
