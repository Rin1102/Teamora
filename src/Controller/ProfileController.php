<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $entreprise = $user->getEntreprise();
        if (!$entreprise) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à ce compte RH.');
        }

        $form = $this->createFormBuilder([
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'companyName' => $entreprise->getName(),
            'companyAddress' => $entreprise->getAddress(),
            'companyCity' => $entreprise->getCity(),
            'companyPhone' => $entreprise->getPhone(),
            'companyEmail' => $entreprise->getEmail(),
        ])
            ->add('fullName', TextType::class, ['label' => 'Nom complet', 'constraints' => [new Assert\NotBlank()]])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail RH', 'constraints' => [new Assert\NotBlank(), new Assert\Email()]])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil', 'mapped' => false, 'required' => false,
                'constraints' => [new File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'], mimeTypesMessage: 'Formats acceptés : JPG, PNG ou WEBP.')],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'constraints' => [new Assert\Length(min: 8, minMessage: '8 caractères minimum')],
                ],
                'second_options' => ['label' => 'Confirmer le nouveau mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ])
            ->add('companyName', TextType::class, ['label' => 'Nom de l’entreprise', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyAddress', TextType::class, ['label' => 'Adresse de l’entreprise', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyCity', TextType::class, ['label' => 'Ville', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyPhone', TextType::class, ['label' => 'Téléphone', 'constraints' => [new Assert\NotBlank()]])
            ->add('companyEmail', EmailType::class, ['label' => 'E-mail de l’entreprise', 'constraints' => [new Assert\NotBlank(), new Assert\Email()]])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $request->request->has('save_photo') && $form->isValid()) {
            /** @var UploadedFile|null $photo */
            $photo = $form->get('photo')->getData();

            if (!$photo) {
                $this->addFlash('error', 'Veuillez choisir une nouvelle photo avant de l’enregistrer.');
                return $this->redirectToRoute('app_profile');
            }

            $directory = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            $extension = match ($photo->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => null,
            };

            if (!$extension) {
                $this->addFlash('error', 'Le format de la photo n’est pas accepté. Utilisez JPG, PNG ou WEBP.');
                return $this->redirectToRoute('app_profile');
            }

            $filename = uniqid('rh_', true) . '.' . $extension;
            try {
                $photo->move($directory, $filename);
            } catch (\Throwable $e) {
                $this->addFlash('error', 'La photo n’a pas pu être enregistrée sur le serveur.');
                return $this->redirectToRoute('app_profile');
            }

            if ($oldPhoto = $user->getProfilePhoto()) {
                $oldPath = $directory . '/' . $oldPhoto;
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            $user->setProfilePhoto($filename);
            $em->flush();
            $this->addFlash('success', 'Photo de profil mise à jour.');
            return $this->redirectToRoute('app_profile');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $otherUser = $em->getRepository(User::class)->findOneBy(['email' => trim($data['email'])]);
            if ($otherUser && $otherUser !== $user) {
                $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');
                return $this->redirectToRoute('app_profile');
            }

            $user->setFullName(trim($data['fullName']))
                ->setEmail(trim($data['email']));

            $newPassword = $form->get('password')->getData();
            if ($newPassword) {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
            }

            /** @var UploadedFile|null $photo */
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $directory = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                if (!is_dir($directory)) {
                    mkdir($directory, 0775, true);
                }

                $extension = match ($photo->getMimeType()) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => null,
                };

                if (!$extension) {
                    $this->addFlash('error', 'Le format de la photo n’est pas accepté. Utilisez JPG, PNG ou WEBP.');
                    return $this->redirectToRoute('app_profile');
                }

                $filename = uniqid('rh_', true) . '.' . $extension;
                try {
                    $photo->move($directory, $filename);
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'La photo n’a pas pu être enregistrée sur le serveur.');
                    return $this->redirectToRoute('app_profile');
                }

                if ($oldPhoto = $user->getProfilePhoto()) {
                    $oldPath = $directory . '/' . $oldPhoto;
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $user->setProfilePhoto($filename);
            }

            $entreprise->setName(trim($data['companyName']))
                ->setAddress(trim($data['companyAddress']))
                ->setCity(trim($data['companyCity']))
                ->setPhone(trim($data['companyPhone']))
                ->setEmail(trim($data['companyEmail']));

            $em->flush();
            $this->addFlash('success', 'Profil et informations de l’entreprise mis à jour.');
            return $this->redirectToRoute('app_profile');
        }

        if ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('security/profile.html.twig', [
            'form' => $form,
            'user' => $user,
            'entreprise' => $entreprise,
        ]);
    }
}
