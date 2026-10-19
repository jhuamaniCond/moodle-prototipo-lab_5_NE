<?php
// Carga conversaciones de ejemplo en la sesión de chat de cada módulo (semana 2, 19:00 hora de Lima).
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

global $DB;
$course = $DB->get_record('course', ['shortname' => 'INFBAS'], '*', MUST_EXIST);
$uid = fn(string $u) => $DB->get_field('user', 'id', ['username' => $u, 'deleted' => 0], MUST_EXIST);
$ana = $uid('aquispe');
$luis = $uid('lmamani');

$conversaciones = [
    1 => ['wturpo', [
        ['p', 'Buenas noches, bienvenidos a la sesión de consultas del Módulo 1. ¿Tienen dudas sobre la Tarea 1?'],
        ['a', 'Buenas noches, profesor. En el inventario, ¿el disco duro va como almacenamiento o como procesamiento?'],
        ['p', 'Va como dispositivo de almacenamiento. El procesamiento corresponde al procesador (CPU) y a la memoria RAM.'],
        ['l', 'Profesor, ¿el antivirus se considera software de aplicación o de sistema?'],
        ['p', 'Es software de aplicación; el sistema operativo es Windows. Pueden revisar el libro digital del módulo, tema de software.'],
        ['a', '¿La tarea se entrega en Word o puede ser en PDF?'],
        ['p', 'Ambos formatos son válidos. La fecha límite es el domingo 15/11 a las 23:59.'],
        ['l', 'Entendido, muchas gracias.'],
    ]],
    2 => ['ochilo', [
        ['p', 'Buenas noches. Iniciamos la sesión de chat del Módulo 2: Word. ¿Alguna consulta?'],
        ['a', 'Profesor, la tabla de contenido no me muestra todos los títulos del informe.'],
        ['p', 'Seguramente algunos títulos no tienen aplicado el estilo "Título 1" o "Título 2". Aplíquelos y luego actualice la tabla.'],
        ['a', 'Ya funcionó, gracias.'],
        ['l', '¿Cómo hago para que la portada no tenga número de página?'],
        ['p', 'En Encabezado y pie de página, marque "Primera página diferente". Así la portada queda sin número.'],
        ['l', 'Perfecto. ¿La carta comercial también lleva encabezado?'],
        ['p', 'Sí, con el logo y datos de Asesores Unidos S.A.C. Recuerden que la entrega es el domingo 29/11.'],
    ]],
    3 => ['rgarcia', [
        ['p', 'Buenas noches a todos. Sesión de consultas del Módulo 3: Excel.'],
        ['l', 'Profesor, al copiar la fórmula del total, la tarifa por hora cambia de celda.'],
        ['p', 'Debe usar una referencia absoluta: escriba $B$1 en lugar de B1, o presione F4 sobre la referencia.'],
        ['l', 'Ahora sí se mantiene fija, gracias.'],
        ['a', '¿Cómo uso la función SI para marcar a los clientes con más de 20 horas?'],
        ['p', 'Por ejemplo: =SI(D2>20;"Cliente frecuente";"Regular"). Luego arrastre la fórmula hacia abajo.'],
        ['a', '¿El gráfico puede ser de columnas?'],
        ['p', 'Sí, un gráfico de columnas de horas por cliente es ideal. Fecha de entrega: domingo 13/12.'],
    ]],
    4 => ['jhuamani', [
        ['p', 'Buenas noches. Bienvenidos al chat del Módulo 4: PowerPoint. ¿En qué los ayudo?'],
        ['a', 'Profesor, ¿cuántas diapositivas debe tener como mínimo la presentación?'],
        ['p', 'Entre 8 y 10 diapositivas: portada, servicios, un gráfico, contacto y cierre.'],
        ['l', '¿Dónde agrego las notas del orador?'],
        ['p', 'En la vista Normal, debajo de cada diapositiva está el panel "Notas". Si no aparece, actívelo desde la pestaña Vista.'],
        ['a', '¿Las transiciones deben ser iguales en todas las diapositivas?'],
        ['p', 'Es recomendable usar una sola transición sencilla para todo el documento; da una imagen más profesional.'],
        ['l', 'Gracias, profesor. Entregamos el domingo 27/12.'],
    ]],
    5 => ['wturpo', [
        ['p', 'Buenas noches. Sesión de consultas del Módulo 5: ERP, CRM y SCM.'],
        ['a', 'Profesor, ¿cuál es la diferencia principal entre un ERP y un CRM?'],
        ['p', 'El ERP integra los procesos internos (finanzas, inventario, compras, RR. HH.); el CRM se enfoca en la relación con los clientes y las ventas.'],
        ['l', 'Para el cuadro comparativo, ¿Odoo puede ser el ejemplo de ERP?'],
        ['p', 'Sí, Odoo o SAP Business One para ERP; Salesforce o HubSpot para CRM; y SAP SCM u Oracle SCM para la cadena de suministro.'],
        ['a', '¿El SCM incluye la relación con los proveedores?'],
        ['p', 'Exacto, gestiona el flujo desde los proveedores hasta el cliente final. Revisen el caso de estudio de la distribuidora.'],
        ['l', 'Muy claro, gracias. Entregamos el domingo 10/01.'],
    ]],
];

foreach ($conversaciones as $num => [$teacher, $msgs]) {
    $chat = $DB->get_record_select('chat', 'course = ? AND ' . $DB->sql_like('name', '?'),
        [$course->id, "Chat del Módulo $num:%"], '*', MUST_EXIST);
    $DB->delete_records('chat_messages', ['chatid' => $chat->id]);
    $users = ['p' => $uid($teacher), 'a' => $ana, 'l' => $luis];

    $t = (int)$chat->chattime;
    $add = function(int $userid, string $text, bool $system) use ($DB, $chat, &$t) {
        $DB->insert_record('chat_messages', (object)['chatid' => $chat->id, 'userid' => $userid, 'groupid' => 0,
            'issystem' => $system ? 1 : 0, 'message' => $text, 'timestamp' => $t]);
    };
    foreach (['p', 'a', 'l'] as $k) {
        $add($users[$k], 'enter', true);
        $t += 20;
    }
    foreach ($msgs as [$k, $text]) {
        $t += 75;
        $add($users[$k], $text, false);
    }
    foreach (['a', 'l', 'p'] as $k) {
        $t += 30;
        $add($users[$k], 'exit', true);
    }
}
echo "Conversaciones de chat cargadas\n";
