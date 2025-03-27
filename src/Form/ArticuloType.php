<?php

namespace App\Form;

use App\Entity\Articulo;
use App\Entity\Subrubro;
use App\Entity\Marca;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticuloType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'attr' => ['class' => 'form-control', 'disabled' => true]
            ])
            ->add('detalle', TextType::class, [
                'label' => 'Detalle',
                'attr' => ['class' => 'form-control', 'disabled' => true]
            ])
            ->add('marca', EntityType::class, [
                'class' => Marca::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione una marca',
                'required' => false,
                'attr' => ['class' => 'form-select', 'disabled' => true],
                'query_builder' => function (\App\Repository\MarcaRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.nombre', 'ASC');
                },
            ])
            ->add('modelo', TextType::class, [
                'label' => 'Modelo',
                'required' => false,
                'attr' => ['class' => 'form-control', 'disabled' => true]
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('precioLista', MoneyType::class, [
                'label' => 'Precio de Lista',
                'currency' => 'ARS',
                'attr' => ['class' => 'form-control', 'disabled' => true]
            ])
            ->add('precio400', MoneyType::class, [
                'label' => 'Precio 400',
                'currency' => 'ARS',
                'attr' => ['class' => 'form-control', 'disabled' => true]
            ])
            ->add('impuesto', MoneyType::class, [
                'label' => 'Impuesto',
                'currency' => 'ARS',
                'attr' => ['class' => 'form-control', 'disabled' => true]
            ])
            ->add('subrubro', EntityType::class, [
                'class' => Subrubro::class,
                'choice_label' => function(Subrubro $subrubro) {
                    return $subrubro->getRubro()->getNombre() . ' > ' . $subrubro->getNombre();
                },
                'group_by' => function(Subrubro $subrubro) {
                    return $subrubro->getRubro()->getNombre();
                },
                'label' => 'Subrubro',
                'attr' => ['class' => 'form-select', 'disabled' => true]
            ])
            ->add('destacado', CheckboxType::class, [
                'label' => 'Destacado',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('novedad', CheckboxType::class, [
                'label' => 'Novedad',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('habilitadoWeb', CheckboxType::class, [
                'label' => 'Habilitado en Web',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])->add('habilitadoGestion', CheckboxType::class, [
                'label' => 'Habilitado en Gestión',
                'required' => false,
                'attr' => ['class' => 'form-check-input', 'disabled' => true]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Articulo::class,
        ]);
    }
} 