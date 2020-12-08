<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Lister;

final class LengthController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    public function index(Lister $lister): JsonResponse
    {
        $length = $lister->getItemCount();
        return $this->json(compact('length'));
    }
}
