<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->import('vendor/wernerdweight/cs/src/ecs.php');
};
