# Levanta el prototipo del Aula Virtual (Moodle 4.5 LTS en Docker) y abre la web.
# La primera vez descarga Moodle, instala la base de datos y crea el curso completo (tarda varios minutos).
# Uso:  powershell -ExecutionPolicy Bypass -File .\ejecutar.ps1

# 'Continue': docker y git escriben su progreso en stderr; los errores se detectan con $LASTEXITCODE.
$ErrorActionPreference = 'Continue'
Set-Location -Path $PSScriptRoot
$url = 'http://localhost:8000'

function Paso([string]$texto) { Write-Host "`n==> $texto" -ForegroundColor Cyan }

function Esperar-Docker {
    docker info *> $null
    if ($LASTEXITCODE -eq 0) { return }
    Paso 'Iniciando Docker Desktop...'
    $candidatos = @(
        (Join-Path $env:LOCALAPPDATA 'Programs\DockerDesktop\Docker Desktop.exe'),
        (Join-Path $env:ProgramFiles 'Docker\Docker\Docker Desktop.exe')
    )
    $exe = $candidatos | Where-Object { Test-Path $_ } | Select-Object -First 1
    if (-not $exe) { throw 'No se encontró Docker Desktop. Instálelo desde https://www.docker.com/products/docker-desktop/' }
    Start-Process $exe
    for ($i = 0; $i -lt 90; $i++) {
        Start-Sleep 2
        docker info *> $null
        if ($LASTEXITCODE -eq 0) { return }
    }
    throw 'Docker Desktop no respondió a tiempo.'
}

function Compose { docker compose @args; if ($LASTEXITCODE -ne 0) { throw "Falló: docker compose $($args[0..2] -join ' ') ..." } }

function Sql([string]$consulta) {
    (docker compose exec -T db mysql -umoodle -pm@0dl3ing moodle -N -e $consulta 2>$null | Out-String).Trim()
}

# 1. Docker.
Esperar-Docker

# 2. Código de Moodle 4.5 LTS.
if (-not (Test-Path '.\moodle\version.php')) {
    Paso 'Descargando Moodle 4.5 LTS (rama MOODLE_405_STABLE)...'
    git clone --depth 1 -b MOODLE_405_STABLE https://github.com/moodle/moodle.git moodle
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo clonar Moodle (¿está instalado Git?).' }
}
Copy-Item '.\docker\config.php' '.\moodle\config.php' -Force

# 3. Contenedores.
Paso 'Levantando contenedores (web + db)...'
Compose up -d

Paso 'Esperando a MySQL...'
for ($i = 0; $i -lt 60; $i++) {
    docker compose exec -T db mysqladmin ping -umoodle -pm@0dl3ing --silent *> $null
    if ($LASTEXITCODE -eq 0) { break }
    Start-Sleep 3
}

# 4. Instalación y escenario (solo la primera vez).
$instalado = Sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='moodle' AND table_name='m_config'"
if ($instalado -ne '1') {
    $chars = [char[]]'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'
    $pass = 'Admin#' + (-join (1..10 | ForEach-Object { $chars | Get-Random }))

    Paso 'Instalando Moodle (esto puede tardar 10-15 minutos)...'
    Compose exec -T web php admin/cli/install_database.php --agree-license `
        --fullname="Asesores Unidos S.A.C. - Campus Virtual" --shortname="AUSAC" `
        --adminuser=admin --adminpass="$pass" --adminemail=admin@asesoresunidos.test

    Paso 'Creando el curso Informatica Basica (modulos, materiales, tareas, chats y cuestionarios)...'
    $usuarios = docker compose exec -T web php /opt/ausac/01_curso.php
    if ($LASTEXITCODE -ne 0) { throw 'Falló scripts/01_curso.php' }
    Compose exec -T web php /opt/ausac/02_cronograma.php
    Compose exec -T web php /opt/ausac/03_chats.php

    $lineas = @("Moodle 4.5 local (Docker) - $url", "admin / $pass (administrador)") +
        ($usuarios | Where-Object { $_ -match ' / ' })
    $lineas | Set-Content '.\credenciales.txt' -Encoding UTF8
    Paso 'Credenciales de prueba guardadas en credenciales.txt'
}

# 5. Web.
Paso "Esperando a que responda $url ..."
for ($i = 0; $i -lt 60; $i++) {
    try {
        Invoke-WebRequest -Uri "$url/login/index.php" -UseBasicParsing -TimeoutSec 30 | Out-Null
        break
    } catch { Start-Sleep 3 }
}
Start-Process $url
Paso "Listo: $url  (usuarios y contraseñas en credenciales.txt)"
