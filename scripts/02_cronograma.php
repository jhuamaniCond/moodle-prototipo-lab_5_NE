<?php
// Agrega el cuadro "Cronograma del curso" en la sección General y desactiva los tours de usuario.
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

global $DB;
\core\session\manager::set_user(get_admin());
$DB->set_field('tool_usertours_tours', 'enabled', 0);

$rows = [
    ['Módulo 1: Introducción a las Tecnologías de la Información', 'Semanas 1–2', '02/11/2026 – 15/11/2026', 'Wilson Josue Turpo Huanca'],
    ['Módulo 2: Procesadores de Texto – Word', 'Semanas 3–4', '16/11/2026 – 29/11/2026', 'Oscar Raul Chilo Huillca'],
    ['Módulo 3: Hoja de Cálculo Electrónica – Excel', 'Semanas 5–6', '30/11/2026 – 13/12/2026', 'Ronald Pablo Garcia Valdivia'],
    ['Módulo 4: Presentaciones – PowerPoint', 'Semanas 7–8', '14/12/2026 – 27/12/2026', 'Jeanpiero Sixto Huamani Condori'],
    ['Módulo 5: Sistemas Empresariales – ERP, CRM y SCM', 'Semanas 9–10', '28/12/2026 – 10/01/2027', 'Wilson Josue Turpo Huanca'],
];
$html = '<h5>Cronograma del curso (10 semanas)</h5><table class="table table-sm table-bordered"><thead><tr>'
    . '<th>Módulo</th><th>Semanas</th><th>Fechas</th><th>Docente</th></tr></thead><tbody>';
foreach ($rows as $r) {
    $html .= '<tr><td>' . implode('</td><td>', $r) . '</td></tr>';
}
$html .= '</tbody></table>';

$course = $DB->get_record('course', ['shortname' => 'INFBAS'], '*', MUST_EXIST);
$gen = new testing_data_generator();
$gen->create_module('label', ['course' => $course->id, 'section' => 0, 'intro' => $html, 'introformat' => FORMAT_HTML]);
purge_all_caches();
echo "Cronograma agregado\n";
