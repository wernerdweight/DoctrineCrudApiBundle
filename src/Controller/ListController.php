<?php

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Lister;
use WernerDweight\RA\RA;

class ListController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    /**
     * @param Lister $lister
     * @return JsonResponse
     */
    public function index(Lister $lister): JsonResponse
    {
        $items = $lister->getItems();
        return $this->json($items->toArray(RA::RECURSIVE));
    }
}
