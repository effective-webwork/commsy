<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class BreadcrumbSubscriber implements EventSubscriberInterface
{
    private $legacyEnvironment;
    private $roomService;
    private $itemService;
    private $translator;
    private $breadcrumbs;
    private $router;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        ItemService $itemService,
        TranslatorInterface $translator,
        RouterInterface $router,
        Breadcrumbs $whiteOctoberBreadcrumbs)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->roomService = $roomService;
        $this->itemService = $itemService;
        $this->breadcrumbs = $whiteOctoberBreadcrumbs;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function onControllerEvent(ControllerEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }
        $request = $event->getRequest();

        $route = explode('_', $request->get('_route'));

        if (count($route) < 3) {
            return;
        }

        list($bundle, $controller, $action) = $route;

        $routeParameters = $request->get('_route_params');

        $roomItem = $this->roomService->getCurrentRoomItem();

        // Longest case:
        // Portal / CommunityRoom / ProjectRooms / ProjectRoomName / Groups / GroupName / Grouproom / Rubric / Entry

        $this->addPortalCrumb($request);

        // portal settings
        if ($controller == 'portal') {

            $portal = $this->legacyEnvironment->getEnvironment()->getCurrentPortalItem();

            $this->breadcrumbs->addRouteItem($this->translator->trans('settings', [], 'portal'), "app_portal_legacysettings", ["roomId" => $portal->getItemId()]);

            $this->breadcrumbs->addItem($this->translator->trans($action, [], 'portal'));

            return;
        }

        if ($roomItem == null) {
            return;
        }

        if($controller == 'profile'){
            $this->addProfileCrumbs($roomItem, $routeParameters, $action);
        }
        elseif ($controller == 'room' && $action == 'home') {
            $this->addRoom($roomItem, false);
        }
        elseif ($controller == 'dashboard' && $action == 'overview') {
            $this->breadcrumbs->addItem($this->translator->trans($controller, [], 'menu'));
        }
        elseif ($controller == 'category') {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addItem($this->translator->trans('Categories', [], 'category'));
        }
        elseif ($controller == 'hashtag') {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addItem($this->translator->trans('hashtags', [], 'room'));
        }
        else {
            $this->addRoom($roomItem, true);

            // rubric & entry
            if(array_key_exists('itemId', $routeParameters)) {

                // link to rubric
                $route[2] = 'list';
                unset($routeParameters['itemId']);
                try {
                    if ($controller == 'context' && $action == 'request') {
                        if ($roomItem->isCommunityRoom()) {
                            $this->addChildRoomListCrumb($roomItem, 'project');
                        }
                        elseif ($roomItem->isProjectRoom()) {
                            $this->addChildRoomListCrumb($roomItem, 'group');
                        }
                    }
                    else {
                        $this->breadcrumbs->addRouteItem($this->translator->trans($controller, [], 'menu'), implode("_", $route), $routeParameters);
                    }
                }
                catch (RouteNotFoundException $e) {
                    // we don't need breadcrumbs for routes like app_item_editdetails etc. to ajax controller actions
                }

                // entry title
                $item = $this->itemService->getTypedItem($request->get('itemId'));
                if ($item) {
                    $this->breadcrumbs->addItem($item->getItemType() == 'user' ? $item->getFullName() : $item->getTitle());
                }
            }

            // rubric only
            else {
                if ($controller == 'room' && $action == 'listall') {
                    $this->breadcrumbs->addItem($this->translator->trans('All rooms', [], 'room'));
                }
                else {
                    $this->breadcrumbs->addItem($this->translator->trans($controller, [], 'menu'));
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
    }

    private function addPortalCrumb($request)
    {
        $portal = $this->legacyEnvironment->getEnvironment()->getCurrentPortalItem();
        if ($portal) {
            $this->breadcrumbs->addRouteItem($portal->getTitle(), "app_helper_portalenter", ["context" => $portal->getItemId()]);
        }
    }

    private function addRoom($roomItem, $asLink)
    {
        if ($roomItem->isGroupRoom()) {
            $this->addGroupRoom($roomItem, $asLink);
        }
        elseif ($roomItem->isUserroom()) {
            $this->addUserRoom($roomItem, $asLink);
        }
        elseif ($roomItem->isProjectRoom()) {
            $this->addProjectRoom($roomItem, $asLink);
        }
        elseif ($roomItem->isCommunityRoom()) {
            $this->addCommunityRoom($roomItem, $asLink);
        }
        elseif ($roomItem->isPrivateRoom()) {
            $this->addDashboard($roomItem, $asLink);
        }
    }

    private function addDashboard($roomItem, $asLink)
    {
        $this->breadcrumbs->addRouteItem($this->translator->trans('dashboard', [], 'menu'), "app_dashboard_overview", ["roomId" => $roomItem->getItemId()]);
    }

    private function addCommunityRoom($roomItem, $asLink)
    {
        $this->addRoomCrumb($roomItem, $asLink);
    }

    private function addProjectRoom($roomItem, $asLink)
    {
        // TODO: when called from addUserRoom(), $communityRoomItem is empty (even if the project room belongs to a community room)
        $communityRoomItem = $roomItem->getCommunityList()->getFirst();
        if ($communityRoomItem) {
            $this->addCommunityRoom($communityRoomItem, true);
            $this->addChildRoomListCrumb($communityRoomItem, 'project');
        }
        $this->addRoomCrumb($roomItem, $asLink);
    }

    private function addGroupRoom($roomItem, $asLink)
    {
        $groupItem = $roomItem->getLinkedGroupItem();
        if ($groupItem) {
            $projectRoom = $roomItem->getLinkedProjectItem();
            if ($projectRoom) {
                // ProjectRoom
                $this->addProjectRoom($projectRoom, true);
                // "Groups" rubric in project room
                $this->addChildRoomListCrumb($projectRoom, 'group');
                // Group (with name)
                $this->breadcrumbs->addRouteItem($groupItem->getTitle(), "app_group_detail", ['roomId' => $projectRoom->getItemId(), 'itemId' => $groupItem->getItemId()]);
                // Grouproom
                $this->addRoomCrumb($roomItem, $asLink);
            }
        }
    }

    private function addUserRoom($roomItem, $asLink)
    {
        $projectRoom = $roomItem->getLinkedProjectItem();
        if ($projectRoom) {
            // ProjectRoom
            $this->addProjectRoom($projectRoom, true);
            // "Persons" rubric in project room
            $this->addChildRoomListCrumb($projectRoom, 'userroom');
            // Userroom
            $this->addRoomCrumb($roomItem, $asLink);
        }
    }

    private function addRoomCrumb($roomItem, $asZelda)
    {
        // NOTE: the "Archived room: " room title prefix may be replaced by the template with a matching icon
        $title = $roomItem->getTitle();
        if ($roomItem->isArchived()) {
            $title = $this->translator->trans('Archived room', [], 'room') . ": " .  $title;
        }

        if ($asZelda == true) {
            $this->breadcrumbs->addRouteItem($title, "app_room_home", [
                'roomId' => $roomItem->getItemID(),
            ]);
        }
        else {
            $this->breadcrumbs->addItem($title);
        }
    }

    private function addProfileCrumbs($roomItem, $routeParameters, $action)
    {
        if($action == 'general' || $action == 'address' || $action == 'contact' || $action == 'deleteroomprofile' || $action == 'notifications') {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addRouteItem($this->translator->trans('Room profile', [], 'menu'), "app_profile_" . $action, $routeParameters);
        }
        else {
            $this->breadcrumbs->addRouteItem($this->translator->trans('Account', [], 'menu'), "app_profile_" . $action, $routeParameters);
        }

    }

    private function addChildRoomListCrumb($roomItem, $childRoomClass)
    {
        if ($childRoomClass == 'project' || $childRoomClass == 'group') {
            $this->breadcrumbs->addRouteItem(ucfirst($this->translator->trans($childRoomClass, [], 'menu')), "app_" . $childRoomClass . "_list", ['roomId' => $roomItem->getItemId()]);
        } else if ($childRoomClass == 'userroom') {
            $this->breadcrumbs->addRouteItem(ucfirst($this->translator->trans($childRoomClass, [], 'menu')), "app_user_list", ['roomId' => $roomItem->getItemId()]);
        }
    }
}