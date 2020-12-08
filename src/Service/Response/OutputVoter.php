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
     * @param string|RA $value
     */
    private function isRegularValue($value): bool
    {
        return true === is_string($value) &&
            ParameterEnum::FALSE_VALUE !== $value &&
            ParameterEnum::TRUE_VALUE !== $value;
    }

    /**
     * @param mixed $value
     *
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    private function isValueAllowed(Stringy $field, $value): bool
    {
        return ParameterEnum::TRUE_VALUE === $value || $value instanceof RA || (
                true === is_string($value) && $field->pregMatch(\Safe\sprintf('/\b%s\b/i', $value))
            );
    }

    private function traverseResponseStructure(RA $responseStructure, Stringy $path): ?RA
    {
        $segments = new RA($path->explode(ParameterEnum::FIELD_SEPARATOR));
        $reducedResponseStructure = $segments->reduce(
            function (RA $carry, string $segment) use ($path, $responseStructure): RA {
                if (true !== $carry->hasKey($segment)) {
                    return new RA();
                }
                $value = $carry->get($segment);
                if ($value instanceof RA) {
                    return $value;
                }
                if (true === $this->isRegularValue($value)) {
                    $value = new Stringy($value);
                    $firstDotPosition = $path->getPositionOfSubstring(ParameterEnum::FIELD_SEPARATOR);
                    if (null !== $firstDotPosition) {
                        $value = (clone $path)->substring(0, $firstDotPosition)->concat(\Safe\sprintf('.%s', $value));
                    }
                    return $this->traverseResponseStructure($responseStructure, $value) ?? new RA();
                }
                return new RA();
            },
            $responseStructure
        );
        if (0 === $reducedResponseStructure->length()) {
            return null;
        }
        return $reducedResponseStructure;
    }

    /**
     * @throws \Safe\Exceptions\MbstringException
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
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
            return $this->isValueAllowed($field, $value);
        }
        return self::NOT_ALLOWED;
    }
}
