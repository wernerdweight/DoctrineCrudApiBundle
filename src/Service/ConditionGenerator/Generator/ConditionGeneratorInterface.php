<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\Generator;

interface ConditionGeneratorInterface
{
    /**
     * @param string $field
     * @param string $parameterName
     *
     * @return string
     */
    public function generate(string $field, string $parameterName): string;

    /**
     * @return string
     */
    public function getOperator(): string;
}
