<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use SRozeIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

abstract class AbstractOAuthAdapter extends AbstractAdapter
{
    /**
     * Signature methods.
     * 
     * @var string
     */
    const SIGNATURE_METHOD_HMAC      = 'HMAC-SHA1';
    const SIGNATURE_METHOD_RSA       = 'RSA-SHA1';
    const SIGNATURE_METHOD_PLAINTEXT = 'PLAINTEXT';
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setRequired(array(
            'client_id',
            'client_secret'
        ));
    }

    /**
     * Generate a non-guessable nonce value.
     *
     * @return string
     */
    protected function generateNonce()
    {
        return md5(microtime(true).uniqid('', true));
    }
    
    /**
     * Sign the request parameters.
     * 
     * Taken from @see HWI\Bundle\OAuthBundle\Security\OAuthUtils
     *
     * @param string $method          Request method
     * @param string $url             Request url
     * @param array  $parameters      Parameters for the request
     * @param string $clientSecret    Client secret to use as key part of signing
     * @param string $tokenSecret     Optional token secret to use with signing
     * @param string $signatureMethod Optional signature method used to sign token
     *
     * @return string
     *
     * @throws AuthorizationException
     */
    public function signRequest($method, $url, $parameters, $clientSecret, $tokenSecret = '', $signatureMethod = self::SIGNATURE_METHOD_HMAC)
    {
        // Validate required parameters
        foreach (array('oauth_consumer_key', 'oauth_timestamp', 'oauth_nonce', 'oauth_version', 'oauth_signature_method') as $parameter) {
            if (!isset($parameters[$parameter])) {
                throw new AuthorizationException(sprintf('Parameter "%s" must be set.', $parameter));
            }
        }
    
        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($parameters['oauth_signature'])) {
            unset($parameters['oauth_signature']);
        }
    
        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($parameters, 'strcmp');
    
        // http_build_query should use RFC3986
        $parts = array(
            $method,
            rawurlencode($url),
            rawurlencode(str_replace(array('%7E','+'), array('~','%20'), http_build_query($parameters, '', '&'))),
        );
    
        $baseString = implode('&', $parts);
    
        switch ($signatureMethod) {
            case self::SIGNATURE_METHOD_HMAC:
                $keyParts = array(
                    rawurlencode($clientSecret),
                    rawurlencode($tokenSecret),
                );
    
                $signature = hash_hmac('sha1', $baseString, implode('&', $keyParts), true);
                break;
    
            case self::SIGNATURE_METHOD_RSA:
                $privateKey = openssl_pkey_get_private(file_get_contents($clientSecret), $tokenSecret);
                $signature  = false;
    
                openssl_sign($baseString, $signature, $privateKey);
                openssl_free_key($privateKey);
                break;
    
            case self::SIGNATURE_METHOD_PLAINTEXT:
                $signature = $baseString;
                break;
    
            default:
                throw new AuthorizationException(sprintf('Unknown signature method selected %s.', $signatureMethod));
        }
    
        return base64_encode($signature);
    }
}
