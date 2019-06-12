<?php

namespace App\Photo;

use App\Entity\ImagePost;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Constraint;
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
        $targetPhoto = $this->imageManager->make(
            $this->photoFilesystem->readStream($imagePost->getFilename())
        );

        $ponkaFilename = __DIR__.'/../../assets/ponka/ponka1.jpg';
        $ponkaPhoto = $this->imageManager->make($ponkaFilename);

        $targetWidth = $targetPhoto->width() * .2;
        $targetHeight = $targetPhoto->height() * .4;

        $ponkaPhoto->resize($targetWidth, $targetHeight, function(Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $targetPhoto = $targetPhoto->insert(
            $ponkaPhoto,
            'bottom-left',
            50,
            50
        );

        $this->photoFilesystem->update(
            $imagePost->getFilename(),
            $targetPhoto->encode()
        );

        $imagePost->markAsPonkaAdded();
        sleep(2);

        $this->entityManager->flush();
    }
}
