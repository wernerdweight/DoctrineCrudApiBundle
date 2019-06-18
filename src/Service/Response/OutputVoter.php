<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class OutputVoter
{
    /** @var bool */
    public const ALLOWED = true;
    /** @var bool */
    public const NOT_ALLOWED = false;

    /**
     * @param RA      $responseStructure
     * @param Stringy $path
     *
     * @return RA|null
     */
    private function traverseResponseStructure(RA $responseStructure, Stringy $path): ?RA
    {
        $segments = new RA($path->explode(ParameterEnum::FIELD_SEPARATOR));
        $reducedResponseStructure = $segments->reduce(function (RA $carry, string $segment): RA {
            if (true !== $carry->hasKey($segment)) {
                return new RA();
            }
            $value = $carry->get($segment);
            if ($value instanceof RA) {
                return $value;
            }
            return new RA();
        }, $responseStructure);
        if (0 === $reducedResponseStructure->length()) {
            return null;
        }
        return $reducedResponseStructure;
    }

    /**
     * @param Stringy                 $field
     * @param DoctrineCrudApiMetadata $configuration
     * @param RA|null                 $responseStructure
     *
     * @return bool
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function vote(
        Stringy $field,
        DoctrineCrudApiMetadata $configuration,
        ?RA $responseStructure
    ): bool {
        $root = new Stringy(ParameterEnum::EMPTY_VALUE);
        $key = (clone $field);
        $lastDotPosition = $field->getPositionOfLastSubstring(ParameterEnum::FIELD_SEPARATOR);
        if (null !== $lastDotPosition) {
            $root = (clone $field)->substring(0, $lastDotPosition);
            $key = (clone $field)->substring($lastDotPosition + 1);
        }

        if (null !== $responseStructure) {
            $responseStructure = $this->traverseResponseStructure($responseStructure, $root);
        }
        if (null === $responseStructure) {
            $responseStructure = $configuration->getDefaultListableFields()->fillKeys(ParameterEnum::TRUE_VALUE);
        }

        if (true === $responseStructure->hasKey((string)$key)) {
            $value = $responseStructure->get((string)$key);
            return ParameterEnum::TRUE_VALUE === $value || $value instanceof RA;
        }
        return self::NOT_ALLOWED;
    }
}
