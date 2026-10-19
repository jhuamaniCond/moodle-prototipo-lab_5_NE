<?php
// Crea el escenario de la Práctica 05: curso "Informática Básica" de Asesores Unidos S.A.C.
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/testing/generator/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/langimport/lib.php');

global $DB, $CFG;
\core\session\manager::set_user(get_admin());

// Idioma, zona horaria, portada y chat.
$lang = new \tool_langimport\controller();
if (!get_string_manager()->translation_exists('es', false)) {
    $lang->install_languagepacks('es');
}
set_config('lang', 'es');
set_config('timezone', 'America/Lima');
set_config('forcetimezone', 'America/Lima');
set_config('frontpage', FRONTPAGEALLCOURSELIST);
set_config('frontpageloggedin', FRONTPAGEALLCOURSELIST);
$DB->set_field('modules', 'visible', 1, ['name' => 'chat']);
$site = get_site();
$site->fullname = 'Asesores Unidos S.A.C. – Campus Virtual';
$site->shortname = 'AUSAC';
$site->summary = '<p>Campus virtual de capacitación del personal de <strong>Asesores Unidos S.A.C.</strong> y de sus empresas clientes.</p>';
$site->summaryformat = FORMAT_HTML;
$DB->update_record('course', $site);
$admin = get_admin();
$DB->update_record('user', (object)['id' => $admin->id, 'lang' => 'es', 'timezone' => '99',
    'firstname' => 'Administrador', 'lastname' => 'Campus AUSAC']);

$tz = new DateTimeZone('America/Lima');
$ts = fn(string $s) => (new DateTime($s, $tz))->getTimestamp();

$gen = new testing_data_generator();

// Limpieza de una ejecución anterior.
if ($old = $DB->get_record('course', ['shortname' => 'INFBAS'])) {
    delete_course($old, false);
}
foreach (['wturpo', 'ochilo', 'rgarcia', 'jhuamani', 'aquispe', 'lmamani'] as $username) {
    if ($old = $DB->get_record('user', ['username' => $username, 'deleted' => 0])) {
        delete_user($old);
        $DB->delete_records('user', ['id' => $old->id]);
    }
}

// Categoría y curso.
$cat = $DB->get_record('course_categories', [], '*', IGNORE_MULTIPLE);
$DB->update_record('course_categories', (object)['id' => $cat->id, 'name' => 'Capacitación de Personal']);
$course = $gen->create_course([
    'fullname' => 'Informática Básica',
    'shortname' => 'INFBAS',
    'category' => $cat->id,
    'format' => 'topics',
    'numsections' => 5,
    'startdate' => $ts('2026-11-02 00:00'),
    'enddate' => $ts('2027-01-10 23:59'),
    'showactivitydates' => 1,
    'summary' => '<p>Curso de nivelación en informática para colaboradores de Asesores Unidos S.A.C. y empresas clientes. '
        . 'Duración: 10 semanas (2 semanas por módulo), del 02/11/2026 al 10/01/2027.</p>',
    'summaryformat' => FORMAT_HTML,
]);
$coursectx = context_course::instance($course->id);

// Usuarios: los 4 integrantes como profesores y 2 alumnos de prueba.
$cred = [];
$people = [
    ['wturpo', 'Wilson Josue', 'Turpo Huanca', 'editingteacher'],
    ['ochilo', 'Oscar Raul', 'Chilo Huillca', 'editingteacher'],
    ['rgarcia', 'Ronald Pablo', 'Garcia Valdivia', 'editingteacher'],
    ['jhuamani', 'Jeanpiero Sixto', 'Huamani Condori', 'editingteacher'],
    ['aquispe', 'Ana', 'Quispe Flores', 'student'],
    ['lmamani', 'Luis', 'Mamani Ccori', 'student'],
];
foreach ($people as [$username, $first, $last, $role]) {
    $pw = 'Ausac#' . random_string(8);
    $u = $gen->create_user(['username' => $username, 'firstname' => $first, 'lastname' => $last,
        'email' => $username . '@asesoresunidos.test', 'password' => $pw, 'lang' => 'es', 'timezone' => '99']);
    $gen->enrol_user($u->id, $course->id, $role);
    $cred[] = "$username / $pw ($role)";
}

// Contenido de los módulos.
$modules = [
    1 => [
        'name' => 'Módulo 1: Introducción a las Tecnologías de la Información',
        'weeks' => 'Semanas 1–2 (02/11/2026 – 15/11/2026)', 'start' => '2026-11-02', 'w2' => '2026-11-09',
        'end' => '2026-11-15', 'chat' => '2026-11-10 19:00', 'docente' => 'Wilson Josue Turpo Huanca',
        'urls' => [
            ['Libro digital: Mantenimiento y Montaje de Equipos Informáticos (Wikilibros)', 'https://es.wikibooks.org/wiki/Mantenimiento_y_Montaje_de_Equipos_Inform%C3%A1ticos'],
            ['Video: Introducción a las Tecnologías de la Información y las Comunicaciones (videolección UCM)', 'https://www.youtube.com/watch?v=YvNnokjFTbg'],
            ['Artículo: Tecnologías de la información y la comunicación (Wikipedia)', 'https://es.wikipedia.org/wiki/Tecnolog%C3%ADas_de_la_informaci%C3%B3n_y_la_comunicaci%C3%B3n'],
        ],
        'caso' => ['Caso de estudio: La rotación de personal en Asesores Unidos S.A.C.',
            '<p>Asesores Unidos S.A.C. incorpora cada mes nuevos colaboradores que solo tienen nociones elementales de informática. '
            . 'Sin embargo, sus clientes trabajan con sistemas ERP, CRM y SCM. Analice: ¿qué conocimientos mínimos de hardware, '
            . 'software e Internet debe tener un nuevo asesor en su primera semana? Proponga una lista priorizada.</p>'],
        'tarea' => ['Tarea 1: Inventario de hardware y software de mi puesto de trabajo',
            '<p>Elabore un documento con el inventario del hardware (dispositivos de entrada, salida, almacenamiento y procesamiento) '
            . 'y del software (sistema operativo y aplicaciones) de su computadora de trabajo, clasificando cada elemento. '
            . 'Entrega: domingo 15/11/2026 a las 23:59.</p>'],
        'quiz' => [
            ['tf', 'El hardware es la parte física de una computadora.', true],
            ['tf', 'Un sistema operativo es un ejemplo de hardware.', false],
            ['mc', '¿Cuál de los siguientes es un dispositivo de entrada?', ['Teclado', 'Monitor', 'Impresora', 'Parlante']],
            ['mc', '¿Qué significa la sigla TIC?', ['Tecnologías de la Información y la Comunicación', 'Técnicas de Internet y Computación', 'Tecnología Integrada de Computadoras', 'Terminales de Información Central']],
            ['sa', '¿Cómo se llama la red mundial que interconecta millones de computadoras? (una palabra)', 'Internet'],
        ],
    ],
    2 => [
        'name' => 'Módulo 2: Procesadores de Texto – Word',
        'weeks' => 'Semanas 3–4 (16/11/2026 – 29/11/2026)', 'start' => '2026-11-16', 'w2' => '2026-11-23',
        'end' => '2026-11-29', 'chat' => '2026-11-24 19:00', 'docente' => 'Oscar Raul Chilo Huillca',
        'urls' => [
            ['Libro digital: Minimódulo de ofimática (Wikilibros)', 'https://es.wikibooks.org/wiki/Minim%C3%B3dulo_de_ofim%C3%A1tica'],
            ['Video: Curso básico de Word para principiantes', 'https://www.youtube.com/watch?v=hl3OVpXWYSE'],
            ['Artículo: Microsoft Word (Wikipedia)', 'https://es.wikipedia.org/wiki/Microsoft_Word'],
            ['Enlace: Centro de aprendizaje oficial de Word (Microsoft)', 'https://support.microsoft.com/es-es/office/aprendizaje-de-word-7bcd85e6-2c3d-4c3c-a2a5-5ed8847eae73'],
        ],
        'caso' => ['Caso de estudio: Informe mensual de asesoría para un cliente',
            '<p>Un asesor debe entregar a una empresa manufacturera un informe mensual con portada, índice automático, '
            . 'tablas de resultados y numeración de páginas. Identifique qué herramientas de Word usaría en cada parte y por qué.</p>'],
        'tarea' => ['Tarea 2: Carta comercial e informe con formato profesional',
            '<p>Redacte una carta comercial y un informe de 3 páginas para un cliente, aplicando estilos de título, tabla de contenido '
            . 'automática, una tabla, encabezado y pie de página. Entrega: domingo 29/11/2026 a las 23:59.</p>'],
        'quiz' => [
            ['tf', 'En Word en español, la combinación Ctrl+N aplica negrita al texto seleccionado.', true],
            ['tf', 'El encabezado de un documento se repite en la parte superior de cada página.', true],
            ['mc', '¿Qué extensión tiene un documento de Word actual?', ['.docx', '.xlsx', '.pptx', '.accdb']],
            ['mc', '¿Qué se necesita para generar automáticamente una tabla de contenido?', ['Aplicar estilos de título', 'Usar copiar formato', 'Aumentar el zoom', 'Revisar la ortografía']],
            ['sa', '¿Cómo se llama la herramienta de Word que revisa la escritura correcta de las palabras? (una palabra)', 'Ortografía'],
        ],
    ],
    3 => [
        'name' => 'Módulo 3: Hoja de Cálculo Electrónica – Excel',
        'weeks' => 'Semanas 5–6 (30/11/2026 – 13/12/2026)', 'start' => '2026-11-30', 'w2' => '2026-12-07',
        'end' => '2026-12-13', 'chat' => '2026-12-08 19:00', 'docente' => 'Ronald Pablo Garcia Valdivia',
        'urls' => [
            ['Libro digital: Microsoft Excel (Wikilibros)', 'https://es.wikibooks.org/wiki/Microsoft_Excel'],
            ['Video: Curso de Excel completo – básico e intermedio', 'https://www.youtube.com/watch?v=eZPCVg4Jk00'],
            ['Artículo: Hoja de cálculo (Wikipedia)', 'https://es.wikipedia.org/wiki/Hoja_de_c%C3%A1lculo'],
            ['Enlace: Centro de aprendizaje oficial de Excel (Microsoft)', 'https://support.microsoft.com/es-es/office/aprendizaje-de-excel-9bc05390-e94c-46af-a5b3-d7c22f6990bb'],
        ],
        'caso' => ['Caso de estudio: Control de horas de asesoría por cliente',
            '<p>La gerencia necesita saber cuántas horas de asesoría se dedicó a cada cliente y cuánto facturar. '
            . 'Diseñe una hoja de cálculo que registre fecha, cliente, asesor y horas, y que calcule totales y promedios por cliente.</p>'],
        'tarea' => ['Tarea 3: Planilla de control de horas y facturación',
            '<p>Construya una planilla en Excel con al menos 15 registros que use las funciones SUMA, PROMEDIO y SI, '
            . 'una referencia absoluta para la tarifa por hora y un gráfico de horas por cliente. Entrega: domingo 13/12/2026 a las 23:59.</p>'],
        'quiz' => [
            ['tf', 'Toda fórmula en Excel debe comenzar con el signo igual (=).', true],
            ['tf', 'La celda B3 se encuentra en la fila B y la columna 3.', false],
            ['mc', '¿Qué función suma los valores de un rango de celdas?', ['SUMA', 'CONTAR', 'PROMEDIO', 'MAX']],
            ['mc', '¿Qué tipo de referencia es $A$1?', ['Absoluta', 'Relativa', 'Mixta', 'Circular']],
            ['sa', '¿Qué función devuelve el valor más alto de un rango? (escriba solo el nombre)', 'MAX'],
        ],
    ],
    4 => [
        'name' => 'Módulo 4: Presentaciones – PowerPoint',
        'weeks' => 'Semanas 7–8 (14/12/2026 – 27/12/2026)', 'start' => '2026-12-14', 'w2' => '2026-12-21',
        'end' => '2026-12-27', 'chat' => '2026-12-22 19:00', 'docente' => 'Jeanpiero Sixto Huamani Condori',
        'urls' => [
            ['Libro digital: OpenOffice.org – Impress, presentaciones (Wikilibros)', 'https://es.wikibooks.org/wiki/OpenOffice.org'],
            ['Video: Curso de PowerPoint desde cero (para principiantes)', 'https://www.youtube.com/watch?v=YvCU_y2idGI'],
            ['Artículo: Microsoft PowerPoint (Wikipedia)', 'https://es.wikipedia.org/wiki/Microsoft_PowerPoint'],
            ['Enlace: Centro de aprendizaje oficial de PowerPoint (Microsoft)', 'https://support.microsoft.com/es-es/office/aprendizaje-de-powerpoint-40e8c930-cb0b-40d8-82c4-bd53d3398787'],
        ],
        'caso' => ['Caso de estudio: Propuesta de asesoría ante un cliente',
            '<p>Un asesor tiene 10 minutos para presentar a un cliente comercial la propuesta de un estudio de mercado. '
            . 'Defina la estructura de diapositivas, el diseño, y qué gráficos e imágenes usaría para que el mensaje sea claro.</p>'],
        'tarea' => ['Tarea 4: Presentación de los servicios de Asesores Unidos S.A.C.',
            '<p>Elabore una presentación de 8 a 10 diapositivas sobre los servicios de la empresa, usando un tema de diseño, '
            . 'imágenes, un gráfico, transiciones y notas del orador. Entrega: domingo 27/12/2026 a las 23:59.</p>'],
        'quiz' => [
            ['tf', 'Las transiciones son efectos que se aplican al pasar de una diapositiva a otra.', true],
            ['tf', 'En PowerPoint no es posible insertar videos en una diapositiva.', false],
            ['mc', '¿Qué vista muestra todas las diapositivas en miniatura para reordenarlas fácilmente?', ['Clasificador de diapositivas', 'Vista de lectura', 'Página de notas', 'Patrón de documentos']],
            ['mc', '¿Qué tecla inicia la presentación desde la primera diapositiva?', ['F5', 'F1', 'F12', 'Esc']],
            ['sa', '¿Cómo se llama cada una de las páginas de una presentación? (una palabra)', 'Diapositiva'],
        ],
    ],
    5 => [
        'name' => 'Módulo 5: Sistemas Empresariales – ERP, CRM y SCM',
        'weeks' => 'Semanas 9–10 (28/12/2026 – 10/01/2027)', 'start' => '2026-12-28', 'w2' => '2027-01-04',
        'end' => '2027-01-10', 'chat' => '2027-01-05 19:00', 'docente' => 'Wilson Josue Turpo Huanca',
        'urls' => [
            ['Libro digital: Sistemas de Gestión I (Wikilibros)', 'https://es.wikibooks.org/wiki/Manual_del_estudiante_de_Ingenier%C3%ADa_en_Sistemas_de_UTN/Sistemas_de_Gesti%C3%B3n_I'],
            ['Video: Los sistemas de información ERP, CRM y SCM', 'https://www.youtube.com/watch?v=6euQ3afEJJI'],
            ['Artículo: ¿Qué es ERP? (Oracle)', 'https://www.oracle.com/latam/erp/what-is-erp/'],
            ['Enlace: ¿Qué es un CRM? (Salesforce)', 'https://www.salesforce.com/mx/crm/what-is-crm/'],
        ],
        'caso' => ['Caso de estudio: Selección de un ERP para una empresa comercial',
            '<p>Una distribuidora cliente de Asesores Unidos registra ventas, inventario y compras en hojas de Excel separadas, '
            . 'lo que genera errores de stock. Analice qué módulos de un ERP necesita, qué aporta un CRM a su área de ventas '
            . 'y cómo un SCM mejoraría la relación con sus proveedores.</p>'],
        'tarea' => ['Tarea 5: Cuadro comparativo ERP, CRM y SCM',
            '<p>Elabore un cuadro comparativo de ERP, CRM y SCM (objetivo, áreas que cubre, usuarios, beneficios) '
            . 'con un ejemplo de software real para cada uno. Entrega: domingo 10/01/2027 a las 23:59.</p>'],
        'quiz' => [
            ['tf', 'Un CRM gestiona las relaciones de la empresa con sus clientes.', true],
            ['tf', 'La sigla SCM significa "Sistema de Control de Memoria".', false],
            ['mc', '¿Qué sistema integra finanzas, inventario, compras y recursos humanos en una sola plataforma?', ['ERP', 'CRM', 'SCM', 'CMS']],
            ['mc', '¿Cuál es el objetivo principal de un SCM?', ['Gestionar la cadena de suministro desde los proveedores hasta el cliente', 'Diseñar la página web de la empresa', 'Calcular la planilla de sueldos', 'Administrar el correo electrónico']],
            ['sa', '¿Qué sigla corresponde a "Planificación de Recursos Empresariales"? (3 letras)', 'ERP'],
        ],
    ],
];

$fb = fn(string $t) => ['text' => $t, 'format' => FORMAT_HTML];

foreach ($modules as $num => $m) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num]);
    $DB->update_record('course_sections', (object)['id' => $section->id, 'name' => $m['name'],
        'summary' => "<p><strong>{$m['weeks']}</strong><br>Docente: {$m['docente']}</p>", 'summaryformat' => FORMAT_HTML]);

    $common = ['course' => $course->id, 'section' => $num];
    foreach ($m['urls'] as [$name, $url]) {
        $gen->create_module('url', $common + ['name' => $name, 'externalurl' => $url, 'display' => 0,
            'intro' => '', 'showdescription' => 0]);
    }
    $gen->create_module('page', $common + ['name' => $m['caso'][0], 'content' => $m['caso'][1],
        'intro' => '', 'contentformat' => FORMAT_HTML]);

    $gen->create_module('assign', $common + [
        'name' => $m['tarea'][0], 'intro' => $m['tarea'][1], 'introformat' => FORMAT_HTML, 'showdescription' => 1,
        'allowsubmissionsfromdate' => $ts($m['start'] . ' 00:00'),
        'duedate' => $ts($m['end'] . ' 23:59'),
        'gradingduedate' => 0, 'cutoffdate' => 0, 'grade' => 20,
        'assignsubmission_file_enabled' => 1, 'assignsubmission_file_maxfiles' => 3,
        'assignsubmission_onlinetext_enabled' => 0,
    ]);

    $gen->create_module('chat', $common + [
        'name' => "Chat del Módulo $num: sesión de consultas (semana 2)",
        'intro' => "<p>Sesión de chat en vivo con el docente para resolver dudas de la tarea y del cuestionario.</p>",
        'chattime' => $ts($m['chat']), 'schedule' => 1, 'keepdays' => 0, 'studentlogs' => 0,
    ]);

    // Banco de preguntas del módulo y cuestionario de 20 puntos (5 preguntas x 4 puntos).
    $qcat = (object)['name' => "Banco de preguntas – Módulo $num", 'info' => '', 'infoformat' => FORMAT_HTML,
        'stamp' => make_unique_id_code(), 'contextid' => $coursectx->id,
        'parent' => question_get_top_category($coursectx->id, true)->id, 'sortorder' => 999];
    $qcat->id = $DB->insert_record('question_categories', $qcat);
    $quiz = $gen->create_module('quiz', $common + [
        'name' => "Cuestionario del Módulo $num",
        'intro' => '<p>5 preguntas de 4 puntos cada una. Calificación máxima: 20 puntos.</p>',
        'grade' => 20, 'sumgrades' => 0, 'questionsperpage' => 0,
        'timeopen' => $ts($m['w2'] . ' 00:00'), 'timeclose' => $ts($m['end'] . ' 23:59'),
    ]);
    foreach ($m['quiz'] as $i => $q) {
        $n = $i + 1;
        $form = (object)['category' => "{$qcat->id},{$coursectx->id}", 'name' => sprintf('M%d-P%d', $num, $n),
            'questiontext' => $fb($q[1]), 'generalfeedback' => $fb(''), 'defaultmark' => 4,
            'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY];
        if ($q[0] === 'tf') {
            $qtype = 'truefalse';
            $form->penalty = 1;
            $form->correctanswer = $q[2] ? '1' : '0';
            $form->feedbacktrue = $fb('');
            $form->feedbackfalse = $fb('');
        } else if ($q[0] === 'mc') {
            $qtype = 'multichoice';
            $form->penalty = 0.3333333;
            $form->single = '1';
            $form->shuffleanswers = 1;
            $form->answernumbering = 'abc';
            $form->showstandardinstruction = 0;
            $form->shownumcorrect = 1;
            $form->noanswers = 4;
            $form->answer = array_map(fn($t) => ['text' => $t, 'format' => FORMAT_PLAIN], $q[2]);
            $form->fraction = ['1.0', '0.0', '0.0', '0.0'];
            $form->feedback = array_fill(0, 4, $fb(''));
            $form->correctfeedback = $fb('Respuesta correcta.');
            $form->partiallycorrectfeedback = $fb('Respuesta parcialmente correcta.');
            $form->incorrectfeedback = $fb('Respuesta incorrecta.');
        } else {
            $qtype = 'shortanswer';
            $form->penalty = 0.3333333;
            $form->usecase = 0;
            $form->answer = [$q[2]];
            $form->fraction = ['1.0'];
            $form->feedback = [$fb('Correcto.')];
        }
        $question = question_bank::get_qtype($qtype)->save_question(
            (object)['qtype' => $qtype, 'createdby' => get_admin()->id, 'idnumber' => null], $form);
        quiz_add_quiz_question($question->id, $quiz, 0, 4);
    }
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    $DB->set_field('quiz', 'grade', 20, ['id' => $quiz->id]);
}

rebuild_course_cache($course->id, true);
purge_all_caches();
echo "Curso creado: id={$course->id}\n";
echo implode("\n", $cred), "\n";


