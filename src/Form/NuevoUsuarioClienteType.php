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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\EntityManagerInterface;

class NuevoUsuarioClienteType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el email'
                ],
                'constraints' => [
                    new Callback([
                        'callback' => function($value, ExecutionContextInterface $context) {
                            $form = $context->getRoot();
                            $parentForm = $form->getParent();
                            
                            if ($parentForm && $parentForm->get('crearNuevoUsuario')->getData()) {
                                if (!$value) {
                                    $context->buildViolation('Por favor ingrese un email')
                                        ->atPath('email')
                                        ->addViolation();
                                }
                            }
                        }
                    ]),
                    new Email([
                        'message' => 'El email no es válido',
                    ])
                ]
            ])
            ->add('nombreReferencia', TextType::class, [
                'label' => 'Nombre de Referencia',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el nombre de referencia'
                ],
                'constraints' => [
                    new Callback([
                        'callback' => function($value, ExecutionContextInterface $context) {
                            $form = $context->getRoot();
                            $parentForm = $form->getParent();
                            
                            if ($parentForm && $parentForm->get('crearNuevoUsuario')->getData()) {
                                if (!$value) {
                                    $context->buildViolation('Por favor ingrese un nombre de referencia')
                                        ->atPath('nombreReferencia')
                                        ->addViolation();
                                }
                            }
                        }
                    ])
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Contraseña',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese la contraseña'
                ],
                'constraints' => [
                    new Callback([
                        'callback' => function($value, ExecutionContextInterface $context) {
                            $form = $context->getRoot();
                            $parentForm = $form->getParent();
                            
                            if ($parentForm && $parentForm->get('crearNuevoUsuario')->getData()) {
                                if (!$value) {
                                    $context->buildViolation('Por favor ingrese una contraseña')
                                        ->atPath('plainPassword')
                                        ->addViolation();
                                }
                            }
                        }
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
            'validation_groups' => ['Default', 'create']
        ]);
    }
} 