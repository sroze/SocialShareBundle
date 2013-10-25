<?php
namespace SRIO\SocialShareBundle;

class SocialShareEvents
{
    /**
     * A user has successfully authenticated a new social account
     * 
     * Listeners will receive an AuthenticationSuccessfulEvent object, which
     * they have to fill with the response object.
     * 
     * @var string
     */
    const AUTHENTICATION_SUCCESSFUL = 'srio.social_share.event.authenticated.success';
    
    /**
     * An authentication failed.
     * 
     * Listeners will receive an AuthenticationFailedEvent object, which
     * they have to fill with the response object.
     * 
     * @var string
     */
    const AUTHENTICATION_FAILED = 'srio.social_share.event.authenticated.failed';
}