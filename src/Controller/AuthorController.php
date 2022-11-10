<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    class AuthorController extends AbstractController
    {
        #[Route('api/author', name: 'app_author', methods:['GET'])]
        public function getAllAuthors(AuthorRepository $authorRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 3);
            $idCache = "getAllAuthors-" . $page . "-" . $limit;
            $jsonAuthorList = $cachePool->get($idCache, function(ItemInterface $item) use ($authorRepository, $page, $limit, $serializer) {
                $item->tag("authorsCache");
                $authorList = $authorRepository->findAllWithPagination($page, $limit);
                $context =SerializationContext::create()->setGroups(['getAuthors']);
                return $serializer->serialize($authorList, 'json', $context);
            });
            return new JsonResponse($jsonAuthorList, Response::HTTP_OK,[], true);
        }

        #[Route('api/author/{id}', name: 'app_detail_author',methods:['GET'])]
        public function getDetailAuthor(int $id, Author $author, SerializerInterface $serializer, TagAwareCacheInterface $cachePool): JsonResponse
        {
                $idCache = "getAuthors-".$id;

                $jsonAuthor = $cachePool->get($idCache, function(ItemInterface $item) use ($author, $serializer) {
                    $item->tag("authorsCache");
                    $context =SerializationContext::create()->setGroups(['getAuthors']);
                    return $serializer->serialize($author, 'json', $context);
                });

                return new JsonResponse($jsonAuthor, Response::HTTP_OK, [],true);
        }

        //DELETE
        #[Route('api/author/{id}', name: 'app_delete_author',methods:['DELETE'])]
        #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un auteur')]
        public function deleteAuthor(Author $author, AuthorRepository $authorRepository,EntityManagerInterface $em, BookRepository $bookRepository, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $Books = $bookRepository->findBy(array('author' => $author->getId()));
            foreach ($Books as $book) {
                $bookRepository->remove($book, true);

            }

            $cachePool->invalidateTags(["authorsCache","booksCache"]);
            $em->remove($author);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        
    


        //CREATE
        #[Route('api/author', name: 'app_create_author',methods:['POST'])]
        public function createAuthor(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $em, UrlGeneratorInterface $urlGeneratorInterface, BookRepository $bookRepository, ValidatorInterface $validator): JsonResponse 
        {
                $author = $serializerInterface->deserialize($request->getContent(),Author::class,'json');

                // On vérifie les erreurs
                $errors = $validator->validate($author);
                if ($errors->count() > 0) {
                    return new JsonResponse($serializerInterface->serialize($errors,
                    'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
                }

                $content =$request->toArray();
                $idBook= $content['book']??-1;
                $author->addBook($bookRepository->findOneBy(array('id' => $idBook)));

                $em->persist($author);//Preparation de la requête 
                $em->flush();//Envoie la requête

                //on reprend les "groups" créés auparavant
                $context =SerializationContext::create()->setGroups(['getAuthors']);
                $jsonAuthor = $serializerInterface->serialize($author, 'json', $context);
                //on génère une URL avec l'id de l'author crée
                $location= $urlGeneratorInterface->generate('app_detail_author',['id'=> $author->getId()],UrlGeneratorInterface::ABSOLUTE_URL);

                //on retourne un JSON
                return new JsonResponse($jsonAuthor, Response::HTTP_CREATED,['location'=>$location],true);
        }
        
        //UPDATE

        #[Route('/api/author/{id}', name: 'app_update_author', methods:['PUT'])]
        #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour éditer un auteur')]
        public function updateAuthor(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $em, Author $currentAuthor, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $newAuthor = $serializerInterface->deserialize($request->getContent(), Author::class, 'json');
    
            $currentAuthor->setFirstName($newAuthor->getFirstName());
            $currentAuthor->setLastName($newAuthor->getLastName());
    
            $errors = $this->validator->validate($currentAuthor);
    
            if ($errors->count() > 0) {
                return new JsonResponse($this->serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
    
            $em->persist($currentAuthor);
            $em->flush();
            $cachePool->invalidateTags(['authorCache']);
    
            return new JsonResponse("Objet mis à jour avec succès", Response::HTTP_ACCEPTED);
        }
    }