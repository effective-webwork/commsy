<?php

namespace App\Feed;

use App\Feed\Creators\Creator;

use App\Utils\ItemService;
use App\Services\LegacyEnvironment;

use cs_environment;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedCreatorFactory
{
    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    private $creators = [];
    private $isGuestAccess = false;

    public function __construct(
        ItemService $itemService,
        LegacyEnvironment $legacyEnvironment,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->itemService = $itemService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->router = $router;
    }

    public function addCreator(Creator $creator)
    {
        $creator->setTextConverter($this->legacyEnvironment->getTextConverter());
        $creator->setTranslator($this->translator);
        $creator->setRouter($this->router);

        $this->creators[] = $creator;
    }

    public function setGuestAccess($isGuestAccess)
    {
        $this->isGuestAccess = $isGuestAccess;
    }

    public function createItem($item) {
        $type = $item['type'];
        $commsyItem = $this->itemService->getTypedItem($item['item_id']);

        if (!$commsyItem) {
            return;
        }

        if ($commsyItem->getType() === 'label') {
            $type = $commsyItem->getLabelType();
            if (in_array($type, ['buzzword'])) {
                return;
            }

            $manager = $this->legacyEnvironment->getManager($type);
            $commsyItem = $manager->getItem($commsyItem->getItemId());
        }

        $creator = $this->findAccurateCreator($type);
        $creator->setGuestAccess($this->isGuestAccess);
        
        $feedItem = $creator->createItem($commsyItem);

        return $feedItem;
    }

    private function findAccurateCreator($rubric)
    {
        foreach ($this->creators as $creator) {
            if ($creator->canCreate($rubric)) {
                return $creator;
            }
        }

        throw new \RuntimeException('No creator found that supports the rubric "' . $rubric . '"');
    }
}