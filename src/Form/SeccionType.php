<?php

namespace App\Form;

use App\Entity\Seccion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeccionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'attr' => ['class' => 'form-control']
            ])
            ->add('orden', IntegerType::class, [
                'label' => 'Orden',
                'attr' => ['class' => 'form-control']
            ])
            ->add('icono', TextType::class, [
                'label' => 'Icono',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ejemplo: bi-cart'
                ],
                'help' => 'Nombre del ícono de Bootstrap Icons (sin el prefijo bi-)'
            ])
            ->add('habilitado', CheckboxType::class, [
                'label' => 'Habilitado',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seccion::class,
        ]);
    }
} 