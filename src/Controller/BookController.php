<?php

namespace App\Controller;

use App\Entity\Book;
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
use Symfony\Component\Serializer\SerializerInterface as SerializerSerializerInterface;
use App\Service\VersioningService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


class BookController extends AbstractController
{

    /**
     * Cette méthode permet de récupérer l'ensemble des livres.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la liste des livres",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Book::class, groups={"getBooks"}))
     *      )
     * )
     * 
     * @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="La page que l'on veut récupérer",
     *      @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Le nombre d'éléments que l'on veut récupérer",
     *      @OA\Schema(type="int")
     * )
     * 
     * @OA\Tag(name="Books")
     * 
     * @param BookRepository $bookRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('api/book', name: 'app_book', methods:['GET'])]
    public function getAllBooks(BookRepository $bookRepository,SerializerInterface $serializer, Request $request,TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $idCache = "getAllBooks-" . $page . "-" . $limit;
        $jsonBookList = $cachePool->get($idCache, function(ItemInterface $item) use ($bookRepository, $page, $limit, $serializer) {
            $item->tag("booksCache");
            $bookList = $bookRepository->findAllWithPagination($page, $limit);
            $context =SerializationContext::create()->setGroups(['getBooks']);
            return $serializer->serialize($bookList, 'json', $context);
        });

        return new JsonResponse($jsonBookList, Response::HTTP_OK,[], true);
    }
   


    // #[Route('api/book/{id}', name: 'app_detail_book',methods:['GET'])]
    // public function getDetailBook(int $id, Book $book, SerializerInterface $serializer, TagAwareCacheInterface $cachePool): JsonResponse
    // {
        
    //     $idCache = "getBooks-".$id;
    //     $jsonBook = $cachePool->get($idCache, function(ItemInterface $item, VersioningService $versioningService) use ($book, $serializer) {
    //         $item->tag("booksCache");
    //         $version = $versioningService->getVersion();
    //         $context =SerializationContext::create()->setGroups(['getBooks']);
    //         $context->setVersion($version);
    //         return $serializer->serialize($book, 'json', $context);
    //     });
    //     return new JsonResponse($jsonBook, Response::HTTP_OK, [],true);
    // }
    
    #[Route('api/book/{id}', name: 'app_detail_book',methods:['GET'])]
    public function getDetailBook(Book $book, SerializerInterface $serializer, VersioningService $versioningService): JsonResponse
    {
    $version = $versioningService->getVersion();
    $context = SerializationContext::create()->setGroups(["getBooks"]);
    $context->setVersion($version);
    $jsonBook = $serializer->serialize($book, 'json',$context);
    return new JsonResponse($jsonBook, Response::HTTP_OK, [],true);
    }



    //DELETE
    #[Route('api/book/{id}', name: 'app_delete_book',methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un livre')]

    public function deleteBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse{
        $cachePool->invalidateTags(["booksCache"]);
        $em->remove($book);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    //CREATE
    #[Route('api/book', name: 'app_create_book',methods:['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
    public function createBook(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $em
    ,UrlGeneratorInterface $urlGeneratorInterface,AuthorRepository $authorRepository, ValidatorInterface $validator): JsonResponse 
    {
            
            //Création de la variable book et on vient deserialiser le contenu du book
            $book = $serializerInterface->deserialize($request->getContent(),Book::class,'json');

            // On vérifie les erreurs
            $errors = $validator->validate($book);
            if ($errors->count() > 0) {
                return new JsonResponse($serializerInterface->serialize($errors,
                'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            //On vient récupérer de l'ensemble des données envoyées sous forme de tableau
            $content =$request->toArray();
            //Récupération de l'ID author, si il n'est pas défini, alors on met -1 par défaut
            $idAuthor= $content['author']??-1;

            //Si l'id existe ça lie l'auteur si il n'edxiste pas ça creer un auteur
            if(gettype($idAuthor) != "integer") {
                $em->persist($book->getAuthor());
            } else {
                //On cherche l'auteur qui correspond et on l'assigne au livre
                $book->setAuthor($authorRepository->find($idAuthor));
            }


            $em->persist($book);//Preparation de la requête 
            $em->flush();//Envoie la requête
            //on reprend les "groups" créés auparavant
            $context =SerializationContext::create()->setGroups(['getBooks']);
            $jsonBook = $serializerInterface->serialize($book, 'json', $context);
            //on génère une URL avec l'id du livre crée
            $location= $urlGeneratorInterface->generate('app_detail_book',['id'=> $book->getId()],UrlGeneratorInterface::ABSOLUTE_URL);
            //on retourne un JSON
            return new JsonResponse($jsonBook, Response::HTTP_CREATED,['location'=>$location],true);
    }

    //UPDATE
    #[Route('api/book/{id}', name: 'app_update_book', methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour éditer un livre')]
    public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse{
        $newBook = $serializer->deserialize($request->getContent(), Book::class, 'json');
        $currentBook->setTitle($newBook->getTitle());
        $currentBook->setCoverText($newBook->getCoverText());

        // On vérifie les erreurs
        $errors = $validator->validate($currentBook);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $currentBook->setAuthor($authorRepository->find($idAuthor));
        $em->persist($currentBook);
        $em->flush();
        // On vide le cache.
        $cache->invalidateTags(["booksCache"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

}
