<?php

namespace SRIO\SocialShareBundle\Controller;

use SRIO\SocialShareBundle\Event\AuthenticationFailedEvent;

use SRIO\SocialShareBundle\SocialShareEvents;

use SRIO\SocialShareBundle\Event\AuthenticationSuccessfulEvent;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\RedirectResponse;

use SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ConnectorController extends Controller
{
    /**
     * Redirect a user to the authentication page.
     * 
     * @Route("/connect/{adapterName}", name="social_connect")
     * @param string $adapterName
     */
    public function redirectAction(Request $request, $adapterName)
    {
        $adapter = $this->getAdapter($adapterName);
        $redirectUri = $this->generateRedirectUrl($adapterName);
        $url = $adapter->getAuthorizationUrl($redirectUri);
        
        return new RedirectResponse($url);
    }
    
    /**
     * Handle an authorization callback.
     * 
     * @Route("/callback/{adapterName}", name="social_callback")
     * @param string $adapterName
     */
    public function callbackAction (Request $request, $adapterName)
    {
        $adapter = $this->getAdapter($adapterName);
        $redirectUri = $this->generateRedirectUrl($adapterName);
        
        try {
            $socialAccount = $adapter->handleAuthorizationResponse($request, $redirectUri);
            
            $event = new AuthenticationSuccessfulEvent($socialAccount);
            $this->get('event_dispatcher')->dispatch(SocialShareEvents::AUTHENTICATION_SUCCESSFUL, $event);
            
            if ($event->getResponse() == null) {
                throw new \LogicException("An event listener should populate the response field");
            }
            
            return $event->getResponse();
        } catch (AuthorizationException $e) {
            $event = new AuthenticationFailedEvent($e);
            $this->get('event_dispatcher')->dispatch(SocialShareEvents::AUTHENTICATION_FAILED, $event);
            
            if ($event->getResponse() == null) {
                throw new \LogicException("An event listener should populate the response field");
            }
            
            return $event->getResponse();
        }
    }
    
    /**
     * Generate the redirect URL.
     * 
     * @param unknown $adapaterName
     * @return string
     */
    protected function generateRedirectUrl ($adapterName)
    {
        return $this->getRequest()->getSchemeAndHttpHost().$this->generateUrl('social_callback', array(
            'adapterName' => $adapterName
        ));
    }
    
    /**
     * Get the adapter instance based on its name.
     * 
     * @param string $adapterName
     * @return AbstractAdapter
     */
    protected function getAdapter ($adapterName)
    {
        return $this->get('srio.social_share.adapter.'.$adapterName);
    }
}
