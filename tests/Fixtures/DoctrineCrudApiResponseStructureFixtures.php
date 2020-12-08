<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Tests\Fixtures;

use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

class DoctrineCrudApiResponseStructureFixtures
{
    public static function createArticleResponseStructure(): RA
    {
        return new RA([
            'article' => [
                'id' => ParameterEnum::TRUE_VALUE,
                'title' => ParameterEnum::TRUE_VALUE,
                'author' => [
                    'name' => ParameterEnum::TRUE_VALUE,
                ],
            ],
        ], RA::RECURSIVE);
    }
}
