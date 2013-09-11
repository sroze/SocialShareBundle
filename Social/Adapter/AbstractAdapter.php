<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;

use SRozeIO\SocialShareBundle\Entity\SocialAccount;

abstract class AbstractAdapter
{
    /**
     * Return a unique name of adapter.
     * 
     */
    abstract function getName ();
    
    /**
     * Share the object on the given account.
     * 
     * @param SocialAccount $account
     * @param SharableObjectInterface $object
     */
    abstract function share (SocialAccount $account, SharableObjectInterface $object, $message);
    
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
