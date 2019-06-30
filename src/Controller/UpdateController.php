<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Updater;
use WernerDweight\RA\RA;

final class UpdateController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    /**
     * @param Updater $updater
     *
     * @return JsonResponse
     */
    public function index(Updater $updater): JsonResponse
    {
        $item = $updater->updateItem();
        return $this->json($item->toArray(RA::RECURSIVE));
    }
}
