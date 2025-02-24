<?php

namespace App\Form;

use App\Entity\Subrubro;
use App\Entity\Rubro;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubrubroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true
                ],
                'disabled' => true
                
            ])
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control']
            ])
            ->add('rubro', EntityType::class, [
                'class' => Rubro::class,
                'choice_label' => 'nombre',
                'label' => 'Rubro',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control']
            ])

            ->add('habilitado', CheckboxType::class, [
                'label' => 'Habilitado',
                'label_attr' => ['class' => 'form-check-label'],
                'row_attr' => ['class' => 'form-check'],
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subrubro::class,
        ]);
    }
} 