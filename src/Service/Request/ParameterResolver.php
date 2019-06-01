<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Exception\InvalidRequestException;
use WernerDweight\RA\RA;

class ParameterResolver
{
    /** @var RA */
    private $parameters;

    /** @var Request */
    private $request;

    /** @var ParameterValidator */
    private $parameterValidator;

    public function __construct(RequestStack $requestStack, ParameterValidator $parameterValidator)
    {
        $this->parameters = new RA();

        $request = $requestStack->getCurrentRequest();
        if ($request === null) {
            throw new InvalidRequestException(InvalidRequestException::EXCEPTION_NO_REQUEST);
        }
        $this->request = $request;

        $this->parameterValidator = $parameterValidator;
    }

    public function resolveList(): self
    {
        $query = $this->request->query;
        $this->parameters
            ->set('offset', $query->getInt('offset', 0))
            ->set('limit', $query->getInt('limit', PHP_INT_MAX))
            ->set('filter', $this->parameterValidator->validateFilter($query->get('filter')))
            ->set('orderBy', $this->parameterValidator->validateOrderBy($query->get('orderBy')))
            ->set('groupBy', $this->parameterValidator->validateGroupBy($query->get('groupBy')))
            ->set('responseStructure', $query->get('responseStructure'))
        ;
        return $this;
    }
}
