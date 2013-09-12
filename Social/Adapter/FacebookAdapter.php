<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use Buzz\Message\Response;

use SRozeIO\SocialShareBundle\Entity\AuthToken;

use SRozeIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

class FacebookAdapter extends AbstractOAuth2Adapter
{
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::share()
     */
    public function share ($message, array $options = array())
    {
        var_dump($message);
        $this->resolveOptions($options);
        var_dump($this->object != null);
        if ($this->object != null) {
            $object = $this->createObject();
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::handleAuthorizationResponse()
     */
    public function handleAuthorizationResponse(Request $request, $redirectUrl) 
    {
        $code = $request->get('code', null);
        $access_token = $request->get('access_token', null);
        if ($code != null) {
            $response = $this->doGet('https://graph.facebook.com/oauth/access_token', array(
                'client_id' => $this->options['client_id'],
                'client_secret' => $this->options['client_secret'],
                'redirect_uri' => $redirectUrl,
                'code' => $code
            ));
            
            $access_token = $this->parseAccessToken($response);
        }
        
        if ($access_token != null) {
            // Exchange accessToken with a long-lived
            $response = $this->doGet('https://graph.facebook.com/oauth/access_token', array(
                'client_id' => $this->options['client_id'],
                'client_secret' => $this->options['client_secret'],
                'grant_type' => 'fb_exchange_token',
                'fb_exchange_token' => $access_token
            ));
            $access_token = $this->parseAccessToken($response);
            
            // Debug token to get more informations
            $response = $this->doGet('https://graph.facebook.com/debug_token', array(
                'input_token' => $access_token,
                'access_token' => $this->getApplicationToken()
            ));
            
            $jsonResponse = json_decode($response->getContent(), true);
            if (!array_key_exists('data', $jsonResponse)) {
                throw new AuthorizationException("Token inspection failed");
            }
            
            // Create the access token object
            $token = new AuthToken();
            $token->setAccessToken($access_token);
            $token->setCreationDate(new \DateTime());
            $token->setExpirationDate(\DateTime::createFromFormat('U', $jsonResponse['data']['expires_at']));
            
            // Create the account object
            $account = new SocialAccount();
            $account->setProvider($this->getName());
            $account->setSocialId($jsonResponse['data']['user_id']);
            $account->setToken($token);
            
            // Get user informations
            $informations = $this->getUserInformations($account);
            $account->setRealname($informations['name']);
            
            return $account;
        }
        
        throw new AuthorizationException("Unable to authenticate user");
    }
    
    /**
     * Get the user informations.
     * 
     * @param SocialAccount $account
     */
    protected function getUserInformations (SocialAccount $account)
    {
        $response = $this->doGet('https://graph.facebook.com/'.$account->getSocialId(), array(
            'access_token' => $account->getToken()->getAccessToken()
        ));
        $jsonResponse = json_decode($response->getContent(), true);
        if (array_key_exists('error', $jsonResponse)) {
            throw new AuthorizationException($jsonResponse['error']['message'], $jsonResponse['error']['code']);
        }
        
        return $jsonResponse;
    }
    
    /**
     * Parse an access token from FB API response.
     * 
     * @param Response $response
     * @throws AuthorizationException
     */
    protected function parseAccessToken (Response $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);
        if ($jsonResponse == null) parse_str($response->getContent(), $jsonResponse);
        if (array_key_exists('error', $jsonResponse)) {
            throw new AuthorizationException($jsonResponse['error']['message'], $jsonResponse['error']['code']);
        } else if (array_key_exists('access_token', $jsonResponse) && array_key_exists('expires', $jsonResponse)) {
            return $jsonResponse['access_token'];
        } else {
            throw new AuthorizationException("Access token response ununderstandable");
        }
    }
    
    /**
     * Get the Facebook application access token.
     * 
     * @return string
     */
    protected function getApplicationToken ()
    {
        return $this->options['client_id'].'|'.$this->options['client_secret'];
    }

    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setDefaults(array(
            'authorization_url' => 'https://www.facebook.com/dialog/oauth'
        ));
    }
    
    /**
     * Create the OpenGraph object.
     * 
     */
    protected function createObject ()
    {
        // Create the graph object
        $graphObject = array(
            'type' => 'article',
            'url' => $this->object->getLink(),
            'title' => $this->object->getTitle(),
            'description' => $this->object->getDescription()
        );
        
        $objectUrl = 'https://graph.facebook.com/'.$this->account->getSocialId().'/objects/article?'.http_build_query(array(
            'access_token' => $this->account->getToken(),
            'object' => json_encode($graphObject)
        ));
        $response = $this->buzz->get($objectUrl);
        
        var_dump($response->getContent());
    }
}
