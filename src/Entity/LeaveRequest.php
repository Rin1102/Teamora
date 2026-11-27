<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LeaveRequest
{
    public const TYPES = ['Congé annuel', 'Congé maladie', 'Congé sans solde', 'Congé maternité'];
    public const PENDING = 'En attente', APPROVED = 'Approuvé', REFUSED = 'Refusé';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'leaves')]
    #[ORM\JoinColumn(nullable: false)]
    public ?Employee $employee = null;
    #[ORM\Column(length: 40)]
    public string $type = 'Congé annuel';
    #[ORM\Column(type: 'date')]
    public \DateTimeInterface $startDate;
    #[ORM\Column(type: 'date')]
    public \DateTimeInterface $endDate;
    #[ORM\Column(length: 20)]
    public string $status = self::PENDING;
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $reason = null;
    #[ORM\Column]
    public bool $paid = true;
    #[ORM\Column]
    public float $salaryDeduction = 0;

    public function __construct() { $this->startDate = new \DateTime(); $this->endDate = new \DateTime('+1 day'); }
    public function getDays(): int { return $this->startDate->diff($this->endDate)->days + 1; }
}
