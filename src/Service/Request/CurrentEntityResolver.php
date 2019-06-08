<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Exception\InvalidRequestException;
use WernerDweight\Stringy\Stringy;

class CurrentEntityResolver
{
    /** @var string */
    private $currentEntity;

    /** @var Request */
    private $request;

    /**
     * CurrentEntityResolver constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();
        if ($request === null) {
            throw new InvalidRequestException(InvalidRequestException::EXCEPTION_NO_REQUEST);
        }
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getCurrentEntity(): string
    {
        if (null === $this->currentEntity) {
            $entityName = $this->request->attributes->getAlpha(ParameterEnum::ENTITY_NAME);
            if (true === empty($entityName)) {
                throw new InvalidRequestException(InvalidRequestException::EXCEPTION_NO_ENTITY_NAME);
            }
            $this->currentEntity = (new Stringy($entityName))->convertCase(Stringy::CASE_KEBAB, Stringy::CASE_PASCAL);
        }
        return $this->currentEntity;
    }
}
