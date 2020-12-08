<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\RA\RA;

interface ReturnableExceptionInterface extends \Throwable
{
    /**
     * Returns data that will be printed as JSON response.
     */
    public function getResponseData(): RA;

    public function getStatusCode(): int;
}
