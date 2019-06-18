<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\Generator;

use Doctrine\ORM\Query\Expr;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;

final class IsEmptyConditionGenerator implements ConditionGeneratorInterface
{
    /**
     * @param string $field
     * @param string $parameterName
     *
     * @return string
     */
    public function generate(string $field, string $parameterName): string
    {
        $expression = new Expr();
        return (string)($expression->orX(
            $expression->isNull($field),
            $expression->eq($field, $parameterName)
        ));
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return ParameterEnum::FILTER_OPERATOR_IS_EMPTY;
    }
}
