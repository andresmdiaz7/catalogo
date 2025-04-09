<?php
namespace App\Controller\Admin;

use App\Entity\Slider;
use App\Entity\SliderArchivo;
use App\Form\SliderArchivoType;
use App\Repository\SliderArchivoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/admin/slider-archivo')]
#[IsGranted('ROLE_ADMIN')]
class SliderArchivoController extends AbstractController
{
    private string $projectDir;

    public function __construct(
        ParameterBagInterface $parameterBag
    ) {
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    #[Route('/slider/{id}/archivos', name: 'app_admin_slider_archivo_index', methods: ['GET'])]
    public function index(Slider $slider, SliderArchivoRepository $sliderArchivoRepository): Response
    {
        return $this->render('admin/slider_archivo/index.html.twig', [
            'slider' => $slider,
            'archivos' => $sliderArchivoRepository->findArchivosPorSlider($slider->getId()),
        ]);
    }

    #[Route('/slider/{id}/new', name: 'app_admin_slider_archivo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Slider $slider, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $sliderArchivo = new SliderArchivo();
        $sliderArchivo->setSlider($slider);
        $form = $this->createForm(SliderArchivoType::class, $sliderArchivo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $archivoFile = $form->get('archivo')->getData();
            $archivoMobileFile = $form->get('archivoMobile')->getData();
            
            // Procesar archivo principal (obligatorio)
            if ($archivoFile) {
                try {
                    // Capturar datos antes de mover el archivo
                    $originalFilename = pathinfo($archivoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $extension = $archivoFile->guessExtension() ?: 'bin';
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;
                    $mimeType = $archivoFile->getMimeType() ?: 'application/octet-stream';
                    $fileSize = $archivoFile->getSize();
                    
                    // Mover el archivo
                    $archivoFile->move(
                        $this->getParameter('slider_directory'),
                        $newFilename
                    );
                    
                    // Guardar metadatos en la entidad
                    $sliderArchivo->setFileName($archivoFile->getClientOriginalName());
                    $sliderArchivo->setFilePath($newFilename);
                    $sliderArchivo->setTipoMime($mimeType);
                    $sliderArchivo->setFileSize($fileSize);
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al subir el archivo principal: '.$e->getMessage());
                    return $this->redirectToRoute('app_admin_slider_archivo_index', ['id' => $slider->getId()]);
                }
            }

            // Procesar archivo móvil (opcional)
            if ($archivoMobileFile) {
                try {
                    // Capturar datos antes de mover el archivo
                    $originalFilenameMobile = pathinfo($archivoMobileFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilenameMobile = $slugger->slug($originalFilenameMobile);
                    $extensionMobile = $archivoMobileFile->guessExtension() ?: 'bin';
                    $newFilenameMobile = $safeFilenameMobile.'-'.uniqid().'.'.$extensionMobile;
                    
                    // Mover el archivo
                    $archivoMobileFile->move(
                        $this->getParameter('slider_directory'),
                        $newFilenameMobile
                    );
                    
                    // Guardar ruta en la entidad
                    $sliderArchivo->setFilePathMobile($newFilenameMobile);
                    
                } catch (\Exception $e) {
                    // Si ya se ha subido el archivo principal, mantenerlo
                    $this->addFlash('warning', 'Error al subir el archivo móvil: '.$e->getMessage() . 
                          '. Se guardará el slider con la imagen principal solamente.');
                    // No hacer return para permitir que se guarde al menos con la imagen principal
                }
            }

            // Guardar entidad
            try {
                $entityManager->persist($sliderArchivo);
                $entityManager->flush();
                $this->addFlash('success', 'Archivo agregado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al guardar en la base de datos: '.$e->getMessage());
            }
            
            return $this->redirectToRoute('app_admin_slider_archivo_index', ['id' => $slider->getId()]);
        }

        return $this->render('admin/slider_archivo/new.html.twig', [
            'slider' => $slider,
            'slider_archivo' => $sliderArchivo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_slider_archivo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SliderArchivo $sliderArchivo, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(SliderArchivoType::class, $sliderArchivo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $archivoFile = $form->get('archivo')->getData();
            $archivoMobileFile = $form->get('archivoMobile')->getData();

            if ($archivoFile) {
                $originalFilename = pathinfo($archivoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$archivoFile->guessExtension();

                //$absolutePath = $this->projectDir . $this->getParameter('slider_directory');
                try {
                    $archivoFile->move(
                        $this->getParameter('slider_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error al subir el archivo: '.$e->getMessage());
                    return $this->redirectToRoute('app_admin_slider_archivo_index', ['id' => $sliderArchivo->getSlider()->getId()]);
                }

                // Eliminar archivo anterior si existe
                if ($sliderArchivo->getFilePath()) {
                    $oldFilePath = $this->getParameter('slider_directory').'/'.$sliderArchivo->getFilePath();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $sliderArchivo->setFileName($archivoFile->getClientOriginalName());
                $sliderArchivo->setFilePath($newFilename);
                $sliderArchivo->setTipoMime($archivoFile->getMimeType());
                $sliderArchivo->setFileSize($archivoFile->getSize());
            }

            if ($archivoMobileFile) {
                $originalFilename = pathinfo($archivoMobileFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$archivoMobileFile->guessExtension();

                //$absolutePath = $this->projectDir . $this->getParameter('slider_directory');
                try {
                    $archivoMobileFile->move(
                        $this->getParameter('slider_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error al subir el archivo móvil: '.$e->getMessage());
                    return $this->redirectToRoute('app_admin_slider_archivo_index', ['id' => $sliderArchivo->getSlider()->getId()]);
                }

                // Eliminar archivo móvil anterior si existe
                if ($sliderArchivo->getFilePathMobile()) {
                    $oldFilePath = $this->getParameter('slider_directory').'/'.$sliderArchivo->getFilePathMobile();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $sliderArchivo->setFilePathMobile($newFilename);
            }

            //$entityManager->flush();

            $this->addFlash('success', 'Archivo actualizado correctamente.');
            return $this->redirectToRoute('app_admin_slider_archivo_index', ['id' => $sliderArchivo->getSlider()->getId()]);
        }

        return $this->render('admin/slider_archivo/edit.html.twig', [
            'slider_archivo' => $sliderArchivo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_slider_archivo_delete', methods: ['POST'])]
    public function delete(Request $request, SliderArchivo $sliderArchivo, EntityManagerInterface $entityManager): Response
    {
        $sliderId = $sliderArchivo->getSlider()->getId();

        if ($this->isCsrfTokenValid('delete'.$sliderArchivo->getId(), $request->request->get('_token'))) {
            // Eliminar archivos físicos
            if ($sliderArchivo->getFilePath()) {
                $filePath = $this->getParameter('slider_directory').'/'.$sliderArchivo->getFilePath();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            if ($sliderArchivo->getFilePathMobile()) {
                $filePath = $this->getParameter('slider_directory').'/'.$sliderArchivo->getFilePathMobile();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $entityManager->remove($sliderArchivo);
            $entityManager->flush();
            $this->addFlash('success', 'Archivo eliminado correctamente.');
        }

        return $this->redirectToRoute('app_admin_slider_archivo_index', ['id' => $sliderId]);
    }
}
