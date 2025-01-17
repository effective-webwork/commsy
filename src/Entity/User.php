<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * User
 *
 * @ORM\Table(
 *     name="user",
 *     indexes={
 *         @ORM\Index(name="creator_idx", columns={"creator_id"}),
 *         @ORM\Index(name="deleted_idx", columns={"deletion_date", "deleter_id"}),
 *         @ORM\Index(name="context_idx", columns={"context_id"}),
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_non_soft_deleted_idx", columns={"user_id", "auth_source", "context_id", "not_deleted"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"api_read"})
     * @OA\Property(description="The unique identifier.")
     */
    public $itemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="item_id")
     */
    private $creator;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modifier_id", referencedColumnName="item_id")
     */
    private $modifier;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     *
     * @Groups({"api_read"})
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     *
     * @Groups({"api_read"})
     */
    private $modificationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @ORM\Column(name="not_deleted", type="boolean", insertable=false, updatable=false,generated="ALWAYS",
     *     columnDefinition="TINYINT(1) AS (IF (deleter_id IS NULL AND deletion_date IS NULL, 1, NULL)) PERSISTENT AFTER deletion_date"
     * )
     */
    private $isNotDeleted;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=100, nullable=false)
     *
     * @Groups({"api_read"})
     */
    public $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", nullable=false)
     *
     * @Groups({"api_read"})
     */
    private $status = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_contact", type="boolean", nullable=false)
     */
    private $isContact = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=50, nullable=false)
     *
     * @Groups({"api_read"})
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=100, nullable=false)
     *
     * @Groups({"api_read"})
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=100, nullable=false)
     */
    private $city;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     */
    private $visible = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=16777215, nullable=true)
     */
    private $extras;

    /**
     * @var integer
     *
     * @ORM\Column(name="auth_source", type="integer", nullable=true)
     */
    private $authSource;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expire_date", type="datetime", nullable=true)
     */
    private $expireDate;

    /**
     * @ORM\Column(name="use_portal_email", type="boolean")
     */
    private $usePortalEmail = false;

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return User
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set creator
     *
     * @param \App\Entity\User $modifier
     *
     * @return User
     */
    public function setCreator(\App\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \App\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set modifier
     *
     * @param \App\Entity\User $modifier
     *
     * @return User
     */
    public function setModifier(\App\Entity\User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return \App\Entity\User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Set deleterId
     *
     * @param integer $deleterId
     *
     * @return User
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId
     *
     * @return integer
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * isDeleted
     *
     * @return boolean
     */
    public function isDeleted(): bool
    {
        return ($this->deleterId !== null && $this->deletionDate !== null);
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return User
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate
     *
     * @param \DateTime $modificationDate
     *
     * @return User
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate
     *
     * @param \DateTime $deletionDate
     *
     * @return User
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate
     *
     * @return \DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set userId
     *
     * @param string $userId
     *
     * @return User
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return User
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set isContact
     *
     * @param boolean $isContact
     *
     * @return User
     */
    public function setIsContact($isContact)
    {
        $this->isContact = $isContact;

        return $this;
    }

    /**
     * Get isContact
     *
     * @return boolean
     */
    public function getIsContact()
    {
        return $this->isContact;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return User
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set lastlogin
     *
     * @param \DateTime $lastlogin
     *
     * @return User
     */
    public function setLastlogin($lastlogin)
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return \DateTime
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     *
     * @return User
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set extras
     *
     * @param string $extras
     *
     * @return User
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set authSource
     *
     * @param integer $authSource
     *
     * @return User
     */
    public function setAuthSource($authSource)
    {
        $this->authSource = $authSource;

        return $this;
    }

    /**
     * Get authSource
     *
     * @return integer
     */
    public function getAuthSource()
    {
        return $this->authSource;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return User
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set expireDate
     *
     * @param \DateTime $expireDate
     *
     * @return User
     */
    public function setExpireDate($expireDate)
    {
        $this->expireDate = $expireDate;

        return $this;
    }

    /**
     * Get expireDate
     *
     * @return \DateTime
     */
    public function getExpireDate()
    {
        return $this->expireDate;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    public function isIndexable()
    {
        return ($this->deleterId == null && $this->deletionDate == null);
    }

    public function getFullname()
    {
        return trim($this->getFirstname() . ' ' . $this->getLastname());
    }
}
