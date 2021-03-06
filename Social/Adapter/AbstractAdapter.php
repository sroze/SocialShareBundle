<?php
namespace SRIO\SocialShareBundle\Social\Adapter;

use SRIO\SocialShareBundle\Social\Exception\SocialException;

use SRIO\SocialShareBundle\Social\Exception\AuthorizationException;

use SRIO\SocialShareBundle\Entity\AuthToken;

use Symfony\Component\Serializer\Serializer;

use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

use Symfony\Component\Config\Util\XmlUtils;

use SRIO\SocialShareBundle\Social\Session\TokenBag;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Buzz\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use SRIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRIO\SocialShareBundle\Entity\SocialAccount;

/**
 * The abstract adapter.
 *
 */
abstract class AbstractAdapter
{
    /**
     * A buzz browser instance.
     * 
     * @var \Buzz\Browser
     */
    private $buzz;
    
    /**
     * The session.
     * 
     * @see http://api.symfony.com/2.3/Symfony/Component/HttpFoundation/Session/SessionInterface.html
     * @var SessionInterface
     */
    private $session;
    
    /**
     * The TokenBag.
     * 
     * @var TokenBag
     */
    private $tokenBag = null;
    
    /**
     * The resolved options.
     * 
     * @var array
     */
    protected $options;
    
    /**
     * The object to be shared.
     * 
     * @var SharableObjectInterface
     */
    protected $object;
    
    /**
     * The social account.
     * 
     * @var SocialAccount
     */
    protected $account;
    
    /**
     * The adapter name.
     * 
     * @var string
     */
    protected $name;
    
    /**
     * Constructor of adapter.
     * 
     * @param Buzz\Browser $buzz
     */
    public function __construct ($name, array $options, \Buzz\Browser $buzz, SessionInterface $session)
    {
        $this->buzz = $buzz;
        $this->name = $name;
        $this->session = $session;
        
        $this->options = $this->resolveOptions($options);
    }
    
    /**
     * Resolve the options with OptionsResolver.
     * 
     * @param array $options
     */
    protected function resolveOptions (array $options)
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $resolvedOptions = $resolver->resolve(is_array($this->options) ? array_merge($this->options, $options) : $options);
        
        return $resolvedOptions;
    }
    
    /**
     * Set the default adapter options.
     * 
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'type'
        ));
    }
    
    /**
     * Return a unique name of adapter.
     * 
     */
    public function getName ()
    {
        return $this->name;
    }
    
    /**
     * Get a SessionBag from the session.
     * 
     * @return TokenBag
     */
    public function getTokenBag ()
    {
        if ($this->tokenBag == null) {
            $this->tokenBag = new TokenBag($this->session);
        }
        
        return $this->tokenBag;
    }
    
    /**
     * Share the object on the given account.
     * 
     * @param SocialAccount $account
     * @param SharableObjectInterface $object
     */
    abstract public function share ($message, array $options);
    
    /**
     * Get the autorization url.
     * 
     * @param string $redirectUrl
     * @param array $parameters
     */
    abstract public function getAuthorizationUrl ($redirectUrl, array $parameters = array());
    
    /**
     * Handle the authorization response.
     * 
     * @param Request $request
     */
    abstract public function handleAuthorizationResponse (Request $request, $redirectUrl);
    
    /**
     * Refresh a token.
     * 
     * @param AuthToken $token
     * @throws AuthorizationException
     */
    abstract public function refreshToken (AuthToken $token);
    
    /**
     * Perform a GET request.
     * 
     * @param unknown $url
     * @param array $parameters
     * @return \Buzz\Message\Response
     */
    protected function doGet ($url, array $parameters)
    {
        $computedUrl = $url.'?'.http_build_query($parameters);
            
        try {
            return $this->buzz->get($computedUrl);
        } catch (ClientException $e) {
            throw new SocialException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Perform a POST request.
     * 
     * @param string $url
     * @param string|array $body
     */
    protected function doPost ($url, $body, $headers = array())
    {
        if (is_array($body)) {
            $content = http_build_query($body);
            if (!array_key_exists('Content-Type', $headers)) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
        } else {
            $content = $body;
        }
        
        try {
            return $this->buzz->post($url, $headers, $content);
        } catch (ClientException $e) {
            throw new SocialException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get the 'parsed' content based on the response headers.
     * 
     * It could be a parsed JSON body or an URL-encoded string decoded
     * with the parse_str function.
     *
     * @param MessageInterface $rawResponse
     *
     * @return array
     */
    protected function getResponseContent(MessageInterface $rawResponse)
    {
        // First check that content in response exists, due too bug: https://bugs.php.net/bug.php?id=54484
        $content = $rawResponse->getContent();
        if (!$content) {
            return array();
        }

        // Depending on content-type header if found
        $contentType = $rawResponse->getHeader('Content-Type');
        if (strpos($contentType, 'text/xml') === 0) {
            return json_decode(json_encode((array)simplexml_load_string($content)),1);
        } else if (strpos($contentType, 'application/json') === 0) {
            return json_decode($content, true);
        }
        
        // Fallback to urlencoded
        parse_str($content, $response);
        return $response;
    }
    
    /**
     * Get the serializer.
     * 
     * @return \Symfony\Component\Serializer\Serializer
     */
    protected function getSerializer ($rootNodeName = 'share')
    {
        $encoders = array(new XmlEncoder($rootNodeName), new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        
        return new Serializer($normalizers, $encoders);
    }
    
    /**
     * Set the social account to use.
     * 
     * @param SocialAccount $account
     */
    public function setSocialAccount (SocialAccount $account)
    {
        $this->account = $account;
    }
    
    /**
     * Set the object to be shared.
     * 
     * @param SharableObjectInterface $object
     */
    public function setObject (SharableObjectInterface $object)
    {
        $this->object = $object;
    }
    
    /**
     * Is this adapter supporting this social account ?
     * 
     * @param SocialAccount $socialAccount
     */
    public function supports (SocialAccount $account)
    {
        return $account->getProvider() == $this->getName();
    }
}
