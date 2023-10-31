<?php

namespace App\Entity;

use App\Repository\UsuariosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UsuariosRepository::class)]
class Usuarios implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    private ?string $nombres = null;

    #[ORM\Column(length: 50)]
    private ?string $apellidos = null;

    #[ORM\ManyToOne(inversedBy: 'estado_cuenta_id')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EstadoCuentaUsuario $estado_cuenta = null;

    #[ORM\ManyToOne(inversedBy: 'genero_id')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Genero $genero = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fecha_de_nacimiento = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha_de_registro = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha_de_acceso = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getNombres(): ?string
    {
        return $this->nombres;
    }

    public function setNombres(string $nombres): static
    {
        $this->nombres = $nombres;

        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): static
    {
        $this->apellidos = $apellidos;

        return $this;
    }

    public function getEstadoCuenta(): ?EstadoCuentaUsuario
    {
        return $this->estado_cuenta;
    }

    public function setEstadoCuenta(?EstadoCuentaUsuario $estado_cuenta): static
    {
        $this->estado_cuenta = $estado_cuenta;

        return $this;
    }

    public function getGenero(): ?Genero
    {
        return $this->genero;
    }

    public function setGenero(?Genero $genero): static
    {
        $this->genero = $genero;

        return $this;
    }

    public function getFechaDeNacimiento(): ?\DateTimeInterface
    {
        return $this->fecha_de_nacimiento;
    }

    public function setFechaDeNacimiento(\DateTimeInterface $fecha_de_nacimiento): static
    {
        $this->fecha_de_nacimiento = $fecha_de_nacimiento;

        return $this;
    }

    public function getFechaDeRegistro(): ?\DateTimeInterface
    {
        return $this->fecha_de_registro;
    }

    public function setFechaDeRegistro(\DateTimeInterface $fecha_de_registro): static
    {
        $this->fecha_de_registro = $fecha_de_registro;

        return $this;
    }

    public function getFechaDeAcceso(): ?\DateTimeInterface
    {
        return $this->fecha_de_acceso;
    }

    public function setFechaDeAcceso(\DateTimeInterface $fecha_de_acceso): static
    {
        $this->fecha_de_acceso = $fecha_de_acceso;

        return $this;
    }

    
}
