<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';
    #[ORM\Column(length: 120)]
    private string $fullName = '';
    #[ORM\Column]
    private array $roles = ['ROLE_HR'];
    #[ORM\Column]
    private string $password = '';
    #[ORM\OneToOne(inversedBy: 'rh', targetEntity: Entreprise::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'entreprise_id', referencedColumnName: 'id', nullable: true, unique: true)]
    private ?Entreprise $entreprise = null;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $e): static { $this->email = $e; return $this; }
    public function getFullName(): string { return $this->fullName; }
    public function setFullName(string $n): static { $this->fullName = $n; return $this; }
    public function getRoles(): array { return array_unique([...$this->roles, 'ROLE_HR']); }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $p): static { $this->password = $p; return $this; }
    public function getUserIdentifier(): string { return $this->email; }
    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $entreprise): static { $this->entreprise = $entreprise; return $this; }
    public function eraseCredentials(): void {}
}
