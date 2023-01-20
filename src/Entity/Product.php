<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "detailProduct",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getProducts")
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "deleteProduct",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getProducts", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "updateProduct",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getProducts", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getProducts'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getProducts'])]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le nom doit faire au minimum {{ limit }} caractères", maxMessage: "Le nom ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getProducts'])]
    #[Assert\NotBlank(message: "La marque du produit est obligatoire")]
    #[Assert\Length(min: 2, max: 255, minMessage: "La marque doit faire au minimum {{ limit }} caractères", maxMessage: "La marque ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $brand = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['getProducts'])]
    #[Assert\NotBlank(message: 'Veuillez renseigner la date de sortie du produit')]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getProducts'])]
    #[Assert\NotBlank(message: 'veuillez renseigner le système (OS) de l\'appareil')]
    private ?string $operatingSystem = null;

    #[ORM\Column]
    #[Groups(['getProducts'])]
    #[Assert\NotNull(message: 'Veuillez renseigner le prix de l\'appareil')]
    private ?int $Price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(\DateTimeInterface $releaseDate): self
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operatingSystem;
    }

    public function setOperatingSystem(string $operatingSystem): self
    {
        $this->operatingSystem = $operatingSystem;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->Price;
    }

    public function setPrice(int $Price): self
    {
        $this->Price = $Price;

        return $this;
    }
}
