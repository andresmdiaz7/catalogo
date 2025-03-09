<?php

namespace App\Controller;

use App\Repository\ArticuloRepository;
use App\Repository\SeccionRepository;
use App\Repository\RubroRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ArticuloRepository $articuloRepository,
        SeccionRepository $seccionRepository,
        RubroRepository $rubroRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'secciones' => $seccionRepository->findAll(),
            'rubros' => $rubroRepository->findAll(),
            'articulos' => $articuloRepository->findBy(
                ['habilitadoWeb' => true],
                ['destacado' => 'DESC', 'codigo' => 'ASC'],
                12
            ),
        ]);
    }

    #[Route('/buscar', name: 'app_buscar')]
    public function buscar(Request $request, ArticuloRepository $articuloRepository): Response
    {
        $query = $request->query->get('q');
        $articulos = $articuloRepository->buscar($query);

        return $this->render('home/buscar.html.twig', [
            'articulos' => $articulos,
            'query' => $query
        ]);
    }

    #[Route('/home/test_email', name: 'test_email')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('cables@ciardi.com.ar')
            ->to('andresmdiaz7@gmail.com')
            ->subject('Correo de prueba')
            ->text('Este es un correo de prueba.');

        try {
            $mailer->send($email);
            return new Response('Correo enviado correctamente');
        } catch (\Exception $e) {
            return new Response('Error al enviar el correo: ' . dump($e), 500);
        }
    }
} 