<?php

declare(strict_types=1);

/**
 * F&A Legal MVP bootstrap for EspoCRM 10.0.x.
 * Creates legal entities, fields and relationships using EspoCRM's own tools.
 * Idempotent: existing entities, fields and links are preserved.
 */

require_once __DIR__ . '/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Tools\EntityManager\EntityManager as EntityTypeManager;
use Espo\Tools\FieldManager\FieldManager;
use Espo\Tools\LinkManager\LinkManager;

$app = new Application();
$container = $app->getContainer();

/** @var InjectableFactory $factory */
$factory = $container->getByClass(InjectableFactory::class);
/** @var Metadata $metadata */
$metadata = $container->getByClass(Metadata::class);
/** @var DataManager $dataManager */
$dataManager = $container->getByClass(DataManager::class);

/** @var EntityTypeManager $entityTypeManager */
$entityTypeManager = $factory->create(EntityTypeManager::class);
/** @var FieldManager $fieldManager */
$fieldManager = $factory->create(FieldManager::class);
/** @var LinkManager $linkManager */
$linkManager = $factory->create(LinkManager::class);

function out(string $message): void { echo $message . PHP_EOL; }

function ensureEntity(
    Metadata $metadata,
    EntityTypeManager $manager,
    string $plainName,
    string $type,
    string $singular,
    string $plural,
    bool $stream = true
): string {
    foreach (['C' . $plainName, $plainName] as $candidate) {
        if ($metadata->get(['scopes', $candidate])) {
            out("[=] Entidad existente: {$candidate}");
            return $candidate;
        }
    }

    $actualName = $manager->create($plainName, $type, [
        'labelSingular' => $singular,
        'labelPlural' => $plural,
        'stream' => $stream,
    ]);

    out("[+] Entidad creada: {$actualName} ({$singular})");
    return $actualName;
}

function ensureField(
    Metadata $metadata,
    FieldManager $manager,
    string $entity,
    string $name,
    array $defs
): void {
    if ($metadata->get(['entityDefs', $entity, 'fields', $name])) {
        out("[=] Campo existente: {$entity}.{$name}");
        return;
    }

    $manager->create($entity, $name, $defs);
    out("[+] Campo creado: {$entity}.{$name} ({$defs['label']})");
}

function relabelField(
    Metadata $metadata,
    FieldManager $manager,
    string $entity,
    string $field,
    string $label
): void {
    if (!$metadata->get(['entityDefs', $entity, 'fields', $field])) {
        return;
    }

    $manager->update($entity, $field, ['label' => $label]);
    out("[~] Etiqueta ajustada: {$entity}.{$field} -> {$label}");
}

function ensureOneToMany(
    Metadata $metadata,
    LinkManager $manager,
    string $parent,
    string $child,
    string $parentLink,
    string $childLink,
    string $parentLabel,
    string $childLabel
): void {
    $leftExists = (bool) $metadata->get(['entityDefs', $parent, 'links', $parentLink]);
    $rightExists = (bool) $metadata->get(['entityDefs', $child, 'links', $childLink]);

    if ($leftExists || $rightExists) {
        out("[=] Relación existente: {$parent}.{$parentLink} <-> {$child}.{$childLink}");
        return;
    }

    $manager->create([
        'linkType' => 'oneToMany',
        'entity' => $parent,
        'link' => $parentLink,
        'entityForeign' => $child,
        'linkForeign' => $childLink,
        'label' => $parentLabel,
        'labelForeign' => $childLabel,
        'linkMultipleField' => false,
        'linkMultipleFieldForeign' => false,
        'audited' => true,
        'auditedForeign' => true,
    ]);

    out("[+] Relación creada: {$parent}.{$parentLink} <-> {$child}.{$childLink}");
}

try {
    out('=== F&A Legal | Bootstrap MVP ===');

    $cliente = ensureEntity($metadata, $entityTypeManager, 'Cliente', 'BasePlus', 'Cliente', 'Clientes');
    $asunto = ensureEntity($metadata, $entityTypeManager, 'Asunto', 'BasePlus', 'Asunto', 'Asuntos');
    $expediente = ensureEntity($metadata, $entityTypeManager, 'Expediente', 'BasePlus', 'Expediente', 'Expedientes');
    $actuacion = ensureEntity($metadata, $entityTypeManager, 'Actuacion', 'Base', 'Actuación', 'Actuaciones');
    $vencimiento = ensureEntity($metadata, $entityTypeManager, 'Vencimiento', 'Event', 'Vencimiento', 'Vencimientos');
    $audiencia = ensureEntity($metadata, $entityTypeManager, 'Audiencia', 'Event', 'Audiencia', 'Audiencias');
    $honorario = ensureEntity($metadata, $entityTypeManager, 'Honorario', 'Base', 'Honorario', 'Honorarios');

    relabelField($metadata, $fieldManager, $cliente, 'name', 'Nombre / Razón Social');
    relabelField($metadata, $fieldManager, $asunto, 'name', 'Asunto');
    relabelField($metadata, $fieldManager, $expediente, 'name', 'Carátula');
    relabelField($metadata, $fieldManager, $actuacion, 'name', 'Título de la actuación');
    relabelField($metadata, $fieldManager, $vencimiento, 'name', 'Vencimiento');
    relabelField($metadata, $fieldManager, $audiencia, 'name', 'Audiencia');
    relabelField($metadata, $fieldManager, $honorario, 'name', 'Concepto');

    $entityFields = [
        $cliente => [
            'clientType' => ['type' => 'enum', 'label' => 'Tipo de cliente', 'options' => ['Persona humana', 'Persona jurídica']],
            'dni' => ['type' => 'varchar', 'label' => 'DNI', 'maxLength' => 30],
            'taxId' => ['type' => 'varchar', 'label' => 'CUIT / CUIL', 'maxLength' => 30],
            'birthDate' => ['type' => 'date', 'label' => 'Fecha de nacimiento'],
            'phoneNumberText' => ['type' => 'varchar', 'label' => 'Teléfono', 'maxLength' => 80],
            'whatsapp' => ['type' => 'varchar', 'label' => 'WhatsApp', 'maxLength' => 80],
            'emailAddressText' => ['type' => 'varchar', 'label' => 'Email', 'maxLength' => 150],
            'addressText' => ['type' => 'text', 'label' => 'Domicilio'],
            'clientStatus' => ['type' => 'enum', 'label' => 'Estado del cliente', 'options' => ['Potencial', 'Activo', 'Inactivo', 'Histórico', 'No aceptar']],
            'intakeDate' => ['type' => 'date', 'label' => 'Fecha de alta'],
            'referredBy' => ['type' => 'varchar', 'label' => 'Referido por', 'maxLength' => 150],
            'driveFolderUrl' => ['type' => 'varchar', 'label' => 'Carpeta de Google Drive', 'maxLength' => 255],
        ],
        $asunto => [
            'internalCode' => ['type' => 'varchar', 'label' => 'Código interno', 'maxLength' => 60],
            'practiceArea' => ['type' => 'enum', 'label' => 'Área de práctica', 'options' => ['Civil','Comercial','Laboral','ART','Penal','Penal Económico','Familia','Sucesiones','Administrativo','Tributario','Consumidor','Societario','Contratos','Real Estate','Daños','Tránsito','Salud','Discapacidad','Investigación patrimonial','Due Diligence','Otros']],
            'matterStatus' => ['type' => 'enum', 'label' => 'Estado', 'options' => ['Consulta','Evaluación','Presupuesto enviado','Pendiente aceptación','Activo','En negociación','Judicializado','Pendiente de resolución','En ejecución','Suspendido','Finalizado','Archivado']],
            'priority' => ['type' => 'enum', 'label' => 'Prioridad', 'options' => ['Baja','Normal','Alta','Urgente']],
            'openingDate' => ['type' => 'date', 'label' => 'Fecha de apertura'],
            'strategy' => ['type' => 'text', 'label' => 'Estrategia'],
            'nextAction' => ['type' => 'text', 'label' => 'Próxima acción'],
            'nextActionDate' => ['type' => 'date', 'label' => 'Fecha próxima acción'],
            'counterparty' => ['type' => 'varchar', 'label' => 'Contraparte', 'maxLength' => 190],
            'feeStatus' => ['type' => 'enum', 'label' => 'Estado de honorarios', 'options' => ['Sin cotizar','Presupuesto enviado','Aceptado','Pago parcial','Pagado','Vencido']],
            'driveFolderUrl' => ['type' => 'varchar', 'label' => 'Carpeta de Google Drive', 'maxLength' => 255],
        ],
        $expediente => [
            'docketNumber' => ['type' => 'varchar', 'label' => 'Número de expediente', 'maxLength' => 100],
            'jurisdiction' => ['type' => 'enum', 'label' => 'Jurisdicción', 'options' => ['Nacional','Federal','CABA','Provincial','Administrativa','Arbitral','Otra']],
            'province' => ['type' => 'enum', 'label' => 'Provincia', 'options' => ['CABA','Buenos Aires','Neuquén','Río Negro','Córdoba','Santa Fe','Mendoza','Otra']],
            'courtArea' => ['type' => 'enum', 'label' => 'Fuero', 'options' => ['Civil','Comercial','Laboral','Penal','Penal Económico','Familia','Contencioso Administrativo','Federal','Otro']],
            'court' => ['type' => 'varchar', 'label' => 'Juzgado / Tribunal', 'maxLength' => 190],
            'clerkOffice' => ['type' => 'varchar', 'label' => 'Secretaría', 'maxLength' => 190],
            'prosecutorOffice' => ['type' => 'varchar', 'label' => 'Fiscalía', 'maxLength' => 190],
            'instance' => ['type' => 'enum', 'label' => 'Instancia', 'options' => ['Primera instancia','Cámara','Casación','Superior Tribunal','Corte Suprema','Otra']],
            'proceedingType' => ['type' => 'varchar', 'label' => 'Tipo de proceso', 'maxLength' => 190],
            'startDate' => ['type' => 'date', 'label' => 'Fecha de inicio'],
            'proceduralStatus' => ['type' => 'enum', 'label' => 'Estado procesal', 'options' => ['Inicio','En trámite','Prueba','Alegatos','Para resolver','Sentencia','Apelación','Ejecución','Suspendido','Finalizado','Archivado']],
            'plaintiff' => ['type' => 'varchar', 'label' => 'Actor / Requirente', 'maxLength' => 190],
            'defendant' => ['type' => 'varchar', 'label' => 'Demandado / Requerido', 'maxLength' => 190],
            'accused' => ['type' => 'varchar', 'label' => 'Imputado', 'maxLength' => 190],
            'complainant' => ['type' => 'varchar', 'label' => 'Querellante / Denunciante', 'maxLength' => 190],
            'courtUrl' => ['type' => 'varchar', 'label' => 'URL de consulta judicial', 'maxLength' => 255],
            'lastDocketDate' => ['type' => 'date', 'label' => 'Fecha última actuación'],
            'nextDocketAction' => ['type' => 'text', 'label' => 'Próxima actuación'],
            'nextDocketDate' => ['type' => 'date', 'label' => 'Fecha próxima actuación'],
        ],
        $actuacion => [
            'actionDate' => ['type' => 'datetime', 'label' => 'Fecha y hora'],
            'actionType' => ['type' => 'enum', 'label' => 'Tipo de actuación', 'options' => ['Escrito presentado','Resolución','Providencia','Cédula / Notificación','Audiencia','Llamada','Reunión','Gestión administrativa','Informe','Otro']],
            'nextAction' => ['type' => 'text', 'label' => 'Próxima acción'],
            'nextActionDate' => ['type' => 'date', 'label' => 'Fecha próxima acción'],
            'generatedDeadline' => ['type' => 'date', 'label' => 'Vencimiento generado'],
        ],
        $vencimiento => [
            'deadlineType' => ['type' => 'enum', 'label' => 'Tipo de vencimiento', 'options' => ['Procesal','Audiencia','Administrativo','Contractual','Pago','Cliente','Interno']],
            'priority' => ['type' => 'enum', 'label' => 'Prioridad', 'options' => ['Baja','Normal','Alta','Crítica']],
        ],
        $audiencia => [
            'hearingType' => ['type' => 'enum', 'label' => 'Tipo de audiencia', 'options' => ['Conciliación','Mediación','Preliminar','Testimonial','Indagatoria','Debate','Vista de causa','Art. 360','Otra']],
            'modality' => ['type' => 'enum', 'label' => 'Modalidad', 'options' => ['Presencial','Virtual','Híbrida']],
            'court' => ['type' => 'varchar', 'label' => 'Juzgado / Organismo', 'maxLength' => 190],
            'addressText' => ['type' => 'varchar', 'label' => 'Dirección', 'maxLength' => 255],
            'meetingUrl' => ['type' => 'varchar', 'label' => 'Enlace de videollamada', 'maxLength' => 255],
            'participants' => ['type' => 'text', 'label' => 'Participantes'],
            'documentsRequired' => ['type' => 'text', 'label' => 'Documentación / preparación'],
        ],
        $honorario => [
            'agreementType' => ['type' => 'enum', 'label' => 'Tipo de acuerdo', 'options' => ['Monto fijo','Por etapas','Por hora','Porcentaje','Cuota litis','UMA','Mixto','Otro']],
            'currencyCode' => ['type' => 'enum', 'label' => 'Moneda / Unidad', 'options' => ['ARS','USD','UMA']],
            'amount' => ['type' => 'float', 'label' => 'Monto'],
            'percentage' => ['type' => 'float', 'label' => 'Porcentaje'],
            'umaAmount' => ['type' => 'float', 'label' => 'Cantidad de UMA'],
            'feeStatus' => ['type' => 'enum', 'label' => 'Estado', 'options' => ['Sin cotizar','Presupuesto enviado','Aceptado','Pago parcial','Pagado','Vencido']],
            'agreementDate' => ['type' => 'date', 'label' => 'Fecha de acuerdo'],
            'dueDate' => ['type' => 'date', 'label' => 'Fecha de vencimiento'],
        ],
    ];

    foreach ($entityFields as $entity => $fields) {
        foreach ($fields as $name => $defs) {
            ensureField($metadata, $fieldManager, $entity, $name, $defs);
        }
    }

    ensureOneToMany($metadata, $linkManager, $cliente, $asunto, 'matters', 'client', 'Asuntos', 'Cliente');
    ensureOneToMany($metadata, $linkManager, $asunto, $expediente, 'dockets', 'matter', 'Expedientes', 'Asunto');
    ensureOneToMany($metadata, $linkManager, $expediente, $actuacion, 'docketActions', 'docket', 'Actuaciones', 'Expediente');
    ensureOneToMany($metadata, $linkManager, $asunto, $vencimiento, 'deadlines', 'matter', 'Vencimientos', 'Asunto');
    ensureOneToMany($metadata, $linkManager, $expediente, $vencimiento, 'deadlines', 'docket', 'Vencimientos', 'Expediente');
    ensureOneToMany($metadata, $linkManager, $asunto, $audiencia, 'hearings', 'matter', 'Audiencias', 'Asunto');
    ensureOneToMany($metadata, $linkManager, $expediente, $audiencia, 'hearings', 'docket', 'Audiencias', 'Expediente');
    ensureOneToMany($metadata, $linkManager, $asunto, $honorario, 'fees', 'matter', 'Honorarios', 'Asunto');
    ensureOneToMany($metadata, $linkManager, $cliente, $honorario, 'fees', 'client', 'Honorarios', 'Cliente');

    out('[*] Reconstruyendo metadata y base de datos...');
    $dataManager->rebuild();

    out('');
    out('=== OK: estructura F&A Legal MVP creada ===');
    out("Entidades: {$cliente}, {$asunto}, {$expediente}, {$actuacion}, {$vencimiento}, {$audiencia}, {$honorario}");
    out('Siguiente paso: revisar layouts, menú y permisos desde Administración.');
} catch (\Throwable $e) {
    fwrite(STDERR, PHP_EOL . '[ERROR] ' . $e::class . ': ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    exit(1);
}
