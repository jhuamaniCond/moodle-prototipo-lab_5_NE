<?php  // Configuración de Moodle para el contenedor "web" (se copia a moodle/config.php).

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = getenv('MOODLE_DB_PASS');
$CFG->prefix    = 'm_';
$CFG->dboptions = ['dbcollation' => 'utf8mb4_bin'];

$CFG->wwwroot   = 'http://localhost:8000';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;

$CFG->debug = 0;
$CFG->debugdisplay = 0;

require_once(__DIR__ . '/lib/setup.php');
