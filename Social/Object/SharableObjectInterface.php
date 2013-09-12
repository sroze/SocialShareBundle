<?php
namespace SRozeIO\SocialShareBundle\Social\Object;

use SRozeIO\SocialShareBundle\Entity\SharedObject;

interface SharableObjectInterface
{
    public function getTitle();
    
    public function getDescription();
    
    public function getLink();
    
    public function getImage();
    
    public function addSharedObject(SharedObject $sharedObject);
}