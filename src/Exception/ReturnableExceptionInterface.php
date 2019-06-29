<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\RA\RA;

interface ReturnableExceptionInterface
{
    /**
     * Returns data that will be printed as JSON response.
     *
     * @return RA
     */
    public function getResponseData(): RA;

    /**
     * @return int
     */
    public function getStatusCode(): int;
}
