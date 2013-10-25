<?php
namespace SRIO\SocialShareBundle\Social\Adapter;

use SRIO\SocialShareBundle\Entity\AuthToken;

use SRIO\SocialShareBundle\Social\Exception\ShareException;

use Symfony\Component\HttpFoundation\Request;

use SRIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRIO\SocialShareBundle\Entity\SocialAccount;

abstract class AbstractOAuth1Adapter extends AbstractOAuthAdapter
{
    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::getAuthorizationUrl()
     */
    public function getAuthorizationUrl ($redirectUrl, array $parameters = array())
    {
        $token = $this->getRequestToken($redirectUrl, $parameters);
        
        return $this->options['authorization_url'].'?'.http_build_query(array_merge(array(
            'oauth_token' => $token['oauth_token']
        ), $parameters));
    }

    /**
     * (non-PHPdoc)
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setOptional(array(
            'signature_method'
        ));
        $resolver->setDefaults(array(
            'signature_method' => self::SIGNATURE_METHOD_HMAC
        ));
        $resolver->setRequired(array(
            'authorization_url',
            'request_token_url',
            'access_token_url'
        ));
    }
    
    /**
     * Get the request token for OAuth1 redirection.
     * 
     * @param string $redirectUrl
     * @param array $parameters
     * @throws AuthenticationException
     * @return array
     */
    protected function getRequestToken ($redirectUrl, array $parameters)
    {
        $oauth_parameters = $this->getOAuthParameters();
        $oauth_parameters['oauth_callback'] = $redirectUrl;
        
        $url = $this->options['request_token_url'];
        $rawResponse = $this->doPost($url, null, array(
            'Authorization' => $this->getAuthorizationHeader('POST', $url, $oauth_parameters)
        ));
        $response = $this->getResponseContent($rawResponse);
        if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
            throw new AuthorizationException('Not a valid request token.');
        }
        
        $response['timestamp'] = $oauth_parameters['oauth_timestamp'];
        $this->getTokenBag()->set($this, $response);
        
        return $response;
    }
    
    /**
     * Get the access token.
     * 
     * @param Request $request
     * @param string $redirectUrl
     * @throws AuthorizationException
     */
    protected function getAccessToken (Request $request, $redirectUrl) 
    {
        if (!$this->getTokenBag()->has($this)) {
            throw new AuthorizationException('No request token found in the storage.');
        }
        
        $request_token = $this->getTokenBag()->get($this);
        $token = $request->get('oauth_token');
        
        if ($token != $request_token['oauth_token']) {
            throw new AuthorizationException('OAuth tokens don\'t match');
        }
        
        $oauth_parameters = $this->getOAuthParameters($token);
        $oauth_parameters['oauth_verifier'] = $request->get('oauth_verifier');
        
        $url = $this->options['access_token_url'];        
        $rawResponse = $this->doPost($url, null, array(
            'Authorization' => $this->getAuthorizationHeader('POST', $url, $oauth_parameters, array(), $request_token['oauth_token_secret'])
        ));
        
        return $this->getResponseContent($rawResponse);
    }
    
    /**
     * Get OAuth1 parameters.
     * 
     * @param string $token
     * @return array
     */
    protected function getOAuthParameters ($token = null)
    {
        $parameters = array(
            'oauth_consumer_key'     => $this->options['client_id'],
            'oauth_timestamp'        => time(),
            'oauth_nonce'            => $this->generateNonce(),
            'oauth_version'          => '1.0',
            'oauth_signature_method' => $this->options['signature_method']
        );
        
        if ($token != null) {
            $parameters['oauth_token'] = $token;
        }
        
        return $parameters;
    }
    
    /**
     * Compute the Authorization header.
     * 
     * @param string $method
     * @param string $url
     * @param array  $parameters
     * @param string $tokenSecret
     * @return string
     */
    protected function getAuthorizationHeader ($method, $url, array $oauth_parameters, array $extraParameters = array(), $tokenSecret = '')
    {
        // Sign the request
        $oauth_parameters['oauth_signature'] = $this->signRequest(
            $method,
            $url,
            array_merge($oauth_parameters, $extraParameters),
            $this->options['client_secret'],
            $tokenSecret,
            $this->options['signature_method']
        );
        
        // Compute the headers
        $computed_oauth_parameters = array();
        foreach ($oauth_parameters as $key => $value) {
            $computed_oauth_parameters[$key] = $key . '="' . rawurlencode($value) . '"';
        }
        
        return 'OAuth '.implode(', ', $computed_oauth_parameters);
    }
    
    /**
     * Do an authorized POST request.
     * 
     * @param string $url
     * @param array $oauth_parameters OAuth parameters that will be computed
     *                                as an Authorization header.
     */
    protected function doAuthorizedPost ($url, array $body, array $headers = array())
    {
        $token = $this->account->getToken();
        $oauth_parameters = $this->getOAuthParameters($token->getAccessToken());
        
        $response = $this->doPost($url, $body, array_merge(array(
            'Authorization' => $this->getAuthorizationHeader('POST', $url, $oauth_parameters, $body, $token->getTokenSecret())
        ), $headers));
        
        $response = $this->getResponseContent($response);
        if (isset($response['oauth_problem']) || (isset($response['oauth_callback_confirmed']) && ($response['oauth_callback_confirmed'] != 'true'))) {
            throw new AuthorizationException(sprintf('OAuth error: "%s"', $response['oauth_problem']));
        }
        
        return $response;
    }
    
    /**
     * Refresh OAuth1 token.
     * 
     * Because OAuth1 tokens don't expires, always return true.
     * 
     * @see \SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter::refreshToken()
     */
    public function refreshToken (AuthToken $token)
    {
        return true;
    }
}
