<?php
namespace SRIO\SocialShareBundle\Social\Object;

use SRIO\SocialShareBundle\Entity\SharedObject;

interface SharableObjectInterface
{
    public function getTitle();
    
    public function getDescription();
    
    public function getLink();
    
    public function getImage();
    
    public function addSharedObject(SharedObject $sharedObject);
}