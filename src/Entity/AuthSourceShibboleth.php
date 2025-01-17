<?php


namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class AuthSourceShibboleth extends AuthSource
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private ?string $loginUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private ?string $logoutUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private ?string $passwordResetUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $mappingUsername;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $mappingFirstname;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $mappingLastname;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $mappingEmail;

    /**
     * @ORM\Column(type="array", name="identity_provider")
     */
    private ?Collection $identityProviders;

    protected string $type = 'shib';

    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
        $this->identityProviders = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getLoginUrl(): ?string
    {
        return $this->loginUrl;
    }

    /**
     * @param string $loginUrl
     * @return self
     */
    public function setLoginUrl(string $loginUrl): self
    {
        $this->loginUrl = $loginUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogoutUrl(): ?string
    {
        return $this->logoutUrl;
    }

    /**
     * @param string|null $logoutUrl
     * @return self
     */
    public function setLogoutUrl(?string $logoutUrl): self
    {
        $this->logoutUrl = $logoutUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordResetUrl(): ?string
    {
        return $this->passwordResetUrl;
    }

    /**
     * @param string|null $passwordResetUrl
     * @return self
     */
    public function setPasswordResetUrl(?string $passwordResetUrl): self
    {
        $this->passwordResetUrl = $passwordResetUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getMappingUsername(): ?string
    {
        return $this->mappingUsername;
    }

    /**
     * @param string $mappingUsername
     * @return self
     */
    public function setMappingUsername(string $mappingUsername): self
    {
        $this->mappingUsername = $mappingUsername;
        return $this;
    }

    /**
     * @return string
     */
    public function getMappingFirstname(): ?string
    {
        return $this->mappingFirstname;
    }

    /**
     * @param string $mappingFirstname
     * @return self
     */
    public function setMappingFirstname(string $mappingFirstname): self
    {
        $this->mappingFirstname = $mappingFirstname;
        return $this;
    }

    /**
     * @return string
     */
    public function getMappingLastname(): ?string
    {
        return $this->mappingLastname;
    }

    /**
     * @param string $mappingLastname
     * @return self
     */
    public function setMappingLastname(string $mappingLastname): self
    {
        $this->mappingLastname = $mappingLastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getMappingEmail(): ?string
    {
        return $this->mappingEmail;
    }

    /**
     * @param string $mappingEmail
     * @return self
     */
    public function setMappingEmail(string $mappingEmail): self
    {
        $this->mappingEmail = $mappingEmail;
        return $this;
    }

    /**
     * @return Collection|ShibbolethIdentityProvider[]|null
     */
    public function getIdentityProviders(): ?Collection
    {
        return $this->identityProviders;
    }

    public function setIdentityProviders(Collection $identityProviders): self
    {
        $this->identityProviders = new ArrayCollection();

        foreach ($identityProviders as $provider) {
            $this->identityProviders->add($provider);
        }
        return $this;
    }
}