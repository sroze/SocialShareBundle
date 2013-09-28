<?php
namespace SRozeIO\SocialShareBundle\Social;

use SRozeIO\SocialShareBundle\Social\Exception\SocialException;

use SRozeIO\SocialShareBundle\Social\Exception\ShareException;

use SRozeIO\SocialShareBundle\Entity\SocialAccount;
use SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter;
use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;

/**
 * This class is the main entry point of the sharing process.
 * 
 * The builder will prepare and set adapters and abstract all
 * the process to the user.
 * 
 * @author Samuel ROZE
 */
class ShareBuilder
{
    /**
     * The object to share if present.
     * 
     * @var SharableObjectInterface
     */
    protected $object;
    
    /**
     * An array containing the adapters.
     * 
     * @var multitype:AbstractAdapter
     */
    protected $adapters = array();
    
    /**
     * An array containing the targeted social accounts.
     * 
     * @var multitype:SocialAccount
     */
    protected $accounts = array();
    
    /**
     * Errors occured during the share process.
     * 
     * @var array
     */
    protected $errors = array();
    
    /**
     * The message that will be join to shared object.
     * 
     * @var string
     */
    protected $message;
    
    /**
     * Adapters options.
     * 
     * @var array
     */
    protected $options = array();
    
    /**
     * Set the sharable object.
     * 
     * @param SharableObjectInterface $object
     */
    public function setObject (SharableObjectInterface $object)
    {
        $this->object = $object;
    }
    
    /**
     * Add a new adapter, which is the vector of a social network.
     * 
     * @param AbstractAdapter $adapter
     */
    public function addAdapter (AbstractAdapter $adapter)
    {
        $this->adapters[] = $adapter;
        
        return $this;
    }
    
    /**
     * Add a targeted social account.
     * 
     * @param SocialAccount $account
     */
    public function addSocialAccount (SocialAccount $account)
    {
        $this->accounts[] = $account;
        
        return $this;
    }
    
    /**
     * Set adapter options.
     * 
     * @param string $name
     * @param array  $options
     * @throws ShareException
     */
    public function setAdapterOptions ($name, array $options)
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->getName() == $name) {
                $this->options[$name] = $options;
                
                return;
            }
        }
        
        throw new ShareException(sprintf('Adapter %s not found', $name));
    }
    
    /**
     * Set the message to be shared with object.
     * 
     * @param string $message
     */
    public function setMessage ($message)
    {
        $this->message = $message;
    }
    
    /**
     * Get errors.
     * 
     * @return array
     */
    public function getErrors ()
    {
        return $this->errors;
    }
    
    /**
     * Does errors appears during share process ?
     * 
     * @return boolean
     */
    public function hasErrors ()
    {
        return count($this->errors) > 0;
    }
    
    /**
     * Launch the share process.
     * 
     * Must check errors to know if some action failed.
     * 
     * @return boolean True if share process started
     */
    public function share ()
    {
        // Try to refresh tokens
        foreach ($this->accounts as $account) {
            foreach ($this->adapters as $adapter) {
                if ($adapter->supports($account)) {
                    try {
                        $adapter->refreshToken($account->getToken());
                    } catch (SocialException $e) {
                        $this->errors[] = array(
                            'code' => $e->getCode(),
                            'message' => $e->getMessage(),
                            'account' => $account,
                            'adapter' => $adapter
                        );
                    }
                }
            }
        }
        
        // If errors appears during token refresh, stop here
        if ($this->hasErrors()) {
            return false;
        }
        
        // Start the share process
        foreach ($this->accounts as $account) {
            foreach ($this->adapters as $adapter) {
                // If adapter supports account, use it to share
                // the object.
                if ($adapter->supports($account)) {
                    $adapter->setObject($this->object);
                    $adapter->setSocialAccount($account);
                    
                    try {
                        $adapter->share(
                            $this->message,
                            array_key_exists($adapter->getName(), $this->options) ? $this->options[$adapter->getName()] : array()
                        );
                    } catch (SocialException $e) {
                        $this->errors[] = array(
                            'code' => $e->getCode(),
                            'message' => $e->getMessage(),
                            'account' => $account,
                            'adapter' => $adapter
                        );
                    }
                }
            }
        }
        
        return true;
    }
}
