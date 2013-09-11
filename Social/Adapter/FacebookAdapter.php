<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

class FacebookAdapter extends AbstractAdapter
{
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::getName()
     */
    public function getName ()
    {
        return 'facebook';
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::share()
     */
    public function share (SocialAccount $account, SharableObjectInterface $object, $message)
    {
        
    }
}
