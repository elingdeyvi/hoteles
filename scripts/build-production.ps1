# Build optimizado para producción (Windows)
# Uso: .\scripts\build-production.ps1

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

Write-Host "`n=== Build producción — hotel ===`n" -ForegroundColor Cyan

if (-not (Test-Path ".env")) {
    Write-Host "AVISO: no existe .env. Usa .env.production.example en el servidor." -ForegroundColor Yellow
}

Write-Host "[1/5] Composer (sin dev)..." -ForegroundColor Cyan
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

Write-Host "`n[2/5] Frontend (Vite)..." -ForegroundColor Cyan
if (Test-Path "package-lock.json") {
    npm ci
} else {
    npm install
}
npm run build

Write-Host "`n[3/5] Storage link..." -ForegroundColor Cyan
php artisan storage:link 2>$null

Write-Host "`n[4/5] Cachés Laravel..." -ForegroundColor Cyan
php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host "`n[5/5] Verificación..." -ForegroundColor Cyan
if (-not (Test-Path "public\build")) {
    Write-Host "ERROR: no se generó public/build" -ForegroundColor Red
    exit 1
}

Write-Host "`n=== Build completado ===`n" -ForegroundColor Green
Write-Host "Guía: docs/PRODUCCION.md"
