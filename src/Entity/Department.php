<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Department
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    public string $name = '';
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $photo = null;
    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Entreprise $entreprise = null;
    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id', nullable: true)]
    public ?Employee $manager = null;
    #[ORM\OneToMany(mappedBy: 'department', targetEntity: Employee::class)]
    public Collection $employees;

    public function __construct() { $this->employees = new ArrayCollection(); }
    public function setEntreprise(?Entreprise $entreprise): static { $this->entreprise = $entreprise; return $this; }
    public function __toString(): string { return $this->name; }
}
