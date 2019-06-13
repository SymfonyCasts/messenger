<?php

namespace App\Controller;

use App\Entity\ImagePost;
use App\Photo\PhotoPonkaficator;
use App\Repository\ImagePostRepository;
use App\Photo\PhotoFileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImagePostController extends AbstractController
{
    /**
     * @Route("/api/images", methods="GET")
     */
    public function list(ImagePostRepository $repository)
    {
        $posts = $repository->findBy([], ['createdAt' => 'DESC']);

        return $this->json([
            'items' => $posts
        ]);
    }

    /**
     * @Route("/api/images", methods="POST")
     */
    public function create(Request $request, ValidatorInterface $validator, PhotoFileManager $photoManager, EntityManagerInterface $entityManager, PhotoPonkaficator $ponkaficator)
    {
        /** @var UploadedFile $imageFile */
        $imageFile = $request->files->get('file');

        $errors = $validator->validate($imageFile, [
            new Image(),
            new NotBlank()
        ]);

        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }

        $newFilename = $photoManager->uploadImage($imageFile);
        $imagePost = new ImagePost();
        $imagePost->setFilename($newFilename);
        $imagePost->setOriginalFilename($imageFile->getClientOriginalName());

        $entityManager->persist($imagePost);
        $entityManager->flush();

        /*
         * Start Ponkafication!
         */
        $updatedContents = $ponkaficator->ponkafy(
            $photoManager->read($newFilename)
        );
        $photoManager->update($newFilename, $updatedContents);
        $imagePost->markAsPonkaAdded();
        $entityManager->flush();
        /*
         * You've been Ponkafied!
         */

        return $this->json($imagePost, 201);
    }

    /**
     * @Route("/api/images/{id}", methods="DELETE")
     */
    public function delete(ImagePost $imagePost, EntityManagerInterface $entityManager, PhotoFileManager $uploaderManager)
    {
        $uploaderManager->deleteImage($imagePost->getFilename());

        $entityManager->remove($imagePost);
        $entityManager->flush();

        return new Response(null, 204);
    }

    /**
     * @Route("/api/images/{id}", methods="GET", name="get_image_post_item")
     */
    public function getItem(ImagePost $imagePost)
    {
        return $this->json($imagePost);
    }

    protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        // add the image:output group by default
        if (!isset($context['groups'])) {
            $context['groups'] = ['image:output'];
        }

        return parent::json($data, $status, $headers, $context);
    }
}
