<?php

declare(strict_types=1);

/**
 * F&A Legal - Ajuste integral de formularios MVP v2
 *
 * - Elimina "Usuario Asignado" duplicado del cuerpo principal.
 * - Lo conserva en el panel lateral nativo de EspoCRM.
 * - Ajusta etiquetas genéricas "Descripción" editando i18n custom directamente.
 * - No borra registros ni relaciones.
 */

require_once __DIR__ . '/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\DataManager;

$app = new Application();
$container = $app->getContainer();

/** @var DataManager $dataManager */
$dataManager = $container->getByClass(DataManager::class);

$entities = [
    'CCliente',
    'CAsunto',
    'CExpediente',
    'CActuacion',
    'CVencimiento',
    'CAudiencia',
    'CHonorario',
];

$descriptionLabels = [
    'CCliente' => 'Notas del cliente',
    'CAsunto' => 'Notas internas',
    'CExpediente' => 'Observaciones del expediente',
    'CActuacion' => 'Detalle / Observaciones',
    'CVencimiento' => 'Observaciones',
    'CAudiencia' => 'Observaciones',
    'CHonorario' => 'Observaciones',
];

function out(string $message): void
{
    echo $message . PHP_EOL;
}

function removeAssignedUserFromLayout(string $path): void
{
    if (!is_file($path)) {
        out("[!] No existe: $path");
        return;
    }

    $raw = file_get_contents($path);

    if ($raw === false) {
        throw new RuntimeException("No se pudo leer: $path");
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        throw new RuntimeException("JSON inválido: $path");
    }

    foreach ($data as &$panel) {
        if (!is_array($panel) || !isset($panel['rows']) || !is_array($panel['rows'])) {
            continue;
        }

        $rows = [];

        foreach ($panel['rows'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $newRow = [];

            foreach ($row as $cell) {
                if (is_array($cell) && ($cell['name'] ?? null) === 'assignedUser') {
                    $newRow[] = false;
                } else {
                    $newRow[] = $cell;
                }
            }

            while (count($newRow) < 2) {
                $newRow[] = false;
            }

            $newRow = array_slice($newRow, 0, 2);

            if (($newRow[0] ?? false) !== false || ($newRow[1] ?? false) !== false) {
                $rows[] = $newRow;
            }
        }

        $panel['rows'] = $rows;
    }
    unset($panel);

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false || file_put_contents($path, $json . PHP_EOL) === false) {
        throw new RuntimeException("No se pudo escribir: $path");
    }

    out("[+] Layout ajustado: $path");
}

function changeFieldLabelInI18n(string $entity, string $field, string $label): void
{
    $pattern = "custom/Espo/Custom/Resources/i18n/*/$entity.json";
    $files = glob($pattern) ?: [];

    foreach ($files as $file) {
        $raw = file_get_contents($file);

        if ($raw === false) {
            continue;
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            continue;
        }

        $data['fields'] ??= [];
        $data['fields'][$field] = $label;

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json !== false) {
            file_put_contents($file, $json . PHP_EOL);
            out("[+] Etiqueta ajustada: $file -> $field = $label");
        }
    }
}

try {
    out("==============================================");
    out(" F&A Legal - Ajuste integral formularios v2");
    out("==============================================");
    out("");

    foreach ($entities as $entity) {
        $base = "custom/Espo/Custom/Resources/layouts/$entity";

        removeAssignedUserFromLayout("$base/detail.json");
        removeAssignedUserFromLayout("$base/detailSmall.json");
    }

    out("");
    out("[*] Ajustando etiquetas jurídicas...");

    foreach ($descriptionLabels as $entity => $label) {
        changeFieldLabelInI18n($entity, 'description', $label);
    }

    out("");
    out("[*] Reconstruyendo metadata y caché...");
    $dataManager->rebuild();

    out("");
    out("=== OK: ajuste integral aplicado ===");
    out("Usuario Asignado queda únicamente en el panel lateral.");
    out("Las etiquetas de notas/observaciones fueron normalizadas.");
    out("No se borraron datos ni relaciones.");
    out("Actualice F&A Legal con Ctrl+F5.");

} catch (\Throwable $e) {
    fwrite(STDERR, PHP_EOL . "[ERROR] " . $e::class . ": " . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    exit(1);
}
