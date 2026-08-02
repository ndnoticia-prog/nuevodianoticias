<?php

declare(strict_types=1);

namespace NDAds\Domain;

enum CampaignType: string
{
    case AdSense = 'adsense';
    case GoogleAdManager = 'gam';
    case Html = 'html';
    case Image = 'image';
    case Video = 'video';
    case Sponsored = 'sponsored';
}
