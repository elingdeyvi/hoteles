# Instalación inicial — Sistema de gestión hotelera (Windows / PowerShell)
# Uso: .\scripts\setup.ps1
# Opcional: .\scripts\setup.ps1 -Fresh   (migrate:fresh --seed)

param(
    [switch]$Fresh
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

function Require-Command($name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        Write-Host "ERROR: no se encontró '$name' en el PATH." -ForegroundColor Red
        exit 1
    }
}

Write-Host "`n=== Setup hotel — $Root ===`n" -ForegroundColor Cyan

Require-Command php
Require-Command composer
Require-Command npm

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Creado .env desde .env.example" -ForegroundColor Green
} else {
    Write-Host ".env ya existe (no se sobrescribe)" -ForegroundColor Yellow
}

Write-Host "`n[1/6] Composer install..." -ForegroundColor Cyan
composer install --no-interaction --prefer-dist

Write-Host "`n[2/6] APP_KEY..." -ForegroundColor Cyan
php artisan key:generate --force

Write-Host "`n[3/6] Base de datos..." -ForegroundColor Cyan
Write-Host "Asegúrate de que MySQL esté activo y DB_* en .env sea correcto." -ForegroundColor Yellow
if ($Fresh) {
    php artisan migrate:fresh --seed --force
} else {
    php artisan migrate --seed --force
}

Write-Host "`n[4/6] Storage link..." -ForegroundColor Cyan
php artisan storage:link 2>$null

Write-Host "`n[5/7] npm install + build..." -ForegroundColor Cyan
npm install
npm run build

Write-Host "`n[6/7] Tests..." -ForegroundColor Cyan
php artisan test

Write-Host "`n[7/7] Resumen..." -ForegroundColor Cyan
php artisan hotel:about
php artisan hotel:preflight

Write-Host "`n=== Listo ===`n" -ForegroundColor Green
Write-Host "Usuarios demo (contraseña: 12345678):"
Write-Host "  admin@gmail.com          — Administrador"
Write-Host "  recepcionista@gmail.com  — Recepcionista"
Write-Host "  housekeeping@gmail.com   — Housekeeping"
Write-Host "  cajero@gmail.com         — Cajero (POS)"
Write-Host ""
Write-Host "Siguiente paso:"
Write-Host "  npm run dev"
Write-Host "  php artisan serve   (si no usas Docker)"
Write-Host ""
Write-Host "Login: /auth/login"
Write-Host "Reservas: /reservar/costa-azul  |  /reservar/sierra-verde"
Write-Host "Sin MySQL local: .\scripts\docker-setup.ps1"
Write-Host "Deploy: docs/DEPLOY-GITHUB.md"
Write-Host ""
