<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->ignoreErrors([ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackages([
        'illuminate/bus',
        'illuminate/console',
        'illuminate/database',
        'illuminate/queue',
        'illuminate/redis',
        'illuminate/support',
        'thecodingmachine/safe',
    ], [ErrorType::UNUSED_DEPENDENCY]);
