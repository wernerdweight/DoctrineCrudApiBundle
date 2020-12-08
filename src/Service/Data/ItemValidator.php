<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\ItemValidatorReturnableException;

class ItemValidator
{
    /** @var string */
    public const API_VALIDATION_GROUP = 'api';

    /** @var ValidatorInterface */
    private $validator;

    /**
     * ItemValidator constructor.
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validate(ApiEntityInterface $item): bool
    {
        /** @var ConstraintViolationList<ConstraintViolationInterface> $errors */
        $errors = $this->validator->validate($item, null, [self::API_VALIDATION_GROUP]);
        if ($errors->count() > 0) {
            /** @var \ArrayIterator<int, ConstraintViolationInterface> $errorIterator */
            $errorIterator = $errors->getIterator();
            throw new ItemValidatorReturnableException(ItemValidatorReturnableException::INVALID_ITEM, $errorIterator->getArrayCopy());
        }
        return true;
    }
}
