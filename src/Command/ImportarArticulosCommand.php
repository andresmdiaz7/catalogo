<?php

namespace App\Command;

use App\Entity\Articulo;
use App\Entity\Marca;
use App\Entity\Rubro;
use App\Entity\Subrubro;
use App\Repository\Mssql\ArticuloMssqlRepository;
use App\Service\EmailService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use League\Csv\CharsetConverter;
use League\Csv\Reader;
use League\Csv\Statement;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:importar-articulos',
    description: 'Importa o actualiza artículos desde MSSQL',
)]

class ImportarArticulosCommand extends Command
{
    private ManagerRegistry $doctrine;
    private string $projectDir;
    private int $cantidadPorLote = 2500;
    private array $rubrosCache = [];
    private array $subrubrosCache = [];
    private array $marcasCache = []; // Caché para marcas
    private ArticuloMssqlRepository $articuloMssqlRepository;
    private array $articulosExistentesCache = []; // Caché de artículos existentes por código
    private EmailService $emailService;
    private ParameterBagInterface $params;
    private bool $mostrarLog = false;
    private LoggerInterface $logger;
    private LoggerInterface $importLogger;

    public function __construct(
        ManagerRegistry $doctrine, 
        string $projectDir, 
        ArticuloMssqlRepository $articuloMssqlRepository,
        EmailService $emailService,
        ParameterBagInterface $params,
        LoggerInterface $logger,
        LoggerInterface $importLogger
    )
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->projectDir = $projectDir;
        $this->articuloMssqlRepository = $articuloMssqlRepository;
        $this->emailService = $emailService;
        $this->params = $params;
        $this->logger = $logger;
        $this->importLogger = $importLogger;
    }

    protected function configure(): void
    {
        $this
            ->addOption('solo-nuevos', null, InputOption::VALUE_NONE, 'No actualizar artículos existentes, solo crear nuevos')
            ->addOption('solo-codigo', 'c', InputOption::VALUE_REQUIRED, 'Procesar solo un artículo específico por código')
            ->addOption('mostrarlog', null, InputOption::VALUE_NONE, 'Mostrar mensajes de log por consola');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $soloNuevos = $input->getOption('solo-nuevos');
        $soloCodigo = $input->getOption('solo-codigo');
        $this->mostrarLog = $input->getOption('mostrarlog');
        
        // Variables para estadísticas
        $tiempoInicio = microtime(true);
        $articulosNuevos = 0;
        $articulosActualizados = 0;
        $errores = 0;

        try {
            // 1. Precarga de datos existentes
            $this->precargarDatos($io);

            // Obtener hashes actuales de la base de datos MySQL
            $hashesExistentes = $this->obtenerHashesArticulosExistentes();
            
            // Obtener solo artículos modificados desde MSSQL
            $articulosModificados = $this->articuloMssqlRepository->getArticulosModificados($hashesExistentes);
            
            if ($this->mostrarLog) {
                $io->info($this->formatearMensajeLog(sprintf('Se encontraron %d artículos que requieren actualización', count($articulosModificados))));
            }
            
            if (empty($articulosModificados)) {
                if ($this->mostrarLog) {
                    $io->info($this->formatearMensajeLog('No hay artículos que requieran actualización. Finalizando.'));
                }
                
                // Enviar correo con el resumen aunque no haya artículos para procesar (ni nuevos ni modificados)
                $tiempoFin = microtime(true);
                $tiempoTotal = round($tiempoFin - $tiempoInicio, 2);
                
                $this->enviarCorreoResumen([
                    'nuevos' => 0,
                    'actualizados' => 0,
                    'errores' => 0,
                    'tiempo' => $tiempoTotal,
                    'total' => 0,
                    'mensaje' => 'No se encontraron artículos nuevos ni existentes que requieran procesamiento'
                ]);
                
                return Command::SUCCESS;
            } else {
                // Solo procesar los artículos que necesitan actualización
                $resultados = $this->procesarArticulosSeleccionados($io, $articulosModificados, $soloNuevos);
            }
            
            $articulosNuevos = $resultados['nuevos'];
            $articulosActualizados = $resultados['actualizados'];
            $errores = $resultados['errores'];
            
            // Calcular tiempo transcurrido
            $tiempoFin = microtime(true);
            $tiempoTotal = round($tiempoFin - $tiempoInicio, 2);
            
            // Mensaje de éxito
            $mensajeExito = sprintf(
                'Importación completada en %s segundos. Nuevos: %d, Actualizados: %d, Errores: %d, Total: %d artículos', 
                $tiempoTotal,
                $articulosNuevos, 
                $articulosActualizados,
                $errores,
                $articulosNuevos + $articulosActualizados
            );
            
            if ($this->mostrarLog) {
                $io->success($this->formatearMensajeLog($mensajeExito));
            }
            
            // Enviar correo con el resumen
            $this->enviarCorreoResumen([
                'nuevos' => $articulosNuevos,
                'actualizados' => $articulosActualizados,
                'errores' => $errores,
                'tiempo' => $tiempoTotal,
                'total' => $articulosNuevos + $articulosActualizados
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $mensajeError = 'Error durante la importación: ' . $e->getMessage();
            
            if ($this->mostrarLog) {
                $io->error($this->formatearMensajeLog($mensajeError));
            }
            
            // Enviar correo de error
            $this->enviarCorreoResumen([
                'nuevos' => $articulosNuevos,
                'actualizados' => $articulosActualizados,
                'errores' => $errores + 1,
                'tiempo' => round(microtime(true) - $tiempoInicio, 2),
                'total' => $articulosNuevos + $articulosActualizados,
                'error' => $e->getMessage()
            ]);
            
            return Command::FAILURE;
        }
    }

    private function precargarDatos(SymfonyStyle $io): void
    {
        $em = $this->doctrine->getManager();
        
        // Cargar todos los rubros existentes
        $rubros = $em->getRepository(Rubro::class)->findAll();
        foreach ($rubros as $rubro) {
            $this->rubrosCache[$rubro->getCodigo()] = $rubro;
        }
        
        // Cargar todos los subrubros existentes
        $subrubros = $em->getRepository(Subrubro::class)->findAll();
        foreach ($subrubros as $subrubro) {
            $this->subrubrosCache[$subrubro->getCodigo()] = $subrubro;
        }
        
        // Cargar todas las marcas existentes
        $marcas = $em->getRepository(Marca::class)->findAll();
        foreach ($marcas as $marca) {
            $this->marcasCache[$marca->getCodigo()] = $marca;
        }

        if ($this->mostrarLog) {
            $io->info($this->formatearMensajeLog(sprintf('Precargados %d rubros, %d subrubros y %d marcas', 
                count($this->rubrosCache), 
                count($this->subrubrosCache),
                count($this->marcasCache)
            )));
        }
    }

    private function procesarArticulosMssql(SymfonyStyle $io, bool $soloNuevos = false, ?string $soloCodigo = null): array
    {
        if ($this->mostrarLog) {
            $io->info($this->formatearMensajeLog('Obteniendo artículos desde MSSQL...'));
        }
        
        // Obtener todos los artículos desde MSSQL
        $articulosMssql = $this->articuloMssqlRepository->getAllArticulos();
        
        if ($soloCodigo) {
            $articulosMssql = array_filter($articulosMssql, function($a) use ($soloCodigo) {
                return $a['codigo'] === $soloCodigo;
            });
            
            if (empty($articulosMssql)) {
                if ($this->mostrarLog) {
                    $io->warning($this->formatearMensajeLog("No se encontró el artículo con código: $soloCodigo"));
                }
                return ['nuevos' => 0, 'actualizados' => 0, 'errores' => 0];
            }
        }
        
        if ($this->mostrarLog) {
            $io->info($this->formatearMensajeLog(sprintf('Se encontraron %d artículos en MSSQL', count($articulosMssql))));
        }
        
        $lote = [];
        $contador = 0;
        $numLote = 1;
        $articulosNuevos = 0;
        $articulosActualizados = 0;
        $errores = 0;
        
        foreach ($articulosMssql as $articuloData) {
            try {
                $resultado = $this->procesarArticulo($articuloData, $soloNuevos);
                
                if ($resultado) {
                    $lote[] = $resultado['articulo'];
                    $contador++;
                    
                    if ($resultado['accion'] === 'nuevo') {
                        $articulosNuevos++;
                    } else {
                        $articulosActualizados++;
                    }
                    
                    if (count($lote) >= $this->cantidadPorLote) {
                        if ($this->mostrarLog) {
                            $io->info($this->formatearMensajeLog(sprintf('Procesando lote %d (%d artículos)', $numLote, count($lote))));
                        }
                        $this->guardarLote($lote, $io);
                        $lote = [];
                        $numLote++;
                        // Liberar memoria
                        $this->articulosExistentesCache = [];
                        gc_collect_cycles();
                    }
                }
            } catch (\Exception $e) {
                $errores++;
                if ($this->mostrarLog) {
                    $io->error($this->formatearMensajeLog(sprintf('Error en artículo %s: %s', $articuloData['codigo'] ?? 'desconocido', $e->getMessage())));
                }
            }
        }

        if (!empty($lote)) {
            if ($this->mostrarLog) {
                $io->info($this->formatearMensajeLog(sprintf('Procesando lote final %d (%d artículos)', $numLote, count($lote))));
            }
            $this->guardarLote($lote, $io);
        }

        return [
            'nuevos' => $articulosNuevos, 
            'actualizados' => $articulosActualizados,
            'errores' => $errores
        ];
    }

    private function procesarArticulo(array $articuloData, bool $soloNuevos = false): ?array
    {
        $codigo = trim((string)($articuloData['codigo'] ?? ''));
        
        // Verificar si el artículo ya existe
        $articuloExistente = $this->buscarArticuloExistente($codigo);
        
        // Si solo estamos creando nuevos y este existe, omitirlo
        if ($soloNuevos && $articuloExistente) {
            return null;
        }
        
        // Si el artículo existe y tiene hash de sincronización, verificar si es necesario actualizar
        if ($articuloExistente && method_exists($articuloExistente, 'getHashSinc')) {
            $hashNuevo = ArticuloMssqlRepository::generarHashArticulo($articuloData);
            
            // Si el hash coincide, no es necesario actualizar
            if ($articuloExistente->getHashSinc() === $hashNuevo) {
                return null;
            }
            
            // Actualizar el artículo existente
            $this->actualizarArticuloExistente($articuloExistente, $articuloData);
            return [
                'articulo' => $articuloExistente,
                'accion' => 'actualizado'
            ];
        } elseif ($articuloExistente) {
            // Si no tiene hash, de todos modos actualizarlo
            $this->actualizarArticuloExistente($articuloExistente, $articuloData);
            return [
                'articulo' => $articuloExistente,
                'accion' => 'actualizado'
            ];
        }
        
        // Crear nuevo artículo
        $articulo = $this->crearArticuloDesdeData($articuloData);
        return [
            'articulo' => $articulo,
            'accion' => 'nuevo'
        ];
    }

    private function buscarArticuloExistente(string $codigo): ?Articulo
    {
        // Si ya lo tenemos en caché, devolverlo
        if (isset($this->articulosExistentesCache[$codigo])) {
            return $this->articulosExistentesCache[$codigo];
        }
        
        // Si no, buscarlo en la base de datos
        $em = $this->doctrine->getManager();
        $articulo = $em->getRepository(Articulo::class)->findOneBy(['codigo' => $codigo]);
        
        // Guardar en caché para futuras referencias
        if ($articulo) {
            $this->articulosExistentesCache[$codigo] = $articulo;
        }
        
        return $articulo;
    }

    private function actualizarArticuloExistente(Articulo $articulo, array $articuloData): void
    {
        // Actualizar rubro y subrubro si es necesario
        $rubroCodigo = $articuloData['codigo_rubro'];
        $subrubroCodigo = $articuloData['codigo_subrubro'];
        
        // Actualizar subrubro y su relación con rubro
        if ($articulo->getSubrubro()->getCodigo() !== $subrubroCodigo || 
            $articulo->getSubrubro()->getRubro()->getCodigo() !== $rubroCodigo) {
            
            // Garantizar que tenemos el rubro correcto
            if (!isset($this->rubrosCache[$rubroCodigo])) {
                $rubro = new Rubro();
                $rubro->setCodigo($rubroCodigo);
                $rubro->setNombre($articuloData['rubro']);
                $this->rubrosCache[$rubroCodigo] = $rubro;
            }
            
            // Garantizar que tenemos el subrubro correcto
            if (!isset($this->subrubrosCache[$subrubroCodigo])) {
                $subrubro = new Subrubro();
                $subrubro->setCodigo($subrubroCodigo);
                $subrubro->setNombre($articuloData['subrubro']);
                $subrubro->setRubro($this->rubrosCache[$rubroCodigo]);
                $this->subrubrosCache[$subrubroCodigo] = $subrubro;
            }
            
            $articulo->setSubrubro($this->subrubrosCache[$subrubroCodigo]);
        }
        
        // Actualizar marca si es necesario
        $marcaCodigo = $articuloData['codigo_marca'] ?? null;
        $marcaNombre = $articuloData['marca'] ?? null;
        
        if (!empty($marcaCodigo)) {
            $marcaActual = $articulo->getMarca();
            
            // Si no tiene marca o la marca es diferente, actualizarla
            if (!$marcaActual || $marcaActual->getCodigo() !== $marcaCodigo) {
                if (!isset($this->marcasCache[$marcaCodigo])) {
                    $marca = new Marca();
                    $marca->setCodigo($marcaCodigo);
                    $marca->setNombre($marcaNombre ?: $marcaCodigo);
                    $marca->setHabilitado(true);
                    $this->marcasCache[$marcaCodigo] = $marca;
                }
                
                $articulo->setMarca($this->marcasCache[$marcaCodigo]);
            }
        } else if ($articulo->getMarca()) {
            // Si tenía marca pero ya no debe tenerla
            $articulo->setMarca(null);
        }
        
        // Actualizar propiedades del artículo
        $articulo->setDetalle($articuloData['detalle']);
        $articulo->setModelo($articuloData['modelo'] ?? null);
        $articulo->setDetalleWeb($articuloData['detalle_web'] ?? null);
        $articulo->setImpuesto($articuloData['impuesto'] ?? null);
        $articulo->setPrecioLista($articuloData['precio_lista'] ?? 0);
        $articulo->setPrecio400($articuloData['precio_400'] ?? 0);
        $articulo->setHabilitadoGestion($articuloData['estado'] ?? false);
        
        // Actualizar hash de sincronización si corresponde
        if (method_exists($articulo, 'setHashSinc')) {
            $articulo->setHashSinc(ArticuloMssqlRepository::generarHashArticulo($articuloData));
        }
        
        // Actualizar fecha de actualización si corresponde
        if (method_exists($articulo, 'setFechaActualizacion')) {
            $articulo->setFechaActualizacion(new \DateTime());
        }
    }

    private function crearArticuloDesdeData(array $articuloData): Articulo
    {
        $rubroCodigo = $articuloData['codigo_rubro'];
        $subrubroCodigo = $articuloData['codigo_subrubro'];
        $marcaCodigo = $articuloData['codigo_marca'] ?? null;
        $marcaNombre = $articuloData['marca'] ?? null;

        // Obtener o crear rubro
        if (!isset($this->rubrosCache[$rubroCodigo])) {
            $rubro = new Rubro();
            $rubro->setCodigo($rubroCodigo);
            $rubro->setNombre($articuloData['rubro']);
            $this->rubrosCache[$rubroCodigo] = $rubro;
        }

        // Obtener o crear subrubro
        if (!isset($this->subrubrosCache[$subrubroCodigo])) {
            $subrubro = new Subrubro();
            $subrubro->setCodigo($subrubroCodigo);
            $subrubro->setNombre($articuloData['subrubro']);
            $subrubro->setRubro($this->rubrosCache[$rubroCodigo]);
            $this->subrubrosCache[$subrubroCodigo] = $subrubro;
        }
        
        // Obtener o crear marca si existe el código
        $marca = null;
        if (!empty($marcaCodigo)) {
            if (!isset($this->marcasCache[$marcaCodigo])) {
                $marca = new Marca();
                $marca->setCodigo($marcaCodigo);
                $marca->setNombre($marcaNombre ?: $marcaCodigo); // Usar el nombre o el código si no hay nombre
                $marca->setHabilitado(true);
                $this->marcasCache[$marcaCodigo] = $marca;
            } else {
                $marca = $this->marcasCache[$marcaCodigo];
            }
        }

        $articulo = new Articulo();
        $articulo->setCodigo($articuloData['codigo']);
        $articulo->setDetalle($articuloData['detalle']);
        $articulo->setMarca($marca);
        $articulo->setModelo($articuloData['modelo'] ?? null);
        $articulo->setDetalleWeb($articuloData['detalle_web'] ?? null);
        $articulo->setImpuesto($articuloData['impuesto'] ?? null);
        $articulo->setPrecioLista($articuloData['precio_lista'] ?? 0);
        $articulo->setPrecio400($articuloData['precio_400'] ?? 0);
        $articulo->setDestacado(false);
        $articulo->setHabilitadoWeb(false);
        $articulo->setHabilitadoGestion($articuloData['estado'] ?? false);
        $articulo->setNovedad(false);
        $articulo->setSubrubro($this->subrubrosCache[$subrubroCodigo]);

        // Generar hash de sincronización si corresponde
        if (method_exists($articulo, 'setHashSinc')) {
            $articulo->setHashSinc(ArticuloMssqlRepository::generarHashArticulo($articuloData));
        }
        
        // Establecer fecha de creación/actualización si corresponde
        if (method_exists($articulo, 'setFechaCreacion')) {
            $articulo->setFechaCreacion(new \DateTime());
        }
        
        if (method_exists($articulo, 'setFechaActualizacion')) {
            $articulo->setFechaActualizacion(new \DateTime());
        }

        return $articulo;
    }

    private function guardarLote(array $articulos, SymfonyStyle $io): void
    {
        $em = $this->doctrine->getManager();

        try {
            // Iniciar transacción para mejorar rendimiento
            $em->getConnection()->beginTransaction();
            
            // Obtener códigos únicos de rubros, subrubros y marcas del lote actual
            $rubrosCodigos = [];
            $subrubrosCodigos = [];
            $marcasCodigos = [];
            
            foreach ($articulos as $articulo) {
                $rubrosCodigos[] = $articulo->getSubrubro()->getRubro()->getCodigo();
                $subrubrosCodigos[] = $articulo->getSubrubro()->getCodigo();
                if ($articulo->getMarca()) {
                    $marcasCodigos[] = $articulo->getMarca()->getCodigo();
                }
            }
            
            $rubrosCodigos = array_unique($rubrosCodigos);
            $subrubrosCodigos = array_unique($subrubrosCodigos);
            $marcasCodigos = array_unique($marcasCodigos);

            // Verificar rubros existentes
            $rubrosExistentes = $em->createQueryBuilder()
                ->select('r')
                ->from(Rubro::class, 'r')
                ->where('r.codigo IN (:codigos)')
                ->setParameter('codigos', $rubrosCodigos)
                ->getQuery()
                ->getResult();

            // Actualizar caché de rubros con los existentes
            foreach ($rubrosExistentes as $rubro) {
                $this->rubrosCache[$rubro->getCodigo()] = $rubro;
            }

            // Verificar subrubros existentes
            $subrubrosExistentes = $em->createQueryBuilder()
                ->select('s')
                ->from(Subrubro::class, 's')
                ->where('s.codigo IN (:codigos)')
                ->setParameter('codigos', $subrubrosCodigos)
                ->getQuery()
                ->getResult();

            // Actualizar caché de subrubros con los existentes
            foreach ($subrubrosExistentes as $subrubro) {
                $this->subrubrosCache[$subrubro->getCodigo()] = $subrubro;
            }
            
            // Verificar marcas existentes
            if (!empty($marcasCodigos)) {
                $marcasExistentes = $em->createQueryBuilder()
                    ->select('m')
                    ->from(Marca::class, 'm')
                    ->where('m.codigo IN (:codigos)')
                    ->setParameter('codigos', $marcasCodigos)
                    ->getQuery()
                    ->getResult();

                // Actualizar caché de marcas con las existentes
                foreach ($marcasExistentes as $marca) {
                    $this->marcasCache[$marca->getCodigo()] = $marca;
                }
            }

            // Persistir solo los rubros, subrubros y marcas nuevos
            foreach ($articulos as $articulo) {
                $rubro = $articulo->getSubrubro()->getRubro();
                $subrubro = $articulo->getSubrubro();
                $marca = $articulo->getMarca();

                // Solo persistir rubros nuevos
                if (!$em->contains($rubro)) {
                    $em->persist($rubro);
                }

                // Solo persistir subrubros nuevos
                if (!$em->contains($subrubro)) {
                    $em->persist($subrubro);
                }
                
                // Solo persistir marcas nuevas
                if ($marca && !$em->contains($marca)) {
                    $em->persist($marca);
                }

                // El artículo ya está gestionado (nuevo o actualizado)
                if (!$em->contains($articulo)) {
                    $em->persist($articulo);
                }
            }

            // Confirmar transacción
            $em->flush();
            $em->getConnection()->commit();
            
            // Limpiar el EntityManager y recargar las entidades del caché
            $em->clear();
            
            // Recargar las entidades en el caché después del clear
            $this->recargarCache($em);
            
            gc_collect_cycles();

            if ($this->mostrarLog) {
                $io->info($this->formatearMensajeLog(sprintf('Procesados %d artículos', count($articulos))));
            }
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            if ($em->getConnection()->isTransactionActive()) {
                $em->getConnection()->rollBack();
            }
            
            if ($em->isOpen()) {
                $em->close();
            }
            $this->doctrine->resetManager();
            throw $e;
        }
    }

    private function recargarCache(EntityManager $em): void
    {
        // Recargar rubros
        $rubros = $em->createQueryBuilder()
            ->select('r')
            ->from(Rubro::class, 'r')
            ->where('r.codigo IN (:codigos)')
            ->setParameter('codigos', array_keys($this->rubrosCache))
            ->getQuery()
            ->getResult();

        $this->rubrosCache = [];
        foreach ($rubros as $rubro) {
            $this->rubrosCache[$rubro->getCodigo()] = $rubro;
        }

        // Recargar subrubros
        $subrubros = $em->createQueryBuilder()
            ->select('s')
            ->from(Subrubro::class, 's')
            ->where('s.codigo IN (:codigos)')
            ->setParameter('codigos', array_keys($this->subrubrosCache))
            ->getQuery()
            ->getResult();

        $this->subrubrosCache = [];
        foreach ($subrubros as $subrubro) {
            $this->subrubrosCache[$subrubro->getCodigo()] = $subrubro;
        }
        
        // Recargar marcas
        if (!empty($this->marcasCache)) {
            $marcas = $em->createQueryBuilder()
                ->select('m')
                ->from(Marca::class, 'm')
                ->where('m.codigo IN (:codigos)')
                ->setParameter('codigos', array_keys($this->marcasCache))
                ->getQuery()
                ->getResult();

            $this->marcasCache = [];
            foreach ($marcas as $marca) {
                $this->marcasCache[$marca->getCodigo()] = $marca;
            }
        }
    }

    /**
     * Envía un correo electrónico con el resumen de la importación
     */
    private function enviarCorreoResumen(array $estadisticas): void
    {
        $destinatario = $this->params->get('import_email_to');
        $asunto = $this->params->get('import_email_subject');
        
        $email = (new TemplatedEmail())
            ->from($this->emailService->getEmailFrom())
            ->to($destinatario)
            ->subject($asunto)
            ->htmlTemplate('emails/import_resumen.html.twig')
            ->context([
                'estadisticas' => $estadisticas,
                'fecha' => new \DateTime()
            ]);

        try {
            // Crear un registro en el log antes de enviar
            if ($this->mostrarLog) {
                $this->logger->info(sprintf(
                    'Enviando correo de resumen a %s con asunto "%s" (Nuevos: %d, Actualizados: %d, Errores: %d)',
                    $destinatario,
                    $asunto,
                    $estadisticas['nuevos'],
                    $estadisticas['actualizados'],
                    $estadisticas['errores']
                ));
            }
            
            $this->emailService->getMailer()->send($email);
            
            // Registro después del envío exitoso
            if ($this->mostrarLog) {
                $this->logger->info(sprintf('Correo enviado exitosamente a %s', $destinatario));
            }
        } catch (\Exception $e) {
            // Registrar error de envío de correo
            if ($this->mostrarLog) {
                $this->logger->error(sprintf('Error al enviar correo: %s', $e->getMessage()));
            }
        }
    }

    /**
     * Formatea un mensaje de log añadiendo la fecha y hora actual al principio
     * y lo guarda en el archivo de logs (siempre, independientemente de mostrarLog)
     */
    private function formatearMensajeLog(string $mensaje): string
    {
        // Formato para mostrar en consola
        $fechaFormateada = date('d-m-Y H:i:s');
        $mensajeFormateado = $fechaFormateada . ' | ' . $mensaje;
        
        // Guardar en el archivo de logs solo el mensaje sin formato de fecha
        // para evitar la redundancia con el formato de fecha de Monolog
        $this->importLogger->info($mensaje);
        
        return $mensajeFormateado;
    }

    private function obtenerHashesArticulosExistentes(): array
    {
        $em = $this->doctrine->getManager();
        $qb = $em->createQueryBuilder();
        
        return $qb->select('a.codigo, a.hashSinc as hash')
            ->from(Articulo::class, 'a')
            ->where('a.hashSinc IS NOT NULL')
            ->getQuery()
            ->getArrayResult();
    }

    private function procesarArticulosSeleccionados(SymfonyStyle $io, array $articulosModificados, bool $soloNuevos = false): array
    {
        if ($this->mostrarLog) {
            $io->info($this->formatearMensajeLog('Procesando artículos seleccionados...'));
        }
        
        $lote = [];
        $contador = 0;
        $numLote = 1;
        $articulosNuevos = 0;
        $articulosActualizados = 0;
        $errores = 0;
        
        foreach ($articulosModificados as $articuloData) {
            try {
                $resultado = $this->procesarArticulo($articuloData, $soloNuevos);
                
                if ($resultado) {
                    $lote[] = $resultado['articulo'];
                    $contador++;
                    
                    if ($resultado['accion'] === 'nuevo') {
                        $articulosNuevos++;
                    } else {
                        $articulosActualizados++;
                    }
                    
                    if (count($lote) >= $this->cantidadPorLote) {
                        if ($this->mostrarLog) {
                            $io->info($this->formatearMensajeLog(sprintf('Procesando lote %d (%d artículos)', $numLote, count($lote))));
                        }
                        $this->guardarLote($lote, $io);
                        $lote = [];
                        $numLote++;
                        // Liberar memoria
                        $this->articulosExistentesCache = [];
                        gc_collect_cycles();
                    }
                }
            } catch (\Exception $e) {
                $errores++;
                if ($this->mostrarLog) {
                    $io->error($this->formatearMensajeLog(sprintf('Error en artículo %s: %s', $articuloData['codigo'] ?? 'desconocido', $e->getMessage())));
                }
            }
        }

        if (!empty($lote)) {
            if ($this->mostrarLog) {
                $io->info($this->formatearMensajeLog(sprintf('Procesando lote final %d (%d artículos)', $numLote, count($lote))));
            }
            $this->guardarLote($lote, $io);
        }

        return [
            'nuevos' => $articulosNuevos, 
            'actualizados' => $articulosActualizados,
            'errores' => $errores
        ];
    }
}