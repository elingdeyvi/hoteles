# Arranca Laravel + Vite en Windows (2 ventanas).
# Uso: .\scripts\dev.ps1
# Requiere haber ejecutado antes: .\scripts\setup-sqlite.ps1

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

if (-not (Test-Path (Join-Path $Root ".env"))) {
    Write-Host "ERROR: no hay .env. Ejecute primero .\scripts\setup-sqlite.ps1" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path (Join-Path $Root "public\build\manifest.json"))) {
    Write-Host "AVISO: falta public/build/manifest.json — ejecute npm run build" -ForegroundColor Yellow
} elseif (Test-Path (Join-Path $Root "public\hot")) {
    Write-Host "AVISO: existe public/hot (Vite dev). Si solo usa php artisan serve, ejecute npm run build." -ForegroundColor Yellow
}

Write-Host "`n=== Dev hotel ===" -ForegroundColor Cyan
Write-Host "App:  http://127.0.0.1:8000"
Write-Host "Vite: http://127.0.0.1:5173 (hot reload)"
Write-Host "Ctrl+C en cada ventana para detener.`n"

Start-Process powershell -ArgumentList @(
    "-NoExit", "-Command",
    "Set-Location '$Root'; Write-Host 'Laravel serve' -ForegroundColor Green; php artisan serve --host=127.0.0.1 --port=8000"
)

Start-Sleep -Seconds 1

Start-Process powershell -ArgumentList @(
    "-NoExit", "-Command",
    "Set-Location '$Root'; Write-Host 'Vite dev' -ForegroundColor Green; npm run dev"
)

Write-Host "Servidores iniciados en ventanas separadas." -ForegroundColor Green
