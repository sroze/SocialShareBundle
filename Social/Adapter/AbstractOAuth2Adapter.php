<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use Symfony\Component\HttpFoundation\Request;

use SRozeIO\SocialShareBundle\Social\Exception\TokenException;

use SRozeIO\SocialShareBundle\Entity\AuthToken;

use SRozeIO\SocialShareBundle\Entity\OAuth2Token;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

abstract class AbstractOAuth2Adapter extends AbstractOAuthAdapter
{
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::getAuthorizationUrl()
     */
    public function getAuthorizationUrl ($redirectUrl, array $parameters = array())
    {
        if (!array_key_exists('scope', $parameters) && array_key_exists('scope', $this->options)) {
            $parameters['scope'] = $this->options['scope'];
        }
        
        return $this->options['authorization_url'].'?'.http_build_query(array_merge(array(
            'client_id' => $this->options['client_id'],
            'redirect_uri' => $redirectUrl
        ), $parameters));
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
        $rawResponse = $this->doPost($this->options['request_token_url'], array(
            'code' => $code,
            'client_id' => $this->options['client_id'],
            'client_secret' => $this->options['client_secret'],
            'redirect_uri' => $redirectUrl,
            'grant_type' => 'authorization_code'
        ));
        
        $response = $this->getResponseContent($rawResponse);
        $token = new OAuth2Token();
        
        $this->populateToken($token, $response);
        
        return $token;
    }
    
    /**
     * Populate token fields with server response.
     * 
     * @param OAuth2Token $token
     * @param array       $response
     */
    protected function populateToken (OAuth2Token $token, $response)
    {
        if (!array_key_exists('access_token', $response)) {
            throw new TokenException("Bad token request response");
        }
        
        $token->setAccessToken($response['access_token']);
        if (array_key_exists('refresh_token', $response)) {
            $token->setRefreshToken($response['refresh_token']);
        }
        
        $expirationDate = new \DateTime();
        $expirationDate->add(new \DateInterval('PT'.$response['expires_in'].'S'));
        $token->setExpirationDate($expirationDate);
        $token->setCreationDate(new \DateTime());
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
            $informations = $this->getUserInformations($token);
    
            // Create the account object
            $account = new SocialAccount();
            $account->setProvider($this->getName());
            $account->setSocialId($informations['id']);
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
     * Return an array with at least these keys:
     * - id
     * - name
     * 
     * @param OAuth2Token $token
     * @return array
     */
    protected function getUserInformations (OAuth2Token $token)
    {
        $response = $this->doAuthorizedGet($this->options['user_informations_url'], array(), $token);
        $response = $this->getResponseContent($response);
        if ($response == null || array_key_exists('error', $response)) {
            throw new AuthorizationException("Unable to grab user informations");
        }
        
        return $response;
    }
    
    /**
     * Do an authorized get request.
     * 
     * @param string $url
     * @param array  $parameters
     * @param OAuth2Token $token
     */
    protected function doAuthorizedGet ($url, array $parameters = array(), $token = null)
    {
        if ($token == null) {
            $token = $this->account->getToken();
        }
        
        return $this->doGet($url, array_merge(array(
            $this->options['access_token_parameter_name'] => $token->getAccessToken()
        ), $parameters));
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::refreshToken()
     */
    public function refreshToken (AuthToken $token)
    {
        if (!($token instanceof OAuth2Token)) {
            throw new \RuntimeException("An OAuth2Token should be passed");
        }
        
        if ($token->getRemainingTime() > 120) {
            return true;
        }
        
        $this->doRefreshToken($token);
    }
    
    /**
     * Do the token refresh process.
     * 
     * @param OAuth2Token $token
     */
    public function doRefreshToken (OAuth2Token $token)
    {
        throw new TokenException('Unable to programmatically refresh token');
    }

    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setRequired(array(
            'authorization_url',
            'request_token_url',
            'user_informations_url',
            
            'access_token_parameter_name'
        ));
        
        $resolver->setDefaults(array(
            'access_token_parameter_name' => 'access_token'
        ));

        $resolver->setOptional(array(
            'scope'
        ));
    }
}
