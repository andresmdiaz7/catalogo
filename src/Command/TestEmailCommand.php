<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email',
    description: 'Envía un correo electrónico de prueba',
)]
class TestEmailCommand extends Command
{
    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Enviando correo de prueba...');
        
        try {
            $email = (new Email())
                ->from('andres.diaz@ciardi.com.ar')
                ->to('andresmdiaz7@gmail.com') // Cambia esto a tu dirección
                ->subject('Correo de prueba desde Symfony')
                ->text('Este es un correo de prueba enviado desde la aplicación Symfony.')
                ->html('<p>Este es un correo de <b>prueba</b> enviado desde la aplicación Symfony.</p>');

            $this->mailer->send($email);
            
            $output->writeln('<info>¡Correo enviado correctamente!</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error al enviar el correo: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}