<?php
namespace SRozeIO\SocialShareBundle\Entity;
use SRozeIO\SocialShareBundle\Social\Object\AbstractSharedObject;

class SharedObject extends AbstractSharedObject
{
    /**
     * Object ID.
     *
     * @var integer
     */
    protected $id;

    /**
     * The social provider.
     * 
     * @var string
     */
    protected $provider;

    /**
     * The unique identifier on the social network.
     * 
     * @var integer
     */
    protected $socialId;

    /**
     * The message linked with.
     * 
     * @var string
     */
    protected $message;

    /**
     * Social account used to share this object.
     * 
     * @var SocialAccount
     */
    protected $socialAccount;

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
     * Set socialId
     *
     * @param string $socialId
     * @return SocialAccount
     */
    public function setSocialId($socialId)
    {
        $this->socialId = $socialId;

        return $this;
    }

    /**
     * Get socialId
     *
     * @return string 
     */
    public function getSocialId()
    {
        return $this->socialId;
    }

    /**
     * Get the social provider.
     * 
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the provider name.
     * 
     * @param string $provider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get the message.
     * 
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the message
     * 
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get social account used to share object.
     * 
     * @return \SRozeIO\SocialShareBundle\Entity\SocialAccount
     */
    public function getSocialAccount()
    {
        return $this->socialAccount;
    }

    /**
     * Set social account used to share object.
     * 
     * @param SocialAccount $socialAccout
     */
    public function setSocialAccount(SocialAccount $socialAccount)
    {
        $this->socialAccount = $socialAccount;
    }
}
