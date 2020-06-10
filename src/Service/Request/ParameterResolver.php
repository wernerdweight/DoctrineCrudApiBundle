<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Exception\InvalidRequestException;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ParameterResolver
{
    /** @var RA */
    private $parameters;

    /** @var Request */
    private $request;

    /** @var ParameterValidator */
    private $parameterValidator;

    /** @var CurrentEntityResolver */
    private $currentEntityResolver;

    /**
     * ParameterResolver constructor.
     *
     * @param RequestStack          $requestStack
     * @param ParameterValidator    $parameterValidator
     * @param CurrentEntityResolver $currentEntityResolver
     */
    public function __construct(
        RequestStack $requestStack,
        ParameterValidator $parameterValidator,
        CurrentEntityResolver $currentEntityResolver
    ) {
        $this->parameters = new RA();

        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new InvalidRequestException(InvalidRequestException::EXCEPTION_NO_REQUEST);
        }
        $this->request = $request;

        $this->parameterValidator = $parameterValidator;
        $this->currentEntityResolver = $currentEntityResolver;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasKey(string $key): bool
    {
        return $this->parameters->hasKey($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $key)
    {
        return $this->parameters->get($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getBool(string $key): bool
    {
        return $this->parameters->getBool($key);
    }

    /**
     * @param string $key
     *
     * @return bool|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getBoolOrNull(string $key): ?bool
    {
        return $this->parameters->getBoolOrNull($key);
    }

    /**
     * @param string $key
     *
     * @return int
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getInt(string $key): int
    {
        return $this->parameters->getInt($key);
    }

    /**
     * @param string $key
     *
     * @return int|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getIntOrNull(string $key): ?int
    {
        return $this->parameters->getIntOrNull($key);
    }

    /**
     * @param string $key
     *
     * @return float
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFloat(string $key): float
    {
        return $this->parameters->getFloat($key);
    }

    /**
     * @param string $key
     *
     * @return float|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFloatOrNull(string $key): ?float
    {
        return $this->parameters->getFloatOrNull($key);
    }

    /**
     * @param string $key
     *
     * @return string
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getString(string $key): string
    {
        return $this->parameters->getString($key);
    }

    /**
     * @param string $key
     *
     * @return string|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getStringOrNull(string $key): ?string
    {
        return $this->parameters->getStringOrNull($key);
    }

    /**
     * @param string $key
     *
     * @return mixed[]
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getArray(string $key): array
    {
        return $this->parameters->getArray($key);
    }

    /**
     * @param string $key
     *
     * @return mixed[]|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getArrayOrNull(string $key): ?array
    {
        return $this->parameters->getArrayOrNull($key);
    }

    /**
     * @param string $key
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getRA(string $key): RA
    {
        return $this->parameters->getRA($key);
    }

    /**
     * @param string $key
     *
     * @return RA|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getRAOrNull(string $key): ?RA
    {
        return $this->parameters->getRAOrNull($key);
    }

    /**
     * @param string $key
     *
     * @return Stringy
     */
    public function getStringy(string $key): Stringy
    {
        /** @var Stringy $value */
        $value = $this->parameters->get($key);
        return $value;
    }

    /**
     * @param string $key
     *
     * @return Stringy|null
     */
    public function getStringyOrNull(string $key): ?Stringy
    {
        /** @var Stringy|null $value */
        $value = $this->parameters->get($key);
        return $value;
    }

    /**
     * @param string $key
     *
     * @return callable
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCallable(string $key): callable
    {
        return $this->parameters->getCallable($key);
    }

    /**
     * @param string $key
     *
     * @return callable|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCallableOrNull(string $key): ?callable
    {
        return $this->parameters->getCallableOrNull($key);
    }

    /**
     * @param string $key
     *
     * @return iterable<mixed, mixed>
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getIterable(string $key): iterable
    {
        return $this->parameters->getIterable($key);
    }

    /**
     * @param string $key
     *
     * @return iterable<mixed, mixed>|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getIterableOrNull(string $key): ?iterable
    {
        return $this->parameters->getIterableOrNull($key);
    }

    /**
     * @return RA
     */
    public function eject(): RA
    {
        return clone $this->parameters;
    }

    /**
     * @return string
     *
     * @throws \Safe\Exceptions\StringsException
     */
    public function getEntityPrefix(): string
    {
        return \Safe\sprintf(
            '%s%s',
            (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst(),
            ParameterEnum::FIELD_SEPARATOR
        );
    }

    /**
     * @return ParameterResolver
     */
    private function resolveCommon(): self
    {
        $this->parameters->set(ParameterEnum::ENTITY_NAME, $this->currentEntityResolver->getCurrentEntity());
        return $this;
    }

    /**
     * @return ParameterResolver
     */
    public function resolveList(): self
    {
        $this->resolveCommon();

        $query = $this->request->query;
        $this->parameters
            ->set(ParameterEnum::OFFSET, $query->getInt(ParameterEnum::OFFSET, 0))
            ->set(ParameterEnum::LIMIT, $query->getInt(ParameterEnum::LIMIT, PHP_INT_MAX))
            ->set(ParameterEnum::FILTER, $this->parameterValidator->validateFilter($query->all(ParameterEnum::FILTER)))
            ->set(
                ParameterEnum::ORDER_BY,
                $this->parameterValidator->validateOrderBy($query->all(ParameterEnum::ORDER_BY))
            )
            ->set(
                ParameterEnum::GROUP_BY,
                $this->parameterValidator->validateGroupBy($query->all(ParameterEnum::GROUP_BY))
            )
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $query->all(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    /**
     * @return ParameterResolver
     */
    public function resolveDetail(): self
    {
        $this->resolveCommon();

        $query = $this->request->query;
        $attributes = $this->request->attributes;
        $this->parameters
            ->set(ParameterEnum::PRIMARY_KEY, $attributes->get(ParameterEnum::PRIMARY_KEY))
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $query->all(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    /**
     * @return ParameterResolver
     */
    public function resolveCreate(): self
    {
        $this->resolveCommon();

        $query = $this->request->query;
        $request = $this->request->request;
        $this->parameters
            ->set(
                ParameterEnum::FIELDS,
                $this->parameterValidator->validateFields(
                    $request->all(ParameterEnum::FIELDS)
                )
            )
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $query->all(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    /**
     * @return ParameterResolver
     */
    public function resolveUpdate(): self
    {
        $this->resolveCommon();

        $request = $this->request->request;
        $attributes = $this->request->attributes;
        $this->parameters
            ->set(ParameterEnum::PRIMARY_KEY, $attributes->get(ParameterEnum::PRIMARY_KEY))
            ->set(
                ParameterEnum::FIELDS,
                $this->parameterValidator->validateFields(
                    $request->all(ParameterEnum::FIELDS)
                )
            )
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $this->request->get(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    /**
     * @return ParameterResolver
     */
    public function resolveDelete(): self
    {
        $this->resolveCommon();

        $attributes = $this->request->attributes;
        $this->parameters
            ->set(ParameterEnum::PRIMARY_KEY, $attributes->get(ParameterEnum::PRIMARY_KEY))
        ;
        return $this;
    }
}
