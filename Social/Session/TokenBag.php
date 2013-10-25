<?php
namespace SRIO\SocialShareBundle\Social\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use SRIO\SocialShareBundle\Social\Adapter\AbstractAdapter;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

class TokenBag
{
    /**
     * Session.
     * 
     * @var SessionInterface
     */
    protected $session;
    
    /**
     * Constructor.
     * 
     * @param string $storageKey
     */
    public function __construct (SessionInterface $session)
    {
        $this->session = $session;
    }
    
    /**
     * Set a token value.
     * 
     * @param mixed $key
     * @param mixed $value
     */
    public function set ($key, $value)
    {
        $key = $this->parseKey($key);
        $this->session->set($key, $value);
    }
    
    /**
     * Get a token by its key.
     * 
     * @param mixed $key
     */
    public function get ($key)
    {
        $key = $this->parseKey($key);
        return $this->session->get($key);
    }
    
    /**
     * Has the token bag its key ?
     * 
     * @param string $key
     * @return boolean
     */
    public function has ($key)
    {
        $key = $this->parseKey($key);
        return $this->session->has($key);
    }
    
    /**
     * Parse the key.
     * 
     * @param mixed $key
     * @return string
     */
    private function parseKey ($key)
    {
        if ($key instanceof AbstractAdapter) {
            $key = $this->generateKey($key);
        }
        
        return $key;
    }

    /**
     * Key to for fetching or saving a token.
     *
     * @param AbstractAdapter $adapter
     * @return string
     */
    protected function generateKey(AbstractAdapter $adapter)
    {
        return sprintf(
            '_social_share.token.%s', 
            $adapter->getName()
        );
    }
}
