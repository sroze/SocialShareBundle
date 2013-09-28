<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use Symfony\Component\Config\Util\XmlUtils;

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

class LinkedInAdapter extends AbstractOAuth2Adapter
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
    
        // Generate a state parameter
        $state = $this->generateNonce();
        $parameters = array_merge(array(
            'response_type' => $this->options['response_type'],
            'state' => $state
            //'access_type' => $this->options['access_type']
        ), $parameters);
        $this->getTokenBag()->set($this, $parameters);
        return parent::getAuthorizationUrl($redirectUrl, $parameters);
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
            //'access_type',
            //'request_visible_actions',
            //'approval_prompt'
        ));
        
        $resolver->setDefaults(array(
            'authorization_url' => 'https://www.linkedin.com/uas/oauth2/authorization',
            'request_token_url' => 'https://www.linkedin.com/uas/oauth2/accessToken',
            
            'response_type' => 'code',
            //'scope' => 'openid profile',
            //'access_type' => 'offline'
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
            // Check state code
            $parameters = $this->getTokenBag()->get($this);
            if ($request->get('state', null) != $parameters['state']) {
                throw new AuthorizationException("CSRF token is invalid");
            }
            
            $token = $this->requestToken($code, $redirectUrl);
            $informations = $this->requestUserInformations($token);
            
            // Create the account object
            $account = new SocialAccount();
            $account->setProvider($this->getName());
            $account->setSocialId($informations['id']);
            $account->setToken($token);
            $account->setRealname($informations['first-name'].' '.$informations['last-name']);
            
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
        $response = $this->doGet('https://api.linkedin.com/v1/people/~:(id,first-name,last-name)', array(
            'oauth2_access_token' => $token->getAccessToken()
        ));
        
        $response = $this->getResponseContent($response);
        if ($response == null || array_key_exists('error', $response)) {
            throw new AuthorizationException("Unable to grab user informations");
        } else if (!array_key_exists('id', $response)) {
            throw new AuthorizationException("Unable to get user ID");
        }
        
        return $response;
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
        
        $token = new OAuth2Token();
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
