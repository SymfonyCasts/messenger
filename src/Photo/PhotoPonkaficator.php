<?php

namespace App\Photo;

use App\Entity\ImagePost;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemInterface;

class PhotoPonkaficator
{
    private $entityManager;
    private $imageManager;
    private $photoFilesystem;

    public function __construct(EntityManagerInterface $entityManager, ImageManager $imageManager, FilesystemInterface $photoFilesystem)
    {
        $this->entityManager = $entityManager;
        $this->imageManager = $imageManager;
        $this->photoFilesystem = $photoFilesystem;
    }

    public function ponkafy(ImagePost $imagePost)
    {
        $image = $this->imageManager->make(
            $this->photoFilesystem->readStream($imagePost->getFilename())
        );

        $image = $image->insert(
            file_get_contents(__DIR__.'/../../assets/ponka/ponka1.jpg'),
            'bottom-left',
            50,
            50
        );

        $this->photoFilesystem->update(
            $imagePost->getFilename(),
            $image->encode()
        );

        $imagePost->markAsPonkaAdded();
        sleep(2);

        $this->entityManager->flush();
    }
}
