<?php
namespace SRIO\SocialShareBundle\Social\Adapter;

use Symfony\Component\Config\Util\XmlUtils;

use SRIO\SocialShareBundle\Entity\OAuth2Token;

use SRIO\SocialShareBundle\Entity\SharedObject;

use SRIO\SocialShareBundle\Social\Exception\ShareException;

use Symfony\Component\HttpFoundation\Request;

use SRIO\SocialShareBundle\Entity\AuthToken;

use SRIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRIO\SocialShareBundle\Entity\SocialAccount;

class LinkedInAdapter extends AbstractOAuth2Adapter
{
    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractOAuth2Adapter::getAuthorizationUrl()
     */
    public function getAuthorizationUrl($redirectUrl, array $parameters = array())
    {
        if (!array_key_exists('scope', $parameters) && array_key_exists('scope', $this->options)) {
            $parameters['scope'] = $this->options['scope'];
        }
    
        // Generate a state parameter
        $state = $this->generateNonce();
        $parameters = array_merge(array(
            'response_type' => $this->options['response_type'],
            'state' => $state
        ), $parameters);
        $this->getTokenBag()->set($this, $parameters);
        
        return parent::getAuthorizationUrl($redirectUrl, $parameters);
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setOptional(array(
            'scope',
            'response_type'
        ));
        
        $resolver->setDefaults(array(
            'authorization_url' => 'https://www.linkedin.com/uas/oauth2/authorization',
            'request_token_url' => 'https://www.linkedin.com/uas/oauth2/accessToken',
            'user_informations_url' => 'https://api.linkedin.com/v1/people/~:(id,first-name,last-name)',
                
            'access_token_parameter_name' => 'oauth2_access_token',
                
            'response_type' => 'code'
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::handleAuthorizationResponse()
     */
    public function handleAuthorizationResponse(Request $request, $redirectUrl) 
    {
        if ($request->get('error', null) == null) {
            // Check state code
            $parameters = $this->getTokenBag()->get($this);
            if ($request->get('state', null) != $parameters['state']) {
                throw new AuthorizationException("CSRF token is invalid");
            }
        }
        
        return parent::handleAuthorizationResponse($request, $redirectUrl);
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractOAuth2Adapter::getUserInformations()
     */
    protected function getUserInformations (OAuth2Token $token)
    {
        $response = parent::getUserInformations($token);
        if (!array_key_exists('id', $response)) {
            var_dump($response);
            throw new AuthorizationException("Unable to get user ID");
        }
        
        // Set the realname parameter
        $response['name'] = $response['first-name'].' '.$response['last-name'];
        
        return $response;
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::share()
     */
    public function share ($message, array $options = array())
    {
        $this->resolveOptions($options);
        
        // Compute body
        $content = $this->removeEmpty(array(
            'comment' => $message,
            'content' => array(
                'title' => $this->object->getTitle(),
                'description' => $this->object->getDescription(),
                'submitted-url' => $this->object->getLink(),
                'submitted-image-url' => $this->object->getImage()
            ),
            'visibility' => array(
                'code' => 'anyone'
            )
        ));
        
        // Share on LinkedIn profile
        $body = $this->getSerializer('share')->serialize($content, 'xml');
        $rawResponse = $this->doPost('https://api.linkedin.com/v1/people/~/shares?'.http_build_query(array(
            'oauth2_access_token' => $this->account->getToken()->getAccessToken()
        )), $body, array(
            'Content-type' => 'application/xml'
        ));
        $response = $this->getResponseContent($rawResponse);
        if (array_key_exists('error-code', $response)) {
            throw new ShareException('Unable to share to LinkedIn: '.$response['message']);
        } else if (!array_key_exists('update-key', $response)) {
            throw new ShareException('Unable to get shared object ID');
        }
        
        // Create the shared object
        $sharedObject = new SharedObject();
        $sharedObject->setMessage($message);
        $sharedObject->setProvider($this->getName());
        $sharedObject->setSocialId($response['update-key']);
        $sharedObject->setSocialAccount($this->account);
        
        // Add object to parent
        $this->object->addSharedObject($sharedObject);
    }
    
    /**
     * Remove empty values.
     *
     * @param array $array
     * @return array
     */
    protected function removeEmpty (array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $value = $this->removeEmpty($value);
            }
            
            if (empty($value)) {
                unset($array[$key]);
            }
        }
        
        return $array;
    }
}
