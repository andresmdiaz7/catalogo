<?php

namespace App\Form;

use App\Entity\Usuario;
use App\Entity\TipoUsuario;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'attr' => ['class' => 'form-control'],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'attr' => ['class' => 'form-control'],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Contraseña',
                'mapped' => false,
                'required' => $options['require_password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese una contraseña',
                        'groups' => ['create']
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                        'max' => 4096,
                        'groups' => ['create', 'edit']
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('tipoUsuario', EntityType::class, [
                'class' => TipoUsuario::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione un tipo de usuario',
                'required' => true,
                'label' => 'Tipo de Usuario',
                'attr' => ['class' => 'form-select'],
                'row_attr' => [
                    'class' => 'mb-3'
                ],
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.activo = :activo')
                        ->setParameter('activo', true)
                        ->orderBy('t.nombre', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
            'require_password' => true,
            'validation_groups' => ['Default', 'create']
        ]);
    }
} 