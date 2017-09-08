<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jsvrcek\ICS\Model\Calendar;
use Nette\Utils\Strings;

/**
 * Invitations
 *
 * @ORM\Table(name="calendars", indexes={@ORM\Index(name="id", columns={"id"})})
 * @ORM\Entity
 */
class RoomCategories
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="portal_id", type="integer", nullable=false)
     */
    private $portal_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set portalId
     *
     * @param integer $portalId
     *
     * @return Calendars
     */
    public function setPortalId($portalId)
    {
        $this->portal_id = $portalId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getPortalId()
    {
        return $this->portal_id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Calendars
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
