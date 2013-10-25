<?php
namespace SRIO\SocialShareBundle\Entity;

class OAuth1Token extends AuthToken
{
    protected $tokenSecret;

    /**
     * Set the token secret.
     * 
     * @param string $secret
     */
    public function setTokenSecret ($secret)
    {
        $this->tokenSecret = $secret;
    }
    
    /**
     * Get the token secret.
     * 
     * @return string
     */
    public function getTokenSecret ()
    {
        return $this->tokenSecret;
    }
}
