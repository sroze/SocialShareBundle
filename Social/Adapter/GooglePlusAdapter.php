<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use SRozeIO\SocialShareBundle\Entity\SharedObject;

use SRozeIO\SocialShareBundle\Social\Exception\ShareException;

use Symfony\Component\HttpFoundation\Request;

use SRozeIO\SocialShareBundle\Entity\AuthToken;

use SRozeIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

class GooglePlusAdapter extends AbstractOAuth2Adapter
{
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractOAuth2Adapter::getAuthorizationUrl()
     */
    public function getAuthorizationUrl($redirectUrl, array $parameters = array())
    {
        if (!array_key_exists('scope', $parameters) && array_key_exists('scope', $this->options)) {
            $parameters['scope'] = $this->options['scope'];
        }
        if (!array_key_exists('request_visible_actions', $parameters)
            && array_key_exists('request_visible_actions', $this->options)) {
            $parameters['request_visible_actions'] = $this->options['request_visible_actions'];
        }if (!array_key_exists('approval_prompt', $parameters)
            && array_key_exists('approval_prompt', $this->options)) {
            $parameters['approval_prompt'] = $this->options['approval_prompt'];
        }
    
        return parent::getAuthorizationUrl($redirectUrl, array_merge(array(
            'response_type' => $this->options['response_type'],
            'access_type' => $this->options['access_type']
        ), $parameters));
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setOptional(array(
            'scope',
            'response_type',
            'access_type',
            'request_visible_actions',
            'approval_prompt'
        ));
        
        $resolver->setDefaults(array(
            'authorization_url' => 'https://accounts.google.com/o/oauth2/auth',
            'request_token_url' => 'https://accounts.google.com/o/oauth2/token',
            
            'response_type' => 'code',
            'scope' => 'openid profile',
            'access_type' => 'offline'
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::handleAuthorizationResponse()
     */
    public function handleAuthorizationResponse(Request $request, $redirectUrl) 
    {
        if (($error = $request->get('error', null)) != null) {
            throw new AuthorizationException("Unable to authenticate user: ".$error);
        } else if (($code = $request->get('code', null)) != null) {
            $token = $this->requestToken($code, $redirectUrl);
            $informations = $this->requestUserInformations($token);
            
            // Create the account object
            $account = new SocialAccount();
            $account->setProvider($this->getName());
            $account->setSocialId($informations['sub']);
            $account->setToken($token);
            $account->setRealname($informations['name']);
            
            return $account;
        } else {
            throw new AuthorizationException("Unable to authenticate user, bad response.");
        }
    }
    
    /**
     * Request the user informations.
     * 
     * @param AuthToken $token
     */
    protected function requestUserInformations (AuthToken $token)
    {
        $response = $this->doGet('https://www.googleapis.com/oauth2/v3/userinfo', array(
            'access_token' => $token->getAccessToken()
        ));
        
        $jsonResponse = json_decode($response->getContent(), true);
        if ($jsonResponse == null || array_key_exists('error', $jsonResponse)) {
            throw new AuthorizationException("Unable to grab user informations");
        } else if (!array_key_exists('sub', $jsonResponse)) {
            throw new AuthorizationException("Unable to get user ID (sub)");
        }
        
        return $jsonResponse;
    }
    
    /**
     * Request for a token based on the code.
     * 
     * @param string $code
     * @param string $redirectUrl
     * @return AuthToken
     */
    protected function requestToken ($code, $redirectUrl)
    {
        $response = $this->doPost($this->options['request_token_url'], array(
            'code' => $code,
            'client_id' => $this->options['client_id'],
            'client_secret' => $this->options['client_secret'],
            'redirect_uri' => $redirectUrl,
            'grant_type' => 'authorization_code'
        ));
        
        $jsonResponse = json_decode($response->getContent(), true);
        if (!array_key_exists('access_token', $jsonResponse)) {
            throw new AuthorizationException("Bad token request response");
        }
        
        $token = new AuthToken();
        $token->setAccessToken($jsonResponse['access_token']);
        if (array_key_exists('refresh_token', $jsonResponse)) {
            $token->setRefreshToken($jsonResponse['refresh_token']);
        }
        
        $expirationDate = new \DateTime();
        $expirationDate->add(new \DateInterval('PT'.$jsonResponse['expires_in'].'S'));
        $token->setExpirationDate($expirationDate);
        $token->setCreationDate(new \DateTime());
        
        return $token;
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::share()
     */
    public function share ($message, array $options = array())
    {
        $this->resolveOptions($options);
        
        $query_parameters = http_build_query(array(
            'access_token' => $this->account->getToken()->getAccessToken()
        ));
        $content = json_encode(array(
            'type' => 'http://schemas.google.com/CreateActivity',
            'target' => array(
                'id' => 'afuckingid',
                //'url' => $this->object->getLink(),
                'type' => 'http://schema.org/WebPage',
                'name' => $message,
                'caption' => $this->object->getTitle(),
                'description' => $this->object->getDescription(),
                'image' => $this->object->getImage(),
            )
        ));
        $response = $this->doPost(
            'https://www.googleapis.com/plus/v1/people/'.$this->account->getSocialId().'/moments/vault?'.$query_parameters, 
            $content,
            array(
                'Content-type' => 'application/json'
            )
        );
        
        $jsonResponse = json_decode($response->getContent(), true);
        if (array_key_exists('error', $jsonResponse)) {
            throw new ShareException("Unable to share to G+: ".json_encode($jsonResponse['error']));
        }
        
        // Create the shared object
        $sharedObject = new SharedObject();
        $sharedObject->setMessage($message);
        $sharedObject->setProvider($this->getName());
        $sharedObject->setSocialId($jsonResponse['id']);
        
        // Add object to parent
        $this->object->addSharedObject($sharedObject);
    }
}
