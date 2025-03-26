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
                $extension = $archivo->getClientOriginalExtension();
                $mimeType = $archivo->getMimeType();
                $size = $archivo->getSize();
                
                // Crear un nombre seguro para el archivo
                $safeFilename = $this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME));
                $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;
                
                // Subir el archivo usando el FileUploader service
                try {
                    $filename = $this->fileUploader->upload($archivo);
                    
                    // Crear nueva entidad Archivo
                    $nuevaEntidad = new Archivo();
                    $nuevaEntidad->setFileName($originalFilename);
                    $nuevaEntidad->setFilePath($filename);
                    $nuevaEntidad->setTipoMime($mimeType);
                    $nuevaEntidad->setTamanio($size);
                    
                    // Calcular hash del archivo para evitar duplicados
                    $hash = md5_file($this->getParameter('kernel.project_dir').'/public/'.$this->fileUploader->getTargetDirectory() . $filename);
                    $nuevaEntidad->setHash($hash);
                    
                    $this->entityManager->persist($nuevaEntidad);
                    
                    // Crear relación artículo-archivo
                    $articuloArchivo = new ArticuloArchivo();
                    $articuloArchivo->setArticulo($articulo);
                    $articuloArchivo->setArchivo($nuevaEntidad);
                    $articuloArchivo->setEsPrincipal(false); // Por defecto no es principal
                    
                    $this->entityManager->persist($articuloArchivo);
                    
                    $resultados[] = [
                        'id' => 'temp_' . count($resultados),
                        'originalFilename' => $originalFilename,
                        'newFilename' => $filename,
                    ];
                } catch (\Exception $e) {
                    $errores[] = "Error al procesar '{$originalFilename}': " . $e->getMessage();
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
            'message' => 'Archivos subidos correctamente',
            'resultados' => $resultados
        ]);
    }

    #[Route('/{id}/desasociar', name: 'app_admin_articulo_archivo_desasociar', methods: ['POST'])]
    public function desasociar(Request $request, ArticuloArchivo $articuloArchivo): JsonResponse
    {
        /**
         * Comentado funciona, quedo de antes de otra implementacion
         */
        //if (!$request->isXmlHttpRequest()) {
        //    return $this->json(['success' => false, 'message' => 'Solicitud no válida'], 400);
        //}
        
        try {
            $articuloId = $articuloArchivo->getArticulo()->getCodigo();
            $this->entityManager->remove($articuloArchivo);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'Archivo desasociado correctamente'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Error al desasociar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/establecer-principal', name: 'app_admin_articulo_archivo_principal', methods: ['POST'])]
    public function establecerPrincipal(Request $request, ArticuloArchivo $articuloArchivo): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => 'Solicitud no válida'], 400);
        }
        
        try {
            $articulo = $articuloArchivo->getArticulo();
            
            // Primero desactivamos todos los archivos principales para este artículo
            $archivosArticulo = $this->entityManager->getRepository(ArticuloArchivo::class)->findBy([
                'articulo' => $articulo,
                'esPrincipal' => true // Nota: cambiado de principal a esPrincipal
            ]);
            
            foreach ($archivosArticulo as $aa) {
                $aa->setEsPrincipal(false); // Nota: cambiado de setPrincipal a setEsPrincipal
            }
            
            // Luego establecemos el nuevo archivo principal
            $articuloArchivo->setEsPrincipal(true); // Nota: cambiado de setPrincipal a setEsPrincipal
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => 'Archivo establecido como principal correctamente'
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