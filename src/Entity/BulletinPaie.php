<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bulletin_paie')]
class BulletinPaie
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'bulletinsPaie')]
    #[ORM\JoinColumn(nullable: false)]
    public ?Employee $employee = null;
    #[ORM\Column(length: 7)]
    public string $period = '';          // format YYYY-MM
    #[ORM\Column]
    public float $baseSalary = 0;
    #[ORM\Column]
    public float $bonus = 0;

    public function getTotal(): float { return $this->baseSalary + $this->bonus; }
}
