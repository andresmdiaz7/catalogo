<?php

namespace App\Command;

use App\Entity\Pedido;
use App\Service\EmailService;
use App\Repository\PedidoRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-pedido-email',
    description: 'Envía correos de prueba para confirmación y notificación de pedidos',
)]
class TestPedidoEmailCommand extends Command
{
    public function __construct(
        private EmailService $emailService,
        private PedidoRepository $pedidoRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('pedido_id', InputArgument::OPTIONAL, 'ID del pedido a usar para la prueba (si no se especifica, usa el último pedido)')
            ->setHelp('Este comando permite probar el envío de emails de pedidos usando un pedido existente.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $pedidoId = $input->getArgument('pedido_id');
        
        // Obtener el pedido
        if ($pedidoId) {
            $pedido = $this->pedidoRepository->find($pedidoId);
            if (!$pedido) {
                $io->error("No se encontró el pedido con ID: {$pedidoId}");
                return Command::FAILURE;
            }
        } else {
            // Usar el último pedido
            $pedido = $this->pedidoRepository->findOneBy([], ['id' => 'DESC']);
            if (!$pedido) {
                $io->error('No hay pedidos en la base de datos. Crea un pedido primero.');
                return Command::FAILURE;
            }
        }

        $io->title("Probando emails para el Pedido #{$pedido->getId()}");
        
        $cliente = $pedido->getCliente();
        $estado = $pedido->getEstado();
        $io->text([
            "Cliente: {$cliente->getRazonSocial()}",
            "Email: {$cliente->getEmail()}",
            "Total: \${$pedido->getTotal()}",
            "Estado: " . ($estado ? $estado->value : 'N/A'),
        ]);

        try {
            // Probar email de confirmación al cliente
            $io->section('Enviando email de confirmación al cliente...');
            $this->emailService->sendPedidoConfirmation($pedido);
            $io->success("✅ Email de confirmación enviado a: {$cliente->getEmail()}");

            // Probar email de notificación (vendedor y logística)
            $io->section('Enviando notificaciones...');
            $this->emailService->sendPedidoNotification($pedido);
            
            $vendedor = $cliente->getVendedor();
            $responsableLogistica = $cliente->getResponsableLogistica();
            
            if ($vendedor && $vendedor->getEmail()) {
                $io->success("✅ Notificación enviada al vendedor: {$vendedor->getEmail()}");
            } else {
                $io->warning("⚠️  No hay vendedor asignado o no tiene email");
            }
            
            if ($responsableLogistica && $responsableLogistica->getEmail()) {
                $io->success("✅ Notificación enviada al responsable de logística: {$responsableLogistica->getEmail()}");
            } else {
                $io->warning("⚠️  No hay responsable de logística asignado o no tiene email");
            }

            $io->success('🎉 Todos los emails fueron enviados correctamente');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("❌ Error al enviar emails: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
} 