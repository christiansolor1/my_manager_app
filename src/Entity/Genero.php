<?php

namespace App\Entity;

use App\Repository\GeneroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeneroRepository::class)]
class Genero
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $genero = null;

    #[ORM\OneToMany(mappedBy: 'genero', targetEntity: Usuarios::class)]
    private Collection $genero_id;

    public function __construct()
    {
        $this->genero_id = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGenero(): ?string
    {
        return $this->genero;
    }

    public function setGenero(string $genero): static
    {
        $this->genero = $genero;

        return $this;
    }

    /**
     * @return Collection<int, Usuarios>
     */
    public function getGeneroId(): Collection
    {
        return $this->genero_id;
    }

    public function addGeneroId(Usuarios $generoId): static
    {
        if (!$this->genero_id->contains($generoId)) {
            $this->genero_id->add($generoId);
            $generoId->setGenero($this);
        }

        return $this;
    }

    public function removeGeneroId(Usuarios $generoId): static
    {
        if ($this->genero_id->removeElement($generoId)) {
            // set the owning side to null (unless already changed)
            if ($generoId->getGenero() === $this) {
                $generoId->setGenero(null);
            }
        }

        return $this;
    }
}
