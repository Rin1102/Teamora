<?php
namespace App\Controller;

use App\Entity\{Department, Employee, Entreprise, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, DateType, EmailType, NumberType, TextType};
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employees')]
class EmployeeController extends AbstractController
{
    #[Route('', name: 'employee_index')]
    public function index(Request $r, EntityManagerInterface $em): Response
    {
        $entreprise = $this->getEntreprise();
        $q = trim((string) $r->query->get('q'));
        $qb = $em->getRepository(Employee::class)->createQueryBuilder('e')
            ->andWhere('e.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->orderBy('e.lastName');
        if ($q !== '') {
            $qb->andWhere('e.firstName LIKE :q OR e.lastName LIKE :q OR e.position LIKE :q OR e.email LIKE :q')->setParameter('q', "%$q%");
        }
        return $this->render('employee/index.html.twig', ['employees' => $qb->getQuery()->getResult(), 'q' => $q]);
    }

    #[Route('/new', name: 'employee_new')]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $entreprise = $this->getEntreprise();
        $e = new Employee();
        $e->entreprise = $entreprise;
        $count = $em->getRepository(Employee::class)->count(['entreprise' => $entreprise]);
        $e->matricule = 'EMP-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        return $this->save($r, $em, $e, 'Ajouter un employé');
    }

    #[Route('/{id}', name: 'employee_show', requirements: ['id' => '\d+'])]
    public function show(Employee $employee): Response
    {
        $this->assertEntreprise($employee->entreprise);
        return $this->render('employee/show.html.twig', ['e' => $employee]);
    }

    #[Route('/{id}/edit', name: 'employee_edit')]
    public function edit(Request $r, EntityManagerInterface $em, Employee $employee): Response
    {
        $this->assertEntreprise($employee->entreprise);
        return $this->save($r, $em, $employee, "Modifier l'employé");
    }

    #[Route('/{id}/delete', name: 'employee_delete', methods: ['POST'])]
    public function delete(Request $r, EntityManagerInterface $em, Employee $employee): Response
    {
        $this->assertEntreprise($employee->entreprise);
        if ($this->isCsrfTokenValid('del'.$employee->id, $r->request->get('_token'))) {
            $em->remove($employee);
            $em->flush();
            $this->addFlash('success', 'Employé supprimé.');
        }
        return $this->redirectToRoute('employee_index');
    }

    private function save(Request $r, EntityManagerInterface $em, Employee $e, string $title): Response
    {
        $form = $this->createFormBuilder($e)
            ->add('matricule', TextType::class, ['label' => 'Matricule'])
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('gender', ChoiceType::class, ['label' => 'Genre', 'choices' => ['Homme' => 'M', 'Femme' => 'F']])
            ->add('email', EmailType::class, ['label' => 'E-mail'])
            ->add('phone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('city', TextType::class, ['label' => 'Ville', 'required' => false])
            ->add('position', TextType::class, ['label' => 'Poste'])
            ->add('department', EntityType::class, [
                'label' => 'Département',
                'class' => Department::class,
                'placeholder' => '— Choisir —',
                'required' => false,
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('d')
                        ->andWhere('d.entreprise = :entreprise')
                        ->setParameter('entreprise', $this->getEntreprise())
                        ->orderBy('d.name', 'ASC');
                },
            ])
            ->add('hireDate', DateType::class, ['label' => "Date d'embauche", 'widget' => 'single_text', 'input' => 'datetime'])
            ->add('contractType', ChoiceType::class, ['label' => 'Type de contrat', 'choices' => array_combine(['CDI', 'CDD', 'Stage', 'Freelance'], ['CDI', 'CDD', 'Stage', 'Freelance'])])
            ->add('status', ChoiceType::class, ['label' => 'Statut', 'choices' => ['Actif' => 'Actif', 'En congé' => 'En congé']])
            ->add('baseSalary', NumberType::class, ['label' => 'Salaire de base (€)'])
            ->getForm()->handleRequest($r);
        return $this->persist($form, $em, $e, $title, 'employee_index');
    }

    private function getEntreprise(): Entreprise
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getEntreprise()) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à ce compte RH.');
        }
        return $user->getEntreprise();
    }

    private function assertEntreprise(?Entreprise $entreprise): void
    {
        if (!$entreprise || $entreprise->getId() !== $this->getEntreprise()->getId()) {
            throw $this->createNotFoundException();
        }
    }

    private function persist(FormInterface $form, EntityManagerInterface $em, object $o, string $title, string $back): Response
    {
        if ($form->isSubmitted() && $form->isValid()) {
            if ($o instanceof Employee) {
                $o->entreprise = $this->getEntreprise();
            }
            $em->persist($o);
            $em->flush();
            $this->addFlash('success', 'Enregistré avec succès.');
            return $this->redirectToRoute($back);
        }
        return $this->render('crud/form.html.twig', ['form' => $form, 'title' => $title, 'back' => $back]);
    }
}
