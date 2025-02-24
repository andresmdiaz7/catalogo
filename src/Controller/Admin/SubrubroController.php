<?php

namespace App\Controller\Admin;

use App\Entity\Subrubro;
use App\Entity\Rubro;
use App\Form\SubrubroType;
use App\Repository\SubrubroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/admin/subrubros')]
class SubrubroController extends AdminController
{
    public function __construct(
        private PaginatorInterface $paginator
    ) {
    }

    #[Route('/', name: 'app_admin_subrubro_index', methods: ['GET'])]
    public function index(
        Request $request,
        SubrubroRepository $subrubroRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        // Obtener todos los rubros para el filtro
        $rubros = $entityManager->getRepository(Rubro::class)
            ->createQueryBuilder('r')
            ->orderBy('r.nombre', 'ASC')
            ->getQuery()
            ->getResult();

        $queryBuilder = $subrubroRepository->createQueryBuilder('s')
            ->leftJoin('s.rubro', 'r')
            ->addSelect('r');

        // Filtro por rubro
        if ($rubroId = $request->query->get('rubro')) {
            $queryBuilder
                ->andWhere('r.codigo = :rubro')
                ->setParameter('rubro', $rubroId);
        }

        // Filtro por habilitado
        if ($habilitado = $request->query->get('habilitado')) {
            $queryBuilder
                ->andWhere('s.habilitado = :habilitado')
                ->setParameter('habilitado', $habilitado === 'true');
        }

        $queryBuilder
            ->orderBy('r.nombre', 'ASC')
            ->addOrderBy('s.nombre', 'ASC');

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/subrubro/index.html.twig', [
            'pagination' => $pagination,
            'rubros' => $rubros,
            'filtro_rubro' => $rubroId,
            'filtro_habilitado' => $habilitado,
        ]);
    }

    #[Route('/nuevo/{codigo}', name: 'app_admin_subrubro_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        Rubro $rubro
    ): Response {
        $subrubro = new Subrubro();
        $subrubro->setRubro($rubro);
        
        $form = $this->createForm(SubrubroType::class, $subrubro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subrubro);
            $entityManager->flush();

            $this->addSuccessFlash('Subrubro creado correctamente.');
            return $this->redirectToRoute('app_admin_rubro_edit', ['codigo' => $rubro->getCodigo()]);
        }

        return $this->render('admin/subrubro/new.html.twig', [
            'subrubro' => $subrubro,
            'rubro' => $rubro,
            'form' => $form
        ]);
    }

    #[Route('/editar/{codigo}', name: 'app_admin_subrubro_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        #[MapEntity(id: 'codigo')] Subrubro $subrubro, 
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(SubrubroType::class, $subrubro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addSuccessFlash('Subrubro actualizado correctamente.');
            return $this->redirectToRoute('app_admin_subrubro_edit', ['codigo' => $subrubro->getCodigo()]);
        }

        return $this->render('admin/subrubro/edit.html.twig', [
            'subrubro' => $subrubro,
            'rubro' => $subrubro->getRubro(),
            'form' => $form
        ]);
    }

    #[Route('/{codigo}', name: 'app_admin_subrubro_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        #[MapEntity(id: 'codigo')] Subrubro $subrubro, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$subrubro->getCodigo(), $request->request->get('_token'))) {
            try {
                $rubroCodigo = $subrubro->getRubro()->getCodigo();
                $entityManager->remove($subrubro);
                $entityManager->flush();
                $this->addSuccessFlash('Subrubro eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addErrorFlash('No se puede eliminar el subrubro porque tiene artículos asociados.');
            }

            return $this->redirectToRoute('app_admin_subrubro_index');
        }

        return $this->redirectToRoute('app_admin_subrubro_index');
    }
} 