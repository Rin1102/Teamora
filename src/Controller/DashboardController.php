<?php
namespace App\Controller;

use App\Entity\{Department, Employee, Entreprise, LeaveRequest, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $entreprise = $this->getEntreprise();
        $employees = $em->getRepository(Employee::class)->findBy(['entreprise' => $entreprise]);
        $total = count($employees);
        $labels = $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $end = new \DateTime("last day of -$i month");
            $labels[] = $end->format('M');
            $data[] = count(array_filter($employees, fn (Employee $e) => $e->hireDate <= $end));
        }
        $today = new \DateTime('today');
        $onLeave = (int) $em->createQuery(
            'SELECT COUNT(DISTINCT IDENTITY(l.employee)) FROM App\Entity\LeaveRequest l
             JOIN l.employee e
             WHERE l.status = :s AND l.startDate <= :d AND l.endDate >= :d
             AND e.entreprise = :entreprise')
            ->setParameters(['s' => LeaveRequest::APPROVED, 'd' => $today, 'entreprise' => $entreprise])
            ->getSingleScalarResult();

        return $this->render('dashboard.html.twig', [
            'total' => $total,
            'departments' => $em->getRepository(Department::class)->count(['entreprise' => $entreprise]),
            'pending' => (int) $em->createQuery(
                'SELECT COUNT(l) FROM App\Entity\LeaveRequest l JOIN l.employee e
                 WHERE l.status = :status AND e.entreprise = :entreprise')
                ->setParameters(['status' => LeaveRequest::PENDING, 'entreprise' => $entreprise])
                ->getSingleScalarResult(),
            'bonuses' => (int) $em->createQuery(
                'SELECT COUNT(p) FROM App\Entity\BulletinPaie p JOIN p.employee e
                 WHERE p.period = :p AND p.bonus > 0 AND e.entreprise = :entreprise')
                ->setParameters(['p' => date('Y-m'), 'entreprise' => $entreprise])
                ->getSingleScalarResult(),
            'labels' => $labels, 'data' => $data,
            'onLeave' => $onLeave,
            'percent' => $total ? round($onLeave / $total * 100) : 0,
        ]);
    }

    private function getEntreprise(): Entreprise
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getEntreprise()) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à ce compte RH.');
        }
        return $user->getEntreprise();
    }
}
