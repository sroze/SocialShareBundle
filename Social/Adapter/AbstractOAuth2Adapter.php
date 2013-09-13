<?php
namespace SRozeIO\SocialShareBundle\Social\Adapter;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use SRozeIO\SocialShareBundle\Social\Object\SharableObjectInterface;
use SRozeIO\SocialShareBundle\Entity\SocialAccount;

abstract class AbstractOAuth2Adapter extends AbstractOAuthAdapter
{
    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::getAuthorizationUrl()
     */
    public function getAuthorizationUrl ($redirectUrl, array $parameters = array())
    {
        return $this->options['authorization_url'].'?'.http_build_query(array_merge(array(
            'client_id' => $this->options['client_id'],
            'redirect_uri' => $redirectUrl
        ), $parameters));
    }

    /**
     * (non-PHPdoc)
     * @see \SRozeIO\SocialShareBundle\Social\Adapter\AbstractAdapter::setDefaultOptions()
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setRequired(array(
            'authorization_url',
            'request_token_url'
        ));
    }
}
