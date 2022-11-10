<?php

namespace App\Entity;

use App\Entity\Book;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;


/**
* @Hateoas\Relation(
* "self",
* href = @Hateoas\Route(
* "app_detail_author",
* parameters = { "id" = "expr(object.getId())" }
* ),
* exclusion = @Hateoas\Exclusion(groups="getAuthors")
* )
*
* @Hateoas\Relation(
* "delete",
* href = @Hateoas\Route(
* "app_delete_author",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getAuthors", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
* @Hateoas\Relation(
* "update",
* href = @Hateoas\Route(
* "app_update_author",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getAuthors", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
*/

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getBooks","getAuthors"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks","getAuthors"])]
    #[Assert\NotBlank(message: "Le prénom de l'auteur est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le prénom de l'auteur doit faire au moins {{ limit }} caractères",
        maxMessage: "Le prénom de l'auteur ne peut pas faire plus de {{ limit }} caractères"
    )]
    private ?string $firstName = null;





    #[ORM\Column(length: 255)]
    #[Groups(["getBooks","getAuthors"])]
    #[Assert\NotBlank(message: "Le nom de l'auteur est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le nom de l'auteur doit faire au moins {{ limit }} caractères",
        maxMessage: "Le nom de l'auteur ne peut pas faire plus de {{ limit }} caractères"
    )]
    private ?string $lastName = null;

    
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Book::class)]
    #[Groups(["getAuthors"])]
    
    private Collection $book;

    public function __construct()
    {
        $this->book = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBook(): Collection
    {
        return $this->book;
    }

    public function addBook(Book $book): self
    {
        if (!$this->book->contains($book)) {
            $this->book->add($book);
            $book->setAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): self
    {
        if ($this->book->removeElement($book)) {
            // set the owning side to null (unless already changed)
            if ($book->getAuthor() === $this) {
                $book->setAuthor(null);
            }
        }

        return $this;
    }
}
