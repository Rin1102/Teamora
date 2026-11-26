<?php
namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints as Assert;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $auth, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) { return $this->redirectToRoute('app_dashboard'); }
        return $this->render('security/login.html.twig', [
            'last_username' => $auth->getLastUsername(),
            'error' => $auth->getLastAuthenticationError(),
            'canRegister' => $em->getRepository(User::class)->count([]) === 0,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createFormBuilder()
            ->add('fullName', TextType::class, ['label' => 'Nom complet', 'constraints' => [new Assert\NotBlank()]])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail RH', 'constraints' => [new Assert\NotBlank(), new Assert\Email()]])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [new Assert\Length(min: 8, minMessage: '8 caractères minimum')],
            ])
            ->add('companyName', TextType::class, ['label' => 'Nom de l’entreprise', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyAddress', TextType::class, ['label' => 'Adresse de l’entreprise', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyCity', TextType::class, ['label' => 'Ville', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyPhone', TextType::class, ['label' => 'Téléphone', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyEmail', EmailType::class, ['label' => 'Adresse e-mail de l’entreprise', 'constraints' => [new Assert\NotBlank(), new Assert\Email()]])
            ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $companyRepository = $em->getRepository(Entreprise::class);
            $entreprise = $companyRepository->findOneBy(['name' => trim($d['companyName'])]);

            if ($entreprise && $entreprise->getRh()) {
                $this->addFlash('error', 'Cette entreprise possède déjà un responsable RH.');
                return $this->redirectToRoute('app_register');
            }

            if (!$entreprise) {
                $entreprise = (new Entreprise())
                    ->setName($d['companyName'])
                    ->setAddress($d['companyAddress'])
                    ->setCity($d['companyCity'])
                    ->setPhone($d['companyPhone'])
                    ->setEmail($d['companyEmail']);
            }

            $u = (new User())->setFullName($d['fullName'])->setEmail($d['email']);
            $u->setPassword($hasher->hashPassword($u, $d['password']));
            $u->setEntreprise($entreprise);
            $entreprise->setRh($u);

            $em->persist($entreprise);
            $em->persist($u);
            $em->flush();
            $this->addFlash('success', 'Entreprise et compte RH créés, connectez-vous.');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('security/register.html.twig', ['form' => $form]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never { throw new \LogicException('Handled by the firewall.'); }
}
