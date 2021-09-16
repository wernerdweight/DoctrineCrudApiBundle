<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\FilteringException;
use WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\Generator\ConditionGeneratorInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;

class ConditionGeneratorFactory
{
    /** @var RA */
    private $conditionGenerators;

    /**
     * ConditionGeneratorFactory constructor.
     *
     * @param RewindableGenerator<ConditionGeneratorInterface> $conditionGenerators
     */
    public function __construct(RewindableGenerator $conditionGenerators)
    {
        $this->conditionGenerators = new RA();
        /** @var \Generator<ConditionGeneratorInterface> $iterator */
        $iterator = $conditionGenerators->getIterator();
        while ($iterator->valid()) {
            /** @var ConditionGeneratorInterface $conditionGenerator */
            $conditionGenerator = $iterator->current();
            $this->conditionGenerators->set($conditionGenerator->getOperator(), $conditionGenerator);
            $iterator->next();
        }
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $operator): ConditionGeneratorInterface
    {
        if (true !== $this->conditionGenerators->hasKey($operator)) {
            throw new FilteringException(FilteringException::EXCEPTION_INVALID_FILTER_OPERATOR, [
                $operator,
                implode(', ', ParameterEnum::AVAILABLE_FILTERING_OPERATORS),
            ]);
        }
        /** @var ConditionGeneratorInterface $conditionGenerator */
        $conditionGenerator = $this->conditionGenerators->get($operator);
        return $conditionGenerator;
    }
}
