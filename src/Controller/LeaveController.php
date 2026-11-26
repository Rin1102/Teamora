<?php
namespace App\Controller;

use App\Entity\{Employee, Entreprise, LeaveRequest, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, DateType, TextareaType};
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/leaves')]
class LeaveController extends AbstractController
{
    #[Route('', name: 'leave_index')]
    public function index(Request $r, EntityManagerInterface $em): Response
    {
        $entreprise = $this->getEntreprise();
        $repo = $em->getRepository(LeaveRequest::class);
        $filter = $r->query->get('status');
        $base = $em->createQueryBuilder()
            ->select('l')->from(LeaveRequest::class, 'l')->join('l.employee', 'e')
            ->andWhere('e.entreprise = :entreprise')->setParameter('entreprise', $entreprise);
        $leaves = clone $base;
        if ($filter) { $leaves->andWhere('l.status = :status')->setParameter('status', $filter); }
        $counts = ['all' => (int) (clone $base)->select('COUNT(l)')->getQuery()->getSingleScalarResult()];
        foreach ([LeaveRequest::PENDING, LeaveRequest::APPROVED, LeaveRequest::REFUSED] as $s) {
            $counts[$s] = (int) (clone $base)->select('COUNT(l)')->andWhere('l.status = :s')->setParameter('s', $s)->getQuery()->getSingleScalarResult();
        }
        $leaves->orderBy('l.startDate', 'DESC');
        return $this->render('leave/index.html.twig', [
            'leaves' => $leaves->getQuery()->getResult(),
            'counts' => $counts, 'filter' => $filter,
        ]);
    }

    #[Route('/new', name: 'leave_new')]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $l = new LeaveRequest();
        $form = $this->createFormBuilder($l)
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
            ->add('type', ChoiceType::class, ['label' => 'Type', 'choices' => array_combine(LeaveRequest::TYPES, LeaveRequest::TYPES)])
            ->add('startDate', DateType::class, ['label' => 'Début', 'widget' => 'single_text', 'input' => 'datetime'])
            ->add('endDate', DateType::class, ['label' => 'Fin', 'widget' => 'single_text', 'input' => 'datetime'])
            ->add('reason', TextareaType::class, ['label' => 'Motif', 'required' => false])
            ->getForm()->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($l->endDate < $l->startDate) {
                $form->addError(new \Symfony\Component\Form\FormError('La date de fin précède la date de début.'));
            } else {
                $em->persist($l);
                $em->flush();
                return $this->redirectToRoute('leave_index');
            }
        }
        return $this->render('crud/form.html.twig', ['form' => $form, 'title' => 'Nouvelle demande de congé', 'back' => 'leave_index']);
    }

    #[Route('/{id}/{decision}', name: 'leave_decide', methods: ['POST'], requirements: ['decision' => 'approve|refuse'])]
    public function decide(Request $r, EntityManagerInterface $em, LeaveRequest $leave, string $decision): Response
    {
        if ($leave->employee && $leave->employee->entreprise?->getId() !== $this->getEntreprise()->getId()) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('leave'.$leave->id, $r->request->get('_token'))) {
            $leave->status = $decision === 'approve' ? LeaveRequest::APPROVED : LeaveRequest::REFUSED;
            $em->flush();
        }
        return $this->redirectToRoute('leave_index');
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
