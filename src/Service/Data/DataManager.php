<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use WernerDweight\RA\RA;

class DataManager
{
    public function getPortion(int $offset, int $limit, RA $orderBy, RA $filter): RA
    {
        // TODO: get current repo, apply filters, ordering and pagination, return results
    }
    
    public function getGroupedPortion(int $offset, int $limit, RA $orderBy, RA $filter, RA $groupBy): RA
    {
        // TODO: get current repo, apply filters, fetch groups, apply ordering and pagination, return results
    }
}
