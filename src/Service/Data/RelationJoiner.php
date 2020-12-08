<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\ORM\QueryBuilder;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\Stringy\Stringy;

class RelationJoiner
{
    /** @var FilteringHelper */
    private $filteringHelper;

    /**
     * RelationJoiner constructor.
     */
    public function __construct(FilteringHelper $filteringHelper)
    {
        $this->filteringHelper = $filteringHelper;
    }

    /**
     * @return RelationJoiner
     *
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    public function joinRequiredRelations(QueryBuilder $queryBuilder, Stringy $field): self
    {
        if (true === $field->pregMatch('/^[a-z\.]+$/i')) {
            $currentPrefix = clone $field;
            $firstSeparatorPosition = $field->getPositionOfSubstring(ParameterEnum::FIELD_SEPARATOR);
            if (null !== $firstSeparatorPosition) {
                $currentPrefix = $currentPrefix->substring(0, $firstSeparatorPosition);
            }

            if (DataManager::ROOT_ALIAS !== (string)$currentPrefix) {
                $previousPrefix = new Stringy(DataManager::ROOT_ALIAS);
                $currentField = (clone $field)->substring($currentPrefix->length() + 1);
                while (true !== $currentPrefix->sameAs($previousPrefix)) {
                    if (true !== in_array((string)$currentPrefix, $queryBuilder->getAllAliases(), true)) {
                        $queryBuilder->leftJoin(
                            \Safe\sprintf('%s.%s', $previousPrefix, $currentPrefix),
                            (string)$currentPrefix
                        );
                    }
                    $previousPrefix = clone $currentPrefix;
                    $nextSeparatorPosition = $currentField
                        ->getPositionOfSubstring(ParameterEnum::FIELD_SEPARATOR);
                    if (null !== $nextSeparatorPosition) {
                        $currentPrefix = (clone $currentField)->substring(0, $nextSeparatorPosition);
                        $currentField = $currentField->substring($nextSeparatorPosition + 1);
                    }
                }
                return $this;
            }

            $currentField = (clone $field)
                ->replace(\Safe\sprintf('%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FIELD_SEPARATOR), '');
            if (true === $this->filteringHelper->isManyToManyField($currentField) &&
                true !== in_array((string)$currentField, $queryBuilder->getAllAliases(), true)
            ) {
                $queryBuilder->leftJoin(
                    \Safe\sprintf('%s.%s', DataManager::ROOT_ALIAS, $currentField),
                    (string)$currentField
                );
            }
        }
        return $this;
    }
}
