<?php
namespace SRozeIO\SocialShareBundle\Social;

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
    protected $adapters;
    
    /**
     * An array containing the targeted social accounts.
     * 
     * @var multitype:SocialAccount
     */
    protected $accounts;
    
    /**
     * The message that will be join to shared object.
     * 
     * @var string
     */
    protected $message;
    
    /**
     * The object to share.
     * 
     */
    public function __construct ()
    {
        $this->adapters = array();
        $this->accounts = array();
    }
    
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
     * Set the message to be shared with object.
     * 
     * @param string $message
     */
    public function setMessage ($message)
    {
        $this->message = $message;
    }
    
    /**
     * Launch the share process.
     * 
     */
    public function share ()
    {
        foreach ($this->accounts as $account) {
            foreach ($this->adapters as $adapter) {
                if ($adapter->supports($account)) {
                    $adapter->share($account, $this->object, $this->message);
                }
            }
        }
    }
}
