<?php
namespace SRozeIO\SocialShareBundle\Entity;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;

class AuthToken extends OAuthToken
{
    protected $id;
    
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
     * Return the remaining time before the token expires.
     * 
     * @return integer Seconds (0 means expired)
     */
    public function getRemainingTime ()
    {
        return $this->isExpired() ? 0 : ($this->createdAt + $this->expiresIn - time());
    }
    
    /**
     * Get the created timestamp.
     * 
     * @return number
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * Set the created timestamp.
     * 
     * @param unknown $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}