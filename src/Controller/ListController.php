<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Lister;
use WernerDweight\RA\RA;

final class ListController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    /**
     * @param Lister $lister
     *
     * @return JsonResponse
     */
    public function index(Lister $lister): JsonResponse
    {
        $items = $lister->getItems();
        return $this->json($items->toArray(RA::RECURSIVE));
    }
}
