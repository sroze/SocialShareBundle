<?php
namespace SRozeIO\SocialShareBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents a social account.
 * 
 */
class SocialAccount
{
    /**
     * Account ID.
     * 
     * @var integer
     */
    protected $id;

    /**
     * The token used for authentication.
     * 
     * @var AuthToken
     */
    protected $token;

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
     * The realname to be displayed.
     * 
     * @var string
     */
    protected $realname;
    
    /**
     * Return an array representation of the field for form usage.
     * 
     * @return multitype:string
     */
    public function getFormField ()
    {
        return array(
            'provider' => $this->getProvider(),
            'realname' => $this->getRealName()
        );
    }

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
     * Set realname
     *
     * @param string $realname
     * @return SocialAccount
     */
    public function setRealname($realname)
    {
        $this->realname = $realname;

        return $this;
    }

    /**
     * Get realname
     *
     * @return string 
     */
    public function getRealname()
    {
        return $this->realname;
    }

    /**
     * Set token
     *
     * @param \SRozeIO\SocialShareBundle\Entity\AuthToken $token
     * @return SocialAccount
     */
    public function setToken(AuthToken $token = null)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return \SRozeIO\SocialShareBundle\Entity\AuthToken 
     */
    public function getToken()
    {
        return $this->token;
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

}
