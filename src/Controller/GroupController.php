<?php

namespace App\Controller;

use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\MarkReadAction;
use App\Event\CommsyEditEvent;
use App\Filter\GroupFilterType;
use App\Form\DataTransformer\GroupTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\GrouproomType;
use App\Form\Type\GroupSendType;
use App\Form\Type\GroupType;
use App\Http\JsonDataResponse;
use App\Services\CalendarsService;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\CategoryService;
use App\Utils\GroupService;
use App\Utils\LabelService;
use App\Utils\MailAssistant;
use App\Utils\TopicService;
use App\Utils\UserService;
use cs_room_item;
use cs_user_item;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Swift_Mailer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class GroupController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'group')")
 */
class GroupController extends BaseController
{
    /**
     * @var GroupService
     */
    private GroupService $groupService;

    /**
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @var Swift_Mailer
     */
    private Swift_Mailer $mailer;

    /**
     * @required
     * @param GroupService $groupService
     */
    public function setGroupService(GroupService $groupService): void
    {
        $this->groupService = $groupService;
    }


    /**
     * @required
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }


    /**
     * @required
     * @param Swift_Mailer $mailer
     */
    public function setMailer(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @required
     * @param UserService $userService
     */
    public function setUserService(UserService $userService): void
    {
        $this->userService = $userService;
    }


    /**
     * @Route("/room/{roomId}/group")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        int $roomId
    ) {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in group manager
            $this->groupService->setFilterConditions($filterForm);
        } else {
            $this->groupService->hideDeactivatedEntries();
        }

        // get group list from manager service 
        $itemsCountArray = $this->groupService->getCountArray($roomId);

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('group') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('group');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('group');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'group',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
            'showAssociations' => false,
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/print/{sort}", defaults={"sort" = "none"})
     * @param Request $request
     * @param PrintService $printService
     * @param int $roomId
     * @param string $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        PrintService $printService,
        int $roomId,
        string $sort
    ) {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        $filterForm = $this->createFilterForm($roomItem);
        $numAllGroups = $this->groupService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in group manager
            $this->groupService->setFilterConditions($filterForm);
        } else {
            $this->groupService->hideDeactivatedEntries();
        }

        // get group list from manager service 
        if ($sort != "none") {
            $groups = $this->groupService->getListGroups($roomId, $numAllGroups, 0, $sort);
        } elseif ($this->session->get('sortGroups')) {
            $groups = $this->groupService->getListGroups($roomId, $numAllGroups, 0,
                $this->session->get('sortGroups'));
        } else {
            $groups = $this->groupService->getListGroups($roomId, $numAllGroups, 0, 'date');
        }

        $readerList = array();
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        // get group list from manager service 
        $itemsCountArray = $this->groupService->getCountArray($roomId);

        $html = $this->renderView('group/list_print.html.twig', [
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList,
            'module' => 'group',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/group/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param UserService $userService
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        UserService $userService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $groupFilter = $request->get('groupFilter');
        if (!$groupFilter) {
            $groupFilter = $request->query->get('group_filter');
        }

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($groupFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($groupFilter);

            $this->groupService->setFilterConditions($filterForm);
        } else {
            $this->groupService->hideDeactivatedEntries();
        }

        // get group list from manager service 
        $groups = $this->groupService->getListGroups($roomId, $max, $start, $sort);

        $this->session->set('sortGroups', $sort);

        // contains member status of current user for each group and grouproom
        $allGroupsMemberStatus = [];

        $readerList = array();
        $allowedActions = array();
        foreach ($groups as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'sendmail', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'sendmail');
            }

            // add groupMember and groupRoomMember status to each group!
            $groupMemberStatus = [];

            // group member status
            $membersList = $item->getMemberItemList();
            $members = $membersList->to_array();
            $groupMemberStatus['groupMember'] = $membersList->inList($this->legacyEnvironment->getCurrentUserItem());

            // grouproom member status
            if ($item->isGroupRoomActivated()) {
                if ($item->getGroupRoomItem()) {
                    $groupMemberStatus['groupRoomMember'] = $this->userService->getMemberStatus(
                        $item->getGroupRoomItem(),
                        $this->legacyEnvironment->getCurrentUser()
                    );
                } else {
                    $groupMemberStatus['groupRoomMember'] = 'deactivated';
                }
            } else {
                $groupMemberStatus['groupRoomMember'] = 'deactivated';
            }
            $allGroupsMemberStatus[$item->getItemID()] = $groupMemberStatus;
        }

        return array(
            'roomId' => $roomId,
            'groups' => $groups,
            'readerList' => $readerList,
            'showRating' => false,
            'allowedActions' => $allowedActions,
            'memberStatus' => $allGroupsMemberStatus,
            'isRoot' => $this->legacyEnvironment->getCurrentUser()->isRoot(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'group')")
     * @param Request $request
     * @param AnnotationService $annotationService
     * @param CategoryService $categoryService
     * @param UserService $userService
     * @param TopicService $topicService
     * @param LegacyMarkup $legacyMarkup
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        AnnotationService $annotationService,
        CategoryService $categoryService,
        UserService $userService,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($annotationService, $categoryService, $roomId, $itemId);

        $memberStatus = '';

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($infoArray['group']->isGroupRoomActivated()) {
            $groupRoomItem = $infoArray['group']->getGroupRoomItem();
            if ($groupRoomItem && !empty($groupRoomItem)) {
                $memberStatus = $this->userService->getMemberStatus(
                    $groupRoomItem,
                    $this->legacyEnvironment->getCurrentUser()
                );
            } else {
                $memberStatus = 'deactivated';
            }
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($infoArray['group']->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));

        $currentUserIsLastGrouproomModerator = $this->userService->userIsLastModeratorForRoom($infoArray['group']->getGroupRoomItem());

        return array(
            'roomId' => $roomId,
            'group' => $infoArray['group'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'groupList' => $infoArray['groupList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'roomCategories' => $infoArray['roomCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'userIsMember' => $infoArray['userIsMember'],
            'memberStatus' => $memberStatus,
            'annotationForm' => $form->createView(),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isArchived' => $roomItem->isArchived(),
            'lastModeratorStanding' => $currentUserIsLastGrouproomModerator,
            'userRubricVisible' => in_array("user", $this->roomService->getRubricInformation($roomId)),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/print")
     * @param AnnotationService $annotationService
     * @param CategoryService $categoryService
     * @param PrintService $printService
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        AnnotationService $annotationService,
        CategoryService $categoryService,
        PrintService $printService,
        int $roomId,
        int $itemId
    ) {

        $infoArray = $this->getDetailInfo($annotationService, $categoryService, $roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('group/detail_print.html.twig', [
            'roomId' => $roomId,
            'group' => $infoArray['group'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'groupList' => $infoArray['groupList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    private function getDetailInfo(
        AnnotationService $annotationService,
        CategoryService $categoryService,
        int $roomId,
        int $itemId
    ) {
        $infoArray = array();

        $group = $this->groupService->getGroup($itemId);

        $item = $group;
        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        // when group is newly created, "modificationDate" is equal to "reader['read_date']", so operator "<=" instead of "<" should be used here
        if (empty($reader) || $reader['read_date'] <= $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $this->legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        // when group is newly created, "modificationDate" is equal to "noticed['read_date']", so operator "<=" instead of "<" should be used here
        if (empty($noticed) || $noticed['read_date'] <= $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $readerManager = $this->legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($group->getContextId());
        $numTotalMember = $roomItem->getAllUsers();

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $group->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $group->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);

        $readerList = array();
        $modifierList = array();
        $reader = $this->readerService->getLatestReader($group->getItemId());
        if (empty($reader)) {
            $readerList[$item->getItemId()] = 'new';
        } elseif ($reader['read_date'] < $group->getModificationDate()) {
            $readerList[$group->getItemId()] = 'changed';
        }

        $modifierList[$group->getItemId()] = $this->itemService->getAdditionalEditorsForItem($group);

        $groups = $this->groupService->getListGroups($roomId);
        $groupList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundGroup = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($groups as $tempGroup) {
            if (!$foundGroup) {
                if ($counterBefore > 5) {
                    array_shift($groupList);
                } else {
                    $counterBefore++;
                }
                $groupList[] = $tempGroup;
                if ($tempGroup->getItemID() == $group->getItemID()) {
                    $foundGroup = true;
                }
                if (!$foundGroup) {
                    $prevItemId = $tempGroup->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $groupList[] = $tempGroup;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempGroup->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($groups)) {
            if ($prevItemId) {
                $firstItemId = $groups[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $groups[sizeof($groups) - 1]->getItemId();
            }
        }
        // mark annotations as readed
        $annotationList = $group->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);


        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $groupCategories = $group->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $groupCategories);
        }

        $infoArray['group'] = $group;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['groupList'] = $groupList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($groups);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $read_count;
        $infoArray['readSinceModificationCount'] = $read_since_modification_count;
        $infoArray['userCount'] = $all_user_count;
        $infoArray['draft'] = $this->itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $this->legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['roomCategories'] = $categories;
        $infoArray['members'] = $members;
        $infoArray['userIsMember'] = $membersList->inList($infoArray['user']);

        return $infoArray;
    }

    private function getTagDetailArray(
        $baseCategories,
        $itemCategories
    ) {
        $result = array();
        $tempResult = array();
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }
            $tempArray = array();
            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = array(
                            'title' => $baseCategory['title'],
                            'item_id' => $baseCategory['item_id'],
                            'children' => $tempResult
                        );
                    } else {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']);
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = array(
                        'title' => $baseCategory['title'],
                        'item_id' => $baseCategory['item_id'],
                        'children' => $tempResult
                    );
                }
            }
            $tempResult = array();
            $addCategory = false;
        }
        return $result;
    }


    /**
     * @Route("/room/{roomId}/group/create")
     * @Template()
     * @param int $roomId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'group')")
     */
    public function createAction(
        int $roomId
    ) {

        // create new group item
        $groupItem = $this->groupService->getNewGroup();
        $groupItem->setDraftStatus(1);
        $groupItem->setPrivateEditing(1);
        $groupItem->save();

        // add current user to new group
        $groupItem->addMember($this->legacyEnvironment->getCurrentUser());

        return $this->redirectToRoute('app_group_detail',
            array('roomId' => $roomId, 'itemId' => $groupItem->getItemId()));
    }


    /**
     * @Route("/room/{roomId}/group/new")
     * @Template()
     * @param Request $request
     * @param int $roomId
     */
    public function newAction(
        Request $request,
        int $roomId
    ) {

    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")
     * @param Request $request
     * @param ItemController $itemController
     * @param CategoryService $categoryService
     * @param GroupTransformer $transformer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        ItemController $itemController,
        CategoryService $categoryService,
        LabelService $labelService,
        GroupTransformer $transformer,
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);
        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $groupItem = null;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        // get date from DateService
        $groupItem = $this->groupService->getGroup($itemId);
        if (!$groupItem) {
            throw $this->createNotFoundException('No group found for id ' . $itemId);
        }
        $formData = $transformer->transform($groupItem);
        $formData['categoriesMandatory'] = $categoriesMandatory;
        $formData['hashtagsMandatory'] = $hashtagsMandatory;
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId,
            $this->legacyEnvironment);
        $formData['draft'] = $isDraft;
        $form = $this->createForm(GroupType::class, $formData, array(
            'action' => $this->generateUrl('app_group_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $categoryService),
                'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
                'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $this->legacyEnvironment),
                'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
            ],
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $groupItem = $transformer->applyTransformation($groupItem, $form->getData());

                // update modifier
                $groupItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($categoriesMandatory) {
                    $categoryIds = $formData['category_mapping']['categories'] ?? [];

                    if (isset($formData['category_mapping']['newCategory'])) {
                        $newCategoryTitle = $formData['category_mapping']['newCategory'];
                        $newCategory = $categoryService->addTag($newCategoryTitle, $roomId);
                        $categoryIds[] = $newCategory->getItemID();
                    }

                    $groupItem->setTagListByID($categoryIds);
                }
                if ($hashtagsMandatory) {
                    $hashtagIds = $formData['hashtag_mapping']['hashtags'] ?? [];

                    if (isset($formData['hashtag_mapping']['newHashtag'])) {
                        $newHashtagTitle = $formData['hashtag_mapping']['newHashtag'];
                        $newHashtag = $labelService->getNewHashtag($newHashtagTitle, $roomId);
                        $hashtagIds[] = $newHashtag->getItemID();
                    }

                    $groupItem->setBuzzwordListByID($hashtagIds);
                }

                $groupItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else {
                if ($form->get('cancel')->isClicked()) {
                    // ToDo ...
                }
            }
            return $this->redirectToRoute('app_group_save', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($groupItem), CommsyEditEvent::EDIT);

        return array(
            'form' => $form->createView(),
            'group' => $groupItem,
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $this->legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);
        $group = $this->groupService->getGroup($itemId);

        $itemArray = array($group);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $readerManager = $this->legacyEnvironment->getReaderManager();
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $group->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($group->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $group->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $read_percentage = round(($read_count / $all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count / $all_user_count) * 100);

        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($group), CommsyEditEvent::SAVE);

        return array(
            'roomId' => $roomId,
            'item' => $group,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }


    /**
     * @Route("/room/{roomId}/group/{itemId}/editgrouproom")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'group')")
     * @param Request $request
     * @param CalendarsService $calendarsService
     * @param GroupTransformer $transformer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     * @throws Exception
     */
    public function editgrouproomAction(
        Request $request,
        CalendarsService $calendarsService,
        GroupTransformer $transformer,
        int $roomId,
        int $itemId
    ) {
        $groupItem = null;

        // get group from GroupService
        $groupItem = $this->groupService->getGroup($itemId);
        if (!$groupItem) {
            throw $this->createNotFoundException('No group found for id ' . $itemId);
        }
        $formData = $transformer->transform($groupItem);
        $form = $this->createForm(GrouproomType::class, $formData, array(
            'action' => $this->generateUrl('app_group_editgrouproom', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'templates' => $this->getAvailableTemplates(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ($saveType == 'save') {

                $originalGroupName = "";
                if ($groupItem->getGroupRoomItem() && !empty($groupItem->getGroupRoomItem())) {
                    $originalGroupName = $groupItem->getGroupRoomItem()->getTitle();
                }

                $groupItem = $transformer->applyTransformation($groupItem, $form->getData());

                // update modifier
                $groupItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                $groupItem->save(true);

                $groupRoom = $groupItem->getGroupRoomItem();

                // only initialize the name of the grouproom the first time it is created!
                if ($groupRoom && !empty($groupRoom)) {
                    if ($originalGroupName == "") {
                        $groupRoom->setTitle($groupItem->getTitle() . " (" . $this->translator->trans('grouproom', [],
                                'group') . ")");
                    } else {
                        $groupRoom->setTitle($originalGroupName);
                    }
                    $groupRoom->save(false);

                    $calendarsService->createCalendar($groupRoom, null, null, true);

                    // take values from a template?
                    if ($form->has('master_template')) {
                        $masterTemplate = $form->get('master_template')->getData();

                        $masterRoom = $this->roomService->getRoomItem($masterTemplate);
                        if ($masterRoom) {
                            $groupRoom = $this->copySettings($masterRoom, $groupRoom);
                        }
                    }
                    $groupItem->save(true);
                }

            } else {
                // ToDo ...
            }
            return $this->redirectToRoute('app_group_savegrouproom', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($groupItem), CommsyEditEvent::EDIT);

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/join/{joinRoom}", defaults={"joinRoom"=false})
     * @param int $roomId
     * @param int $itemId
     * @param bool $joinRoom
     * @return JsonDataResponse|RedirectResponse
     * @throws Exception
     */
    public function joinAction(
        int $roomId,
        int $itemId,
        bool $joinRoom
    ) {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $groupItem = $this->groupService->getGroup($itemId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        } elseif (!$groupItem) {
            throw $this->createNotFoundException('The requested group does not exists');
        }

        $current_user = $this->legacyEnvironment->getCurrentUser();

        // first, join group
        if ($groupItem->getMemberItemList()->inList($current_user)) {
            throw new Exception("ERROR: User '" . $current_user->getUserID() . "' cannot join group '" . $groupItem->getName() . "' since (s)he already is a member!");
        } else {
            $groupItem->addMember($current_user);
        }

        // then, join grouproom
        if ($joinRoom) {
            $grouproomItem = $groupItem->getGroupRoomItem();
            if ($grouproomItem) {
                $memberStatus = $this->userService->getMemberStatus($grouproomItem,
                    $this->legacyEnvironment->getCurrentUser());
                if ($memberStatus == 'join') {
                    return $this->redirectToRoute('app_context_request', [
                        'roomId' => $roomId,
                        'itemId' => $grouproomItem->getItemId(),
                    ]);
                } else {
                    throw new Exception("ERROR: User '" . $current_user->getUserID() . "' cannot join group room '" . $grouproomItem->getTitle() . "' since (s)he has room member status '" . $memberStatus . "' (requires status 'join' to become a room member)!");
                }
            } else {
                throw new Exception("ERROR: User '" . $current_user->getUserID() . "' cannot join the group room of group '" . $groupItem->getName() . "' since it does not exist!");
            }
        }

        return new JsonDataResponse([
            'title' => $groupItem->getTitle(),
            'groupId' => $itemId,
            'memberId' => $current_user->getItemId(),
        ]);
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/leave")
     * @param int $roomId
     * @param int $itemId
     * @return JsonDataResponse
     */
    public function leaveAction(
        int $roomId,
        int $itemId
    ) {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        $groupItem = $this->groupService->getGroup($itemId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        } elseif (!$groupItem) {
            throw $this->createNotFoundException('The requested group does not exists');
        }

        $current_user = $this->legacyEnvironment->getCurrentUser();
        $groupItem->removeMember($current_user);

        return new JsonDataResponse([
            'title' => $groupItem->getTitle(),
            'groupId' => $itemId,
            'memberId' => $current_user->getItemId(),
        ]);
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/members", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'group')")
     * @param int $itemId
     * @return array
     */
    public function membersAction(
        int $itemId
    ) {
        $group = $this->groupService->getGroup($itemId);
        $membersList = $group->getMemberItemList();
        $members = $membersList->to_array();
        return [
            'group' => $group,
            'members' => $members,
        ];
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/grouproom", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'group')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function groupRoomAction(
        int $roomId,
        int $itemId
    ) {
        $group = $this->groupService->getGroup($itemId);
        $membersList = $group->getMemberItemList();
        $memberStatus = $this->userService->getMemberStatus(
            $group->getGroupRoomItem(),
            $this->legacyEnvironment->getCurrentUser()
        );

        return [
            'group' => $group,
            'roomId' => $roomId,
            'userIsMember' => $membersList->inList($this->legacyEnvironment->getCurrentUserItem()),
            'memberStatus' => $memberStatus,
        ];
    }

    /**
     * @Route("/room/{roomId}/group/sendMultiple")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @return array|RedirectResponse
     * @throws Exception
     */
    public function sendMultipleAction(
        Request $request,
        MailAssistant $mailAssistant,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);

        $groupIds = [];
        if (!$request->request->has('group_send')) {
            $users = $this->getItemsForActionRequest($room, $request);

            foreach ($users as $user) {
                $groupIds[] = $user->getItemId();
            }
        } else {
            $postData = $request->request->get('group_send');
            $groupIds = $postData['groups'];
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        // we exclude any locked/rejected or registered users here since these shouldn't receive any group mails
        $users = $this->userService->getUsersByGroupIds($roomId, $groupIds, true);

        // include a footer message in the email body
        $groupCount = count($groupIds);
        $defaultBodyMessage = '';
        if ($groupCount) {
            $defaultBodyMessage .= '<br/><br/><br/>' . '--' . '<br/>';
            if ($groupCount == 1) {
                $group = $this->groupService->getGroup(reset($groupIds));
                if ($group) {
                    $defaultBodyMessage .= $this->translator->trans(
                        'This email has been sent to all users of this group',
                        [
                            '%sender_name%' => $currentUser->getFullName(),
                            '%group_name%' => $group->getName(),
                            '%room_name%' => $room->getTitle()
                        ],
                        'mail'
                    );
                }
            } elseif ($groupCount > 1) {
                $defaultBodyMessage .= $this->translator->trans(
                    'This email has been sent to multiple users of this room',
                    [
                        '%sender_name%' => $currentUser->getFullName(),
                        '%user_count%' => count($users),
                        '%room_name%' => $room->getTitle()
                    ],
                    'mail'
                );
            }
        }

        $formData = [
            'message' => $defaultBodyMessage,
            'copy_to_sender' => false,
            'groups' => $groupIds,
        ];

        $form = $this->createForm(GroupSendType::class, $formData, [
            'uploadUrl' => $this->generateUrl('app_upload_mailattachments', [
                'roomId' => $roomId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save') {
                $formData = $form->getData();

                $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

                $from = $this->getParameter('commsy.email.from');

                // NOTE: as of #2461 all mail should be sent as BCC mail; but, for now, we keep the original logic here
                // TODO: refactor all mail sending code so that it is handled by a central class (like `MailAssistant.php`)
                $forceBCCMail = true;

                $to = [];
                $toBCC = [];
                $validator = new EmailValidator();
                $failedUsers = [];
                foreach ($users as $user) {
                    $userEmail = $user->getEmail();
                    $userName = $user->getFullName();
                    if ($validator->isValid($userEmail, new RFCValidation())) {
                        if ($user->isEmailVisible()) {
                            $to[$userEmail] = $userName;
                        } else {
                            $toBCC[$userEmail] = $userName;
                        }
                    } else {
                        $failedUsers[] = $user;
                    }
                }

                $replyTo = [];
                $toCC = [];
                $currentUserEmail = $currentUser->getEmail();
                $currentUserName = $currentUser->getFullName();
                if ($validator->isValid($currentUserEmail, new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[$currentUserEmail] = $currentUserName;
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        if ($currentUser->isEmailVisible()) {
                            $toCC[$currentUserEmail] = $currentUserName;
                        } else {
                            $toBCC[$currentUserEmail] = $currentUserName;
                        }
                    }
                }

                // TODO: use MailAssistant to generate the Swift message and to add its recipients etc
                $message = (new \Swift_Message())
                    ->setSubject($formData['subject'])
                    ->setBody($formData['message'], 'text/html')
                    ->setFrom([$from => $portalItem->getTitle()])
                    ->setReplyTo($replyTo);

                $formDataFiles = $formData['files'];
                if ($formDataFiles) {
                    $message = $mailAssistant->addAttachments($formDataFiles, $message);
                }

                $recipientCount = 0;

                if ($forceBCCMail) {
                    $allRecipients = array_merge($to, $toCC, $toBCC);
                    $message->setBcc($allRecipients);
                    $recipientCount += count($allRecipients);
                } else {
                    if (!empty($to)) {
                        $message->setTo($to);
                        $recipientCount += count($to);
                    }

                    if (!empty($toCC)) {
                        $message->setCc($toCC);
                        $recipientCount += count($toCC);
                    }

                    if (!empty($toBCC)) {
                        $message->setBcc($toBCC);
                        $recipientCount += count($toBCC);
                    }
                }

                $this->addFlash('recipientCount', $recipientCount);

                // send mail
                $failedRecipients = [];
                $this->mailer->send($message, $failedRecipients);

                foreach ($failedUsers as $failedUser) {
                    $this->addFlash('failedRecipients', $failedUser->getUserId());
                }

                foreach ($failedRecipients as $failedRecipient) {
                    $failedUser = array_filter($users, function ($user) use ($failedRecipient) {
                        return $user->getEmail() == $failedRecipient;
                    });

                    if ($failedUser) {
                        $this->addFlash('failedRecipients', $failedUser[0]->getUserId());
                    }
                }

                // redirect to success page
                return $this->redirectToRoute('app_group_sendmultiplesuccess', [
                    'roomId' => $roomId,
                ]);
            } else {
                // redirect to group feed
                return $this->redirectToRoute('app_group_list', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/group/sendMultiple/success")
     * @Template()
     * @param int $roomId
     * @return array
     */
    public function sendMultipleSuccessAction(
        int $roomId
    ) {
        return [
            'link' => $this->generateUrl('app_group_list', [
                'roomId' => $roomId,
            ]),
        ];
    }

    /**
     * @Route("/room/{roomId}/group/{itemId}/send")
     * @Template()
     * @param Request $request
     * @param UserService $userService
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function sendAction(
        Request $request,
        UserService $userService,
        MailAssistant $mailAssistant,
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $room = $this->getRoom($roomId);

        $defaultBodyMessage = '<br/><br/><br/>' . '--' . '<br/>' . $this->translator->trans(
                'This email has been sent to all users of this group',
                [
                    '%sender_name%' => $currentUser->getFullName(),
                    '%group_name%' => $item->getName(),
                    '%room_name%' => $room->getTitle()
                ],
                'mail'
            );

        $formData = [
            'message' => $defaultBodyMessage,
            'copy_to_sender' => false,
        ];

        $form = $this->createForm(GroupSendType::class, $formData, [
            'uploadUrl' => $this->generateUrl('app_upload_mailattachments', [
                'roomId' => $roomId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();

            if ($saveType == 'save') {
                $formData = $form->getData();

                $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

                $from = $this->getParameter('commsy.email.from');

                // we exclude any locked/rejected or registered users here since these shouldn't receive any group mails
                $users = $this->userService->getUsersByGroupIds($roomId, $item->getItemID(), true);

                // NOTE: as of #2461 all mail should be sent as BCC mail; but, for now, we keep the original logic here
                // TODO: refactor all mail sending code so that it is handled by a central class (like `MailAssistant.php`)
                $forceBCCMail = true;

                $to = [];
                $toBCC = [];
                $validator = new EmailValidator();
                $failedUsers = [];
                foreach ($users as $user) {
                    $userEmail = $user->getEmail();
                    $userName = $user->getFullName();
                    if ($validator->isValid($userEmail, new RFCValidation())) {
                        if ($user->isEmailVisible()) {
                            $to[$userEmail] = $userName;
                        } else {
                            $toBCC[$userEmail] = $userName;
                        }
                    } else {
                        $failedUsers[] = $user;
                    }
                }

                $replyTo = [];
                $toCC = [];
                $currentUserEmail = $currentUser->getEmail();
                $currentUserName = $currentUser->getFullName();
                if ($validator->isValid($currentUserEmail, new RFCValidation())) {
                    if ($currentUser->isEmailVisible()) {
                        $replyTo[$currentUserEmail] = $currentUserName;
                    }

                    // form option: copy_to_sender
                    if (isset($formData['copy_to_sender']) && $formData['copy_to_sender']) {
                        if ($currentUser->isEmailVisible()) {
                            $toCC[$currentUserEmail] = $currentUserName;
                        } else {
                            $toBCC[$currentUserEmail] = $currentUserName;
                        }
                    }
                }

                // TODO: use MailAssistant to generate the Swift message and to add its recipients etc
                $message = (new \Swift_Message())
                    ->setSubject($formData['subject'])
                    ->setBody($formData['message'], 'text/html')
                    ->setFrom([$from => $portalItem->getTitle()])
                    ->setReplyTo($replyTo);

                $formDataFiles = $formData['files'];
                if ($formDataFiles) {
                    $message = $mailAssistant->addAttachments($formDataFiles, $message);
                }

                $recipientCount = 0;

                if ($forceBCCMail) {
                    $allRecipients = array_merge($to, $toCC, $toBCC);
                    $message->setBcc($allRecipients);
                    $recipientCount += count($allRecipients);
                } else {
                    if (!empty($to)) {
                        $message->setTo($to);
                        $recipientCount += count($to);
                    }

                    if (!empty($toCC)) {
                        $message->setCc($toCC);
                        $recipientCount += count($toCC);
                    }

                    if (!empty($toBCC)) {
                        $message->setBcc($toBCC);
                        $recipientCount += count($toBCC);
                    }
                }

                $this->addFlash('recipientCount', $recipientCount);

                // send mail
                $failedRecipients = [];
                $this->mailer->send($message, $failedRecipients);

                foreach ($failedUsers as $failedUser) {
                    $this->addFlash('failedRecipients', $failedUser->getUserId());
                }

                foreach ($failedRecipients as $failedRecipient) {
                    $failedUser = array_filter($users, function ($user) use ($failedRecipient) {
                        return $user->getEmail() == $failedRecipient;
                    });

                    if ($failedUser) {
                        $this->addFlash('failedRecipients', $failedUser[0]->getUserId());
                    }
                }

                // redirect to success page
                return $this->redirectToRoute('app_group_sendmultiplesuccess', [
                    'roomId' => $roomId,
                ]);
            } else {
                // redirect to group feed
                return $this->redirectToRoute('app_group_list', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/group/download")
     * @param Request $request
     * @param DownloadAction $action
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function downloadAction(
        Request $request,
        DownloadAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/group/xhr/markread", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrMarkReadAction(
        Request $request,
        MarkReadAction $markReadAction,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);
        return $markReadAction->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/group/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @param $masterRoom
     * @param $targetRoom
     * @return mixed
     * @throws Exception
     */
    private function copySettings($masterRoom, $targetRoom)
    {
        $old_room = $masterRoom;
        $new_room = $targetRoom;

        $old_room_id = $old_room->getItemID();

        /**/
        $user_manager = $this->legacyEnvironment->getUserManager();
        $creator_item = $user_manager->getItem($new_room->getCreatorID());
        if ($creator_item->getContextID() == $new_room->getItemID()) {
            $creator_id = $creator_item->getItemID();
        } else {
            $user_manager->resetLimits();
            $user_manager->setContextLimit($new_room->getItemID());
            $user_manager->setUserIDLimit($creator_item->getUserID());
            $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
            $user_manager->setModeratorLimit();
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
                $creator_item = $user_list->getFirst();
                $creator_id = $creator_item->getItemID();
            } else {
                throw new Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->setPublishMaterialWantMail('yes');
        $creator_item->save();

        // copy room settings
        require_once('include/inc_room_copy_config.php');

        // save new room
        $new_room->save(false);

        // copy data
        require_once('include/inc_room_copy_data.php');
        /**/

        $targetRoom = $new_room;

        return $targetRoom;
    }

    /**
     * @return array
     */
    private function getAvailableTemplates()
    {

        $templates = [];

        $currentPortal = $this->legacyEnvironment->getCurrentPortalItem();
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($currentPortal->getItemID());
        $roomManager->setOnlyGrouproom();
        $roomManager->setTemplateLimit();
        $roomManager->select();
        $roomList = $roomManager->get();

        $defaultId = $this->legacyEnvironment->getCurrentPortalItem()->getDefaultProjectTemplateID();
        if ($roomList->isNotEmpty() or $defaultId != '-1') {
            $currentUser = $this->legacyEnvironment->getCurrentUser();
            if ($defaultId != '-1') {
                $defaultItem = $roomManager->getItem($defaultId);
                if (isset($defaultItem)) {
                    $template_availability = $defaultItem->getTemplateAvailability();
                    if ($template_availability == '0') {
                        $templates[$defaultItem->getTitle()] = $defaultItem->getItemID();
                    }
                }
            }
            $item = $roomList->getFirst();
            while ($item) {
                $templateAvailability = $item->getTemplateAvailability();

                if (($templateAvailability == '0') or
                    ($this->legacyEnvironment->inCommunityRoom() and $templateAvailability == '3') or
                    ($templateAvailability == '1' and $item->mayEnter($currentUser)) or
                    ($templateAvailability == '2' and $item->mayEnter($currentUser) and ($item->isModeratorByUserID($currentUser->getUserID(),
                            $currentUser->getAuthSource())))
                ) {
                    if ($item->getItemID() != $defaultId or $item->getTemplateAvailability() != '0') {
                        $templates[$item->getTitle()] = $item->getItemID();
                    }

                }
                $item = $roomList->getNext();
            }
            unset($currentUser);
        }

        return $templates;
    }

    /**
     * @param cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm($room)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
        ];

        return $this->createForm(GroupFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_group_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => false,
            'hasCategories' => false,
        ]);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_user_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        // get the user service
        if ($selectAll) {
            if ($request->query->has('group_filter')) {
                $currentFilter = $request->query->get('group_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->groupService->setFilterConditions($filterForm);
            } else {
                $this->groupService->hideDeactivatedEntries();
            }

            return $this->groupService->getListGroups($roomItem->getItemID());
        } else {
            return $this->groupService->getGroupsById($roomItem->getItemID(), $itemIds);
        }
    }
}
