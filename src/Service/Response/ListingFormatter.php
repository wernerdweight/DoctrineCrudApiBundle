<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use WernerDweight\DoctrineCrudApiBundle\Exception\FormatterException;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\FilteringHelper;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\QueryBuilderDecorator;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ListingFormatter
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var Printer */
    private $printer;

    /** @var ManyFormatter */
    private $formatter;

    /**
     * ListingFormatter constructor.
     *
     * @param ParameterResolver $parameterResolver
     * @param Printer           $printer
     * @param ManyFormatter     $formatter
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        Printer $printer,
        ManyFormatter $formatter
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->printer = $printer;
        $this->formatter = $formatter;
    }

    /**
     * @param RA $aggregateFields
     *
     * @return RA
     */
    private function formatGroupAggregates(RA $aggregateFields): RA
    {
        return $aggregateFields
            ->map(function ($value, string $field): ?RA {
                $field = (new Stringy($field))
                    ->substring((new Stringy(QueryBuilderDecorator::AGGREGATE_PREFIX))->length() + 1);
                $lastUnderscorePosition = $field
                    ->getPositionOfLastSubstring(QueryBuilderDecorator::AGGREGATE_FUNCTION_SEPARATOR);
                if (null === $lastUnderscorePosition) {
                    throw new FormatterException(
                        FormatterException::EXCEPTION_INVALID_AGGREGATE_FIELD_NAME,
                        [$field]
                    );
                }
                $functionName = (clone $field)->substring(0, $lastUnderscorePosition);
                $field = $field->substring($lastUnderscorePosition + 1);
                return new RA([
                    (string)$field => [
                        (string)$functionName => $value,
                    ],
                ], RA::RECURSIVE);
            }, $aggregateFields->keys());
    }

    /**
     * @param RA     $groups
     * @param int    $level
     * @param string $groupingField
     *
     * @return RA
     */
    private function formatGroupped(RA $groups, int $level, string $groupingField): RA
    {
        return $groups->map(function (RA $group) use ($groupingField, $level): RA {
            $aggregateFields = $group->filter(function (string $field): bool {
                return 0 === (new Stringy($field))->getPositionOfSubstring(QueryBuilderDecorator::AGGREGATE_PREFIX);
            }, ARRAY_FILTER_USE_KEY);

            return (new RA())
                ->set(ParameterEnum::GROUP_BY_AGGREGATES, $this->formatGroupAggregates($aggregateFields))
                ->set(ParameterEnum::GROUP_BY_FIELD, $groupingField)
                ->set(ParameterEnum::GROUP_BY_VALUE, $this->printer->print($group->get(ParameterEnum::GROUP_BY_VALUE)))
                ->set(ParameterEnum::GROUP_BY_HAS_GROUPS, $level > 1)
                ->set(
                    ParameterEnum::GROUP_BY_ITEMS,
                    $this->formatListing($group->getRA(ParameterEnum::GROUP_BY_ITEMS), $level - 1)
                );
        });
    }

    /**
     * @param RA  $items
     * @param int $level
     * @param RA  $groupBy
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function formatGrouppedListing(RA $items, int $level, RA $groupBy): RA
    {
        $levelConfiguration = $groupBy->getRAOrNull($groupBy->length() - $level) ?? new RA();
        $levelGroupingField = FilteringHelper::IDENTIFIER_FIELD_NAME;
        if (true === $levelConfiguration->hasKey(ParameterEnum::GROUP_BY_FIELD)) {
            /** @var Stringy $field */
            $field = $levelConfiguration->get(ParameterEnum::GROUP_BY_FIELD);
            $levelGroupingField = (new RA($field->explode(ParameterEnum::FIELD_SEPARATOR)))->last();
        }
        return $this->formatGroupped($items, $level, $levelGroupingField);
    }

    /**
     * @param RA       $items
     * @param int|null $level
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function formatListing(RA $items, ?int $level = null): RA
    {
        $groupBy = $this->parameterResolver->getRAOrNull(ParameterEnum::GROUP_BY);
        if (null === $level) {
            $level = null !== $groupBy ? $groupBy->length() : 0;
        }
        if ($level > 0 && null !== $groupBy) {
            return $this->formatGrouppedListing($items, $level, $groupBy);
        }
        $responseStructure = $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE);
        return $this->formatter->format($items, $responseStructure, $this->parameterResolver->getEntityPrefix());
    }
}
