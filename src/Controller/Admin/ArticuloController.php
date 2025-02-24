<?php

namespace App\Controller\Admin;

use App\Entity\Articulo;
use App\Form\ArticuloType;
use App\Repository\ArticuloRepository;
use App\Repository\RubroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\ArticuloArchivo;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;


#[Route('/admin/articulos')]
#[IsGranted('ROLE_ADMIN')]
class ArticuloController extends AbstractController
{
    #[Route('/', name: 'app_admin_articulo_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ArticuloRepository $articuloRepository,
        RubroRepository $rubroRepository,
        PaginatorInterface $paginator
    ): Response {
        $filters = $request->query->all();
        
        $queryBuilder = $articuloRepository->createQueryBuilderWithFilters($filters);
        
        $pagination = $paginator->paginate(
            $queryBuilder, // Query builder o query
            $request->query->getInt('page', 1), // Página actual
            10 // Límite por página
        );

        return $this->render('admin/articulo/index.html.twig', [
            'articulos' => $pagination,
            'filtros' => $filters,
            'rubros' => $rubroRepository->findAll()
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_articulo_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $articulo = new Articulo();
        $form = $this->createForm(ArticuloType::class, $articulo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $archivos = $form->get('archivos')->getData();
            
            if ($archivos) {
                foreach ($archivos as $archivo) {
                    $this->procesarImagen($archivo, $articulo, $slugger, $entityManager);
                }
            }

            $entityManager->persist($articulo);
            $entityManager->flush();

            $this->addFlash('success', 'Artículo creado correctamente');
            return $this->redirectToRoute('app_admin_articulo_index');
        }

        return $this->render('admin/articulo/new.html.twig', [
            'articulo' => $articulo,
            'form' => $form,
        ]);
    }

    #[Route('/{codigo}/editar', name: 'app_admin_articulo_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        #[MapEntity(id: 'codigo')] Articulo $articulo, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger

    ): Response {
        $form = $this->createForm(ArticuloType::class, $articulo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $archivos = $form->get('archivos')->getData();
            
            if ($archivos) {
                foreach ($archivos as $archivo) {
                    $this->procesarImagen($archivo, $articulo, $slugger, $entityManager);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Artículo actualizado correctamente');
            return $this->redirectToRoute('app_admin_articulo_edit', ['codigo' => $articulo->getCodigo()]);
        }

        return $this->render('admin/articulo/edit.html.twig', [
            'articulo' => $articulo,
            'form' => $form,
        ]);
    }

    #[Route('/imagen/{id}', name: 'app_admin_articulo_delete_imagen', methods: ['POST'])]
    public function deleteImage(
        ArticuloArchivo $archivo, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$archivo->getId(), $request->request->get('_token'))) {
            // Eliminar archivo físico
            $rutaArchivo = $this->getParameter('kernel.project_dir') . '/public/uploads/articulos/' . $archivo->getNombreArchivo();
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }

            $entityManager->remove($archivo);
            $entityManager->flush();

            $this->addFlash('success', 'Imagen eliminada correctamente');
        }

        return $this->redirectToRoute('app_admin_articulo_edit', [
            'codigo' => $archivo->getArticulo()->getCodigo()
        ]);
    }

    #[Route('/imagen/{id}/principal', name: 'app_admin_articulo_set_imagen_principal', methods: ['POST'])]
    public function setImagenPrincipal(
        ArticuloArchivo $archivo,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('principal'.$archivo->getId(), $request->request->get('_token'))) {
            $articulo = $archivo->getArticulo();
            
            // Quitar principal de todas las imágenes
            foreach ($articulo->getArchivos() as $img) {
                $img->setEsPrincipal(false);
            }
            
            // Establecer la nueva imagen principal
            $archivo->setEsPrincipal(true);
            $entityManager->flush();

            $this->addFlash('success', 'Imagen principal actualizada correctamente');
        }

        return $this->redirectToRoute('app_admin_articulo_edit', [
            'codigo' => $archivo->getArticulo()->getCodigo()
        ]);
    }

    #[Route('/{codigo}/toggle-habilitado', name: 'app_admin_articulo_toggle_habilitado', methods: ['POST'])]
    public function toggleHabilitado(
        Request $request, 
        #[MapEntity(id: 'codigo')] Articulo $articulo, 
        EntityManagerInterface $entityManager

    ): Response {
        if ($this->isCsrfTokenValid('toggle-habilitado'.$articulo->getCodigo(), $request->request->get('_token'))) {
            $articulo->setHabilitadoWeb(!$articulo->isHabilitadoWeb());
            $entityManager->flush();

            $this->addFlash(
                'success',
                'El artículo ha sido ' . ($articulo->isHabilitadoWeb() ? 'habilitado' : 'deshabilitado')
            );
        }

        return $this->redirectToRoute('app_admin_articulo_index');
    }

    private function procesarImagen($imagenOrigen, Articulo $articulo, SluggerInterface $slugger, EntityManagerInterface $entityManager): void
    {
        $originalFilename = pathinfo($imagenOrigen->getClientOriginalName(), PATHINFO_FILENAME);
        $directorioRandom = rand(0, 99);
        $safeFilename = $slugger->slug($originalFilename);
        
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imagenOrigen->guessExtension();
        
        try {
            $imagenOrigen->move(
                $this->getParameter('kernel.project_dir').'/public/'.$this->getParameter('directorio_archivos').$directorioRandom,

                $newFilename
            );

            $archivo = new ArticuloArchivo();
            $archivo->setArticulo($articulo);
            $archivo->setNombreArchivo($imagenOrigen->getClientOriginalName());
            $archivo->setRutaArchivo($directorioRandom.'/'.$newFilename);
            if($imagenOrigen->getClientOriginalExtension() == 'png' || $imagenOrigen->getClientOriginalExtension() == 'jpg' || $imagenOrigen->getClientOriginalExtension() == 'jpeg'){
                $archivo->setTipoArchivo('imagen');
            }else{
                $archivo->setTipoArchivo('documento');
            }
            // Si es la primera imagen, establecerla como principal
            if ($articulo->getArchivos()->isEmpty()) {
                $archivo->setEsPrincipal(true);
            }
            
            $entityManager->persist($archivo);
        } catch (FileException $e) {
            $this->addFlash('error', 'Error al subir la imagen: ' . $e->getMessage());
        }
    }
} 