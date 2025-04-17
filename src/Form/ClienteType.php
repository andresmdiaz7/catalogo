<?php

namespace App\Form;

use App\Entity\Cliente;
use App\Entity\Localidad;
use App\Entity\TipoCliente;
use App\Entity\Vendedor;
use App\Entity\ResponsableLogistica;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\LocalidadRepository;
use App\Entity\CategoriaImpositiva;
use App\Repository\CategoriaImpositivaRepository;
use Doctrine\ORM\EntityRepository;
use App\Entity\Categoria;
use App\Repository\CategoriaRepository;

class ClienteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'maxlength' => 50,
                    
                ],
                'attr' => ['class' => 'form-control','readonly' => true],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('razonSocial', TextType::class, [
                'label' => 'Razón Social',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('categoriaImpositiva', EntityType::class, [
                'class' => CategoriaImpositiva::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Categoría impositiva',
                'required' => true,
                'label' => 'Categoría Impositiva',
                'label_attr' => ['class' => 'form-label'],
                'query_builder' => function (CategoriaImpositivaRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('cuit', TextType::class, [
                'label' => 'CUIT',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('tipoCliente', EntityType::class, [
                'class' => TipoCliente::class,
                'choice_label' => 'nombre',
                'label' => 'Tipo de Cliente <i class="bi bi-info-circle-fill text-info" data-bs-toggle="tooltip" data-bs-title="Si el tipo de cliente es Mayorista toma la lista de precios 400"></i>',
                'label_html' => true,
                'label_attr' => ['class' => 'form-label'],
                'required' => true,
                'placeholder' => 'Seleccione un tipo de cliente',
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('localidad', EntityType::class, [
                'class' => Localidad::class,
                'label' => 'Localidad',
                'label_attr' => ['class' => 'form-label'],
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione una localidad',
                'required' => true,
                'query_builder' => function (LocalidadRepository $lr) {
                    return $lr->createQueryBuilder('l')
                        ->orderBy('l.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('categoria', EntityType::class, [
                'class' => Categoria::class,
                'label' => 'Categoría <i class="bi bi-info-circle-fill text-info" data-bs-toggle="tooltip" data-bs-title="La categoría del cliente define el contenido que se mostrará personalizado en menú, banners, productos destacados, etc."></i>',
                'label_html' => true,
                'label_attr' => ['class' => 'form-label'],
                'choice_label' => 'nombre',
                'required' => false,
                'placeholder' => 'Seleccione una categoría',
                'attr' => ['class' => 'form-select'],
                'query_builder' => function (CategoriaRepository $cr) {
                    return $cr->createQueryBuilder('c')
                        ->where('c.activo = :activo')
                        ->setParameter('activo', true)
                        ->orderBy('c.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('telefono', TextType::class, [
                'label' => 'Teléfono',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('web', TextType::class, [
                'label' => 'Sitio Web',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('porcentajeDescuento', NumberType::class, [
                'label' => 'Porcentaje de Descuento',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('rentabilidad', NumberType::class, [
                'label' => 'Rentabilidad',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Nueva Contraseña',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('currentPassword', TextType::class, [
                'label' => 'Contraseña Actual',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'mapped' => false,
                'disabled' => true,
                'data' => 'Contraseña configurada',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('observaciones', TextType::class, [
                'label' => 'Observaciones',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('vendedor', EntityType::class, [
                'label' => 'Vendedor Asignado',
                'label_attr' => ['class' => 'form-label'],
                'class' => Vendedor::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Ninguno',
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('responsableLogistica', EntityType::class, [
                'class' => ResponsableLogistica::class,
                'label' => 'Responsable de Logística',
                'label_attr' => ['class' => 'form-label'],
                'placeholder' => 'Ninguno',
                'choice_label' => function($responsable) {
                    return $responsable->getApellido() . ', ' . $responsable->getNombre();
                },
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('habilitado', CheckboxType::class, [
                'label' => '¿Cliente habilitado?',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cliente::class,
            'current_password' => null,
        ]);
    }
}