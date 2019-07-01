<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Deleter;
use WernerDweight\RA\RA;

final class DeleteController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    /**
     * @param Deleter $deleter
     *
     * @return JsonResponse
     */
    public function index(Deleter $deleter): JsonResponse
    {
        $item = $deleter->getItem();
        return $this->json($item->toArray(RA::RECURSIVE));
    }
}
