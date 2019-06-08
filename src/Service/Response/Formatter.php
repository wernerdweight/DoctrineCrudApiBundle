<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use WernerDweight\DoctrineCrudApiBundle\Service\Data\QueryBuilderDecorator;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\Stringy\Stringy;

class Formatter
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /**
     * Formatter constructor.
     * @param ParameterResolver $parameterResolver
     */
    public function __construct(ParameterResolver $parameterResolver)
    {
        $this->parameterResolver = $parameterResolver;
    }
    
    private function formatGroupped(): RA
    {
        
    }

    public function formatMany(): RA
    {

    }

    public function formatListing(RA $items, ?int $level = null): RA
    {
        $groupBy = $this->parameterResolver->getRAOrNull(ParameterEnum::GROUP_BY);
        if ($level === null) {
            $level = $groupBy !== null ? $groupBy->length() : 0;
        }
        if ($level > 0 && $groupBy !== null) {
            $levelConfiguration = $groupBy->getRAOrNull($groupBy->length() - $level) ?? new RA();
            $levelGroupingField = $levelConfiguration->hasKey(ParameterEnum::GROUP_BY_FIELD)
                ? (new RA(
                    (new Stringy($levelConfiguration->getString(ParameterEnum::GROUP_BY_FIELD)))
                        ->explode(ParameterEnum::FILTER_FIELD_SEPARATOR)
                ))->last()
                : QueryBuilderDecorator::IDENTIFIER_FIELD_NAME;
            return $this->formatGroupped($items, $level, $levelGroupingField);
        }
        return $this->formatMany($result);
    }
}
