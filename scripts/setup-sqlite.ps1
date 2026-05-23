# Instalacion local con SQLite (Windows) - sin MySQL ni Docker.
# Uso: .\scripts\setup-sqlite.ps1
#      .\scripts\setup-sqlite.ps1 -Fresh

param(
    [switch]$Fresh
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

function Require-Command($name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        Write-Host "ERROR: no se encontro '$name' en el PATH." -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "=== Setup hotel SQLite - $Root ===" -ForegroundColor Cyan
Write-Host ""

Require-Command php
Require-Command composer
Require-Command npm

$phpModules = & php -m
if ($phpModules -notcontains 'pdo_sqlite') {
    Write-Host "ERROR: extension PHP pdo_sqlite no disponible." -ForegroundColor Red
    Write-Host "Active sqlite en php.ini" -ForegroundColor Yellow
    exit 1
}

Copy-Item ".env.sqlite.example" ".env" -Force
Write-Host "Configurado .env desde .env.sqlite.example" -ForegroundColor Green

$dbFile = Join-Path $Root "database\database.sqlite"
if (-not (Test-Path $dbFile)) {
    New-Item -ItemType File -Path $dbFile -Force | Out-Null
    Write-Host "Creado database/database.sqlite" -ForegroundColor Green
}

Write-Host ""
Write-Host "[1/7] Composer install..." -ForegroundColor Cyan
composer install --no-interaction --prefer-dist

Write-Host ""
Write-Host "[2/7] APP_KEY..." -ForegroundColor Cyan
php artisan key:generate --force

Write-Host ""
Write-Host "[3/7] Base de datos SQLite..." -ForegroundColor Cyan
if ($Fresh) {
    php artisan migrate:fresh --seed --force
} else {
    php artisan migrate --seed --force
}

Write-Host ""
Write-Host "[4/7] Storage link..." -ForegroundColor Cyan
php artisan storage:link 2>$null

Write-Host ""
Write-Host "[5/7] npm install + build..." -ForegroundColor Cyan
npm install
npm run build

Write-Host ""
Write-Host "[6/7] Tests..." -ForegroundColor Cyan
php artisan test

Write-Host ""
Write-Host "[7/7] Resumen..." -ForegroundColor Cyan
php artisan hotel:about
php artisan hotel:preflight

Write-Host ""
Write-Host "=== Listo SQLite ===" -ForegroundColor Green
Write-Host ""
Write-Host "Arrancar: .\scripts\dev.ps1"
Write-Host "Login: http://127.0.0.1:8000/auth/login"
Write-Host "Reservas: /reservar/costa-azul | /reservar/sierra-verde"
Write-Host "Usuario: admin@gmail.com / password"
Write-Host ""
