<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

/**
 * The abstract adapter.
 *
 */
abstract class AbstractAdapter
{
    /**
     * The resolved options.
     * 
     * @var array
     */
    protected $options;
    
    /**
     * A buzz browser instance.
     * 
     * @var \Buzz\Browser
     */
    protected $buzz;
    
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
    public function __construct (\Buzz\Browser $buzz, $name, array $options)
    {
        $this->buzz = $buzz;
        $this->name = $name;
        
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
     * Perform a GET request.
     * 
     * @param unknown $url
     * @param array $parameters
     * @return \Buzz\Message\Response
     */
    protected function doGet ($url, array $parameters)
    {
        $computedUrl = $url.'?'.http_build_query($parameters);
        
        return $this->buzz->get($computedUrl);
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
