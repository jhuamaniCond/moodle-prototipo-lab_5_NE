# Prototipo de Aula Virtual – Asesores Unidos S.A.C.

Práctica 05 de Negocios Electrónicos (E-Learning). Prototipo de la mejor alternativa evaluada: **Moodle 4.5 LTS**,
ejecutado en Docker con **MySQL 8.4** y **PHP 8.3**.

## Requisitos

- Windows 10/11 con [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Conexión a Internet la primera vez (descarga de imágenes y de Moodle 4.5.15)
- Puerto `8000` libre

## Uso

```powershell
powershell -ExecutionPolicy Bypass -File .\ejecutar.ps1   # levanta todo y abre http://localhost:8000
powershell -ExecutionPolicy Bypass -File .\detener.ps1    # apaga los contenedores (conserva los datos)
```

La **primera ejecución** construye la imagen web con Moodle 4.5.15 incluido, instala la base de datos y crea todo el
escenario, mostrando una barra de progreso con porcentaje y tiempo restante (el detalle queda en `instalacion.log`).
Las contraseñas de prueba se generan al azar y se guardan en `credenciales.txt`.
Las siguientes ejecuciones solo encienden los contenedores. Si una instalación se interrumpe, la siguiente ejecución
la detecta, limpia la base de datos y vuelve a instalar.

Para borrar todo y volver a instalar desde cero: `docker compose down -v` y luego `.\ejecutar.ps1`.

## Qué se crea

| Elemento | Detalle |
|---|---|
| Sitio | "Asesores Unidos S.A.C. – Campus Virtual", en español, zona horaria America/Lima, chat activado |
| Curso | **Informática Básica** (INFBAS), 10 semanas: 02/11/2026 – 10/01/2027, cronograma en la sección General |
| Usuarios | 4 profesores (integrantes del grupo) y 2 alumnos de prueba |
| Módulos | 1 Introducción a las TI · 2 Word · 3 Excel · 4 PowerPoint · 5 **ERP, CRM y SCM** (reemplaza a Access) |
| Por módulo | Dos subsecciones con fechas: **Semana 1** (libro digital, video y la tarea, que se entrega al final de la semana 2) y **Semana 2** (artículo, enlace, caso de estudio, chat con conversación de ejemplo y cuestionario de 5 preguntas V/F, opción múltiple y respuesta corta × 4 puntos = 20) |

## Estructura

```
prototipo/
├── docker-compose.yml     # servicios web (imagen propia) y db (mysql:8.4)
├── docker/
│   ├── Dockerfile         # moodlehq/moodle-php-apache:8.3 + código de Moodle 4.5.15 dentro de la imagen
│   └── config.php         # config.php de Moodle
├── scripts/
│   ├── 01_curso.php       # sitio, usuarios, curso, materiales, tareas, chats y cuestionarios
│   ├── 02_cronograma.php  # cuadro de cronograma en la sección General
│   └── 03_chats.php       # conversaciones de ejemplo en cada sesión de chat
├── ejecutar.ps1           # levanta todo y abre la web
└── detener.ps1            # apaga los contenedores
```

El código de Moodle va dentro de la imagen y no en una carpeta de Windows montada. Así la instalación y la web son
varias veces más rápidas, porque Docker Desktop lee muy lento los archivos compartidos con Windows.

Los datos persisten en los volúmenes de Docker `ausac-moodle_dbdata` y `ausac-moodle_moodledata`.

## Notas

- Se usa Moodle 4.5 y no 5.x porque en Moodle 5.0 la actividad Chat salió del núcleo y el enunciado exige una
  sesión de chat por módulo.
- Las conversaciones de chat están fechadas en la sesión programada de cada módulo (semana 2, 19:00).
