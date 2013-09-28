<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use SRozeIO\SocialShareBundle\Social\Exception\TokenException;

use SRozeIO\SocialShareBundle\Entity\OAuth2Token;

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
            'user_informations_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
            
            'response_type' => 'code',
            'scope' => 'openid profile',
            'access_type' => 'offline'
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractOAuth2Adapter::getUserInformations()
     */
    protected function getUserInformations (OAuth2Token $token)
    {
        $response = parent::getUserInformations($token);
        if (!array_key_exists('sub', $response)) {
            throw new AuthorizationException("Unable to get user ID (sub)");
        }
        
        // Set response minimum parameters
        $response['id'] = $response['sub'];
        
        return $response;
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractOAuth2Adapter::doRefreshToken()
     */
    public function doRefreshToken (OAuth2Token $token)
    {
        if (!$token->hasRefreshToken()) {
            throw new TokenException('Unable to refresh token without a refresh token');
        }
        
        $rawResponse = $this->doPost($this->options['request_token_url'], array(
            'client_id' => $this->options['client_id'],
            'client_secret' => $this->options['client_secret'],
            'refresh_token' => $token->getRefreshToken(),
            'grant_type' => 'refresh_token'
        ));
        $response = $this->getResponseContent($rawResponse);
        $this->populateToken($token, $response);
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
                'id' => $this->generateNonce(),
                'url' => $this->object->getLink(),
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
            $errorMessage = $jsonResponse['error']['message'];
            throw new ShareException("Unable to share to G+: ".$errorMessage);
        }
        
        // Create the shared object
        $sharedObject = new SharedObject();
        $sharedObject->setMessage($message);
        $sharedObject->setProvider($this->getName());
        $sharedObject->setSocialId($jsonResponse['id']);
        $sharedObject->setSocialAccount($this->account);
        
        // Add object to parent
        $this->object->addSharedObject($sharedObject);
    }
}
