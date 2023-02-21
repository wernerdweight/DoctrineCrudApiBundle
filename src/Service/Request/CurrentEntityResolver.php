<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Request;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\InvalidRequestException;
use WernerDweight\Stringy\Stringy;

class CurrentEntityResolver
{
    /**
     * @var Stringy
     */
    private $currentEntity;

    /**
     * @var Stringy
     */
    private $currentEntityFQCN;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new InvalidRequestException(InvalidRequestException::EXCEPTION_NO_REQUEST);
        }
        $this->request = $request;
        $this->entityManager = $entityManager;
    }

    public function getCurrentEntity(): Stringy
    {
        if (null === $this->currentEntity) {
            $entityName = $this->request->attributes->get(ParameterEnum::ENTITY_NAME);
            if (true === empty($entityName)) {
                throw new InvalidRequestException(InvalidRequestException::EXCEPTION_NO_ENTITY_NAME);
            }
            $this->currentEntity = (new Stringy($entityName))->convertCase(Stringy::CASE_KEBAB, Stringy::CASE_PASCAL);
        }
        return $this->currentEntity;
    }

    /**
     * @throws \Safe\Exceptions\StringsException
     * @throws \Safe\Exceptions\SplException
     */
    public function getCurrentEntityFQCN(): Stringy
    {
        if (null === $this->currentEntityFQCN) {
            $entityName = $this->getCurrentEntity();
            $registeredNamespaces = $this->entityManager->getConfiguration()
                ->getEntityNamespaces();
            foreach ($registeredNamespaces as $namespace) {
                $fqcn = \Safe\sprintf('%s\\%s', $namespace, $entityName);
                if (class_exists($fqcn) && in_array(ApiEntityInterface::class, \Safe\class_implements($fqcn), true)) {
                    $this->currentEntityFQCN = new Stringy($fqcn);
                    return $this->currentEntityFQCN;
                }
            }
            throw new InvalidRequestException(InvalidRequestException::EXCEPTION_INVALID_FILTERING_ENTITY, [
                $entityName,
            ]);
        }
        return $this->currentEntityFQCN;
    }
}
