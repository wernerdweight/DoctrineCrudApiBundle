<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\Generator;

use Doctrine\ORM\Query\Expr;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;

final class EndsWithConditionGenerator implements ConditionGeneratorInterface
{
    public function generate(string $field, string $parameterName): string
    {
        $expression = new Expr();
        return (string)($expression->like(
            (string)($expression->lower($field)),
            (string)($expression->lower($parameterName))
        ));
    }

    public function getOperator(): string
    {
        return ParameterEnum::FILTER_OPERATOR_ENDS_WITH;
    }
}
