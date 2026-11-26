<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'absence')]
class Absence
{
    public const TYPES = [
        'Maladie', 'Accident de travail', 'Absence personnelle',
        'Absence familiale', 'Absence exceptionnelle', 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    public ?Employee $employee = null;

    #[ORM\Column(length: 40)]
    #[Assert\Choice(choices: self::TYPES)]
    public string $type = 'Maladie';

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    public \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    public \DateTimeInterface $endDate;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $reason = null;

    #[ORM\Column]
    public bool $justified = false;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $document = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $rhComment = null;

    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime();
    }

    public function getDays(): int
    {
        return $this->startDate->diff($this->endDate)->days + 1;
    }
}
