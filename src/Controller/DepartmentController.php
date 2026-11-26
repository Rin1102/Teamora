<?php
namespace App\Controller;

use App\Entity\{Department, Entreprise, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/departments')]
class DepartmentController extends AbstractController
{
    #[Route('', name: 'department_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $departments = $em->getRepository(Department::class)->findBy(
            ['entreprise' => $this->getEntreprise()], ['name' => 'ASC']
        );
        return $this->render('department/index.html.twig', ['departments' => $departments]);
    }

    #[Route('/new', name: 'department_new')]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $department = (new Department())->setEntreprise($this->getEntreprise());
        return $this->save($r, $em, $department, 'Ajouter un département');
    }

    #[Route('/{id}/edit', name: 'department_edit')]
    public function edit(Request $r, EntityManagerInterface $em, Department $department): Response
    {
        $this->assertEntreprise($department->entreprise);
        return $this->save($r, $em, $department, 'Modifier le département');
    }

    #[Route('/{id}/delete', name: 'department_delete', methods: ['POST'])]
    public function delete(Request $r, EntityManagerInterface $em, Department $department): Response
    {
        $this->assertEntreprise($department->entreprise);
        if ($this->isCsrfTokenValid('del'.$department->id, $r->request->get('_token'))) {
            foreach ($department->employees as $e) { $e->department = null; }
            $em->remove($department);
            $em->flush();
        }
        return $this->redirectToRoute('department_index');
    }

    private function save(Request $r, EntityManagerInterface $em, Department $d, string $title): Response
    {
        $form = $this->createFormBuilder($d)
            ->add('name', TextType::class, ['label' => 'Nom du département'])
            ->add('icon', TextType::class, ['label' => 'Icône (emoji)', 'help' => 'Ex : 💻 📣 🏦 🎨 🏭 🚚'])
            ->getForm()->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d->entreprise = $this->getEntreprise();
            $em->persist($d);
            $em->flush();
            return $this->redirectToRoute('department_index');
        }
        return $this->render('crud/form.html.twig', ['form' => $form, 'title' => $title, 'back' => 'department_index']);
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
}
