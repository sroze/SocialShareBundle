<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use SRozeIO\SocialShareBundle\Entity\OAuth1Token;

use SRozeIO\SocialShareBundle\Entity\SharedObject;

use SRozeIO\SocialShareBundle\Social\Exception\ShareException;

use Buzz\Message\Response;

use SRozeIO\SocialShareBundle\Entity\AuthToken;

use SRozeIO\SocialShareBundle\Social\Exception\AuthorizationException;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

class TwitterAdapter extends AbstractOAuth1Adapter
{
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setDefaults(array(
            'authorization_url' => 'https://api.twitter.com/oauth/authenticate',
            'request_token_url' => 'https://api.twitter.com/oauth/request_token',
            'access_token_url'    => 'https://api.twitter.com/oauth/access_token'
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::handleAuthorizationResponse()
     */
    public function handleAuthorizationResponse(Request $request, $redirectUrl) 
    {
        $response = $this->getAccessToken($request, $redirectUrl);

        // Create the Token object
        $token = new OAuth1Token();
        $token->setAccessToken($response['oauth_token']);
        $token->setTokenSecret($response['oauth_token_secret']);
        $token->setCreationDate(new \DateTime());
        
        // Create the account object
        $account = new SocialAccount();
        $account->setProvider($this->getName());
        $account->setSocialId($response['user_id']);
        $account->setRealname($response['screen_name']);
        $account->setToken($token);
        
        return $account;
    }
    
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::share()
     */
    public function share ($message, array $options = array())
    {
        $this->resolveOptions($options);
        
        // Tweet this message
        $objectUrl = 'https://api.twitter.com/1.1/statuses/update.json';
        $body = array(
            'status' => $message
        );
        
        $response = $this->doAuthorizedPost($objectUrl, $body);
        if (array_key_exists('errors', $response)) {
            throw new ShareException($response['errors'][0]['message'], $response['errors'][0]['code']);
        } else if (!array_key_exists('id', $response)) {
            throw new ShareException("Unable to share: malformated response");
        }
        
        // Create the sharedobject
        $sharedObject = new SharedObject();
        $sharedObject->setProvider($this->getName());
        $sharedObject->setMessage($message);
        $sharedObject->setSocialId($response['id']);
        $sharedObject->setSocialAccount($this->account);
        
        // Add object to parent
        $this->object->addSharedObject($sharedObject);
    }
}
