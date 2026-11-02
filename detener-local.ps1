# ==============================================================================
# Script de Detención Local - CampusGate / DeltaSoft
# ==============================================================================
Write-Host "==========================================================" -ForegroundColor Yellow
Write-Host "      Deteniendo servidores locales de CampusGate         " -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor Yellow

# Detener procesos de PHP y Node que tengan puertos 8000 y 5173
$ports = 8000, 5173
foreach ($port in $ports) {
    $connections = Get-NetTCPConnection -LocalPort $port -ErrorAction SilentlyContinue
    if ($connections) {
        $pids = $connections | Select-Object -ExpandProperty OwningProcess -Unique
        foreach ($procId in $pids) {
            $p = Get-Process -Id $procId -ErrorAction SilentlyContinue
            if ($p) {
                Write-Host "Deteniendo proceso $($p.ProcessName) (PID: $procId) en puerto $port..." -ForegroundColor Cyan
                Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue
            }
        }
    }
}

Write-Host "Puertos 8000 y 5173 liberados exitosamente." -ForegroundColor Green
Write-Host "Nota: El servicio de PostgreSQL permanece activo para futuras sesiones." -ForegroundColor Gray
