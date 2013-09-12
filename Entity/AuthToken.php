<?php
namespace SRozeIO\SocialShareBundle\Entity;
class AuthToken
{
    protected $id;
    protected $accessToken;
    protected $refreshToken;
    protected $expirationDate;
    protected $creationDate;

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
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the $accessToken
     *
     * @param unknown_type
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
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

    /**
     * Return the unknown_type
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set the $creationDate
     *
     * @param \DateTime
     */
    public function setCreationDate(\DateTime $creationDate)
    {
        $this->creationDate = $creationDate;
    }

}
