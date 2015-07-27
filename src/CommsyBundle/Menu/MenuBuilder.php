<?php

namespace CommsyBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Commsy\LegacyBundle\Utils\RoomService;
use Symfony\Component\Translation\Translator;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\UserService;

class MenuBuilder
{
    /**
    * @var Knp\Menu\FactoryInterface $factory
    */
    private $factory;

    private $roomService;

    private $legacyEnvironment;
    
    private $userService;

    /**
    * @param FactoryInterface $factory
    */
    public function __construct(FactoryInterface $factory, RoomService $roomService, LegacyEnvironment $legacyEnvironment, UserService $userService)
    {
        $this->factory = $factory;
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
    }

    /**
     * creates the profile sidebar
     * @param  RequestStack $requestStack [description]
     * @return knpMenu                    KnpMenu
     */
    public function createProfileMenu(RequestStack $requestStack)
    {
        // create profile
        $currentStack = $requestStack->getCurrentRequest();
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        $menu = $this->factory->createItem('root');

        $menu->addChild('profileImage', array(
            'label' => $currentUser->getFullname(),
            // 'route' => 'commsy_user_detail',
            // 'routeParameters' => array('itemId' => $currentUser->getItemId()),
            'extras' => array(
                'img' => '/app_dev.php/room/201/user/202/image',
                'imgClass' => 'uk-border-circle uk-img-preserve uk-thumbnail uk-align-center',
                'user' => $currentUser
            )
        ));

        // profile configuration
        $menu->addChild('profileConfig', array(
            'label' => ' ',
            'route' => 'commsy_room_home',
            'routeParameters' => array('roomId' => $currentStack->attributes->get('roomId')),
            'extras' => array('icon' => 'uk-icon-cog uk-icon-small')
        ));

        return $menu;
    }

    public function createSettingsMenu(RequestStack $requestStack)
    {
        // get room Id
        $currentStack = $requestStack->getCurrentRequest();
        $roomId = $currentStack->attributes->get('roomId');

        // create root item
        $menu = $this->factory->createItem('root');

        if ($roomId) {
            // dashboard
            $menu->addChild('dashboard', array(
                'label' => 'Dashboard',
                'route' => 'commsy_settings_dashboard',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-dashboard uk-icon-small')
            ));

            // general settings
            $menu->addChild('general', array(
                'label' => 'General',
                'route' => 'commsy_settings_general',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-server uk-icon-small')
            ));
        }
        
        // identifier
        
        // moderation
        
        // additional
        
        // extensions
        
        // plugins

        return $menu;
    }

    /**
     * creates rubric menu
     * @param  RequestStack $requestStack [description]
     * @return KnpMenu                    KnpMenu
     */
    public function createMainMenu(RequestStack $requestStack)
    {
        // get room id
        $currentStack = $requestStack->getCurrentRequest();

        // create root item for knpmenu
        $menu = $this->factory->createItem('root');

        $roomId = $currentStack->attributes->get('roomId');

        if ($roomId)
        {
            // dashboard
            $user = $this->userService->getPortalUserFromSessionId();
            $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
            $authSource = $authSourceManager->getItem($user->getAuthSource());
            $this->legacyEnvironment->setCurrentPortalID($authSource->getContextId());
            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($user,$this->legacyEnvironment->getCurrentPortalID());
            
            $menu->addChild('dashboard', array(
                'label' => 'DASHBOARD',
                'route' => 'commsy_dashboard_index',
                'routeParameters' => array('roomId' => $privateRoom->getItemId()),
                'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            ));

            if ($roomId != $privateRoom->getItemId()) {
                // rubric room information
                $rubrics = $this->roomService->getRubricInformation($roomId);
                
                // room navigation
                $menu->addChild('room_navigation', array(
                    'label' => 'Raum-Navigation',
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => 'uk-icon-home uk-icon-small')
                ));

                // home navigation
                $menu->addChild('room_home', array(
                    'label' => 'Home',
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => 'uk-icon-home uk-icon-small')
                ));
    
                // loop through rubrics to build the menu
                foreach ($rubrics as $value) {
                    $menu->addChild($value, array(
                        'label' => $value,
                        'route' => 'commsy_'.$value.'_list',
                        'routeParameters' => array('roomId' => $roomId),
                        'extras' => array('icon' => $this->getRubricIcon($value))
                    ));
                }
            } else {
                $menu->addChild('')->setAttribute('class', 'uk-nav-divider');
                
                $projectArray = array();
                $projectList = $user->getRelatedProjectList();
                $project = $projectList->getFirst();
                while ($project) {
                    $menu->addChild($project->getTitle(), array(
                        'label' => $project->getTitle(),
                        'route' => 'commsy_room_home',
                        'routeParameters' => array('roomId' => $project->getItemId()),
                        'extras' => array('icon' => 'uk-icon-home uk-icon-small')
                    ));
                    $project = $projectList->getNext();
                }
            }
        }

        return $menu;
    }

    /**
     * returns the uikit icon classname for a specific rubric
     * @param  string $rubric rubric name
     * @return string         uikit icon class
     */
    public function getRubricIcon($rubric)
    {
        // return uikit icon class for rubric
        switch ($rubric) {
            case 'announcement':
                $class = "uk-icon-justify uk-icon-comment-o uk-icon-small";
                break;
            case 'date':
                $class = "uk-icon-justify uk-icon-calendar uk-icon-small";
                break;
            case 'material':
                $class = "uk-icon-justify uk-icon-file-o uk-icon-small";
                break;
            case 'discussion':
                $class = "uk-icon-justify uk-icon-comments-o uk-icon-small";
                break;
            case 'user':
                $class = "uk-icon-justify uk-icon-user uk-icon-small";
                break;
            case 'group':
                $class = "uk-icon-justify uk-icon-group uk-icon-small";
                break;
            case 'todo':
                $class = "uk-icon-justify uk-icon-home uk-icon-small";
                break;
            case 'topic':
                $class = "uk-icon-justify uk-icon-check uk-icon-small";
                break;
            
            default:
                $class = "uk-icon-justify uk-icon-home uk-icon-small";
                break;
        }
        return $class;
    }

    /**
     * creates the breadcrumb
     * @param  RequestStack $requestStack [description]
     * @return menuItem                   [description]
     */
    public function createBreadcrumbMenu(RequestStack $requestStack)
    {
        // get room id
        $currentStack = $requestStack->getCurrentRequest();

        // create breadcrumb menu
        $menu = $this->factory->createItem('root');

        $roomId = $currentStack->attributes->get('roomId');
        if ($roomId) {
            // this item will always be displayed
            $user = $this->userService->getPortalUserFromSessionId();
            $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
            $authSource = $authSourceManager->getItem($user->getAuthSource());
            $this->legacyEnvironment->setCurrentPortalID($authSource->getContextId());
            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($user,$this->legacyEnvironment->getCurrentPortalID());
            
            $menu->addChild('DASHBOARD', array(
                'route' => 'commsy_dashboard_index',
                'routeParameters' => array('roomId' => $privateRoom->getItemId()),
            ));

            $itemId = $currentStack->attributes->get('itemId');
            $roomItem = $this->roomService->getRoomItem($roomId);
    
            if ($roomItem) {
                // get route information
                $route = explode('_', $currentStack->attributes->get('_route'));
                
                // room
                $menu->addChild($roomItem->getTitle(), array(
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $roomId)
                ));
        
                if ($route[1] && $route[1] != "room" && $route[1] != "dashboard" && $route[2] != "search") {
                    // rubric
                    $menu->addChild($route[1], array(
                        'route' => 'commsy_'.$route[1].'_'.'list',
                        'routeParameters' => array('roomId' => $roomId)
                    ));
        
                    if ($route[2] != "list") {
                        // item
                        $itemService = $this->legacyEnvironment->getItemManager();
                        $item = $itemService->getItem($itemId);
                        $tempManager = $this->legacyEnvironment->getManager($item->getItemType());
                        $tempItem = $tempManager->getItem($itemId);
                        $itemText = '';
                        if ($tempItem->getItemType() == 'user') {
                            $itemText = $tempItem->getFullname();
                        } else {
                            $itemText = $tempItem->getTitle();
                        }
                        $menu->addChild($itemText, array(
                            'route' => 'commsy_'.$route[1].'_'.$route[2],
                            'routeParameters' => array(
                                'roomId' => $roomId,
                                'itemId' => $itemId
                            )
                        ));
                    }
                }
            }
        }
        

        return $menu;
    }

}