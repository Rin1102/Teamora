<?php

namespace App\Controller;

use App\Entity\{Absence, Employee, Entreprise, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, DateType, FileType, TextareaType};
use Symfony\Component\HttpFoundation\{File\UploadedFile, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;

#[Route('/pointage')]
class AbsenceController extends AbstractController
{
    #[Route('', name: 'absence_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $entreprise = $this->getEntreprise();
        $month = $request->query->get('month', date('Y-m'));

        $qb = $em->createQueryBuilder()
            ->select('a')->from(Absence::class, 'a')->join('a.employee', 'e')
            ->andWhere('e.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->orderBy('a.startDate', 'DESC');

        if ($month) {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');
            if ($start) {
                $end = $start->modify('last day of this month')->setTime(23, 59, 59);
                $qb->andWhere('a.startDate <= :end AND a.endDate >= :start')
                    ->setParameter('end', \DateTime::createFromInterface($end))
                    ->setParameter('start', \DateTime::createFromInterface($start));
            }
        }

        return $this->render('absence/index.html.twig', [
            'absences' => $qb->getQuery()->getResult(),
            'month' => $month,
        ]);
    }

    #[Route('/absence/new', name: 'absence_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $absence = new Absence();
        $form = $this->createFormBuilder($absence)
            ->add('employee', EntityType::class, [
                'label' => 'Employé concerné', 'class' => Employee::class,
                'choice_label' => fn (Employee $e) => $e->getFullName() . ' — ' . ($e->position ?: 'Employé'),
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('e')->andWhere('e.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->getEntreprise())->orderBy('e.lastName', 'ASC');
                },
            ])
            ->add('type', ChoiceType::class, ['label' => 'Type d’absence', 'choices' => array_combine(Absence::TYPES, Absence::TYPES)])
            ->add('startDate', DateType::class, ['label' => 'Date de début', 'widget' => 'single_text', 'input' => 'datetime'])
            ->add('endDate', DateType::class, ['label' => 'Date de fin', 'widget' => 'single_text', 'input' => 'datetime'])
            ->add('reason', TextareaType::class, ['label' => 'Motif de l’absence', 'required' => false])
            ->add('justified', ChoiceType::class, ['label' => 'Absence justifiée ?', 'choices' => ['Oui' => true, 'Non' => false], 'expanded' => true])
            ->add('document', FileType::class, [
                'label' => 'Justificatif', 'mapped' => false, 'required' => false,
                'constraints' => [new File(maxSize: '5M', mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'], mimeTypesMessage: 'Formats acceptés : PDF, JPG ou PNG.')],
            ])
            ->add('rhComment', TextareaType::class, ['label' => 'Commentaire du RH', 'required' => false])
            ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document = $form->get('document')->getData();
            if ($absence->justified && !$document) {
                $form->get('document')->addError(new \Symfony\Component\Form\FormError('Un justificatif est obligatoire pour une absence justifiée.'));
            } elseif ($absence->endDate < $absence->startDate) {
                $form->addError(new \Symfony\Component\Form\FormError('La date de fin ne peut pas précéder la date de début.'));
            } else {
                /** @var UploadedFile|null $document */
                $document = $form->get('document')->getData();
                if ($document) {
                    $directory = $this->getParameter('kernel.project_dir') . '/public/uploads/absences';
                    if (!is_dir($directory)) { mkdir($directory, 0775, true); }
                    $filename = uniqid('absence_', true) . '.' . $document->guessExtension();
                    $document->move($directory, $filename);
                    $absence->document = $filename;
                }
                $em->persist($absence);
                $em->flush();
                $this->addFlash('success', 'Absence enregistrée.');
                return $this->redirectToRoute('absence_index');
            }
        }

        return $this->render('absence/new.html.twig', ['form' => $form]);
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
