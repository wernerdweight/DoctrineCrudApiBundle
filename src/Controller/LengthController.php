<?php

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Lister;

class LengthController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    /**
     * @param Lister $lister
     * @return JsonResponse
     */
    public function index(Lister $lister): JsonResponse
    {
        $length = $lister->getItemCount();
        return $this->json(compact('length'));
    }
}
