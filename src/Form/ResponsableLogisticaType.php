<?php

namespace App\Form;

use App\Entity\ResponsableLogistica;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResponsableLogisticaType extends AbstractType
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
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el responsable logístico',
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
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el apellido del responsable logística',
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
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el email del responsable logística',
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])


        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResponsableLogistica::class,
        ]);
    }
} 