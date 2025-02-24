<?php

namespace App\Form;

use App\Entity\Articulo;
use App\Entity\Subrubro;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

class ArticuloType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'attr' => ['class' => 'form-control']
            ])
            ->add('detalle', TextType::class, [
                'label' => 'Detalle',
                'attr' => ['class' => 'form-control']
            ])
            ->add('marca', TextType::class, [
                'label' => 'Marca',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('modelo', TextType::class, [
                'label' => 'Modelo',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('precioLista', MoneyType::class, [
                'label' => 'Precio de Lista',
                'currency' => 'ARS',
                'attr' => ['class' => 'form-control']
            ])
            ->add('precio400', MoneyType::class, [
                'label' => 'Precio 400',
                'currency' => 'ARS',
                'attr' => ['class' => 'form-control']
            ])
            ->add('impuesto', MoneyType::class, [
                'label' => 'Impuesto',
                'currency' => 'ARS',
                'attr' => ['class' => 'form-control']
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
                'attr' => ['class' => 'form-select']
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
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('archivos', FileType::class, [
                'label' => 'Imágenes o archivos',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '5M', // Tamaño máximo de 5MB
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/jpg',
                                    'image/png',
                                    'application/pdf',
                                ],
                                'mimeTypesMessage' => 'Solo se permiten archivos en formato JPG, JPEG, PNG o PDF.',
                                'maxSizeMessage' => 'El archivo no puede superar los 5MB.',
                            ])
                        ],
                    ]),
                ],
                
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