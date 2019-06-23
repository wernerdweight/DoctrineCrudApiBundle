<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Detailer;
use WernerDweight\RA\RA;

final class DetailController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    /**
     * @param Detailer $detailer
     *
     * @return JsonResponse
     */
    public function index(Detailer $detailer): JsonResponse
    {
        $item = $detailer->getItem();
        return $this->json($item->toArray(RA::RECURSIVE));
    }
}
