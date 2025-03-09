<?php

namespace App\Form;

use App\Entity\Vendedor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VendedorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]

            ])
            ->add('telefono', TextType::class, [
                'label' => 'Teléfono',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('activo', CheckboxType::class, [
                'label' => 'Activo',
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',

                ],
                'row_attr' => [
                    'class' => 'form-check mb-3'
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vendedor::class,
        ]);
    }
} 