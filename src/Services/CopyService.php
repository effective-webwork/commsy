<?php

namespace App\Services;

use App\Utils\ItemService;
use App\Utils\RoomService;
use cs_environment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CopyService
{
    private cs_environment $legacyEnvironment;

    private ItemService $itemService;

    private SessionInterface $session;

    private $type;

    /**
     * CopyService constructor.
     * @param \App\Services\LegacyEnvironment $legacyEnvironment
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param SessionInterface $session
     */
    public function __construct(LegacyEnvironment $legacyEnvironment, ItemService $itemService, SessionInterface $session)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->session = $session;
        $this->type = false;
    }

    public function getCountArray($roomId)
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);

        if ($this->type) {
            $itemsCountArray['count'] = sizeof($this->getListEntries($roomId));
        } else {
            $itemsCountArray['count'] = sizeof($currentClipboardIds);
        }

        $itemsCountArray['countAll'] = sizeof($currentClipboardIds);

        return $itemsCountArray;
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @return \cs_item[]
     */
    public function getListEntries($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);

        $entries = [];
        $counter = 0;
        foreach ($currentClipboardIds as $currentClipboardId) {
            if (!$start) {
                $start = 0;
            }
            if (!$max) {
                $max = count($currentClipboardIds);
            }
            if ($counter >= $start && $counter < $start + $max) {
                $typedItem = $this->itemService->getTypedItem($currentClipboardId);
                if ($this->type) {
                    if ($typedItem->getItemType() == $this->type) {
                        $entries[] = $typedItem;
                    }
                } else {
                    $entries[] = $typedItem;
                }
            }
            $counter++;
        }

        return $entries;
    }

    /**
     * @param integer $roomId
     * @param integer[] $ids
     * @return \cs_item[]
     */
    public function getCopiesById($roomId, $ids)
    {
        $allCopies = $this->getListEntries($roomId);

        $filteredCopies = [];
        foreach ($allCopies as $copy) {
            if (in_array($copy->getItemID(), $ids)) {
                $filteredCopies[] = $copy;
            }
        }

        return $filteredCopies;
    }

    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        if ($formData['type']) {
            $this->type = $formData['type'];
        }
    }

    public function removeEntries($roomId, $entries)
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);

        $clipboardIds = [];
        foreach ($currentClipboardIds as $currentClipboardId) {
            if (!in_array($currentClipboardId, $entries)) {
                $clipboardIds[] = $currentClipboardId;
            }
        }

        $this->session->set('clipboard_ids', $clipboardIds);

        return $this->getCountArray($roomId);
    }
}