-- ConductorLedger — Crear base de datos PostgreSQL
-- Ejecutar como superusuario: psql -U postgres -f create_database.sql

CREATE DATABASE conductor_ledger
    WITH ENCODING = 'UTF8'
    LC_COLLATE = 'Spanish_Spain.1252'
    LC_CTYPE = 'Spanish_Spain.1252'
    TEMPLATE = template0;

-- Conectar a conductor_ledger y ejecutar:
-- php artisan migrate --seed
