<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use SRozeIO\SocialShareBundle\Social\Exception\ShareException;

use Symfony\Component\HttpFoundation\Request;

use SRozeIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

abstract class AbstractOAuth1Adapter extends AbstractOAuthAdapter
{
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::getAuthorizationUrl()
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
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
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
        $timestamp = time();
        $oauth_parameters = array(
            'oauth_consumer_key'     => $this->options['client_id'],
            'oauth_timestamp'        => $timestamp,
            'oauth_nonce'            => $this->generateNonce(),
            'oauth_version'          => '1.0',
            'oauth_callback'         => $redirectUrl,
            'oauth_signature_method' => $this->options['signature_method'],
        );
        
        $url = $this->options['request_token_url'];
        $response = $this->doAuthorizedPost($url, null, array(), $oauth_parameters);

        if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
            throw new AuthorizationException('Not a valid request token.');
        }
        
        $response['timestamp'] = $timestamp;
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
        
        $oauth_parameters = array(
            'oauth_consumer_key'     => $this->options['client_id'],
            'oauth_timestamp'        => time(),
            'oauth_nonce'            => $this->generateNonce(),
            'oauth_version'          => '1.0',
            'oauth_signature_method' => $this->options['signature_method'],
            'oauth_token'            => $request_token['oauth_token'],
            'oauth_verifier'         => $request->get('oauth_verifier')
        );
        $url = $this->options['access_token_url'];
        
        return $this->doAuthorizedPost($url, null, array(), $oauth_parameters, $request_token['oauth_token_secret']);
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::doPost()
     */
    protected function doPost($url, $body, $headers = array()) 
    {
        if (!is_array($body)) {
            throw new \LogicException("Body must be an array to sign request with");
        }
        $token = $this->account->getToken();
        $oauth_parameters = array_merge(array(
            'oauth_consumer_key'     => $this->options['client_id'],
            'oauth_timestamp'        => time(),
            'oauth_nonce'            => $this->generateNonce(),
            'oauth_version'          => '1.0',
            'oauth_signature_method' => $this->options['signature_method'],
            'oauth_token'            => $token->getAccessToken()
        ), $body);
        
        return $this->doAuthorizedPost($url, $body, $headers, $oauth_parameters, $token->getTokenSecret());
    }
    
    /**
     * Do an authorized POST request.
     * 
     * @param string $url
     * @param array $oauth_parameters OAuth parameters that will be computed
     *                                as an Authorization header.
     */
    protected function doAuthorizedPost ($url, $body, array $headers, array $oauth_parameters, $tokenSecret = '')
    {
        // Sign the request
        $oauth_parameters['oauth_signature'] = $this->signRequest(
            "POST",
            $url,
            $oauth_parameters,
            $this->options['client_secret'],
            $tokenSecret,
            $this->options['signature_method']
        );
        
        // Compute the headers
        $computed_oauth_parameters = array();
        foreach ($oauth_parameters as $key => $value) {
            $computed_oauth_parameters[$key] = $key . '="' . rawurlencode($value) . '"';
        }
        
        $response = parent::doPost($url, $body, array_merge(array(
            'Authorization' => 'OAuth '.implode(', ', $computed_oauth_parameters)
        ), $headers));
        $response = $this->getResponseContent($response);
         
        if (isset($response['oauth_problem']) || (isset($response['oauth_callback_confirmed']) && ($response['oauth_callback_confirmed'] != 'true'))) {
            throw new AuthorizationException(sprintf('OAuth error: "%s"', $response['oauth_problem']));
        }
        
        return $response;
    }
}
