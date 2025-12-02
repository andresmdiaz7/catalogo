<?php

namespace App\Form;

use App\Entity\Cliente;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientePerfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3'],
                'required' => true
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
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3'],
                'required' => true
            ])
            ->add('web', TextType::class, [
                'label' => 'Sitio Web',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.ejemplo.com'
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('rentabilidad', NumberType::class, [
                'label' => 'Rentabilidad (%)',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100',
                    'placeholder' => '0.00'
                ],
                'row_attr' => ['class' => 'mb-3'],
                'help' => 'Ingrese el porcentaje de rentabilidad (ej: 15.50 para 15.5%)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cliente::class,
        ]);
    }
} 