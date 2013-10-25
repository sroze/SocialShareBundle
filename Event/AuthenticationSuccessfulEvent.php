<?php
namespace SRIO\SocialShareBundle\Event;

use Symfony\Component\HttpFoundation\Response;

use SRIO\SocialShareBundle\Entity\SocialAccount;

use Symfony\Component\EventDispatcher\Event;

class AuthenticationSuccessfulEvent extends Event
{
    protected $account;
    protected $response;
    
    /**
     * Constuctor.
     * 
     * @param SocialAccount $account
     */
    public function __construct (SocialAccount $account)
    {
        $this->account = $account;
    }
    
    /**
     * Get the social account created by this authentication.
     * 
     * @return SocialAccount
     */
    public function getAccount ()
    {
        return $this->account;
    }
    
    /**
     * Set the Response object that will be returned by the
     * connector controller.
     * 
     * @param Response $response
     */
    public function setResponse (Response $response)
    {
        $this->response = $response;
    }
    
    /**
     * Return the set response.
     * 
     * @return Response
     */
    public function getResponse ()
    {
        return $this->response;
    }
}