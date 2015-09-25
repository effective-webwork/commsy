<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommSy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class PrivateRoomTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param cs_room_item $roomItem
     * @return array
     */
    public function transform($privateRoomItem)
    {
        $privateRoomData = array();
        if ($privateRoomItem) {
            $privateRoomData['newsletterStatus'] = $privateRoomItem->getPrivateRoomNewsletterActivity();
            if ($privateRoomItem->getCSBarShowWidgets() == '1') {
                $privateRoomData['widgetStatus'] = true;
            } else {
                $privateRoomData['widgetStatus'] = false;
            }
            if ($privateRoomItem->getCSBarShowCalendar() == '1') {
                $privateRoomData['calendarStatus'] = true;
            } else {
                $privateRoomData['calendarStatus'] = false;
            }
            if ($privateRoomItem->getCSBarShowStack() == '1') {
                $privateRoomData['stackStatus'] = true;
            } else {
                $privateRoomData['stackStatus'] = false;
            }
            if ($privateRoomItem->getCSBarShowPortfolio() == '1') {
                $privateRoomData['portfolioStatus'] = true;
            } else {
                $privateRoomData['portfolioStatus'] = false;
            }
            if ($privateRoomItem->getCSBarShowOldRoomSwitcher() == '1') {
                $privateRoomData['switchRoomStatus'] = true;
            } else {
                $privateRoomData['switchRoomStatus'] = false;
            }
        }
        return $privateRoomData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($privateRoomObject, $privateRoomData)
    {
        if ($privateRoomObject) {
           if ($privateRoomData['widgetStatus'] == '1') {
                $privateRoomObject->setCSBarShowWidgets('1');
            } else {
                $privateRoomObject->setCSBarShowWidgets('-1');
            }
            
            if ($privateRoomData['calendarStatus'] == '1') {
                $privateRoomObject->setCSBarShowCalendar('1');
            } else {
                $privateRoomObject->setCSBarShowCalendar('-1');
            }
            
            if ($privateRoomData['stackStatus'] == '1') {
                $privateRoomObject->setCSBarShowStack('1');
            } else {
                $privateRoomObject->setCSBarShowStack('-1');
            }
            
            if ($privateRoomData['portfolioStatus'] == '1') {
                $privateRoomObject->setCSBarShowPortfolio('1');
            } else {
                $privateRoomObject->setCSBarShowPortfolio('-1');
            }
            
            if ($privateRoomData['switchRoomStatus'] == '1') {
                $privateRoomObject->setCSBarShowOldRoomSwitcher('1');
            } else {
                $privateRoomObject->setCSBarShowOldRoomSwitcher('-1');
            }
        }
        return $privateRoomObject;
    }
}