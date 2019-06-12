<?php

namespace App\Serializer\Normalizer;

use App\Entity\ImagePost;
use App\Upload\PhotoUploaderManager;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ImagePostNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;
    private $uploaderManager;

    public function __construct(ObjectNormalizer $normalizer, PhotoUploaderManager $uploaderManager)
    {
        $this->normalizer = $normalizer;
        $this->uploaderManager = $uploaderManager;
    }

    /**
     * @param ImagePost $imagePost
     */
    public function normalize($imagePost, $format = null, array $context = array()): array
    {
        $data = $this->normalizer->normalize($imagePost, $format, $context);

        $data['url'] = $this->uploaderManager->getPublicPath($imagePost);

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof ImagePost;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
