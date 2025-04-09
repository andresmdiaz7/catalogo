<?php
namespace App\Form;

use App\Entity\SliderUbicacion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SliderUbicacionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'attr' => ['maxlength' => 100]
            ])
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'attr' => ['maxlength' => 50]
            ])
            ->add('anchoMaximo', IntegerType::class, [
                'label' => 'Ancho máximo (px)'
            ])
            ->add('altoMaximo', IntegerType::class, [
                'label' => 'Alto máximo (px)'
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false
            ])
            ->add('activo', CheckboxType::class, [
                'label' => 'Activo',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SliderUbicacion::class,
        ]);
    }
}
