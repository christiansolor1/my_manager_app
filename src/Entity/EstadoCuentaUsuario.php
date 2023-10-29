<?php

namespace App\Entity;

use App\Repository\EstadoCuentaUsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EstadoCuentaUsuarioRepository::class)]
class EstadoCuentaUsuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $estado_cuenta = null;

    #[ORM\OneToMany(mappedBy: 'estado_cuenta', targetEntity: Usuarios::class)]
    private Collection $estado_cuenta_id;

    public function __construct()
    {
        $this->estado_cuenta_id = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEstadoCuenta(): ?string
    {
        return $this->estado_cuenta;
    }

    public function setEstadoCuenta(string $estado_cuenta): static
    {
        $this->estado_cuenta = $estado_cuenta;

        return $this;
    }

    /**
     * @return Collection<int, Usuarios>
     */
    public function getEstadoCuentaId(): Collection
    {
        return $this->estado_cuenta_id;
    }

    public function addEstadoCuentaId(Usuarios $estadoCuentaId): static
    {
        if (!$this->estado_cuenta_id->contains($estadoCuentaId)) {
            $this->estado_cuenta_id->add($estadoCuentaId);
            $estadoCuentaId->setEstadoCuenta($this);
        }

        return $this;
    }

    public function removeEstadoCuentaId(Usuarios $estadoCuentaId): static
    {
        if ($this->estado_cuenta_id->removeElement($estadoCuentaId)) {
            // set the owning side to null (unless already changed)
            if ($estadoCuentaId->getEstadoCuenta() === $this) {
                $estadoCuentaId->setEstadoCuenta(null);
            }
        }

        return $this;
    }
}
