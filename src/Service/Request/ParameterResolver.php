<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Request;

use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Exception\InvalidRequestException;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ParameterResolver
{
    /**
     * @var RA
     */
    private $parameters;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ParameterValidator
     */
    private $parameterValidator;

    /**
     * @var CurrentEntityResolver
     */
    private $currentEntityResolver;

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

    public function hasKey(string $key): bool
    {
        return $this->parameters->hasKey($key);
    }

    /**
     * @return mixed
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $key)
    {
        return $this->parameters->get($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getBool(string $key): bool
    {
        return $this->parameters->getBool($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getBoolOrNull(string $key): ?bool
    {
        return $this->parameters->getBoolOrNull($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getInt(string $key): int
    {
        return $this->parameters->getInt($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getIntOrNull(string $key): ?int
    {
        return $this->parameters->getIntOrNull($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFloat(string $key): float
    {
        return $this->parameters->getFloat($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFloatOrNull(string $key): ?float
    {
        return $this->parameters->getFloatOrNull($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getString(string $key): string
    {
        return $this->parameters->getString($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getStringOrNull(string $key): ?string
    {
        return $this->parameters->getStringOrNull($key);
    }

    /**
     * @return mixed[]
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getArray(string $key): array
    {
        return $this->parameters->getArray($key);
    }

    /**
     * @return mixed[]|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getArrayOrNull(string $key): ?array
    {
        return $this->parameters->getArrayOrNull($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getRA(string $key): RA
    {
        return $this->parameters->getRA($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getRAOrNull(string $key): ?RA
    {
        return $this->parameters->getRAOrNull($key);
    }

    public function getStringy(string $key): Stringy
    {
        /** @var Stringy $value */
        $value = $this->parameters->get($key);
        return $value;
    }

    public function getStringyOrNull(string $key): ?Stringy
    {
        /** @var Stringy|null $value */
        $value = $this->parameters->get($key);
        return $value;
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCallable(string $key): callable
    {
        return $this->parameters->getCallable($key);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCallableOrNull(string $key): ?callable
    {
        return $this->parameters->getCallableOrNull($key);
    }

    /**
     * @return iterable<mixed, mixed>
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getIterable(string $key): iterable
    {
        return $this->parameters->getIterable($key);
    }

    /**
     * @return iterable<mixed, mixed>|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getIterableOrNull(string $key): ?iterable
    {
        return $this->parameters->getIterableOrNull($key);
    }

    public function eject(): RA
    {
        return clone $this->parameters;
    }

    /**
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

    private function enhanceParametersFromJson(RA $parameters): RA
    {
        $data = new RA($this->request->toArray());
        if ($data->hasKey(ParameterEnum::FIELDS)) {
            $parameters->set(ParameterEnum::FIELDS, $data->getArrayOrNull(ParameterEnum::FIELDS));
        }
        if ($data->hasKey(ParameterEnum::RESPONSE_STRUCTURE)) {
            $parameters->set(ParameterEnum::RESPONSE_STRUCTURE, $data->getArrayOrNull(ParameterEnum::RESPONSE_STRUCTURE));
        }
        if ($data->hasKey(ParameterEnum::OFFSET)) {
            $parameters->set(ParameterEnum::OFFSET, $data->getIntOrNull(ParameterEnum::OFFSET) ?? 0);
        }
        if ($data->hasKey(ParameterEnum::LIMIT)) {
            $parameters->set(ParameterEnum::LIMIT, $data->getIntOrNull(ParameterEnum::LIMIT) ?? PHP_INT_MAX);
        }
        if ($data->hasKey(ParameterEnum::FILTER)) {
            $parameters->set(ParameterEnum::FILTER, $data->getArrayOrNull(ParameterEnum::FILTER));
        }
        if ($data->hasKey(ParameterEnum::ORDER_BY)) {
            $parameters->set(ParameterEnum::ORDER_BY, $data->getArrayOrNull(ParameterEnum::ORDER_BY));
        }
        if ($data->hasKey(ParameterEnum::GROUP_BY)) {
            $parameters->set(ParameterEnum::GROUP_BY, $data->getArrayOrNull(ParameterEnum::GROUP_BY));
        }
        return $parameters;
    }

    private function resolveParameters(): RA
    {
        $query = $this->request->query;
        $request = $this->request->request;
        $attributes = $this->request->attributes;
        $parameters = new RA();

        $parameters
            ->set(ParameterEnum::PRIMARY_KEY, $attributes->get(ParameterEnum::PRIMARY_KEY))
            ->set(ParameterEnum::FIELDS, $request->all(ParameterEnum::FIELDS))
            ->set(ParameterEnum::RESPONSE_STRUCTURE, $this->getArrayValueFromQuery($query, ParameterEnum::RESPONSE_STRUCTURE))
            ->set(ParameterEnum::OFFSET, $query->getInt(ParameterEnum::OFFSET, 0))
            ->set(ParameterEnum::LIMIT, $query->getInt(ParameterEnum::LIMIT, PHP_INT_MAX))
            ->set(ParameterEnum::FILTER, $this->getArrayValueFromQuery($query, ParameterEnum::FILTER))
            ->set(ParameterEnum::ORDER_BY, $this->getArrayValueFromQuery($query, ParameterEnum::ORDER_BY))
            ->set(ParameterEnum::GROUP_BY, $this->getArrayValueFromQuery($query, ParameterEnum::GROUP_BY))
        ;

        $contentType = $this->request->getContentTypeFormat();
        if ($contentType === 'json') {
            return $this->enhanceParametersFromJson($parameters);
        }
        return $parameters;
    }

    public function resolveList(): self
    {
        $this->resolveCommon();
        $requestParameters = $this->resolveParameters();

        $this->parameters
            ->set(ParameterEnum::OFFSET, $requestParameters->getInt(ParameterEnum::OFFSET))
            ->set(ParameterEnum::LIMIT, $requestParameters->getInt(ParameterEnum::LIMIT))
            ->set(ParameterEnum::FILTER,
                $this->parameterValidator->validateFilter(
                    $requestParameters->getArrayOrNull(ParameterEnum::FILTER)
                )
            )
            ->set(
                ParameterEnum::ORDER_BY,
                $this->parameterValidator->validateOrderBy(
                    $requestParameters->getArrayOrNull(ParameterEnum::ORDER_BY)
                )
            )
            ->set(
                ParameterEnum::GROUP_BY,
                $this->parameterValidator->validateGroupBy(
                    $requestParameters->getArrayOrNull(ParameterEnum::GROUP_BY)
                )
            )
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $requestParameters->getArrayOrNull(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    public function resolveDetail(): self
    {
        $this->resolveCommon();
        $requestParameters = $this->resolveParameters();

        $this->parameters
            ->set(ParameterEnum::PRIMARY_KEY, $requestParameters->getString(ParameterEnum::PRIMARY_KEY))
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $requestParameters->getArrayOrNull(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    public function resolveCreate(): self
    {
        $this->resolveCommon();
        $requestParameters = $this->resolveParameters();

        $this->parameters
            ->set(
                ParameterEnum::FIELDS,
                $this->parameterValidator->validateFields(
                    $requestParameters->getArray(ParameterEnum::FIELDS)
                )
            )
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $requestParameters->getArrayOrNull(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    public function resolveUpdate(): self
    {
        $this->resolveCommon();
        $requestParameters = $this->resolveParameters();

        $this->parameters
            ->set(ParameterEnum::PRIMARY_KEY, $requestParameters->getString(ParameterEnum::PRIMARY_KEY))
            ->set(
                ParameterEnum::FIELDS,
                $this->parameterValidator->validateFields(
                    $requestParameters->getArray(ParameterEnum::FIELDS)
                )
            )
            ->set(
                ParameterEnum::RESPONSE_STRUCTURE,
                $this->parameterValidator->validateResponseStructure(
                    $requestParameters->getArrayOrNull(ParameterEnum::RESPONSE_STRUCTURE),
                    (clone $this->getStringy(ParameterEnum::ENTITY_NAME))->lowercaseFirst()
                )
            )
        ;
        return $this;
    }

    public function resolveDelete(): self
    {
        $this->resolveCommon();

        $attributes = $this->request->attributes;
        $this->parameters
            ->set(ParameterEnum::PRIMARY_KEY, $attributes->get(ParameterEnum::PRIMARY_KEY))
        ;
        return $this;
    }

    private function resolveCommon(): self
    {
        $this->parameters->set(ParameterEnum::ENTITY_NAME, $this->currentEntityResolver->getCurrentEntity());
        return $this;
    }

    /**
     * @param InputBag<mixed> $query
     *
     * @return mixed[]|null
     */
    private function getArrayValueFromQuery(InputBag $query, string $key): ?array
    {
        if (true !== $query->has($key)) {
            return null;
        }
        return $query->all($key);
    }
}
