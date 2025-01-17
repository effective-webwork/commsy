<?php

namespace App\Controller;

use App\Event\CommsyEditEvent;
use App\Form\DataTransformer\ItemTransformer;
use App\Form\DataTransformer\TransformerManager;
use App\Form\Model\Send;
use App\Form\Type\ItemCatsBuzzType;
use App\Form\Type\ItemDescriptionType;
use App\Form\Type\ItemLinksType;
use App\Form\Type\ItemWorkflowType;
use App\Form\Type\SendListType;
use App\Form\Type\SendType;
use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\DateService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\MailAssistant;
use App\Utils\MaterialService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_dates_item;
use cs_item;
use cs_manager;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Class ItemController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class ItemController extends AbstractController
{
    /**
     * @var LabelService
     */
    private LabelService $labelService;

    /**
     * @var TransformerManager
     */
    private TransformerManager $transformerManager;

    /**
     * @required
     * @param mixed $transformerManager
     */
    public function setTransformerManager(TransformerManager $transformerManager): void
    {
        $this->transformerManager = $transformerManager;
    }

    /**
     * @param LabelService $labelService
     */
    public function __construct(LabelService $labelService)
    {
        $this->labelService = $labelService;
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/editdescription/{draft}")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param DateService $dateService
     * @param ItemService $itemService
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyEnvironment $environment
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @param bool $draft
     * @return array|RedirectResponse
     */
    public function editDescriptionAction(
        DateService $dateService,
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        Request $request,
        int $roomId,
        int $itemId,
        bool $draft = false
    ) {
        /** @var cs_item $item */
        $item = $itemService->getTypedItem($itemId);

        $transformer = $this->transformerManager->getConverter($item->getItemType());

        $itemType = $item->getItemType();

        // NOTE: we disable the CommSy-related & MathJax toolbar items for users & groups, so their CKEEditor controls
        // won't allow any media upload; this is done since user & group detail views currently have no means to manage
        // (e.g. delete again) any attached files
        $configName = ($itemType === 'user' || $itemType === 'group') ? 'cs_item_nomedia_config' : 'cs_item_config' ;

        $url = $this->generateUrl('app_upload_ckupload', array(
            'roomId' => $roomId,
            'itemId' => $itemId
        ));
        $url .= '?CKEditorFuncNum=42&command=QuickUpload&type=Images';

        $formData = $transformer->transform($item);
        $formOptions = array(
            'itemId' => $itemId,
            'configName' => $configName,
            'uploadUrl' => $url,
            'filelistUrl' => $this->generateUrl('app_item_filelist', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        );
        
        $withRecurrence = false;
        if ($itemType == 'date') {
            /** @var cs_dates_item $item */
            if ($item->getRecurrencePattern() != '' && !$draft) {
                $formOptions['attr']['unsetRecurrence'] = true;
                $withRecurrence = true;
            }
        }

        if (in_array($item->getItemType(), [CS_SECTION_TYPE, CS_STEP_TYPE, CS_DISCARTICLE_TYPE])) {
            $eventDispatcher->dispatch(new CommsyEditEvent($item->getLinkedItem()), CommsyEditEvent::EDIT);
        } else {
            $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);
        }

        $form = $this->createForm(ItemDescriptionType::class, $formData, $formOptions);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            $legacyEnvironment = $environment->getEnvironment();
            if ($saveType == 'save' || $saveType == 'saveThisDate') {
                $item = $transformer->applyTransformation($item, $form->getData());
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();
                if (($item->getItemType() == CS_SECTION_TYPE) || ($item->getItemType() == CS_STEP_TYPE)) {
                    $linkedItem = $itemService->getTypedItem($item->getlinkedItemID());
                    $linkedItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                    $linkedItem->save();
                }
            } else if ($saveType == 'saveAllDates') {
                $datesArray = $dateService->getRecurringDates($item->getContextId(), $item->getRecurrenceId());
                $formData = $form->getData();
                $item = $transformer->applyTransformation($item, $formData);
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();
                foreach ($datesArray as $tempDate) {
                    $tempDate->setDescription($item->getDescription());
                    $tempDate->save();
                }
            } else {
                throw new \UnexpectedValueException("Value must be one of 'save', 'saveThisDate' and 'saveAllDates'.");
            }

            return $this->redirectToRoute('app_item_savedescription', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        // etherpad
        $isMaterial = false;
        if ($itemType == "material") {
            $isMaterial = true;
        }

        return array(
            'isMaterial' => $isMaterial,
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form->createView(),
            'withRecurrence' => $withRecurrence,
        );
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/savedescription")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param ItemService $itemService
     * @param EventDispatcherInterface $eventDispatcher
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveDescriptionAction(
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getTypedItem($itemId);
        $itemArray = array($item);
    
        $modifierList = array();
        foreach ($itemArray as $tempItem) {
            $modifierList[$tempItem->getItemId()] = $itemService->getAdditionalEditorsForItem($tempItem);
        }

        if (in_array($item->getItemType(), [CS_SECTION_TYPE, CS_STEP_TYPE, CS_DISCARTICLE_TYPE])) {
            $eventDispatcher->dispatch(new CommsyEditEvent($item->getLinkedItem()), CommsyEditEvent::SAVE);
        } else {
            $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);
        }

        return array(
            // etherpad subscriber (material save)
            // important: save and item->id parameter are needed
            'save' => true,
            'roomId' => $roomId,
            'item' => $item,
            'modifierList' => $modifierList
        );
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/editworkflow")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param MaterialService $materialService
     * @param ItemTransformer $transformer
     * @param LegacyEnvironment $environment
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editWorkflowAction(
        RoomService $roomService,
        ItemService $itemService,
        MaterialService $materialService,
        ItemTransformer $transformer,
        LegacyEnvironment $environment,
        Request $request,
        int $roomId,
        int $itemId
    ) {
        $room = $roomService->getRoomItem($roomId);
        $item = $itemService->getItem($itemId);

        $formData = array();
        $tempItem = NULL;
        
        if ($item->getItemType() == 'material') {
            // get material from MaterialService
            $tempItem = $materialService->getMaterial($itemId);
            if (!$tempItem) {
                throw $this->createNotFoundException('No material found for id ' . $roomId);
            }
            $formData = $transformer->transform($tempItem);
        }
        
        $form = $this->createForm(ItemWorkflowType::class, $formData, array());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $legacyEnvironment = $environment->getEnvironment();
                $tempItem = $transformer->applyTransformation($tempItem, $form->getData());
                $tempItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $tempItem->save();
            }
            
            return $this->redirectToRoute('app_material_saveworkflow', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $workflowData['textGreen'] = $room->getWorkflowTrafficLightTextGreen();
        $workflowData['textYellow'] = $room->getWorkflowTrafficLightTextYellow();
        $workflowData['textRed'] = $room->getWorkflowTrafficLightTextRed();
        $workflowData['withTrafficLight'] = $room->withWorkflowTrafficLight();
        $workflowData['withResubmission'] = $room->withWorkflowResubmission();
        $workflowData['workflowValidity'] = $room->withWorkflowValidity();


        return array(
            'item' => $tempItem,
            'form' => $form->createView(),
            'workflow' => $workflowData
        );
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/editlinks/{feedAmount}", defaults={"feedAmount" = 20})
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyEnvironment $environment
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @param int $feedAmount
     * @return array|RedirectResponse
     */
    public function editLinksAction(
        LabelService $labelService,
        RoomService $roomService,
        ItemService $itemService,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        Request $request,
        int $roomId,
        int $itemId,
        int $feedAmount
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        $item = $itemService->getTypedItem($itemId);
        $roomItem = $roomService->getRoomItem($roomId);

        $current_context = $legacyEnvironment->getCurrentContextItem();

        $formData = array();
        $optionsData = array();
        $items = array();
        
        // get all items that are linked or can be linked
        $rubricInformation = $roomService->getRubricInformation($roomId);
        if (in_array('group', $rubricInformation)) {
            $rubricInformation[] = 'label';
        }

        $optionsData['filterRubric']['all'] = 'all';
        foreach ($rubricInformation as $rubric) {
            $optionsData['filterRubric'][$rubric] = $rubric;
        }

        $optionsData['filterPublic']['public'] = 'public';
        $optionsData['filterPublic']['all'] = 'all';

        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);
        $itemManager->setTypeArrayLimit($rubricInformation);

        // get all linked items
        $itemLinkedList = $itemManager->getItemList($item->getAllLinkedItemIDArray());
        $tempLinkedItem = $itemLinkedList->getFirst();
        while ($tempLinkedItem) {
            $tempTypedLinkedItem = $itemService->getTypedItem($tempLinkedItem->getItemId());
            if ($tempTypedLinkedItem->getItemType() != 'user') {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getTitle();
                $items[$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem;
            } else {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getFullname();
                $items[$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem;
            }
            $tempLinkedItem = $itemLinkedList->getNext();
        }
        if (empty($optionsData['itemsLinked'])) {
            $optionsData['itemsLinked'] = [];
        }
        // add number of linked items to feed amount
        $countLinked = count($optionsData['itemsLinked']);

        $itemManager->setIntervalLimit($feedAmount + $countLinked);
        $itemManager->select();
        $itemList = $itemManager->get();
        
        // get all items except linked items
        $optionsData['items'] = [];
        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $itemService->getTypedItem($tempItem->getItemId());
            // skip already linked items
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                $items[$tempTypedItem->getItemId()] = $tempTypedItem;
            }
            $tempItem = $itemList->getNext();
            
        }

        $linkedItemIds = $item->getAllLinkedItemIDArray();
        foreach ($linkedItemIds as $linkedId) {
            $formData['itemsLinked'][$linkedId] = true;
        }

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($legacyEnvironment->getCurrentUser()->getUserId());
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $i = 0;
        $latestItem = $latestItemList->getFirst();
        while ($latestItem && $i < 5) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                if (
                    $tempTypedItem->getType() != "discarticle" &&
                    $tempTypedItem->getType() != "task" &&
                    $tempTypedItem->getType() != 'link_item' &&
                    $tempTypedItem->getType() != 'tag' &&
                    $tempTypedItem->getType() != 'step'
                ) {
                    $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                    $i++;
                }
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = [];
        }

        // get all categories -> tree
        $optionsData['categories'] = $labelService->getCategories($roomId);
        $formData['categories'] = $labelService->getLinkedCategoryIds($item);
        $categoryConstraints = ($current_context->withTags() && $current_context->isTagMandatory()) ? [new Count(array('min' => 1))] : array();

        // get all hashtags -> list
        $optionsData['hashtags'] = $labelService->getHashtags($roomId);
        $formData['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
        $hashtagConstraints = ($current_context->withBuzzwords() && $current_context->isBuzzwordMandatory()) ? [new Count(array('min' => 1))] : [];

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

        $form = $this->createForm(ItemLinksType::class, $formData, [
            'filterRubric' => $optionsData['filterRubric'],
            'filterPublic' => $optionsData['filterPublic'],
            'items' => $optionsData['items'],
            'itemsLinked' => array_flip($optionsData['itemsLinked']),
            'itemsLatest' => array_flip($optionsData['itemsLatest']),
            'categories' => $optionsData['categories'],
            'categoryConstraints' => array(),
            'hashtags' => $optionsData['hashtags'],
            'hashtagConstraints' => array(),
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            'placeholderText' => $translator->trans('Hashtag', [], 'hashtag'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
             if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                $itemData = array_merge(array_keys($data['itemsLinked']), $data['itemsLatest']);

                // update modifier
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // save links
                $item->setLinkedItemsByIDArray($itemData);
                $item->setTagListByID($data['categories']);
                $item->setBuzzwordListByID($data['hashtags']);

                if ($item->getItemType() == CS_TOPIC_TYPE) {
                    if (empty($itemData)) {
                        $item->deactivatePath();
                    }
                }

                // persist
                $item->save();
            }

            return $this->redirectToRoute('app_item_savelinks', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form->createView(),
            'showCategories' => $roomItem->withTags(),
            'showHashtags' => $roomItem->withBuzzwords(),
            'items' => $items,
            'itemsLatest' => $optionsData['itemsLatest'],
        ];
    }


    /**
     * @Route("/room/{roomId}/item/{itemId}/editCatsBuzz/{feedAmount}", defaults={"feedAmount" = 20})
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param CategoryService $categoryService
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $eventDispatcher
     * @param LegacyEnvironment $environment
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @param int $feedAmount
     * @return array|RedirectResponse
     */
    public function editCatsBuzzAction(
        CategoryService $categoryService,
        LabelService $labelService,
        RoomService $roomService,
        ItemService $itemService,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        Request $request,
        int $roomId,
        int $itemId,
        int $feedAmount
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        $item = $itemService->getTypedItem($itemId);
        $roomItem = $roomService->getRoomItem($roomId);

        $current_context = $legacyEnvironment->getCurrentContextItem();

        $formData = array();
        $optionsData = array();
        $items = array();

        // get all items that are linked or can be linked
        $rubricInformation = $roomService->getRubricInformation($roomId);
        if (in_array('group', $rubricInformation)) {
            $rubricInformation[] = 'label';
        }

        $optionsData['filterRubric']['all'] = 'all';
        foreach ($rubricInformation as $rubric) {
            $optionsData['filterRubric'][$rubric] = $rubric;
        }

        $optionsData['filterPublic']['public'] = 'public';
        $optionsData['filterPublic']['all'] = 'all';

        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);
        $itemManager->setTypeArrayLimit($rubricInformation);

        // get all linked items
        $itemLinkedList = $itemManager->getItemList($item->getAllLinkedItemIDArray());
        $tempLinkedItem = $itemLinkedList->getFirst();
        while ($tempLinkedItem) {
            $tempTypedLinkedItem = $itemService->getTypedItem($tempLinkedItem->getItemId());
            if ($tempTypedLinkedItem->getItemType() != 'user') {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getTitle();
                $items[$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem;
            } else {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getFullname();
                $items[$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem;
            }
            $tempLinkedItem = $itemLinkedList->getNext();
        }
        if (empty($optionsData['itemsLinked'])) {
            $optionsData['itemsLinked'] = [];
        }
        // add number of linked items to feed amount
        $countLinked = count($optionsData['itemsLinked']);

        $itemManager->setIntervalLimit($feedAmount + $countLinked);
        $itemManager->select();
        $itemList = $itemManager->get();

        // get all items except linked items
        $optionsData['items'] = [];
        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $itemService->getTypedItem($tempItem->getItemId());
            // skip already linked items
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                $items[$tempTypedItem->getItemId()] = $tempTypedItem;
            }
            $tempItem = $itemList->getNext();

        }

        $linkedItemIds = $item->getAllLinkedItemIDArray();
        foreach ($linkedItemIds as $linkedId) {
            $formData['itemsLinked'][$linkedId] = true;
        }

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($legacyEnvironment->getCurrentUser()->getUserId());
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $i = 0;
        $latestItem = $latestItemList->getFirst();
        while ($latestItem && $i < 5) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                if ($tempTypedItem->getType() != "discarticle" && $tempTypedItem->getType() != "task" && $tempTypedItem->getType() != 'link_item' && $tempTypedItem->getType() != 'tag') {
                    $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                    $i++;
                }
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = [];
        }

        // get all categories -> tree
        $optionsData['categories'] = $labelService->getCategories($roomId);
        $formData['categories'] = $labelService->getLinkedCategoryIds($item);
        $categoryConstraints = ($current_context->withTags() && $current_context->isTagMandatory()) ? [new Count(array('min' => 1))] : array();

        // get all hashtags -> list
        $optionsData['hashtags'] = $labelService->getHashtags($roomId);
        $formData['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
        $hashtagConstraints = ($current_context->withBuzzwords() && $current_context->isBuzzwordMandatory()) ? [new Count(array('min' => 1))] : [];

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

        $form = $this->createForm(ItemCatsBuzzType::class, $formData, [
            'filterRubric' => [],
            'filterPublic' => [],
            'items' => [],
            'itemsLinked' => [],
            'itemsLatest' => [],
            'categories' => $optionsData['categories'],
            'categoryConstraints' => $categoryConstraints,
            'hashtags' => $optionsData['hashtags'],
            'hashtagConstraints' => $hashtagConstraints,
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            'placeholderText' => $translator->trans('Hashtag', [], 'hashtag'),
            'placeholderTextCategories' => $translator->trans('New category', [], 'category'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                // $itemData = array_merge(array_keys($data['itemsLinked']), $data['itemsLatest']);
                if($data['newCategory']){
                    $data['categories'][] = $categoryService->addTag($data['newCategory'],$roomId)->getItemID();
                }

                // update modifier
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // save links
                //$item->setLinkedItemsByIDArray($itemData);
                $item->setTagListByID($data['categories']);
                $item->setBuzzwordListByID($data['hashtags']);

                if ($item->getItemType() == CS_TOPIC_TYPE) {
                    if (empty($itemData)) {
                        $item->deactivatePath();
                    }
                }

                // persist
                $item->save();
            }

            return $this->redirectToRoute('app_item_savelinks', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form->createView(),
            'showCategories' => $roomItem->withTags(),
            'showHashtags' => $roomItem->withBuzzwords(),
            'items' => $items,
            'itemsLatest' => $optionsData['itemsLatest'],
        ];
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/savelinks")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param EventDispatcherInterface $eventDispatcher
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveLinksAction(
        RoomService $roomService,
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        int $roomId,
        int $itemId
    ) {
        $roomItem = $roomService->getRoomItem($roomId);
        $tempItem = $itemService->getTypedItem($itemId);

        $itemArray = array($tempItem);
    
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);

        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'modifierList' => $modifierList
        );
    }

    /**
     * @Route("/room/{roomId}/{itemId}/send")
     * @Template()
     * @param Request $request
     * @param ItemService $itemService
     * @param MailAssistant $mailAssistant
     * @param LegacyEnvironment $legacyEnvironment
     * @param Mailer $mailer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function sendAction(
        Request $request,
        ItemService $itemService,
        MailAssistant $mailAssistant,
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        int $roomId,
        int $itemId
    ) {
        // get item
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        // prepare form
        $groupChoices = $mailAssistant->getGroupChoices($item);
        $defaultGroupId = null;
        if (count($groupChoices) > 0) {
            $defaultGroupId = array_values($groupChoices)[0];
        }

        $isShowGroupAllRecipients = $mailAssistant->showGroupAllRecipients($request);

        $formData = new Send();
        $formData->setAdditionalRecipients(['']);
        $formData->setSendToGroups([$defaultGroupId]);
        if($isShowGroupAllRecipients){
            $formData->setSendToGroupAll(false);
        }else{
            $formData->setSendToGroupAll(null);
        }
        $formData->setSendToAll(false);
        $formData->setMessage($mailAssistant->prepareMessage($item));
        $formData->setSendToCreator(false);
        $formData->setCopyToSender(false);


        $form = $this->createForm(SendType::class, $formData, [
            'item' => $item,
            'uploadUrl' => $this->generateUrl('app_upload_mailattachments', [
                'roomId' => $roomId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // if cancel was clicked, redirect back to detail page
            if ($form->get('cancel')->isClicked()) {

                $itemType = $item->getType();
                if ($item->getType() === 'label') {
                    $itemType = $item->getLabelType();
                }

                return $this->redirectToRoute('app_' . $itemType . '_detail', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            }

            // send mail
            $email = $mailAssistant->getItemSendMessage($form, $item);
            $mailer->sendEmailObject($email, $portalItem->getTitle());

            $recipientCount = count($email->getTo() ?? []) + count($email->getCc() ?? []) + count($email->getBcc() ?? []);
            $this->addFlash('recipientCount', $recipientCount);

            // redirect to success page
            return $this->redirectToRoute('app_item_sendsuccess', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/{itemId}/send/success")
     * @Template()
     * @param ItemService $itemService
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function sendSuccessAction(
        ItemService $itemService,
        int $roomId, int $itemId
    ) {
        // get item
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        $itemType = $item->getType();
        if ($item->getType() == 'label') {
            $itemType = $item->getLabelType();
        }

        return [
            'link' => $this->generateUrl('app_' . $itemType . '_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'title' => $item->getTitle(),
        ];
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/autocomplete/{feedAmount}", defaults={"feedAmount" = 20})
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param $feedAmount
     * @return JsonResponse
     */
    public function autocompleteAction(
        RoomService $roomService,
        ItemService $itemService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        $feedAmount
    ) {
        $environment = $legacyEnvironment->getEnvironment();

        $optionsData = array();
        $items = array();
        
        // get all items that are linked or can be linked
        $rubricInformation = $roomService->getRubricInformation($roomId);
        $optionsData['filterRubric']['all'] = 'all';
        foreach ($rubricInformation as $rubric) {
            $optionsData['filterRubric'][$rubric] = $rubric;
        }

        $optionsData['filterPublic']['public'] = 'public';
        $optionsData['filterPublic']['all'] = 'all';

        $itemManager = $environment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);
        $itemManager->setTypeArrayLimit($rubricInformation);


        $itemManager->setIntervalLimit($feedAmount);
        $itemManager->select();
        $itemList = $itemManager->get();
        
        // get all items except linked items
        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $itemService->getTypedItem($tempItem->getItemId());
            // skip already linked items
            if ($tempTypedItem) {
                $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                $items[$tempTypedItem->getItemId()] = $tempTypedItem;
            }
            $tempItem = $itemList->getNext();
            
        }

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($environment->getCurrentUser()->getUserId());
        $itemManager->setIntervalLimit(10);
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $latestItem = $latestItemList->getFirst();
        while ($latestItem) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem) {
                $optionsData['itemsLatest'][] = array(
                    'title' => $tempTypedItem->getTitle(), 
                    'text' => $tempTypedItem->getType(), 
                    'url' => '', 
                    'id' => $tempTypedItem->getItemId()
                );
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = array();
        }

        return new JsonResponse([
            $optionsData['itemsLatest']
        ]);
    }

    /**
     * @Route("/room/{roomId}/item/sendlist", condition="request.isXmlHttpRequest()")
     * @Template()
     * @param Request $request
     * @param RoomService $roomService
     * @param UserService $userService
     * @param LegacyEnvironment $legacyEnvironment
     * @param Mailer $mailer
     * @param int $roomId
     * @return array|JsonResponse
     * @throws Exception
     */
    public function sendlistAction(
        Request $request,
        RoomService $roomService,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        int $roomId
    ) {
        // extract item id from request data
        $requestContent = $request->getContent();
        if (empty($requestContent)) {
            throw new Exception('no request content given');
        }

        $room = $roomService->getRoomItem($roomId);

        $environment = $legacyEnvironment->getEnvironment();
        $currentUser = $environment->getCurrentUser();

        // prepare form
        $formMessage = $this->renderView('email/item_list_template.txt.twig', array('user' => $currentUser, 'room' => $room));

        $formData = [
            'message' => $formMessage,
        ];

        $form = $this->createForm(SendListType::class, $formData, []);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $data = $form->getData();

            $userIds = explode(',', $data['entries']);
            $recipients = [];
            foreach ($userIds as $userId) {
                $user = $userService->getUser($userId);
                if ($user) {
                    $recipients[] = RecipientFactory::createRecipient($user);
                }
            }

            $mailer->sendMultipleRaw(
                $data['subject'],
                $data['message'],
                $recipients,
                $currentUser->getFullname(),
                [],
                $data['copy_to_sender'] ? [$currentUser->getEmail()] : []
            );

            return new JsonResponse([
                'message' => 'send ...',
                'timeout' => '5550',
                'layout' => 'cs-notify-message',
                'data' => NULL,
            ]);
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/filelist")
     * @Template()
     * @param $roomId
     * @param $itemId
     * @param Request $request
     * @param ItemService $itemService
     * @return JsonResponse
     */
    public function filelistAction($roomId, $itemId, Request $request, ItemService $itemService)
    {
        /** @var cs_item $item */
        $item = $itemService->getItem($itemId);

        /** @var \cs_file_item[] $files */
        $files = $item->getFileList()->to_array();
        $fileArray = array();

        foreach ($files as $key => $file) {
            $fileArray[] = array (
                'name' => $file->getFileName(),
                'path' => $this->generateUrl('app_file_getfile', [
                    'fileId' => $file->getFileID(),
                ], UrlGeneratorInterface::ABSOLUTE_PATH),
                'id' => $file->getFileID(),
                'ext' => $file->getExtension(),
            );
        }

        return new JsonResponse([
            'files' => $fileArray,
        ]);

    }


    /**
     * @Route("/room/{roomId}/item/{itemId}/stepper")
     * @Template()
     * @param $roomId
     * @param $itemId
     * @param Request $request
     * @param ItemService $itemService
     * @param LegacyEnvironment $legacyEnvironment
     * @return array
     */
    public function stepperAction($roomId, $itemId, Request $request, ItemService $itemService, LegacyEnvironment $legacyEnvironment)
    {
        $environment = $legacyEnvironment->getEnvironment();

        /** @var cs_item $baseItem */
        $baseItem = $itemService->getItem($itemId);

        /** @var cs_manager $rubricManager */
        $rubricManager = $environment->getManager($baseItem->getItemType());

        /** @var cs_item $item */
        $item = $rubricManager->getItem($itemId);

        if ($baseItem->getItemType() == 'project') {
            $rubricManager->setCommunityroomLimit($roomId);
            $rubricManager->setContextLimit($environment->getCurrentPortalID());
        } else {
            $rubricManager->setContextLimit($roomId);
        }
        
        if ($item->getItemType() == 'date') {
            $rubricManager->setWithoutDateModeLimit();
        }
        if(!$environment->getCurrentUserItem()->isModerator() ){
            $rubricManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        }
        $rubricManager->select();
        $itemList = $rubricManager->get();
        $items = $itemList->to_array();
        $itemList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundItem = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($items as $tempItem) {
            if (!$foundItem) {
                if ($counterBefore > 5) {
                    array_shift($itemList);
                } else {
                    $counterBefore++;
                }
                $itemList[] = $tempItem;
                if ($tempItem->getItemID() == $item->getItemID()) {
                    $foundItem = true;
                }
                if (!$foundItem) {
                    $prevItemId = $tempItem->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $itemList[] = $tempItem;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempItem->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($items)) {
            if ($prevItemId) {
                $firstItemId = $items[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $items[sizeof($items)-1]->getItemId();
            }
        }
        
        return array(
            'rubric' => $item->getItemType(),
            'roomId' => $roomId,
            'itemList' => $itemList,
            'item' => $item,
            'counterPosition' => $counterPosition,
            'count' => sizeof($items),
            'firstItemId' => $firstItemId,
            'prevItemId' => $prevItemId,
            'nextItemId' => $nextItemId,
            'lastItemId' => $lastItemId,
        );
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/get", condition="request.isXmlHttpRequest()")
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     * @param ItemService $itemService
     * @param int $itemId
     * @return array
     */
    public function singleArticleAction(
        ItemService $itemService,
        int $itemId
    ) {
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        return [
            'item' => $item,
        ];
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/links")
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param CategoryService $categoryService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function linksAction(
        RoomService $roomService,
        ItemService $itemService,
        CategoryService $categoryService,
        LegacyEnvironment $environment,
        int $roomId, int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $item = $itemService->getItem($itemId);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $itemCategories = $item->getTagsArray();
            $categories = $this->labelService->getTagDetailArray($roomCategories, $itemCategories);
        }

        $roomItem = $roomService->getRoomItem($roomId);

        return [
            'item' => $item,
            'showHashtags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'roomCategories' => $categories,
        ];
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/canceledit")
     * @Template()
     * @param ItemService $itemService
     * @param EventDispatcherInterface $eventDispatcher
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function cancelEditAction(
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getTypedItem($itemId);
        
        if ($item->getItemType() === CS_SECTION_TYPE ||$item->getItemType() === CS_STEP_TYPE) {
            $eventDispatcher->dispatch(new CommsyEditEvent($item->getLinkedItem()), CommsyEditEvent::CANCEL);
        } else {
            $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::CANCEL);
        }

        return array(
            'canceledEdit' => true,
            'roomId' => $roomId,
            'item' => $item,
        );
    }
}
