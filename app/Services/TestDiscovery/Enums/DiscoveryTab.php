<?php

namespace App\Services\TestDiscovery\Enums;

enum DiscoveryTab:string
{
    case TRENDING = 'trending';
    case NEW = 'new';
    case MOST_PARTICIPATED = 'most_participated';
    case FREE = 'free';
}
