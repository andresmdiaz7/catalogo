<?php

namespace App\Service;

use App\Entity\Pedido;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private string $emailFrom;

    public function __construct(
        private MailerInterface $mailer,
        string $emailFrom = 'noreply@tudominio.com'
    ) {
        $this->emailFrom = $emailFrom;
    }

    public function sendPedidoConfirmation(Pedido $pedido): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->emailFrom, 'Tu Empresa'))
            ->to(new Address($pedido->getCliente()->getEmail(), $pedido->getCliente()->getRazonSocial()))
            ->subject('Confirmación de Pedido #' . $pedido->getId())
            ->htmlTemplate('emails/pedido_confirmation.html.twig')
            ->context([
                'pedido' => $pedido
            ]);

        $this->mailer->send($email);
    }

    public function sendPedidoNotification(Pedido $pedido): void
    {
        // Email para el vendedor
        $emailVendedor = (new TemplatedEmail())
            ->from(new Address($this->emailFrom, 'Tu Empresa'))
            ->to($pedido->getCliente()->getVendedor()->getEmail())
            ->subject('Nuevo pedido de ' . $pedido->getCliente()->getRazonSocial())
            ->htmlTemplate('emails/pedido_notification.html.twig')
            ->context([
                'pedido' => $pedido,
                'recipient' => 'vendedor'
            ]);

        // Email para el responsable de logística
        $emailLogistica = (new TemplatedEmail())
            ->from(new Address($this->emailFrom, 'Tu Empresa'))
            ->to($pedido->getCliente()->getResponsableLogistica()->getEmail())
            ->subject('Nuevo pedido de ' . $pedido->getCliente()->getRazonSocial())
            ->htmlTemplate('emails/pedido_notification.html.twig')
            ->context([
                'pedido' => $pedido,
                'recipient' => 'logistica'
            ]);

        $this->mailer->send($emailVendedor);
        $this->mailer->send($emailLogistica);
    }
} 