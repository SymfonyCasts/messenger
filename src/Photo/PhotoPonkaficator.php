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

    public function ponkafy(string $imageContents): string
    {
        $targetPhoto = $this->imageManager->make($imageContents);

        $ponkaFilename = sprintf(
            __DIR__.'/../../assets/ponka/ponka%d.jpg',
            rand(1, 32)
        );
        $ponkaPhoto = $this->imageManager->make($ponkaFilename);

        $targetWidth = $targetPhoto->width() * .3;
        $targetHeight = $targetPhoto->height() * .4;

        $ponkaPhoto->resize($targetWidth, $targetHeight, function(Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $targetPhoto = $targetPhoto->insert(
            $ponkaPhoto,
            'bottom-left',
            (int) ($targetPhoto->width() * .05),
            (int) ($targetPhoto->height() * .05)
        );

        // for dramatic effect, make this *really* slow
        sleep(2);

        return (string) $targetPhoto->encode();
    }
}
