<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use Symfony\Component\Validator\ConstraintViolation;
use WernerDweight\RA\RA;

class ItemValidatorReturnableException extends AbstractReturnableException
{
    /** @var int */
    public const INVALID_ITEM = 1;

    /** @var string[] */
    protected static $messages = [
        self::INVALID_ITEM => 'Item is not valid!',
    ];

    /**
     * @return RA
     */
    public function getResponseData(): RA
    {
        $responseData = parent::getResponseData();
        return $responseData->map(function (ConstraintViolation $violation): RA {
            return (new RA())
                ->set('property', $violation->getPropertyPath())
                ->set('message', $violation->getMessage())
            ;
        });
    }
}
