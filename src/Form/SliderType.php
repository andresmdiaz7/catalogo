<?php

namespace App\Form;

use App\Entity\Slider;
use App\Entity\Categoria;
use App\Entity\SliderUbicacion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'attr' => ['maxlength' => 100]
            ])
            ->add('urlDestino', UrlType::class, [
                'label' => 'URL de destino',
                'required' => false
            ])
            ->add('activo', CheckboxType::class, [
                'label' => 'Activo',
                'required' => false
            ])
            ->add('categoria', EntityType::class, [
                'class' => Categoria::class,
                'choice_label' => 'nombre',
                'label' => 'Categoría',
                'required' => false,
                'placeholder' => 'Sin categoría'
            ])
            ->add('fechaInicio', DateTimeType::class, [
                'label' => 'Fecha de inicio',
                'widget' => 'single_text'
            ])
            ->add('fechaFin', DateTimeType::class, [
                'label' => 'Fecha de fin',
                'widget' => 'single_text'
            ])
            ->add('orden', IntegerType::class, [
                'label' => 'Orden'
            ])
            ->add('ubicacion', EntityType::class, [
                'class' => SliderUbicacion::class,
                'choice_label' => 'nombre',
                'label' => 'Ubicación',
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.activo = :activo')
                        ->setParameter('activo', true)
                        ->orderBy('u.nombre', 'ASC');
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Slider::class,
        ]);
    }
}
