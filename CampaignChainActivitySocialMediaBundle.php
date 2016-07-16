<?php

namespace CampaignChain\Activity\SocialMediaBundle;

use CampaignChain\Activity\SocialMediaBundle\DependencyInjection\CampaignChainActivitySocialMediaExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CampaignChainActivitySocialMediaBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CampaignChainActivitySocialMediaExtension();
    }
}
