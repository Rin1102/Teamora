<?php
namespace App\Controller;

use App\Entity\{BulletinPaie, Employee, Entreprise, LeaveRequest, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{NumberType, TextType};
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/paie')]
class PaieController extends AbstractController
{
    #[Route('', name: 'paie_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $entreprise = $this->getEntreprise();
        $period = date('Y-m');
        $qb = $em->createQueryBuilder()
            ->select('b')->from(BulletinPaie::class, 'b')->join('b.employee', 'e')
            ->andWhere('e.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);
        $bulletins = (clone $qb)->orderBy('b.period', 'DESC')->addOrderBy('b.id', 'DESC')->setMaxResults(20)->getQuery()->getResult();
        $month = (clone $qb)->andWhere('b.period = :period')->setParameter('period', $period)->getQuery()->getResult();
        return $this->render('paie/index.html.twig', [
            'bulletins' => $bulletins,
            'count' => count($month),
            'mass' => array_sum(array_map(fn (BulletinPaie $b) => $b->getTotal(), $month)),
            'bonuses' => count(array_filter($month, fn (BulletinPaie $b) => $b->bonus > 0)),
        ]);
    }

    #[Route('/new', name: 'paie_new')]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $bulletin = new BulletinPaie();
        $bulletin->period = date('Y-m');
        $form = $this->createFormBuilder($bulletin)
            ->add('employee', EntityType::class, [
                'label' => 'Employé', 'class' => Employee::class,
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('e')->andWhere('e.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->getEntreprise())->orderBy('e.lastName', 'ASC');
                },
            ])
            ->add('period', TextType::class, ['label' => 'Période (AAAA-MM)', 'attr' => ['pattern' => '\d{4}-\d{2}']])
            ->add('bonus', NumberType::class, ['label' => 'Prime (DT)', 'required' => false])
            ->getForm()->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $existing = $em->getRepository(BulletinPaie::class)->findOneBy([
                'employee' => $bulletin->employee,
                'period' => $bulletin->period,
            ]);
            if ($existing) {
                $this->addFlash('error', 'Un bulletin existe déjà pour cet employé sur cette période.');
            } else {
                $bulletin->baseSalary = $bulletin->employee->baseSalary;
                $bulletin->bonus = (float) ($bulletin->bonus ?? 0);

                $start = \DateTime::createFromFormat('Y-m-d', $bulletin->period . '-01');
                $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);

                $deduction = (float) $em->createQueryBuilder()
                    ->select('COALESCE(SUM(l.salaryDeduction), 0)')
                    ->from(LeaveRequest::class, 'l')
                    ->andWhere('l.employee = :employee')
                    ->andWhere('l.startDate <= :end AND l.endDate >= :start')
                    ->setParameter('employee', $bulletin->employee)
                    ->setParameter('start', $start)
                    ->setParameter('end', $end)
                    ->getQuery()
                    ->getSingleScalarResult();

                $bulletin->deduction = $deduction;
                $em->persist($bulletin);
                $em->flush();
                $this->addFlash('success', 'Bulletin calculé avec la retenue du mois.');
                return $this->redirectToRoute('paie_index');
            }
        }
        return $this->render('crud/form.html.twig', ['form' => $form, 'title' => 'Nouveau bulletin / prime', 'back' => 'paie_index']);
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
