<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

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
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param ApiEntityInterface $item
     *
     * @return bool
     */
    public function validate(ApiEntityInterface $item): bool
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($item, null, [self::API_VALIDATION_GROUP]);
        if ($errors->count() > 0) {
            throw new ItemValidatorReturnableException(
                ItemValidatorReturnableException::INVALID_ITEM,
                $errors->getIterator()->getArrayCopy()
            );
        }
        return true;
    }
}
