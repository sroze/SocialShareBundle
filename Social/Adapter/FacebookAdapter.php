<?php
namespace SRIO\SocialShareBundle\Social\Adapter;

use SRIO\SocialShareBundle\Entity\OAuth2Token;

use SRIO\SocialShareBundle\Entity\SharedObject;

use SRIO\SocialShareBundle\Social\Exception\ShareException;

use Buzz\Message\Response;

use SRIO\SocialShareBundle\Entity\AuthToken;

use SRIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRIO\SocialShareBundle\Entity\SocialAccount;

class FacebookAdapter extends AbstractOAuth2Adapter
{
    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::handleAuthorizationResponse()
     */
    public function handleAuthorizationResponse(Request $request, $redirectUrl) 
    {
        $code = $request->get('code', null);
        $access_token = $request->get('access_token', null);
        if ($code != null) {
            $response = $this->doGet($this->options['request_token_url'], array(
                'client_id' => $this->options['client_id'],
                'client_secret' => $this->options['client_secret'],
                'redirect_uri' => $redirectUrl,
                'code' => $code
            ));
            
            $access_token = $this->parseAccessToken($response);
        }
        
        if ($access_token != null) {
            // Exchange accessToken with a long-lived
            $response = $this->doGet($this->options['request_token_url'], array(
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
            $token = new OAuth2Token();
            $token->setAccessToken($access_token);
            $token->setCreationDate(new \DateTime());
            $token->setExpirationDate(\DateTime::createFromFormat('U', $jsonResponse['data']['expires_at']));
            
            // Create the account object
            $account = new SocialAccount();
            $account->setProvider($this->getName());
            $account->setSocialId($jsonResponse['data']['user_id']);
            $account->setToken($token);
            
            // Get user informations
            $informations = $this->getUserInformations($token);
            $account->setRealname($informations['name']);
            
            return $account;
        }
        
        throw new AuthorizationException("Unable to authenticate user");
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
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setDefaults(array(
            'authorization_url' => 'https://www.facebook.com/dialog/oauth',
            'request_token_url' => 'https://graph.facebook.com/oauth/access_token',
            'user_informations_url' => 'https://graph.facebook.com/me'
        ));
    }

    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::share()
     */
    public function share ($message, array $options = array())
    {
        $this->resolveOptions($options);
        
        // Share on user feed
        $rawResponse = $this->doAuthorizedGet('https://graph.facebook.com/'.$this->account->getSocialId().'/feed', array(
            'method' => 'POST',
            'message' => $message,
            'link' => $this->object->getLink(),
            'picture' => $this->object->getImage(),
            'description' => $this->object->getDescription(),
            'name' => $this->object->getTitle()
        ));
        $response = $this->getResponseContent($rawResponse);
        
        if (array_key_exists('error', $response)) {
            throw new ShareException($response['error']['message'], $response['error']['code']);
        } else if (!array_key_exists('id', $response)) {
            throw new ShareException("Unable to share: malformated response");
        }
        
        // Create the sharedobject
        $sharedObject = new SharedObject();
        $sharedObject->setProvider($this->getName());
        $sharedObject->setMessage($message);
        $sharedObject->setSocialId($response['id']);
        $sharedObject->setSocialAccount($this->account);
        
        // Add object to parent
        $this->object->addSharedObject($sharedObject);
    }
}
