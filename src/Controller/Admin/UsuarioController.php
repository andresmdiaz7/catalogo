<?php

namespace App\Controller\Admin;

use App\Entity\Usuario;
use App\Form\UsuarioType;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/usuarios')]
#[IsGranted('ROLE_ADMIN')]
class UsuarioController extends AbstractController
{
    #[Route('/', name: 'app_admin_usuario_index', methods: ['GET'])]
    public function index(UsuarioRepository $usuarioRepository): Response
    {
        return $this->render('admin/usuario/index.html.twig', [
            'usuarios' => $usuarioRepository->findAll(),
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_usuario_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UsuarioRepository $usuarioRepository
    ): Response {
        $usuario = new Usuario();
        $form = $this->createForm(UsuarioType::class, $usuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Verificar si el email ya existe antes de persistir
                $emailExistente = $usuarioRepository->findOneBy(['email' => $usuario->getEmail()]);
                if ($emailExistente) {
                    $this->addFlash('danger', 'Este correo electrónico ya está registrado en el sistema.');
                    return $this->render('admin/usuario/new.html.twig', [
                        'usuario' => $usuario,
                        'form' => $form,
                    ]);
                }

                // Hashear la contraseña
                $hashedPassword = $passwordHasher->hashPassword(
                    $usuario,
                    $form->get('plainPassword')->getData()
                );
                $usuario->setPassword($hashedPassword);
                
                // Asignar roles basados en el tipo de usuario
                $this->asignarRolesPorTipoUsuario($usuario);
                
                $entityManager->persist($usuario);
                $entityManager->flush();

                $this->addFlash('success', 'Usuario creado correctamente.');
                return $this->redirectToRoute('app_admin_usuario_index');
            } catch (\Exception $e) {
                // Captura cualquier excepción y muestra un mensaje amigable
                $this->addFlash('error', 'No se pudo crear el usuario. Por favor, verifica que el correo electrónico no esté en uso.');
            }
        }

        return $this->render('admin/usuario/new.html.twig', [
            'usuario' => $usuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_usuario_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Usuario $usuario, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UsuarioRepository $usuarioRepository
    ): Response {
        $tipoUsuarioOriginal = $usuario->getTipoUsuario();
        $emailOriginal = $usuario->getEmail();
        
        $form = $this->createForm(UsuarioType::class, $usuario, [
            'require_password' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Verificar si el email ya existe y no es del usuario actual
                if ($emailOriginal !== $usuario->getEmail()) {
                    $emailExistente = $usuarioRepository->findOneBy(['email' => $usuario->getEmail()]);
                    if ($emailExistente && $emailExistente->getId() !== $usuario->getId()) {
                        $this->addFlash('error', 'Este correo electrónico ya está registrado en el sistema.');
                        return $this->render('admin/usuario/edit.html.twig', [
                            'usuario' => $usuario,
                            'form' => $form,
                        ]);
                    }
                }
                
                // Si se proporcionó una nueva contraseña, hashearla
                if ($plainPassword = $form->get('plainPassword')->getData()) {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $usuario,
                        $plainPassword
                    );
                    $usuario->setPassword($hashedPassword);
                }
                
                // Verificar si el tipo de usuario cambió
                if ($usuario->getTipoUsuario() !== $tipoUsuarioOriginal) {
                    $this->asignarRolesPorTipoUsuario($usuario);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Usuario actualizado correctamente.');
                return $this->redirectToRoute('app_admin_usuario_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo actualizar el usuario. Por favor, verifica que el correo electrónico no esté en uso.');
            }
        }

        return $this->render('admin/usuario/edit.html.twig', [
            'usuario' => $usuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_usuario_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Usuario $usuario, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$usuario->getId(), $request->request->get('_token'))) {
            $entityManager->remove($usuario);
            $entityManager->flush();
            $this->addFlash('success', 'Usuario eliminado correctamente.');
        }

        return $this->redirectToRoute('app_admin_usuario_index');
    }
    
    /**
     * Asigna roles al usuario basados en su tipo
     */
    private function asignarRolesPorTipoUsuario(Usuario $usuario): void
    {
        // Mantener siempre ROLE_USER como base
        $roles = ['ROLE_USER'];
        
        // Agregar roles específicos según tipo
        $tipo = $usuario->getTipoUsuario();
        if ($tipo) {
            switch ($tipo->getCodigo()) {
                case 'admin':
                    $roles[] = 'ROLE_ADMIN';
                    break;
                case 'vendedor':
                    $roles[] = 'ROLE_VENDEDOR';
                    break;
                case 'cliente':
                    $roles[] = 'ROLE_CLIENTE';
                    break;
                case 'responsable_logistica':
                    $roles[] = 'ROLE_LOGISTICA';
                    break;
            }
        }
        
        // Establecer los roles
        $usuario->setRoles($roles);
    }
} 