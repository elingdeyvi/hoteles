# Arranque completo con Docker (Apache + MySQL + Vite).
# Uso: .\scripts\docker-setup.ps1

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

function Require-Command($name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        Write-Host "ERROR: no se encontró '$name'. Instala Docker Desktop." -ForegroundColor Red
        exit 1
    }
}

function Invoke-Compose {
    param([string[]]$Args)
    if (Get-Command "docker" -ErrorAction SilentlyContinue) {
        $null = docker compose version 2>$null
        if ($LASTEXITCODE -eq 0) {
            docker compose @Args
            return
        }
    }
    docker-compose @Args
}

Require-Command docker

if (-not (Test-Path ".env")) {
    Copy-Item ".env.docker.example" ".env"
    Write-Host "Creado .env desde .env.docker.example" -ForegroundColor Green
} else {
    Write-Host ".env existente — para Docker usa DB_HOST=mysql (ver .env.docker.example)" -ForegroundColor Yellow
}

Write-Host "`n=== Docker setup — hotel ===`n" -ForegroundColor Cyan

Set-Location (Join-Path $Root "docker")
Invoke-Compose @("up", "-d", "--build")

Write-Host "`nEsperando MySQL..." -ForegroundColor Cyan
$ready = $false
for ($i = 1; $i -le 60; $i++) {
    Invoke-Compose @("exec", "-T", "mysql", "mysqladmin", "ping", "-h", "localhost", "--silent") 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "MySQL listo." -ForegroundColor Green
        $ready = $true
        break
    }
    Start-Sleep -Seconds 2
}
if (-not $ready) {
    Write-Host "ERROR: MySQL no respondió a tiempo." -ForegroundColor Red
    exit 1
}

Write-Host "`nComposer install..." -ForegroundColor Cyan
Invoke-Compose @("exec", "-T", "apache", "composer", "install", "--no-interaction", "--prefer-dist")

Write-Host "`nAPP_KEY y base de datos..." -ForegroundColor Cyan
Invoke-Compose @("exec", "-T", "apache", "php", "artisan", "key:generate", "--force")
Invoke-Compose @("exec", "-T", "apache", "php", "artisan", "migrate", "--seed", "--force")
Invoke-Compose @("exec", "-T", "apache", "php", "artisan", "storage:link") 2>$null

Write-Host "`nTests..." -ForegroundColor Cyan
Invoke-Compose @("exec", "-T", "apache", "php", "artisan", "test")

Write-Host "`n=== Docker listo ===`n" -ForegroundColor Green
Write-Host "  App:      http://localhost:8000"
Write-Host "  Vite:     http://localhost:5173"
Write-Host "  Login:    http://localhost:8000/auth/login"
Write-Host "  Reservar: http://localhost:8000/reservar"
Write-Host ""
Write-Host "Usuarios demo (password): admin@gmail.com"
Write-Host ""
Write-Host "Logs: cd docker; docker compose logs -f apache"
