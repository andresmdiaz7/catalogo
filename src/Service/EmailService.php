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
        string $emailFrom = 'catalogo@ciardi.com.ar'
    ) {
        $this->emailFrom = $emailFrom;
    }

    /**
     * Devuelve la dirección de correo de remitente configurada
     */
    public function getEmailFrom(): string
    {
        return $this->emailFrom;
    }
    
    /**
     * Devuelve el servicio mailer
     */
    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }

    /**
     * Envia un correo electrónico de confirmación de pedido
     * @param Pedido $pedido
     */
    public function sendPedidoConfirmation(Pedido $pedido): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->emailFrom, 'Ciardi Hnos'))
            ->to(new Address($pedido->getCliente()->getEmail(), $pedido->getCliente()->getRazonSocial()))
            ->subject('Confirmación de Pedido #')
            ->htmlTemplate('emails/pedido_confirmation.html.twig')
            ->context([
                'pedido' => $pedido
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envia un correo electrónico de notificación de pedido
     * @param Pedido $pedido
     */
    public function sendPedidoNotification(Pedido $pedido): void
    {
        $cliente = $pedido->getCliente();
        
        // Enviar notificación al vendedor si existe
        if ($cliente->getVendedor() && $cliente->getVendedor()->getEmail()) {
            $emailVendedor = (new TemplatedEmail())
                ->from(new Address($this->emailFrom, 'Ciardi Hnos'))
                ->to($cliente->getVendedor()->getEmail())
                ->subject('Nuevo pedido de ' . $cliente->getRazonSocial())
                ->htmlTemplate('emails/pedido_notification.html.twig')
                ->context([
                    'pedido' => $pedido,
                    'recipient' => 'vendedor'
                ]);

            $this->mailer->send($emailVendedor);
        }

        /**
         * Envia un correo electrónico de notificación de pedido al responsable de logística
         * @param Pedido $pedido
         */
        if ($cliente->getResponsableLogistica() && $cliente->getResponsableLogistica()->getEmail()) {
            $emailLogistica = (new TemplatedEmail())
                ->from(new Address($this->emailFrom, 'Ciardi Hnos'))
                ->to($cliente->getResponsableLogistica()->getEmail())
                ->subject('Nuevo pedido de ' . $cliente->getRazonSocial())
                ->htmlTemplate('emails/pedido_notification.html.twig')
                ->context([
                    'pedido' => $pedido,
                    'recipient' => 'logistica'
                ]);

            $this->mailer->send($emailLogistica);
        }
    }
}