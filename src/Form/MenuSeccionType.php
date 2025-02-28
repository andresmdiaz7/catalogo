<?php

namespace App\Form;

use App\Entity\MenuSeccion;
use App\Entity\Seccion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuSeccionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('seccion', EntityType::class, [
                'class' => Seccion::class,
                'choice_label' => 'nombre',
                'label' => 'Sección',
                'placeholder' => 'Seleccione una sección',
                'required' => true,
            ])
            ->add('orden', NumberType::class, [
                'label' => 'Orden',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuSeccion::class,
        ]);
    }
}