<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\DateFilterType;

class DateController extends Controller
{    
    /**
     * @Route("/room/{roomId}/date/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(new DateFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_date_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $dates = $dateService->getListDates($roomId, $max, $start);

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($dates as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'roomId' => $roomId,
            'dates' => $dates,
            'readerList' => $readerList
        );
    }

    /**
     * @Route("/room/{roomId}/date")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(new DateFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_date_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'date'
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $dateService = $this->get('commsy_legacy.date_service');
        $itemService = $this->get('commsy.item_service');
        
        $date = $dateService->getDate($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $date;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        
        $itemArray = array($date);

        $readerService = $this->get('commsy.reader_service');
        
        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        return array(
            'roomId' => $roomId,
            'date' => $dateService->getDate($itemId),
            'readerList' => $readerList,
            'modifierList' => $modifierList
        );
    }
    
    /**
     * @Route("/room/{roomId}/date/events")
     */
    public function eventsAction($roomId, Request $request)
    {
        // get the material manager service
        $dateService = $this->get('commsy_legacy.date_service');

        $listDates = $dateService->getCalendarEvents($roomId, $_GET['start'], $_GET['end']);

        $events = array();
        foreach ($listDates as $date) {
            $start = $date->getStartingDay();
            if ($date->getStartingTime() != '') {
                $start .= 'T'.$date->getStartingTime().'Z';
            }
            $end = $date->getEndingDay();
            if ($end == '') {
                $end = $date->getStartingDay();
            }
            if ($date->getEndingTime() != '') {
                $end .= 'T'.$date->getEndingTime().'Z';
            } 
            
            $participantsList = $date->getParticipantsItemList();
            $participantItem = $participantsList->getFirst();
            $participantsNameArray = array();
            while ($participantItem) {
                $participantsNameArray[] = $participantItem->getFullname();
                $participantItem = $participantsList->getNext();    
            }
            $participantsDisplay = 'keine Zuordnung';
            if (!empty($participantsNameArray)) {
                implode(',', $participantsNameArray);
            }
            
            
            $events[] = array(//'id' => $date->getItemId(),
                              'title' => $date->getTitle(),
                              'start' => $start,
                              'end' => $end,
                              'color' => $date->getColor(),
                              'editable' => $date->isPublic(),
                              'description' => $date->getDateDescription(),
                              'place' => $date->getPlace(),
                              'participants' => $participantsDisplay
                             );
        }

        return new JsonResponse($events);
    }
    
    /**
     * @Route("/room/{roomId}/date/create")
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        
    }
}
