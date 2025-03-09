<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestController extends AbstractController
{
    /**
     * @Route("/test-email", name="test_email")
     */
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('cables@ciardi.com.ar', 'Remitente')
            ->to('andresmdiaz7@gmail.com', 'Destinatario')
            ->cc('cc@ejemplo.com', 'Copia')
            ->bcc('bcc@ejemplo.com', 'Copia Oculta')
            ->replyTo('respuesta@ejemplo.com', 'Responder a')
            ->subject('Correo de prueba')
            ->text('Este es un correo de prueba en texto plano.')
            ->html('<p>Este es un correo de prueba en <b>HTML</b>.</p>')
            ->attachFromPath('/ruta/al/archivo.pdf', 'archivo.pdf', 'application/pdf');

        try {
            $mailer->send($email);
            return new Response('Correo enviado correctamente');
        } catch (\Exception $e) {
            return new Response('Error al enviar el correo: ' . $e->getMessage(), 500);
        }
    }
}
