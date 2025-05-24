<?php
// src/Controller/Admin/ArticuloArchivoController.php

namespace App\Controller\Admin;

use App\Entity\Articulo;
use App\Entity\Archivo;
use App\Entity\ArticuloArchivo;
use App\Form\ArchivoType;
use App\Repository\ArchivoRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/articulo-archivo')]
#[IsGranted('ROLE_ADMIN')]
class ArticuloArchivoController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private FileUploader $fileUploader;
    private SluggerInterface $slugger;

    public function __construct(
        EntityManagerInterface $entityManager, 
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->fileUploader = $fileUploader;
        $this->slugger = $slugger;
    }

    #[Route('/buscar', name: 'app_admin_articulo_archivo_buscar', methods: ['GET'])]
    public function buscar(Request $request, ArchivoRepository $archivoRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $tipo = $request->query->get('tipo', '');
        $fechaDesde = $request->query->get('fechaDesde', '');
        $fechaHasta = $request->query->get('fechaHasta', '');
        $pagina = $request->query->getInt('pagina', 1);
        $porPagina = 12; // Número de archivos por página
        
        // Obtener archivos paginados
        $resultado = $archivoRepository->buscarPorFiltros($query, $tipo, $fechaDesde, $fechaHasta, $pagina, $porPagina);
        
        $archivos = $resultado['archivos'];
        $totalArchivos = $resultado['total'];
        $totalPaginas = ceil($totalArchivos / $porPagina);
        
        $resultados = [];
        foreach ($archivos as $archivo) {
            $resultados[] = [
                'id' => $archivo->getId(),
                'fileName' => $archivo->getFileName(),
                'filePath' => $archivo->getFilePath(),
                'tipoMime' => $archivo->getTipoMime(),
                'tamanio' => $archivo->getTamanio(),
                'tamanioFormateado' => $archivo->getTamanioFormateado(),
                'esImagen' => $archivo->esImagen()
            ];
        }
        
        return $this->json([
            'archivos' => $resultados,
            'paginacion' => [
                'paginaActual' => $pagina,
                'totalPaginas' => $totalPaginas,
                'totalItems' => $totalArchivos,
                'itemsPorPagina' => $porPagina
            ]
        ]);
    }

    #[Route('/{id}/asociar-archivos', name: 'app_admin_articulo_asociar_archivos', methods: ['POST'])]
    public function asociarArchivos(Request $request, Articulo $articulo): Response
    {
        if (!$this->isCsrfTokenValid('asociar_archivos'.$articulo->getCodigo(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 400);
        }
        
        // Verificar si la petición es AJAX
        /*
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => 'Solicitud no válida'], 400);
        }
        */
        $archivosIds = $request->request->all('archivos');
        
        if (empty($archivosIds)) {
            $this->addFlash('warning', 'No se seleccionó ningún archivo para asociar.');
            return $this->redirectToRoute('app_admin_articulo_edit', ['id' => $articulo->getCodigo()]);
        }
        
        $archivosRepository = $this->entityManager->getRepository(Archivo::class);
        $asociados = 0;
        
        foreach ($archivosIds as $archivoId) {
            $archivo = $archivosRepository->find($archivoId);
            if (!$archivo) continue;
            
            // Verificar si ya existe una relación con este archivo
            $existeRelacion = $this->entityManager->getRepository(ArticuloArchivo::class)->findOneBy([
                'articulo' => $articulo,
                'archivo' => $archivo
            ]);
            
            if (!$existeRelacion) {
                $articuloArchivo = new ArticuloArchivo();
                $articuloArchivo->setArticulo($articulo);
                $articuloArchivo->setArchivo($archivo);
                $articuloArchivo->setEsPrincipal(false); // Nota: cambiado de setPrincipal a setEsPrincipal
                
                $this->entityManager->persist($articuloArchivo);
                $asociados++;
            }
        }
        
        $this->entityManager->flush();
        
        if ($asociados > 0) {
            return $this->json([
                'success' => true, 
                'message' => "Se han asociado {$asociados} archivo(s) al artículo."
            ]);
        } else {
            return $this->json([
                'success' => true, 
                'message' => 'No se asociaron nuevos archivos.'
            ]);
        }
        
        //return $this->redirectToRoute('app_admin_articulo_edit', ['codigo' => $articulo->getCodigo()]);
    }

    #[Route('/{id}/subir-archivos', name: 'app_admin_articulo_archivo_subir_archivos', methods: ['POST'])]
    public function subirArchivos(Request $request, Articulo $articulo): JsonResponse
    {
        if (!$this->isCsrfTokenValid('subir_archivos'.$articulo->getCodigo(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], Response::HTTP_BAD_REQUEST);
        }
        
        $archivos = $request->files->get('archivos');
        if (!$archivos) {
            return $this->json(['success' => false, 'message' => 'No se enviaron archivos'], Response::HTTP_BAD_REQUEST);
        }
        
        $resultados = [];
        $errores = [];
        
        foreach ($archivos as $archivo) {
            try {
                // Verificar que el archivo temporal existe y es legible
                if (!file_exists($archivo->getPathname()) || !is_readable($archivo->getPathname())) {
                    throw new \Exception("El archivo temporal {$archivo->getPathname()} no existe o no es legible");
                }
                
                // Obtener datos del archivo
                $originalFilename = $archivo->getClientOriginalName();
                $mimeType = $archivo->getMimeType();
                $size = $archivo->getSize();
                
                // Calcular hash del archivo antes de subirlo
                $tempHash = md5_file($archivo->getPathname());
                
                // Buscar si ya existe un archivo con el mismo hash
                $archivoExistente = $this->entityManager->getRepository(Archivo::class)->findOneBy(['hash' => $tempHash]);
                
                if ($archivoExistente) {
                    // El archivo ya existe, verificar si ya está asociado al artículo
                    $existeRelacion = $this->entityManager->getRepository(ArticuloArchivo::class)->findOneBy([
                        'articulo' => $articulo,
                        'archivo' => $archivoExistente
                    ]);
                    
                    if ($existeRelacion) {
                        // El archivo ya está asociado, saltamos este archivo
                        $resultados[] = [
                            'id' => $archivoExistente->getId(),
                            'originalFilename' => $archivoExistente->getFileName(),
                            'filePath' => $archivoExistente->getFilePath(),
                            'duplicado' => true,
                            'yaAsociado' => true
                        ];
                        continue;
                    }
                    
                    // El archivo existe pero no está asociado a este artículo, lo asociamos
                    $articuloArchivo = new ArticuloArchivo();
                    $articuloArchivo->setArticulo($articulo);
                    $articuloArchivo->setArchivo($archivoExistente);
                    $articuloArchivo->setEsPrincipal(false);
                    
                    $this->entityManager->persist($articuloArchivo);
                    
                    $resultados[] = [
                        'id' => $archivoExistente->getId(),
                        'originalFilename' => $archivoExistente->getFileName(),
                        'filePath' => $archivoExistente->getFilePath(),
                        'duplicado' => true,
                        'yaAsociado' => false
                    ];
                } else {
                    // El archivo no existe, lo subimos normalmente
                    try {
                        $filename = $this->fileUploader->upload($archivo);
                    
                        // Crear nueva entidad Archivo
                        $nuevaEntidad = new Archivo();
                        $nuevaEntidad->setFileName($originalFilename);
                        $nuevaEntidad->setFilePath($filename);
                        $nuevaEntidad->setTipoMime($mimeType);
                        $nuevaEntidad->setTamanio($size);
                        $nuevaEntidad->setHash($tempHash); // Usamos el hash calculado previamente
                        
                        // Si hemos agregado el campo fechaCreacion, lo establecemos
                        if (method_exists($nuevaEntidad, 'setFechaCreacion')) {
                            $nuevaEntidad->setFechaCreacion(new \DateTime());
                        }
                        
                        $this->entityManager->persist($nuevaEntidad);
                        
                        // Crear relación artículo-archivo
                        $articuloArchivo = new ArticuloArchivo();
                        $articuloArchivo->setArticulo($articulo);
                        $articuloArchivo->setArchivo($nuevaEntidad);
                        $articuloArchivo->setEsPrincipal(false);
                        
                        $this->entityManager->persist($articuloArchivo);
                        
                        $resultados[] = [
                            'id' => 'temp_' . count($resultados),
                            'originalFilename' => $originalFilename,
                            'filePath' => $filename,
                            'duplicado' => false
                        ];
                    } catch (\Exception $e) {
                        $errores[] = "Error al procesar '{$originalFilename}': " . $e->getMessage();
                    }
                }
            } catch (\Exception $e) {
                $errores[] = "Error con el archivo: " . $e->getMessage();
            }
        }
        
        if (count($resultados) > 0) {
            // Guardar los cambios en la base de datos
            $this->entityManager->flush();
        }
        
        if (count($errores) > 0) {
            // Si hay algunos errores pero también algunos éxitos
            if (count($resultados) > 0) {
                return $this->json([
                    'success' => true,
                    'warning' => true,
                    'message' => 'Algunos archivos se subieron correctamente, pero hubo errores',
                    'errores' => $errores,
                    'resultados' => $resultados
                ]);
            } else {
                // Si todos fallaron
                return $this->json([
                    'success' => false,
                    'message' => implode("\n", $errores)
                ]);
            }
        }
        
        return $this->json([
            'success' => true,
            'message' => 'Archivos procesados correctamente',
            'resultados' => $resultados
        ]);
    }

    #[Route('/{id}/desasociar', name: 'app_admin_articulo_archivo_desasociar', methods: ['POST'])]
    public function desasociar(Request $request, ArticuloArchivo $articuloArchivo): JsonResponse
    {
        // Verificar si la petición es AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => 'Solicitud no válida'], 400);
        }
        
        try {
            $articulo = $articuloArchivo->getArticulo();
            $articuloId = $articulo->getCodigo();
            $eraPrincipal = $articuloArchivo->IsEsPrincipal();
            
            // Eliminar la relación
            $this->entityManager->remove($articuloArchivo);
            $this->entityManager->flush();
            $mensaje = 'Archivo desasociado correctamente.';
            // Si el archivo desasociado era el principal, buscar otro para establecer como principal
            if ($eraPrincipal) {
                        
                // Buscar otras imágenes asociadas a este artículo
                $otrasImagenes = $this->entityManager->createQuery(
                    'SELECT aa FROM App\Entity\ArticuloArchivo aa
                    JOIN aa.archivo a
                    WHERE aa.articulo = :articulo
                    AND a.tipoMime LIKE :tipo
                    ORDER BY aa.id DESC'
                )
                ->setParameters([
                    'articulo' => $articulo,
                    'tipo' => 'image/%'
                ])
                ->setMaxResults(1)
                ->getResult();
                
                if (count($otrasImagenes) > 0) {
                    $nuevaPrincipal = $otrasImagenes[0];
                    $nuevaPrincipal->setEsPrincipal(true);
                    $this->entityManager->flush();
                    
                    $mensaje .= ' Se ha establecido automáticamente otra imagen como principal.';
                } else {
                    $mensaje .= ' Atención: el artículo ya no tiene una imagen principal.';
                } 
                return $this->json([
                    'success' => true, 
                    'message' => $mensaje,
                    'asignadaNuevaPrincipal' => count($otrasImagenes) > 0,
                    'idImagenPrincipal' => $nuevaPrincipal->getId()
                ]);
            }
            return $this->json([
                'success' => true, 
                'message' => $mensaje,
                'idImagenPrincipal' => null
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Error al desasociar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/establecer-principal', name: 'app_admin_articulo_archivo_principal', methods: ['POST'])]
    public function establecerPrincipal(Request $request, int $id): JsonResponse
    {
        $articuloArchivoRepository = $this->entityManager->getRepository(ArticuloArchivo::class);
        $articuloArchivo = $articuloArchivoRepository->find($id);
        
        if (!$articuloArchivo) {
            return $this->json([
                'success' => false,
                'message' => 'No se encontró la relación archivo-artículo con ID ' . $id
            ], 404);
        }
        
        try {
            $articulo = $articuloArchivo->getArticulo();
            
            // Primero desactivamos todos los archivos principales para este artículo
            $archivosArticulo = $articuloArchivoRepository->findBy([
                'articulo' => $articulo,
                'esPrincipal' => true
            ]);
            
            foreach ($archivosArticulo as $aa) {
                $aa->setEsPrincipal(false);
            }
            
            // Luego establecemos el nuevo archivo principal
            $articuloArchivo->setEsPrincipal(true);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'Archivo establecido como principal correctamente',
                'idImagenPrincipal' => $articuloArchivo->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Error al establecer el archivo como principal: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'app_admin_articulo_archivo_eliminar', methods: ['DELETE'])]
    public function eliminar(Request $request, ArticuloArchivo $articuloArchivo): JsonResponse
    {
        if (!$this->isCsrfTokenValid('delete'.$articuloArchivo->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 400);
        }
        
        try {
            $this->entityManager->remove($articuloArchivo);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'Relación eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Error al eliminar la relación: ' . $e->getMessage()
            ], 500);
        }
    }
}
