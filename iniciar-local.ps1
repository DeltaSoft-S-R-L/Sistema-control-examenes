# ==============================================================================
# Script de Inicio Rápido Local (Sin Docker) - CampusGate / DeltaSoft
# ==============================================================================
$env:Path = "C:\Program Files\nodejs;C:\Users\USUARIO\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe;C:\Program Files\PostgreSQL\15\bin;" + $env:Path

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   Iniciando CampusGate en Modo Local Nativo (Windows)    " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Rutas de binarios nativos instalados
$phpPath = "C:\Users\USUARIO\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
$npmPath = "C:\Program Files\nodejs\npm.cmd"

# 2. Verificar servicio de PostgreSQL
Write-Host "[1/4] Verificando servicio de PostgreSQL..." -ForegroundColor Yellow
$pgService = Get-Service postgresql* -ErrorAction SilentlyContinue | Select-Object -First 1
if ($pgService) {
    if ($pgService.Status -ne 'Running') {
        Write-Host "      Iniciando servicio $($pgService.Name)..." -ForegroundColor Yellow
        Start-Service $pgService.Name
    }
    Write-Host "      PostgreSQL activo en puerto 5432." -ForegroundColor Green
} else {
    Write-Host "      PostgreSQL corriendo en segundo plano." -ForegroundColor Green
}

# 3. Lanzar Backend Laravel (puerto 8000)
Write-Host "[2/4] Levantando Backend Laravel en http://localhost:8000..." -ForegroundColor Yellow
$backendDir = Join-Path $PSScriptRoot "backend"
Start-Process -FilePath "powershell.exe" -ArgumentList "-NoExit", "-Command", "`$env:Path = 'C:\Program Files\nodejs;C:\Users\USUARIO\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe;C:\Program Files\PostgreSQL\15\bin;' + `$env:Path; cd '$backendDir'; Write-Host '--- Backend CampusGate (Laravel API) ---' -ForegroundColor Green; & '$phpPath' artisan serve --host=127.0.0.1 --port=8000" -WindowStyle Normal

# 4. Lanzar Frontend Vite (puerto 5173)
Write-Host "[3/4] Levantando Frontend React + Vite en http://localhost:5173..." -ForegroundColor Yellow
$frontendDir = Join-Path $PSScriptRoot "frontend"
Start-Process -FilePath "powershell.exe" -ArgumentList "-NoExit", "-Command", "`$env:Path = 'C:\Program Files\nodejs;C:\Users\USUARIO\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe;C:\Program Files\PostgreSQL\15\bin;' + `$env:Path; cd '$frontendDir'; Write-Host '--- Frontend CampusGate (React + Vite) ---' -ForegroundColor Cyan; & '$npmPath' run dev" -WindowStyle Normal

# 5. Abrir navegador
Write-Host "[4/4] Abriendo aplicacion en el navegador..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
Start-Process "http://localhost:5173"

Write-Host "----------------------------------------------------------" -ForegroundColor Green
Write-Host " Sistema iniciado con exito!                              " -ForegroundColor Green
Write-Host "   Frontend: http://localhost:5173                       " -ForegroundColor White
Write-Host "   Backend:  http://localhost:8000                       " -ForegroundColor White
Write-Host " Para detener los servicios ejecuta: .\detener-local.ps1  " -ForegroundColor Gray
Write-Host "----------------------------------------------------------" -ForegroundColor Green
