<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'entreprise')]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $address = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $city = '';

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    private string $phone = '';

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\OneToOne(mappedBy: 'entreprise', targetEntity: User::class)]
    private ?User $rh = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = trim($name); return $this; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $address): static { $this->address = trim($address); return $this; }

    public function getCity(): string { return $this->city; }
    public function setCity(string $city): static { $this->city = trim($city); return $this; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): static { $this->phone = trim($phone); return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = trim($email); return $this; }

    public function getRh(): ?User { return $this->rh; }
    public function setRh(?User $rh): static { $this->rh = $rh; return $this; }
}
