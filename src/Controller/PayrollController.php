<?php
namespace App\Controller;

use App\Entity\{Employee, Entreprise, Payslip, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{NumberType, TextType};
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/payroll')]
class PayrollController extends AbstractController
{
    #[Route('', name: 'payroll_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $entreprise = $this->getEntreprise();
        $period = date('Y-m');
        $qb = $em->createQueryBuilder()
            ->select('p')->from(Payslip::class, 'p')->join('p.employee', 'e')
            ->andWhere('e.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);
        $slips = (clone $qb)->orderBy('p.period', 'DESC')->addOrderBy('p.id', 'DESC')->setMaxResults(20)->getQuery()->getResult();
        $month = (clone $qb)->andWhere('p.period = :period')->setParameter('period', $period)->getQuery()->getResult();
        return $this->render('payroll/index.html.twig', [
            'slips' => $slips,
            'count' => count($month),
            'mass' => array_sum(array_map(fn (Payslip $p) => $p->getTotal(), $month)),
            'bonuses' => count(array_filter($month, fn (Payslip $p) => $p->bonus > 0)),
        ]);
    }

    #[Route('/new', name: 'payroll_new')]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $p = new Payslip();
        $p->period = date('Y-m');
        $form = $this->createFormBuilder($p)
            ->add('employee', EntityType::class, [
                'label' => 'Employé',
                'class' => Employee::class,
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('e')
                        ->andWhere('e.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->getEntreprise())
                        ->orderBy('e.lastName', 'ASC');
                },
            ])
            ->add('period', TextType::class, ['label' => 'Période (AAAA-MM)', 'attr' => ['pattern' => '\d{4}-\d{2}']])
            ->add('baseSalary', NumberType::class, ['label' => 'Salaire de base (DT)', 'help' => 'Laissez 0 pour reprendre le salaire de l\'employé'])
            ->add('bonus', NumberType::class, ['label' => 'Prime (DT)'])
            ->getForm()->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($p->baseSalary == 0) { $p->baseSalary = $p->employee->baseSalary; }
            $em->persist($p);
            $em->flush();
            return $this->redirectToRoute('payroll_index');
        }
        return $this->render('crud/form.html.twig', ['form' => $form, 'title' => 'Nouveau bulletin / prime', 'back' => 'payroll_index']);
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
