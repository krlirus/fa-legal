<?php
declare(strict_types=1);

namespace Espo\Custom\Hooks\CVencimiento;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;
use stdClass;

class LegalReminders implements BeforeSave
{
    public static int $order = 8;

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew()) {
            return;
        }

        $existing = $entity->get('reminders');

        if (is_array($existing) && count($existing) > 0) {
            return;
        }

        $entity->set('reminders', [
            $this->item(604800),
            $this->item(172800),
            $this->item(86400),
        ]);
    }

    private function item(int $seconds): stdClass
    {
        return (object) [
            'type' => 'Popup',
            'seconds' => $seconds,
        ];
    }
}
