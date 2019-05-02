<?php

namespace App\Controller;

use App\Repository\ImagePostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ImagePostController extends AbstractController
{
    /**
     * @Route("/api/images")
     */
    public function list(ImagePostRepository $repository)
    {
        $posts = $repository->findAll();

        return $this->json([
            'items' => $posts
        ]);
    }
}
