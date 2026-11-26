<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Employee
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;
    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    public string $matricule = '';
    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    public string $firstName = '';
    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    public string $lastName = '';
    #[ORM\Column(length: 1)]
    public string $gender = 'M';
    #[ORM\Column(length: 180)]
    #[Assert\Email]
    public string $email = '';
    #[ORM\Column(length: 30, nullable: true)]
    public ?string $phone = null;
    #[ORM\Column(length: 100, nullable: true)]
    public ?string $city = null;
    #[ORM\Column(length: 100)]
    public string $position = '';
    #[ORM\ManyToOne(inversedBy: 'employees')]
    public ?Department $department = null;
    #[ORM\Column(type: 'date')]
    public \DateTimeInterface $hireDate;
    #[ORM\Column(length: 20)]
    public string $contractType = 'CDI';
    #[ORM\Column(length: 20)]
    public string $status = 'Actif';
    #[ORM\Column]
    public float $baseSalary = 0;
    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Entreprise $entreprise = null;
    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: LeaveRequest::class, cascade: ['remove'])]
    public Collection $leaves;
    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: BulletinPaie::class, cascade: ['remove'])]
    public Collection $bulletinsPaie;

    public function __construct()
    {
        $this->hireDate = new \DateTime();
        $this->leaves = new ArrayCollection();
        $this->bulletinsPaie = new ArrayCollection();
    }
    public function getFullName(): string { return $this->firstName.' '.$this->lastName; }
    public function getAvatar(): string { return $this->gender === 'F' ? 'avatar_female.png' : 'avatar_male.png'; }
    public function __toString(): string { return $this->getFullName(); }
}
