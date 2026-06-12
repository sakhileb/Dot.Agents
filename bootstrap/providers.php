<?php

use App\Providers\AgentServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\GovernanceServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\JetstreamServiceProvider;
use App\Providers\SocialServiceProvider;

return [
    AppServiceProvider::class,
    AgentServiceProvider::class,
    GovernanceServiceProvider::class,
    SocialServiceProvider::class,
    EventServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    JetstreamServiceProvider::class,
];
