<?php

declare(strict_types=1);

/**
 * F&A Legal - UI/Menu/Layout bootstrap for EspoCRM 10.0.x
 * Configures application name, legal navigation, quick-create, search,
 * calendar entities and practical layouts for the MVP.
 */

require_once __DIR__ . '/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Metadata;

$app = new Application();
$container = $app->getContainer();

/** @var InjectableFactory $factory */
$factory = $container->getByClass(InjectableFactory::class);
/** @var Metadata $metadata */
$metadata = $container->getByClass(Metadata::class);
/** @var DataManager $dataManager */
$dataManager = $container->getByClass(DataManager::class);
/** @var ConfigWriter $configWriter */
$configWriter = $factory->create(ConfigWriter::class);

function out(string $s): void { echo $s . PHP_EOL; }

function existingEntity(Metadata $metadata, string $name): bool {
    return (bool) $metadata->get(['scopes', $name]);
}

function divider(string $id, string $text): object {
    return (object) [
        'type' => 'divider',
        'id' => $id,
        'text' => $text,
    ];
}

function field(string $name, array $extra = []): array {
    return array_merge(['name' => $name], $extra);
}

function row(string|array|false $left, string|array|false $right = false): array {
    $normalize = static function ($v) {
        if ($v === false) {
            return false;
        }
        if (is_string($v)) {
            return ['name' => $v];
        }
        return $v;
    };
    return [$normalize($left), $normalize($right)];
}

function panel(string $label, array $rows): array {
    return ['label' => $label, 'rows' => $rows];
}

function writeJson(string $path, mixed $data): void {
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("No se pudo crear $dir");
    }
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false || file_put_contents($path, $json . PHP_EOL) === false) {
        throw new RuntimeException("No se pudo escribir $path");
    }
    out("[+] Layout: $path");
}

function onlyExistingFields(Metadata $metadata, string $entity, array $items): array {
    return array_values(array_filter($items, static function ($item) use ($metadata, $entity) {
        if (!is_array($item) || !isset($item['name'])) return false;
        return (bool) $metadata->get(['entityDefs', $entity, 'fields', $item['name']]);
    }));
}

function filterDetail(Metadata $metadata, string $entity, array $panels): array {
    $out = [];
    foreach ($panels as $panel) {
        $rows = [];
        foreach ($panel['rows'] as $r) {
            $new = [];
            foreach ($r as $cell) {
                if ($cell === false) {
                    $new[] = false;
                    continue;
                }
                $name = $cell['name'] ?? null;
                if ($name && $metadata->get(['entityDefs', $entity, 'fields', $name])) {
                    $new[] = $cell;
                } else {
                    $new[] = false;
                }
            }
            if ($new[0] !== false || $new[1] !== false) {
                $rows[] = $new;
            }
        }
        if ($rows) {
            $out[] = ['label' => $panel['label'], 'rows' => $rows];
        }
    }
    return $out;
}

try {
    out("=== F&A Legal | Configuración visual MVP ===");

    $entities = [
        'CCliente', 'CAsunto', 'CExpediente', 'CActuacion',
        'CVencimiento', 'CAudiencia', 'CHonorario'
    ];

    foreach ($entities as $entity) {
        if (!existingEntity($metadata, $entity)) {
            throw new RuntimeException("No existe la entidad requerida: $entity. Ejecute primero el bootstrap del MVP.");
        }
    }

    // 1) Branding and navigation.
    $configWriter->set('applicationName', 'F&A Legal');

    $tabList = [
        divider('fa1001', 'F&A Legal'),
        'CCliente',
        'CAsunto',
        'CExpediente',

        divider('fa1002', 'Gestión Jurídica'),
        'CActuacion',
        'CVencimiento',
        'CAudiencia',
        'Task',
        'Calendar',

        divider('fa1003', 'Administración'),
        'CHonorario',
        'Document',
        'Email',

        '_delimiter_',

        divider('fa1004', 'Sistema'),
        'User',
        'Team',
        'Import',
    ];

    $configWriter->set('tabList', $tabList);
    $configWriter->set('quickCreateList', [
        'CCliente',
        'CAsunto',
        'CExpediente',
        'CActuacion',
        'CVencimiento',
        'CAudiencia',
        'CHonorario',
        'Task',
    ]);
    $configWriter->set('globalSearchEntityList', [
        'CCliente',
        'CAsunto',
        'CExpediente',
    ]);
    $configWriter->set('calendarEntityList', [
        'CAudiencia',
        'CVencimiento',
        'Task',
    ]);
    $configWriter->set('busyRangesEntityList', [
        'CAudiencia',
    ]);

    $configWriter->save();
    out('[+] Nombre y menú jurídico configurados.');

    // 2) Practical detail layouts.
    $detail = [];

    $detail['CCliente'] = [
        panel('Datos del cliente', [
            row('name', 'clientType'),
            row('dni', 'taxId'),
            row('clientStatus', 'intakeDate'),
            row('phoneNumberText', 'whatsapp'),
            row('emailAddressText', 'birthDate'),
            row('referredBy', 'assignedUser'),
            row(['name' => 'addressText', 'fullWidth' => true], false),
            row(['name' => 'driveFolderUrl', 'fullWidth' => true], false),
            row(['name' => 'description', 'fullWidth' => true], false),
        ]),
    ];

    $detail['CAsunto'] = [
        panel('Identificación', [
            row('name', 'internalCode'),
            row('client', 'matterStatus'),
            row('practiceArea', 'priority'),
            row('openingDate', 'assignedUser'),
            row('counterparty', 'feeStatus'),
        ]),
        panel('Gestión y estrategia', [
            row('nextActionDate', false),
            row(['name' => 'strategy', 'fullWidth' => true], false),
            row(['name' => 'nextAction', 'fullWidth' => true], false),
            row(['name' => 'driveFolderUrl', 'fullWidth' => true], false),
            row(['name' => 'description', 'fullWidth' => true], false),
        ]),
    ];

    $detail['CExpediente'] = [
        panel('Identificación judicial', [
            row('name', 'docketNumber'),
            row('matter', 'proceduralStatus'),
            row('jurisdiction', 'province'),
            row('courtArea', 'instance'),
            row('court', 'clerkOffice'),
            row('prosecutorOffice', 'proceedingType'),
            row('startDate', 'assignedUser'),
        ]),
        panel('Partes y seguimiento', [
            row('plaintiff', 'defendant'),
            row('accused', 'complainant'),
            row('lastDocketDate', 'nextDocketDate'),
            row(['name' => 'nextDocketAction', 'fullWidth' => true], false),
            row(['name' => 'courtUrl', 'fullWidth' => true], false),
            row(['name' => 'description', 'fullWidth' => true], false),
        ]),
    ];

    $detail['CActuacion'] = [
        panel('Actuación', [
            row('name', 'docket'),
            row('actionDate', 'actionType'),
            row('generatedDeadline', 'nextActionDate'),
            row('assignedUser', false),
            row(['name' => 'nextAction', 'fullWidth' => true], false),
            row(['name' => 'description', 'fullWidth' => true], false),
        ]),
    ];

    $detail['CVencimiento'] = [
        panel('Vencimiento', [
            row('name', 'deadlineType'),
            row('matter', 'docket'),
            row('dateStart', 'dateEnd'),
            row('priority', 'status'),
            row('assignedUser', 'reminders'),
            row(['name' => 'description', 'fullWidth' => true], false),
        ]),
    ];

    $detail['CAudiencia'] = [
        panel('Audiencia', [
            row('name', 'hearingType'),
            row('matter', 'docket'),
            row('dateStart', 'dateEnd'),
            row('modality', 'status'),
            row('court', 'assignedUser'),
            row(['name' => 'addressText', 'fullWidth' => true], false),
            row(['name' => 'meetingUrl', 'fullWidth' => true], false),
            row(['name' => 'participants', 'fullWidth' => true], false),
            row(['name' => 'documentsRequired', 'fullWidth' => true], false),
            row(['name' => 'description', 'fullWidth' => true], false),
        ]),
    ];

    $detail['CHonorario'] = [
        panel('Honorarios', [
            row('name', 'agreementType'),
            row('client', 'matter'),
            row('currencyCode', 'amount'),
            row('percentage', 'umaAmount'),
            row('feeStatus', 'agreementDate'),
            row('dueDate', 'assignedUser'),
            row(['name' => 'description', 'fullWidth' => true], false),
        ]),
    ];

    // 3) List layouts.
    $lists = [
        'CCliente' => [
            field('name', ['link' => true]),
            field('clientStatus'),
            field('taxId'),
            field('phoneNumberText'),
            field('assignedUser'),
        ],
        'CAsunto' => [
            field('name', ['link' => true]),
            field('internalCode'),
            field('client'),
            field('practiceArea'),
            field('matterStatus'),
            field('nextActionDate'),
            field('assignedUser'),
        ],
        'CExpediente' => [
            field('name', ['link' => true]),
            field('docketNumber'),
            field('matter'),
            field('court'),
            field('proceduralStatus'),
            field('nextDocketDate'),
            field('assignedUser'),
        ],
        'CActuacion' => [
            field('actionDate'),
            field('name', ['link' => true]),
            field('docket'),
            field('actionType'),
            field('nextActionDate'),
            field('assignedUser'),
        ],
        'CVencimiento' => [
            field('dateStart'),
            field('name', ['link' => true]),
            field('deadlineType'),
            field('matter'),
            field('docket'),
            field('priority'),
            field('assignedUser'),
        ],
        'CAudiencia' => [
            field('dateStart'),
            field('name', ['link' => true]),
            field('hearingType'),
            field('matter'),
            field('docket'),
            field('modality'),
            field('assignedUser'),
        ],
        'CHonorario' => [
            field('name', ['link' => true]),
            field('client'),
            field('matter'),
            field('agreementType'),
            field('currencyCode'),
            field('amount'),
            field('feeStatus'),
            field('dueDate'),
        ],
    ];

    $relationships = [
        'CCliente' => ['matters', 'fees'],
        'CAsunto' => ['dockets', 'deadlines', 'hearings', 'fees'],
        'CExpediente' => ['docketActions', 'deadlines', 'hearings'],
    ];

    foreach ($entities as $entity) {
        $base = "custom/Espo/Custom/Resources/layouts/$entity";

        $d = filterDetail($metadata, $entity, $detail[$entity] ?? []);
        writeJson("$base/detail.json", $d);
        writeJson("$base/detailSmall.json", $d);

        $list = onlyExistingFields($metadata, $entity, $lists[$entity] ?? []);
        writeJson("$base/list.json", $list);
        writeJson("$base/listSmall.json", array_slice($list, 0, 4));

        if (isset($relationships[$entity])) {
            $rels = array_values(array_filter(
                $relationships[$entity],
                static fn(string $link): bool =>
                    (bool) $metadata->get(['entityDefs', $entity, 'links', $link])
            ));
            writeJson("$base/relationships.json", $rels);
        }
    }

    out('[*] Reconstruyendo metadata y limpiando caché...');
    $dataManager->rebuild();

    out('');
    out('=== OK: interfaz F&A Legal configurada ===');
    out('Menú, nombre de aplicación, búsqueda, calendario y layouts aplicados.');
    out('Actualice el navegador con Ctrl+F5.');
} catch (\Throwable $e) {
    fwrite(STDERR, PHP_EOL . '[ERROR] ' . $e::class . ': ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    exit(1);
}
