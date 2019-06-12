<?php

namespace App\Photo;

use App\Entity\ImagePost;
use Doctrine\ORM\EntityManagerInterface;

class PhotoPonkaficator
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function ponkafy(ImagePost $imagePost)
    {
        $imagePost->markAsPonkaAdded();

        $this->entityManager->flush();
    }
}
