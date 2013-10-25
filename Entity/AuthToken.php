<?php
namespace SRIO\SocialShareBundle\Entity;

class AuthToken
{
    protected $id;
    protected $accessToken;
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
     * Is this token expired ?
     * 
     * @return boolean
     */
    public function isExpired ()
    {
        return false;
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
