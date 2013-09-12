<?php
namespace SRozeIO\SocialShareBundle\Social\Object;

interface SharableObjectInterface
{
    public function getTitle();
    
    public function getDescription();
    
    public function getLink();
    
    public function getImage();
}