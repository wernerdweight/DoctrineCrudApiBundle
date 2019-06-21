<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\RA\RA;

class ManyFormatter
{
    /** @var Formatter */
    private $formatter;

    /**
     * ManyFormatter constructor.
     *
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param RA      $items
     * @param RA|null $responseStructure
     * @param string  $prefix
     *
     * @return RA
     */
    public function format(RA $items, ?RA $responseStructure, string $prefix): RA
    {
        return $items->map(function (ApiEntityInterface $item) use ($prefix, $responseStructure): RA {
            return $this->formatter->format($item, $responseStructure, $prefix);
        });
    }
}
