<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor;

use WernerDweight\RA\RA;

class Lister
{
    public function getItems(): RA
    {
        return new RA(['test', 'test2']);
    }
}
