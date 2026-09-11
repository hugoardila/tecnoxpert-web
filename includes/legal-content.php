<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$txLegalDocuments = [
    'informacion-legal' => [
        'title' => 'Información del negocio',
        'description' => 'Identidad legal y canales oficiales de TECNOXPERT.',
        'content' => <<<'HTML'
<p class="legal-note"><strong>Identidad:</strong> TECNOXPERT es un establecimiento de comercio propiedad de Hugo Alberto Ardila Molina, identificado con NIT 1083903212-3, domiciliado en Pitalito, Huila, Colombia.</p>
<h2>Datos de identificación</h2>
<ul>
  <li><strong>Nombre comercial:</strong> TECNOXPERT.</li>
  <li><strong>Titular legal:</strong> Hugo Alberto Ardila Molina.</li>
  <li><strong>Tipo de negocio:</strong> establecimiento de comercio propiedad de una persona natural.</li>
  <li><strong>NIT:</strong> 1083903212-3.</li>
  <li><strong>Ciudad y país:</strong> Pitalito, Huila, Colombia.</li>
  <li><strong>Dominio oficial:</strong> <a href="https://tecnoxpert.com">https://tecnoxpert.com</a>.</li>
</ul>
<h2>Actividad</h2>
<p>TECNOXPERT presta servicios tecnológicos que pueden incluir desarrollo web, tiendas virtuales, CRM, software empresarial, automatización de WhatsApp, bots, integraciones con inteligencia artificial, configuración de servidores, soporte técnico y reparación de computadores o celulares.</p>
<p>La disponibilidad, alcance, precio, entrega y soporte de cada servicio se definen en una cotización u orden específica. La publicación de un servicio en este sitio no sustituye el análisis técnico ni constituye una oferta automática para cualquier alcance.</p>
<h2>Gestión contractual</h2>
<p>Los contratos, pagos, facturas, cotizaciones, órdenes y servicios comercializados bajo el nombre TECNOXPERT son gestionados por Hugo Alberto Ardila Molina como titular del establecimiento. La actividad se realiza desde Colombia.</p>
<h2>Canales oficiales</h2>
<ul>
  <li><strong>Ventas y cotizaciones:</strong> <a href="mailto:asesoria@tecnoxpert.com">asesoria@tecnoxpert.com</a>.</li>
  <li><strong>Soporte y solicitudes:</strong> <a href="mailto:contacto@tecnoxpert.com">contacto@tecnoxpert.com</a>.</li>
  <li><strong>WhatsApp:</strong> <a href="https://wa.me/573117024021">311 702 4021</a>.</li>
  <li><strong>Atención:</strong> con cita previa. Los canales digitales reciben solicitudes en cualquier momento y se responden en orden de recepción.</li>
</ul>
HTML,
    ],
    'terminos-condiciones' => [
        'title' => 'Términos y condiciones',
        'description' => 'Condiciones de uso, contratación y pago de los servicios de TECNOXPERT.',
        'content' => <<<'HTML'
<p class="legal-note">Estos términos regulan el uso del sitio y las contrataciones gestionadas bajo el nombre comercial TECNOXPERT.</p>
<h2>1. Identificación del responsable</h2>
<p>El titular legal es Hugo Alberto Ardila Molina. TECNOXPERT es el nombre comercial de su establecimiento de comercio, identificado con NIT 1083903212-3 y domiciliado en Pitalito, Huila, Colombia. Los contratos, pagos, facturas, cotizaciones y servicios son gestionados por el titular.</p>
<h2>2. Información del sitio</h2>
<p>El sitio presenta servicios tecnológicos y canales para solicitar cotizaciones, soporte o información. Las descripciones son generales; el alcance vinculante es el incluido en la cotización, orden o contrato aceptado.</p>
<h2>3. Proceso de contratación</h2>
<ol>
  <li>El cliente presenta su necesidad y la información necesaria.</li>
  <li>TECNOXPERT analiza los requerimientos y dependencias.</li>
  <li>Se entrega una propuesta con alcance, exclusiones, precio, moneda y plazo estimado.</li>
  <li>El cliente acepta las condiciones y políticas aplicables.</li>
  <li>Se genera una cotización u orden identificada.</li>
  <li>El cliente realiza el anticipo o pago acordado.</li>
  <li>El trabajo comienza cuando también se hayan entregado los accesos y materiales necesarios.</li>
</ol>
<h2>4. Precios, impuestos y pagos</h2>
<p>Los valores se expresan en la moneda indicada en la cotización. El resumen previo al pago identifica el servicio o producto, valor, impuestos cuando correspondan, total y plazo estimado. El cliente no puede definir libremente ni modificar el importe desde el navegador.</p>
<p>El estado de una orden cambia a pagada únicamente después de una confirmación verificable del proveedor de pago o de la conciliación del medio acordado. Una captura de pantalla o mensaje del cliente no sustituye la confirmación.</p>
<h2>5. Anticipos y desarrollos personalizados</h2>
<p>Los desarrollos y configuraciones personalizadas pueden requerir anticipo. Los hitos, entregables y saldo se indican en la propuesta. Las nuevas funciones o cambios de alcance se evalúan y cotizan por separado.</p>
<h2>6. Obligaciones del cliente</h2>
<p>El cliente debe suministrar información veraz, materiales y accesos autorizados; realizar respaldos cuando le corresponda; revisar los entregables; proteger sus credenciales; y no solicitar actividades ilícitas o que infrinjan derechos de terceros.</p>
<h2>7. Entrega, aceptación y retrasos</h2>
<p>La forma y fecha estimada de entrega se establecen en la orden. Los plazos pueden ajustarse cuando el cliente retrase materiales, aprobaciones o accesos, o cuando existan dependencias de terceros. TECNOXPERT informará el impacto conocido.</p>
<h2>8. Garantía y soporte</h2>
<p>La garantía cubre las condiciones descritas en la orden y la corrección de errores atribuibles al alcance original. No incluye ampliaciones, mal uso, cambios del cliente o terceros, incidentes externos ni servicios de proveedores ajenos. Consulta la <a href="/politica-garantias">Política de garantías</a>.</p>
<h2>9. Cancelaciones y reembolsos</h2>
<p>Se aplican la normativa colombiana y la <a href="/politica-reembolsos">Política de reembolsos y cancelaciones</a>. Los trabajos ejecutados, licencias, repuestos, consumos y costos de terceros pueden tener un tratamiento diferente al trabajo no iniciado.</p>
<h2>10. Proveedores tecnológicos</h2>
<p>Algunas funciones dependen de hosting, dominios, pasarelas, mensajería, APIs, operadores, fabricantes o transportadoras. TECNOXPERT no garantiza la disponibilidad continua de servicios externos, pero realizará las gestiones incluidas en el alcance contratado.</p>
<h2>11. Propiedad intelectual y credenciales</h2>
<p>La titularidad, licencias y entrega del código, diseño, contenido, configuraciones o accesos se define en la cotización. El cliente garantiza que puede usar los materiales que suministra.</p>
<h2>12. Protección de datos</h2>
<p>El tratamiento de información se rige por la <a href="/politica-privacidad">Política de privacidad</a> y la <a href="/tratamiento-datos">Política de tratamiento de datos personales</a>.</p>
<h2>13. Comunicaciones y evidencia</h2>
<p>Las cotizaciones, aceptaciones, órdenes, correos, mensajes, registros de entrega y confirmaciones de pago pueden utilizarse como evidencia de la relación comercial, de acuerdo con la normativa aplicable.</p>
<h2>14. Ley aplicable y contacto</h2>
<p>La actividad se desarrolla desde Colombia y se somete a la normativa colombiana aplicable. Las solicitudes pueden enviarse a <a href="mailto:contacto@tecnoxpert.com">contacto@tecnoxpert.com</a>.</p>
HTML,
    ],
    'politica-reembolsos' => [
        'title' => 'Política de reembolsos y cancelaciones',
        'description' => 'Procedimiento y condiciones para cancelaciones, retractos, pagos duplicados y reembolsos.',
        'content' => <<<'HTML'
<p class="legal-note">Esta política distingue productos físicos, servicios técnicos y servicios digitales. Su aplicación no limita los derechos irrenunciables reconocidos por la normativa colombiana.</p>
<h2>1. Cómo presentar una solicitud</h2>
<p>Envía la solicitud a <a href="mailto:contacto@tecnoxpert.com">contacto@tecnoxpert.com</a> o mediante el <a href="/contacto">formulario de contacto</a>. Incluye nombre, número de cotización u orden, fecha, valor, medio de pago, motivo y soportes disponibles. No envíes contraseñas ni datos completos de tarjetas.</p>
<p>La solicitud debe presentarse tan pronto se conozca la situación. Cuando se invoque retracto, reversión u otro derecho legal, se aplicarán los plazos y requisitos de la normativa colombiana vigente. Como procedimiento interno, TECNOXPERT confirmará la recepción y comunicará si necesita información adicional.</p>
<h2>2. Cancelación antes de comenzar</h2>
<p>Si el servicio no ha comenzado y no existen licencias, repuestos, dominios, reservas, consumos o costos de terceros, se revisará la devolución del valor recibido. Los costos no recuperables que hayan sido informados pueden descontarse cuando legalmente proceda.</p>
<h2>3. Servicios digitales y personalizados</h2>
<p>Cuando un servicio personalizado ya haya comenzado, el valor correspondiente al trabajo efectivamente realizado, licencias adquiridas, configuraciones ejecutadas y costos de terceros podrá descontarse del monto a devolver.</p>
<p>Los entregables aceptados, etapas completadas y desarrollos producidos conforme al alcance se evalúan de manera separada al trabajo pendiente. Los cambios de opinión sobre funciones que sí corresponden a la propuesta no equivalen automáticamente a un incumplimiento.</p>
<h2>4. Servicios técnicos</h2>
<p>Antes de reparar un equipo se solicita autorización del diagnóstico, precio y trabajo. Si el cliente cancela después de autorizar, pueden cobrarse el diagnóstico, la intervención ejecutada y los repuestos solicitados o instalados, según lo informado y la ley aplicable.</p>
<h2>5. Productos físicos</h2>
<p>Para una devolución se revisan el estado, accesorios, empaques, número de orden y causal. Los productos deben conservarse de manera razonable mientras se coordina el procedimiento. Las condiciones de retracto, garantía o reversión se aplican según la naturaleza del producto y la normativa.</p>
<h2>6. Casos en los que puede no proceder</h2>
<ul>
  <li>Servicios que comenzaron con autorización del cliente cuando exista una excepción legal aplicable.</li>
  <li>Trabajo entregado y aceptado conforme al alcance, salvo una falla cubierta por garantía.</li>
  <li>Licencias, dominios, repuestos, consumos o servicios de terceros no reembolsables.</li>
  <li>Solicitudes asociadas a mal uso, modificaciones de terceros o información incorrecta del cliente.</li>
  <li>Productos o trabajos personalizados cuando una excepción legal resulte aplicable.</li>
</ul>
<h2>7. Pagos duplicados o cobros incorrectos</h2>
<p>Se contrastará la orden con los registros del proveedor de pago. Cuando se confirme un pago duplicado o un valor cobrado incorrectamente, se solicitará la corrección o devolución por el mismo medio cuando sea posible.</p>
<h2>8. Evaluación y respuesta</h2>
<p>La meta interna es acusar recibo dentro de dos días hábiles y entregar una decisión o actualización dentro de diez días hábiles, contados desde que la información esté completa. Estos son tiempos operativos internos y no sustituyen términos legales aplicables.</p>
<h2>9. Reembolso aprobado</h2>
<p>Cuando se apruebe un reembolso de comercio electrónico, se tramitará dentro de los términos legales aplicables. Como objetivo operativo, TECNOXPERT procurará gestionarlo sin exceder quince días calendario desde la aprobación o desde que se complete la información requerida, según corresponda.</p>
<p>La visualización del dinero en la cuenta depende de los tiempos bancarios y del proveedor de pago. TECNOXPERT entregará la evidencia disponible del trámite.</p>
<h2>10. Reversión del pago</h2>
<p>La reversión procede únicamente en los eventos y mediante el procedimiento previsto por la normativa colombiana. El cliente debe notificar a TECNOXPERT y a la entidad emisora o proveedor de pago cuando la ley así lo exija.</p>
HTML,
    ],
    'politica-entregas' => [
        'title' => 'Política de entrega de servicios y productos',
        'description' => 'Condiciones de inicio, ejecución y entrega para servicios digitales, servicios técnicos y productos físicos.',
        'content' => <<<'HTML'
<p class="legal-note">El medio, cobertura y plazo aplicables se indican en cada cotización u orden antes del pago.</p>
<h2>1. Confirmación del pedido</h2>
<p>La orden se considera confirmada cuando se han aceptado el alcance y las políticas, se ha recibido la información requerida y el medio de pago reporta una confirmación verificable, cuando exista pago inicial.</p>
<h2>2. Servicios digitales</h2>
<h3>Inicio y plazo</h3>
<p>El trabajo comienza después de la confirmación y de recibir accesos, textos, imágenes, datos, autorizaciones y demás insumos. El plazo estimado se incluye en la propuesta y puede organizarse por hitos.</p>
<h3>Forma de entrega</h3>
<p>La entrega puede realizarse por correo corporativo, enlace seguro, repositorio, servidor, panel, archivo o credenciales, según la naturaleza del servicio. Se indicará qué debe revisar el cliente y cómo presentar observaciones.</p>
<h3>Aprobación y retrasos del cliente</h3>
<p>Cuando el cliente retrase materiales, accesos o aprobaciones, el cronograma puede suspenderse o reprogramarse. TECNOXPERT comunicará el efecto conocido y retomará el trabajo según disponibilidad.</p>
<h2>3. Servicios técnicos</h2>
<h3>Recepción y evidencia</h3>
<p>Cuando se recibe un equipo se registra, según corresponda, su identificación, accesorios entregados, estado visible, falla reportada y datos de contacto. Se recomienda que el cliente mantenga respaldo de su información.</p>
<h3>Diagnóstico y autorización</h3>
<p>El diagnóstico identifica el trabajo recomendado, precio y plazo aproximado. No se ejecutan reparaciones adicionales sin autorización previa, salvo tareas expresamente incluidas desde la recepción.</p>
<h3>Entrega o retiro</h3>
<p>Al finalizar se informa el resultado y se coordina la entrega o retiro. Para equipos no reclamados se intentará contactar al cliente y acordar el retiro; no se aplicarán consecuencias no informadas ni contrarias a la ley.</p>
<h2>4. Productos físicos</h2>
<h3>Cobertura y transportadora</h3>
<p>TECNOXPERT realiza envíos únicamente cuando la cotización o pedido lo indique. La cobertura geográfica, transportadora, costo y plazo estimado se confirman antes del pago. No se presume cobertura nacional si no está escrita en la orden.</p>
<h3>Guía y seguimiento</h3>
<p>Cuando la transportadora genere guía, se suministrará al cliente. Los plazos son estimados y pueden verse afectados por la transportadora, ubicación o novedades externas.</p>
<h3>Datos incorrectos, pérdida, daño o retraso</h3>
<p>El cliente debe revisar nombre, teléfono, ciudad y dirección. Si los datos suministrados son incorrectos, los costos de reenvío se evaluarán antes de un nuevo despacho. Ante pérdida, daño o retraso, el cliente debe informar con la orden, guía y evidencia para gestionar la reclamación.</p>
<h2>5. Evidencia de entrega</h2>
<p>Pueden utilizarse correos, actas, mensajes, registros del sistema, guías, fotografías técnicas o confirmaciones del cliente como evidencia, procurando no divulgar datos personales innecesarios.</p>
HTML,
    ],
    'politica-garantias' => [
        'title' => 'Política de garantías',
        'description' => 'Cobertura de garantía para productos físicos, reparaciones, software y servicios tecnológicos.',
        'content' => <<<'HTML'
<p class="legal-note">La duración y cobertura concreta de la garantía se indican en la cotización, orden, diagnóstico o documento de entrega, sin limitar la garantía legal aplicable.</p>
<h2>1. Cómo reportar una garantía</h2>
<p>Escribe a <a href="mailto:contacto@tecnoxpert.com">contacto@tecnoxpert.com</a> o utiliza el <a href="/contacto">formulario</a>. Incluye número de orden, descripción del problema, fecha en que apareció, pasos para reproducirlo y evidencia disponible. Para equipos o productos, agrega fotografías cuando ayuden al diagnóstico.</p>
<h2>2. Evaluación</h2>
<p>TECNOXPERT confirmará la recepción y solicitará la información faltante. El tiempo de evaluación depende de la complejidad, disponibilidad del equipo, repuestos, acceso al sistema y respuesta de proveedores. Se comunicará una estimación antes de realizar trabajos no cubiertos.</p>
<h2>3. Productos físicos</h2>
<p>Se revisará si la falla corresponde a calidad, idoneidad, seguridad o condiciones ofrecidas. La solución puede incluir diagnóstico, reparación, cambio o la medida que corresponda según el caso y la normativa.</p>
<h2>4. Servicio técnico</h2>
<p>La garantía cubre la intervención autorizada y los repuestos suministrados en las condiciones indicadas. Una falla diferente o preexistente no queda cubierta automáticamente por haber sido detectada después.</p>
<h2>5. Desarrollo de software</h2>
<p>Se corrigen errores reproducibles que impidan que una función incluida en el alcance original opere como fue aceptada. La garantía no convierte solicitudes nuevas, cambios de diseño, nuevas integraciones o modificaciones de reglas de negocio en correcciones sin costo.</p>
<h2>6. Servicios de infraestructura y automatización</h2>
<p>La garantía cubre la configuración ejecutada dentro del alcance. La disponibilidad de proveedores de nube, dominio, certificados, mensajería, APIs, redes o pasarelas depende de terceros.</p>
<h2>7. Exclusiones habituales</h2>
<ul>
  <li>Mal uso, golpes, humedad, sobrevoltaje, virus o daño posterior.</li>
  <li>Cambios realizados por el cliente o por terceros sin coordinación.</li>
  <li>Credenciales compartidas, accesos comprometidos o pérdida de información no atribuible al trabajo.</li>
  <li>Fallas de proveedores, servicios externos, equipos o licencias no suministrados por TECNOXPERT.</li>
  <li>Funciones, capacidad, rendimiento o compatibilidad no incluidos en el alcance.</li>
  <li>Uso contrario a instrucciones, documentación o requisitos técnicos informados.</li>
</ul>
<p>Una exclusión se aplicará únicamente cuando tenga relación con la falla reportada y sin desconocer los derechos legales del consumidor.</p>
<h2>8. Cambios adicionales</h2>
<p>Las mejoras o nuevas necesidades descubiertas durante la garantía se documentan y cotizan por separado. TECNOXPERT explicará la diferencia entre un error cubierto y una ampliación.</p>
HTML,
    ],
    'politica-privacidad' => [
        'title' => 'Política de privacidad',
        'description' => 'Información sobre datos, cookies, proveedores y seguridad en tecnoxpert.com.',
        'content' => <<<'HTML'
<p class="legal-note">Responsable: Hugo Alberto Ardila Molina, titular del establecimiento de comercio TECNOXPERT, NIT 1083903212-3, Pitalito, Huila, Colombia.</p>
<h2>1. Datos que podemos recopilar</h2>
<ul>
  <li>Nombre, correo, teléfono, ciudad y datos de contacto.</li>
  <li>Información incluida en formularios, solicitudes, cotizaciones y soporte.</li>
  <li>Datos de facturación, pedido y confirmación de pago, sin almacenar números completos de tarjeta.</li>
  <li>Información técnica básica como dirección IP, fecha, navegador, registros de seguridad y errores.</li>
  <li>Archivos, accesos o información que el cliente entregue para ejecutar un servicio.</li>
</ul>
<h2>2. Finalidades</h2>
<p>Los datos se utilizan para responder solicitudes, preparar cotizaciones, ejecutar servicios, gestionar pagos y entregas, prestar soporte, cumplir obligaciones contables o legales, prevenir abuso, proteger los sistemas y mantener evidencia de la relación comercial.</p>
<p>Las comunicaciones comerciales no se envían sin una base válida o autorización cuando sea necesaria. El titular puede solicitar que se suspendan.</p>
<h2>3. Formularios y contacto</h2>
<p>El formulario solicita aceptación de la política de tratamiento antes de enviar. La información se almacena en un sistema privado de recepción. No publiques contraseñas, datos completos de tarjetas, información médica ni datos sensibles en el mensaje.</p>
<h2>4. Pagos</h2>
<p>Los pagos se procesan, cuando están habilitados, mediante proveedores externos. TECNOXPERT recibe identificadores, estado, valor y datos necesarios para conciliar la orden, pero no necesita almacenar los datos completos de la tarjeta.</p>
<h2>5. Cookies y analítica</h2>
<p>El sitio utiliza cookies técnicas de sesión cuando son necesarias para proteger formularios y consultas de cotizaciones. Actualmente el sitio corporativo no instala herramientas propias de publicidad comportamental ni analítica de terceros. Si se incorporan, esta política y los avisos correspondientes se actualizarán.</p>
<h2>6. Proveedores tecnológicos</h2>
<p>La información puede transmitirse a proveedores de hosting, correo, copias de seguridad, mensajería, transporte, soporte o pasarelas, únicamente para la función contratada o autorizada y bajo obligaciones de seguridad o confidencialidad aplicables.</p>
<h2>7. Conservación</h2>
<p>La información se conserva durante el tiempo necesario para atender la solicitud, ejecutar el contrato, soportar garantías, cumplir obligaciones legales, resolver controversias y proteger la seguridad. Luego se elimina, anonimiza o archiva de forma restringida según corresponda.</p>
<h2>8. Derechos del titular</h2>
<p>El titular puede conocer, actualizar, rectificar y solicitar la supresión de sus datos; pedir prueba de la autorización; conocer el uso realizado; revocar la autorización cuando proceda; y presentar consultas o reclamos conforme a la normativa colombiana.</p>
<h2>9. Seguridad</h2>
<p>Se aplican medidas razonables como HTTPS, controles de acceso, validación de formularios, restricción de archivos y almacenamiento privado. Ningún sistema elimina totalmente el riesgo, por lo que no se afirman certificaciones ni seguridad absoluta.</p>
<h2>10. Consultas y reclamos</h2>
<p>Envía la solicitud a <a href="mailto:contacto@tecnoxpert.com">contacto@tecnoxpert.com</a> con nombre, identificación suficiente de la relación, descripción y documentos pertinentes. No adjuntes datos innecesarios.</p>
HTML,
    ],
    'tratamiento-datos' => [
        'title' => 'Política de tratamiento de datos personales',
        'description' => 'Reglas para la recolección, uso, almacenamiento, circulación y supresión de datos personales.',
        'content' => <<<'HTML'
<p class="legal-note"><strong>Responsable del tratamiento:</strong> Hugo Alberto Ardila Molina, titular de TECNOXPERT, NIT 1083903212-3, Pitalito, Huila, Colombia. Canal: contacto@tecnoxpert.com.</p>
<h2>1. Alcance</h2>
<p>Esta política se aplica a los datos personales tratados por TECNOXPERT en formularios, cotizaciones, órdenes, soporte, pagos, entregas, relaciones con clientes, proveedores y usuarios.</p>
<h2>2. Principios</h2>
<p>El tratamiento se realiza atendiendo los principios de legalidad, finalidad, libertad, veracidad o calidad, transparencia, acceso restringido, seguridad y confidencialidad previstos en la normativa colombiana.</p>
<h2>3. Finalidades autorizadas</h2>
<ul>
  <li>Identificar y contactar al titular.</li>
  <li>Atender consultas, cotizar y ejecutar servicios.</li>
  <li>Gestionar contratos, facturación, pagos, soporte, garantías y entregas.</li>
  <li>Administrar accesos y seguridad de los sistemas.</li>
  <li>Cumplir obligaciones legales, contables y requerimientos de autoridad.</li>
  <li>Enviar información comercial cuando exista autorización o base legal.</li>
</ul>
<h2>4. Autorización</h2>
<p>Cuando sea necesaria, la autorización será previa, expresa e informada y podrá obtenerse por formularios, contratos, mensajes de datos u otros mecanismos que permitan consulta posterior. TECNOXPERT conservará evidencia razonable de la autorización.</p>
<h2>5. Derechos</h2>
<p>El titular puede conocer, actualizar, rectificar y solicitar la supresión de sus datos; solicitar prueba de la autorización; conocer el uso; presentar quejas ante la autoridad competente después de agotar el trámite correspondiente; y revocar la autorización cuando proceda.</p>
<h2>6. Procedimiento para consultas</h2>
<p>Las consultas se envían a <a href="mailto:contacto@tecnoxpert.com">contacto@tecnoxpert.com</a> indicando nombre, medio de respuesta, relación con TECNOXPERT y petición concreta. Se responderán dentro de los términos legales aplicables. Si la solicitud está incompleta se pedirá la información necesaria.</p>
<h2>7. Procedimiento para reclamos</h2>
<p>El reclamo debe identificar al titular, describir los hechos, indicar la solicitud y adjuntar los documentos pertinentes. Si se requiere subsanación, se informará. El trámite y la anotación del reclamo se realizarán conforme a los términos legales aplicables.</p>
<h2>8. Supresión y revocatoria</h2>
<p>La supresión o revocatoria se atenderá cuando proceda. Algunos datos deben conservarse por una obligación legal, contractual, contable, de garantía, seguridad o defensa de derechos.</p>
<h2>9. Transmisión y transferencia</h2>
<p>Los datos pueden ser tratados por proveedores de infraestructura, correo, mensajería, pagos, transporte, soporte o respaldo. Cuando exista transferencia o transmisión nacional o internacional, se aplicarán las medidas y bases permitidas por la normativa.</p>
<h2>10. Datos sensibles y de menores</h2>
<p>TECNOXPERT no solicita datos sensibles ni información de menores mediante el formulario general. Si un servicio excepcional los requiere, se informará el carácter facultativo y se obtendrán las autorizaciones correspondientes.</p>
<h2>11. Vigencia</h2>
<p>Esta política rige desde su publicación. Las bases de datos se conservan durante la relación y por el tiempo necesario para las finalidades y obligaciones aplicables.</p>
HTML,
    ],
];
