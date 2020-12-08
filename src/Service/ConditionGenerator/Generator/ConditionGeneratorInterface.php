<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\Generator;

interface ConditionGeneratorInterface
{
    public function generate(string $field, string $parameterName): string;

    public function getOperator(): string;
}
