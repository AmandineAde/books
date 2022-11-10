<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookRepository;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Since;

/**
* @Hateoas\Relation(
* "self",
* href = @Hateoas\Route(
* "app_detail_book",
* parameters = { "id" = "expr(object.getId())" }
* ),
* exclusion = @Hateoas\Exclusion(groups="getBooks")
* )
*
* @Hateoas\Relation(
* "delete",
* href = @Hateoas\Route(
* "app_delete_book",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getBooks", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
* @Hateoas\Relation(
* "update",
* href = @Hateoas\Route(
* "app_update_book",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getBooks", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
*/


#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getBooks","getAuthors"])]
    private ?int $id = null;


    #[ORM\Column(length: 255)]
    #[Groups(["getBooks","getAuthors"])]
    #[Assert\NotBlank(message: "Le titre du livre est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le titre doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas faire plus de {{ limit }} caractères"
    )]


    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getBooks","getAuthors"])]
    #[Assert\NotBlank(message: "La courverture du livre est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "La courverture du livre doit faire au moins {{ limit }} caractères",
        maxMessage: "La courverture du livre ne peut pas faire plus de {{ limit }} caractères"
    )]
    private ?string $coverText = null;

    #[ORM\ManyToOne(inversedBy: 'book')]
    #[Groups(["getBooks"])]
    private ?Author $author = null;

    #[ORM\Column(type:'text', nullable: true)]
    #[Groups(["getBooks"])]
    #[Since("2.0")]// ce champ n'existe qu'à partir de la version 2 
    private $comment;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCoverText(): ?string
    {
        return $this->coverText;
    }

    public function setCoverText(?string $coverText): self
    {
        $this->coverText = $coverText;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
