# Detiene los contenedores del prototipo sin borrar datos.
# Para borrar todo (base de datos incluida) y empezar de cero:  docker compose down -v
Set-Location -Path $PSScriptRoot
docker compose stop
