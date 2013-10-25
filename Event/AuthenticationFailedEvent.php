<?php
namespace SRIO\SocialShareBundle\Event;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Symfony\Component\HttpFoundation\Response;

use SRIO\SocialShareBundle\Entity\SocialAccount;

use Symfony\Component\EventDispatcher\Event;

class AuthenticationFailedEvent extends Event
{
    protected $exception;
    protected $response;
    
    /**
     * Constuctor.
     * 
     * @param SocialAccount $account
     */
    public function __construct (AuthenticationException $exception)
    {
        $this->exception = $exception;
    }
    
    /**
     * Get the fired exception
     * 
     * @return AuthenticationException
     */
    public function getException ()
    {
        return $this->exception;
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