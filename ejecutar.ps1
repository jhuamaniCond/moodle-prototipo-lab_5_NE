# Levanta el prototipo del Aula Virtual (Moodle 4.5 LTS en Docker) y abre la web.
# La primera vez construye la imagen, instala Moodle y crea el curso completo (unos minutos, con barra de progreso).
# Uso:  powershell -ExecutionPolicy Bypass -File .\ejecutar.ps1

# 'Continue': docker escribe su progreso en stderr; los errores se detectan con $LASTEXITCODE.
$ErrorActionPreference = 'Continue'
Set-Location -Path $PSScriptRoot
$url = 'http://localhost:8000'
$log = Join-Path $PSScriptRoot 'instalacion.log'

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

function Formato-Tiempo([double]$segundos) {
    $t = [TimeSpan]::FromSeconds([math]::Max(0, $segundos))
    if ($t.TotalMinutes -ge 1) { '{0} min {1:00} s' -f [int][math]::Floor($t.TotalMinutes), $t.Seconds } else { '{0} s' -f $t.Seconds }
}

# Ejecuta install_database.php mostrando porcentaje y tiempo restante (cada plugin instalado imprime una línea "-->").
function Instalar-Moodle([string]$pass) {
    $total = [int](docker compose exec -T web sh -c "find /var/www/html -name version.php -not -path '*/tests/*' | wc -l")
    if ($total -lt 100) { $total = 470 }
    $n = 0
    $inicio = Get-Date
    $writer = New-Object IO.StreamWriter($log, $false, (New-Object Text.UTF8Encoding $false))
    docker compose exec -T web php admin/cli/install_database.php --agree-license `
        --fullname="Asesores Unidos S.A.C. - Campus Virtual" --shortname="AUSAC" `
        --adminuser=admin --adminpass="$pass" --adminemail=admin@asesoresunidos.test 2>&1 | ForEach-Object {
            $linea = "$_"
            $writer.WriteLine($linea)
            if ($linea.StartsWith('-->')) {
                $n++
                $pct = [math]::Min(99, [int](100 * $n / $total))
                $seg = ((Get-Date) - $inicio).TotalSeconds
                $estado = "$pct %  ($n de $total componentes)  -  transcurrido: $(Formato-Tiempo $seg)"
                $restante = -1
                if ($n -ge 5) {
                    $restante = [int]($seg / $n * ($total - $n))
                    $estado += "  -  restante aprox.: $(Formato-Tiempo $restante)"
                }
                Write-Progress -Activity 'Instalando Moodle 4.5' -Status $estado -CurrentOperation $linea.Substring(3) `
                    -PercentComplete $pct -SecondsRemaining $restante
            }
        }
    $codigo = $LASTEXITCODE
    $writer.Close()
    Write-Progress -Activity 'Instalando Moodle 4.5' -Completed
    if ($codigo -ne 0) { throw "Falló la instalación de Moodle. Detalle en $log" }
    Write-Host ("    Moodle instalado en {0}" -f (Formato-Tiempo ((Get-Date) - $inicio).TotalSeconds)) -ForegroundColor Green
}

# 1. Docker.
Esperar-Docker

# 2. Imagen web con Moodle 4.5.15 incluido (solo se construye la primera vez).
docker image inspect ausac-moodle-web:4.5.15 *> $null
if ($LASTEXITCODE -ne 0) {
    Paso 'Construyendo la imagen web con Moodle 4.5.15 (solo la primera vez, 1-3 minutos)...'
    Compose build web
}

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
# La marca 'ausac_listo' se guarda al final; si falta, la instalación anterior quedó a medias.
$listo = ''
$tablas = Sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='moodle' AND table_name='m_config'"
if ($tablas -eq '1') { $listo = Sql "SELECT value FROM m_config WHERE name='ausac_listo'" }
if ($listo -ne '1') {
    if ($tablas -eq '1') {
        Paso 'Se encontró una instalación incompleta: limpiando base de datos y datos de Moodle...'
        docker compose exec -T db mysql -uroot -pm@0dl3ing -e "DROP DATABASE moodle; CREATE DATABASE moodle DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin; GRANT ALL ON moodle.* TO 'moodle'@'%';" 2>$null
        Compose exec -T web sh -c 'rm -rf /var/www/moodledata/*'
    }

    $chars = [char[]]'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'
    $pass = 'Admin#' + (-join (1..10 | ForEach-Object { $chars | Get-Random }))

    Paso 'Instalando Moodle (barra de progreso arriba)...'
    Instalar-Moodle $pass

    Paso 'Creando el curso Informatica Basica...'
    $pasos = @(
        @('01_curso.php', 'Sitio, usuarios, módulos, materiales, tareas, chats y cuestionarios'),
        @('02_cronograma.php', 'Cronograma del curso'),
        @('03_chats.php', 'Conversaciones de ejemplo en los chats')
    )
    $usuarios = @()
    for ($i = 0; $i -lt $pasos.Count; $i++) {
        Write-Progress -Activity 'Creando el curso' -Status "Paso $($i + 1) de $($pasos.Count): $($pasos[$i][1])" `
            -PercentComplete ([int](100 * $i / $pasos.Count))
        $salida = docker compose exec -T web php "/opt/ausac/$($pasos[$i][0])"
        if ($LASTEXITCODE -ne 0) { throw "Falló scripts/$($pasos[$i][0])" }
        if ($i -eq 0) { $usuarios = $salida | Where-Object { $_ -match ' / ' } }
    }
    Write-Progress -Activity 'Creando el curso' -Completed

    $lineas = @("Moodle 4.5 local (Docker) - $url", "admin / $pass (administrador)") + $usuarios
    $lineas | Set-Content '.\credenciales.txt' -Encoding UTF8
    Sql "INSERT INTO m_config (name, value) VALUES ('ausac_listo', '1')" | Out-Null
    Paso 'Credenciales de prueba guardadas en credenciales.txt'
}

# 5. Web.
Paso "Esperando a que responda $url ..."
$ok = $false
for ($i = 0; $i -lt 40 -and -not $ok; $i++) {
    try {
        Invoke-WebRequest -Uri "$url/login/index.php" -UseBasicParsing -TimeoutSec 30 | Out-Null
        $ok = $true
    } catch { Start-Sleep 3 }
}
if (-not $ok) { throw "La web no respondió. Revise 'docker compose logs web' o ejecute de nuevo este script." }
Start-Process $url
Paso "Listo: $url  (usuarios y contraseñas en credenciales.txt)"
