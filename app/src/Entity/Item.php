<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata as MappingClassMetadata;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    const UNPUBLISHED = 'unpublished';
    const PUBLISHED = 'published';
    const CLOSED = 'closed';

    const TRANSLATED_STATUS = [
        self::UNPUBLISHED => 'Dépubliée',
        self::PUBLISHED => 'Enchère ouverte',
        self::CLOSED => 'Enchère fermée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $startingPrice = null;

    #[ORM\Column(length: 15)]
    private ?string $status = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'items')]   
    private Collection $categories;

    /**
     * @var Collection<int, Offer>
     */
    #[ORM\OneToMany(targetEntity: Offer::class, mappedBy: 'item', orphanRemoval: true)]
    private Collection $offers;

    #[ORM\Column(nullable: true)]
    private ?float $FinalPrice = null;

    #[ORM\ManyToOne]
    private ?User $winner = null;

    #[ORM\OneToOne(mappedBy: 'item', cascade: ['persist', 'remove'])]
    private ?Payment $payment = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->offers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartingPrice(): ?float
    {
        return $this->startingPrice;
    }

    public function setStartingPrice(float $startingPrice): static
    {
        $this->startingPrice = $startingPrice;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addItem($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeItem($this);
        }

        return $this;
    }

    public function getTranslatedStatus()
    {
        return self::TRANSLATED_STATUS[$this->status];
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): static
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->setItem($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): static
    {
        if ($this->offers->removeElement($offer)) {
            // set the owning side to null (unless already changed)
            if ($offer->getItem() === $this) {
                $offer->setItem(null);
            }
        }

        return $this;
    }

    public function getFinalPrice(): ?float
    {
        return $this->FinalPrice;
    }

    public function setFinalPrice(?float $FinalPrice): static
    {
        $this->FinalPrice = $FinalPrice;

        return $this;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): static
    {
        $this->winner = $winner;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(Payment $payment): static
    {
        // set the owning side of the relation if necessary
        if ($payment->getItem() !== $this) {
            $payment->setItem($this);
        }

        $this->payment = $payment;

        return $this;
    }
}
