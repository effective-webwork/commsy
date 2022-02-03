<?php

namespace App\Cron\Tasks;

use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CronHardDeleteArchive implements CronTaskInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    public function __construct(LegacyEnvironment $legacyEnvironment, ParameterBagInterface $parameterBag)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->parameterBag = $parameterBag;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        if (!$this->legacyEnvironment->isArchiveMode()) {
            $this->legacyEnvironment->toggleArchiveMode();
        }

        $itemTypes = [];
        $itemTypes[] = CS_ANNOTATION_TYPE;
        $itemTypes[] = CS_ANNOUNCEMENT_TYPE;
        $itemTypes[] = CS_DATE_TYPE;
        $itemTypes[] = CS_DISCUSSION_TYPE;
        $itemTypes[] = CS_DISCARTICLE_TYPE;
        $itemTypes[] = CS_LINKITEMFILE_TYPE;
        $itemTypes[] = CS_FILE_TYPE;
        $itemTypes[] = CS_ITEM_TYPE;
        $itemTypes[] = CS_LABEL_TYPE;
        $itemTypes[] = CS_LINK_TYPE;
        $itemTypes[] = CS_LINKITEM_TYPE;
        $itemTypes[] = CS_MATERIAL_TYPE;
        $itemTypes[] = CS_ROOM_TYPE;
        $itemTypes[] = CS_SECTION_TYPE;
        $itemTypes[] = CS_TAG_TYPE;
        $itemTypes[] = CS_TAG2TAG_TYPE;
        $itemTypes[] = CS_TASK_TYPE;
        $itemTypes[] = CS_TODO_TYPE;
        $itemTypes[] = CS_USER_TYPE;

        $deleteDays = $this->parameterBag->get('commsy.settings.delete_days');
        if (!empty($deleteDays) && is_numeric($deleteDays)) {
            foreach ($itemTypes as $itemType) {
                $manager = $this->legacyEnvironment->getManager($itemType);
                $manager->deleteReallyOlderThan($deleteDays);
            }
        }

        if ($this->legacyEnvironment->isArchiveMode()) {
            $this->legacyEnvironment->toggleArchiveMode();
        }
    }

    public function getSummary(): string
    {
        return 'Finally delete soft deleted archived items';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}