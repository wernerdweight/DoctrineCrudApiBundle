<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\Generator;

use Doctrine\ORM\Query\Expr;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
final class GreaterThanOrEqualOrNullConditionGenerator implements ConditionGeneratorInterface
{
    public function generate(string $field, string $parameterName): string
    {
        $expression = new Expr();
        return (string)($expression->orX(
            $expression->gte($field, $parameterName),
            $expression->isNull($field)
        ));
    }

    public function getOperator(): string
    {
        return ParameterEnum::FILTER_OPERATOR_GREATER_THAN_OR_EQUAL_OR_NULL;
    }
}
