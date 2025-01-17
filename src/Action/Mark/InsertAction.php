<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.07.18
 * Time: 14:35
 */

namespace App\Action\Mark;


use App\Http\JsonDataResponse;
use App\Http\JsonErrorResponse;
use App\Services\MarkedService;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class InsertAction
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * @var MarkedService
     */
    private MarkedService $markService;

    public function __construct(
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        MarkedService $markService
    ) {
        $this->translator = $translator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->markService = $markService;
    }

    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        if ($this->legacyEnvironment->isArchiveMode()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items in archived workspaces is not allowed'));
        }

        if ($this->legacyEnvironment->inPortal()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items in portal is not allowed'));
        }

        if ($this->legacyEnvironment->getCurrentUserItem()->isOnlyReadUser()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>' . $this->translator->trans('copy items as read only user is not allowed'));
        }

        if($roomItem->getType() == 'userroom'){
            $imports = $this->markService->getListEntries(0);

            foreach ($items as $user) {
                /** @var \cs_user_item $user */
                //$userRoom = $user->getLinkedUserroomItem();

                foreach ($imports as $import) {
                    /** @var \cs_item $import */
                    $import = $this->itemService->getTypedItem($import->getItemId());

                    $oldContextId = $this->legacyEnvironment->getCurrentContextID();
                    $this->legacyEnvironment->setCurrentContextID($roomItem->getItemID());
                    $copy = $import->copy();
                    $this->legacyEnvironment->setCurrentContextID($oldContextId);

                    if (empty($copy->getErrorArray())) {
                        $readerManager = $this->legacyEnvironment->getReaderManager();
                        $readerManager->markRead($copy->getItemID(), $copy->getVersionID());
                        $noticedManager = $this->legacyEnvironment->getNoticedManager();
                        $noticedManager->markNoticed($copy->getItemID(), $copy->getVersionID());
                    }
                }
            }
        }else{
            foreach ($items as $item) {
                // archive
                $toggleArchive = false;
                if ($item->isArchived() and !$this->legacyEnvironment->isArchiveMode()) {
                    $toggleArchive = true;
                    $this->legacyEnvironment->toggleArchiveMode();
                }

                // archive
                $importItem = $this->itemService->getTypedItem($item->getItemId());

                // archive
                if ($toggleArchive) {
                    $this->legacyEnvironment->toggleArchiveMode();
                }
                $importItem->setExternalViewerAccounts(array());
                // archive
                $copy = $importItem->copy();

                if (empty($copy->getErrorArray())) {
                    $readerManager = $this->legacyEnvironment->getReaderManager();
                    $readerManager->markRead($copy->getItemID(), $copy->getVersionID());
                    $noticedManager = $this->legacyEnvironment->getNoticedManager();
                    $noticedManager->markNoticed($copy->getItemID(), $copy->getVersionID());
                }
            }
        }



        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-paste\'></i> ' . $this->translator->trans('inserted %count% entries in this room',[
                '%count%' => count($items),
            ]),
        ]);
    }
}