<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use WernerDweight\DoctrineCrudApiBundle\Service\ActionProcessor\Creator;
use WernerDweight\RA\RA;

final class CreateController extends AbstractController implements DoctrineCrudApiControllerInterface
{
    public function index(Creator $creator): JsonResponse
    {
        $item = $creator->createItem();
        return $this->json($item->toArray(RA::RECURSIVE));
    }
}
