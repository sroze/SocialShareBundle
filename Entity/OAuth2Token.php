<?php
namespace SRIO\SocialShareBundle\Entity;

class OAuth2Token extends AuthToken
{
    protected $refreshToken;
    protected $expirationDate;
    
    /**
     * Set the $id
     *
     * @param unknown_type
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Return the remaining time before the token expires.
     * 
     * @return integer Seconds (0 means expired)
     */
    public function getRemainingTime()
    {
        return $this->isExpired() ? 0
                : ($this->getExpirationDate()->getTimestamp() - time());
    }
    
    /**
     * Is this token expired ?
     * 
     * @return boolean
     */
    public function isExpired ()
    {
        return $this->getExpirationDate()->getTimestamp() < time();
    }

    /**
     * Return the unknown_type
     *
     * @return function
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }
    
    /**
     * Is this token containing a refresh token ?
     * 
     * @return boolean
     */
    public function hasRefreshToken ()
    {
        return $this->refreshToken != null;
    }

    /**
     * Set the $refreshToken
     *
     * @param unknown_type
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * Return the unknown_type
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Set the $expirationDate
     *
     * @param \DateTime
     */
    public function setExpirationDate(\DateTime $expirationDate)
    {
        $this->expirationDate = $expirationDate;
    }
}
