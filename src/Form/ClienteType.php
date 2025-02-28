<?php

namespace App\Form;

use App\Entity\Cliente;
use App\Entity\Localidad;
use App\Entity\TipoCliente;
use App\Entity\Vendedor;
use App\Entity\ResponsableLogistica;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
                'attr' => ['maxlength' => 50]
            ])
            ->add('razonSocial', TextType::class, [
                'label' => 'Razón Social',
                'attr' => ['class' => 'form-control']
            ])
            ->add('categoriaImpositiva', EntityType::class, [
                'class' => CategoriaImpositiva::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione una categoría impositiva',
                'required' => true,
                'label' => 'Categoría Impositiva',
                'query_builder' => function (CategoriaImpositivaRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.nombre', 'ASC');
                },
            ])
            ->add('cuit', TextType::class, [
                'label' => 'CUIT',
                'attr' => ['class' => 'form-control']
            ])
            ->add('tipoCliente', EntityType::class, [
                'class' => TipoCliente::class,
                'choice_label' => 'nombre',
                'label' => 'Tipo de Cliente'
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('localidad', EntityType::class, [
                'class' => Localidad::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione una localidad',
                'required' => true,
                'query_builder' => function (LocalidadRepository $lr) {
                    return $lr->createQueryBuilder('l')
                        ->orderBy('l.nombre', 'ASC');
                }
            ])
            ->add('categoria', EntityType::class, [
                'class' => Categoria::class,
                'choice_label' => 'nombre',
                'required' => false,
                'placeholder' => 'Seleccione una categoría',
                'label' => 'Categoría',
                'attr' => ['class' => 'form-select'],
                'query_builder' => function (CategoriaRepository $cr) {
                    return $cr->createQueryBuilder('c')
                        ->where('c.activo = :activo')
                        ->setParameter('activo', true)
                        ->orderBy('c.nombre', 'ASC');
                },
            ])
            ->add('telefono', TextType::class, [
                'label' => 'Teléfono',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('web', TextType::class, [
                'label' => 'Sitio Web',
                'required' => false
            ])
            ->add('porcentajeDescuento', NumberType::class, [
                'label' => 'Porcentaje de Descuento',
                'scale' => 2
            ])
            ->add('rentabilidad', NumberType::class, [
                'label' => 'Rentabilidad',
                'scale' => 2
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Contraseña',
                'required' => true,
                'mapped' => false,
            ])
            ->add('observaciones', TextareaType::class, [
                'label' => 'Observaciones',
                'required' => false
            ])
            ->add('vendedor', EntityType::class, [
                'class' => Vendedor::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Ninguno',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('responsableLogistica', EntityType::class, [
                'class' => ResponsableLogistica::class,
                'placeholder' => 'Ninguno',
                'choice_label' => function($responsable) {
                    return $responsable->getApellido() . ', ' . $responsable->getNombre();
                },
                'required' => false,
                'label' => 'Responsable de Logística'
            ])
            ->add('habilitado', CheckboxType::class, [
                'label' => '¿Cliente habilitado?',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cliente::class,
        ]);
    }
}