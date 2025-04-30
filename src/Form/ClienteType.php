<?php

namespace App\Form;

use App\Entity\Cliente;
use App\Entity\Localidad;
use App\Entity\TipoCliente;
use App\Entity\Vendedor;
use App\Entity\ResponsableLogistica;
use App\Entity\Usuario;
use App\Entity\Categoria;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\LocalidadRepository;
use App\Entity\CategoriaImpositiva;
use App\Repository\CategoriaImpositivaRepository;
use Doctrine\ORM\EntityRepository;
use App\Repository\CategoriaRepository;
use App\Repository\UsuarioRepository;
use App\Repository\VendedorRepository;
use App\Repository\ResponsableLogisticaRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ClienteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'maxlength' => 50,
                    'readonly' => true,
                    'class' => 'form-control'
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('razonSocial', TextType::class, [
                'label' => 'Razón Social',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('categoriaImpositiva', EntityType::class, [
                'class' => CategoriaImpositiva::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Categoría impositiva',
                'required' => true,
                'label' => 'Categoría Impositiva',
                'label_attr' => ['class' => 'form-label'],
                'query_builder' => function (CategoriaImpositivaRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('cuit', TextType::class, [
                'label' => 'CUIT',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('tipoCliente', EntityType::class, [
                'class' => TipoCliente::class,
                'choice_label' => 'nombre',
                'label' => 'Tipo de Cliente <i class="bi bi-info-circle-fill text-info" data-bs-toggle="tooltip" data-bs-title="Si el tipo de cliente es Mayorista toma la lista de precios 400"></i>',
                'label_html' => true,
                'label_attr' => ['class' => 'form-label'],
                'required' => true,
                'placeholder' => 'Seleccione un tipo de cliente',
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('localidad', EntityType::class, [
                'class' => Localidad::class,
                'label' => 'Localidad',
                'label_attr' => ['class' => 'form-label'],
                'choice_label' => function(Localidad $localidad) {
                        return sprintf('%s - %s ', $localidad->getProvincia()->getNombre(), $localidad->getNombre());
                    },
                'placeholder' => 'Seleccione una localidad',
                'required' => true,
                'query_builder' => function (LocalidadRepository $lr) {
                    return $lr->createQueryBuilder('l')
                        ->leftJoin('l.provincia', 'p')
                        ->addSelect('p')
                        ->orderBy('p.nombre', 'ASC')
                        ->addOrderBy('l.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
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
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('web', TextType::class, [
                'label' => 'Web',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('porcentajeDescuento', NumberType::class, [
                'label' => 'Porcentaje de Descuento',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('rentabilidad', NumberType::class, [
                'label' => 'Rentabilidad',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('observaciones', TextareaType::class, [
                'label' => 'Observaciones',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('vendedor', EntityType::class, [
                'class' => Vendedor::class,
                'choice_label' => 'nombre',
                'label' => 'Vendedor',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'placeholder' => 'Seleccione un vendedor',
                'query_builder' => function (VendedorRepository $er) {
                    return $er->createQueryBuilder('v')
                        ->orderBy('v.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('responsableLogistica', EntityType::class, [
                'class' => ResponsableLogistica::class,
                'choice_label' => 'nombre',
                'label' => 'Responsable de Logística',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'placeholder' => 'Seleccione un responsable',
                'query_builder' => function (ResponsableLogisticaRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->orderBy('r.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('categoria', EntityType::class, [
                'class' => Categoria::class,
                'choice_label' => 'nombre',
                'label' => 'Categoría',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'placeholder' => 'Seleccione una categoría',
                'query_builder' => function (CategoriaRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.nombre', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('habilitado', CheckboxType::class, [
                'label' => 'Habilitado',
                'label_attr' => ['class' => 'form-check-label'],
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'mb-3 form-check']
            ])
            ->add('habilitadoCuentaCorriente', CheckboxType::class, [
                'label' => 'Habilitado',
                'label_attr' => ['class' => 'form-check-label'],
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'mb-3 form-check'],
                'help' => 'Permite al cliente ver su cuenta corriente en el sistema'
            ]);

        // Solo agregar campos de usuario si estamos creando un nuevo cliente
        if ($options['is_new']) {
            $builder
                ->add('crearNuevoUsuario', CheckboxType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Crear nuevo usuario',
                    'attr' => [
                        'class' => 'form-check-input',
                        'data-bs-toggle' => 'collapse',
                        'data-bs-target' => '#nuevoUsuarioCollapse'
                    ]
                ])
                ->add('usuario', EntityType::class, [
                    'class' => Usuario::class,
                    'choice_label' => function(Usuario $usuario) {
                        return sprintf('%s - %s (%s)', $usuario->getId(), $usuario->getNombreReferencia(), $usuario->getEmail());
                    },
                    'required' => false,
                    'placeholder' => 'Seleccione un usuario existente',
                    'query_builder' => function (UsuarioRepository $er) {
                        return $er->createQueryBuilder('u')
                            ->innerJoin('u.tipoUsuario', 't')
                            ->where('t.codigo = :tipo')
                            ->setParameter('tipo', 'cliente')
                            ->orderBy('u.email', 'ASC');
                    },
                    'attr' => [
                        'class' => 'form-select'
                    ],
                    'constraints' => [
                        new Callback([
                            'callback' => function($value, ExecutionContextInterface $context) {
                                $form = $context->getRoot();
                                $crearNuevoUsuario = $form->get('crearNuevoUsuario')->getData();
                                
                                if (!$crearNuevoUsuario && !$value) {
                                    $context->buildViolation('Debe seleccionar un usuario existente o crear uno nuevo')
                                        ->atPath('usuario')
                                        ->addViolation();
                                }
                            }
                        ])
                    ]
                ])
                ->add('nuevoUsuario', UsuarioType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => false,
                    'attr' => [
                        'class' => 'collapse',
                        'id' => 'nuevoUsuarioCollapse'
                    ],
                    'constraints' => [
                        new Callback([
                            'callback' => function($value, ExecutionContextInterface $context) {
                                $form = $context->getRoot();
                                $crearNuevoUsuario = $form->get('crearNuevoUsuario')->getData();
                                
                                if ($crearNuevoUsuario) {
                                    if (!$value) {
                                        $context->buildViolation('Debe completar los datos del nuevo usuario')
                                            ->atPath('nuevoUsuario')
                                            ->addViolation();
                                    } else {
                                        if (!$value->getEmail()) {
                                            $context->buildViolation('El email es requerido')
                                                ->atPath('nuevoUsuario.email')
                                                ->addViolation();
                                        }
                                        if (!$value->getNombreReferencia()) {
                                            $context->buildViolation('El nombre de referencia es requerido')
                                                ->atPath('nuevoUsuario.nombreReferencia')
                                                ->addViolation();
                                        }
                                        if (!$form->get('nuevoUsuario')->get('plainPassword')->getData()) {
                                            $context->buildViolation('La contraseña es requerida')
                                                ->atPath('nuevoUsuario.plainPassword')
                                                ->addViolation();
                                        }
                                    }
                                }
                            }
                        ])
                    ]
                ]);

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                // Si se selecciona un usuario existente, limpiar los datos del nuevo usuario
                if (isset($data['usuario']) && $data['usuario']) {
                    $data['crearNuevoUsuario'] = false;
                    $data['nuevoUsuario'] = null;
                    $event->setData($data);
                }
            });
        } else {
            // Para edición, solo mostrar el selector de usuarios existentes
            $builder->add('usuario', EntityType::class, [
                'class' => Usuario::class,
                'choice_label' => function(Usuario $usuario) {
                    return sprintf('%s (%s)', $usuario->getNombreReferencia(), $usuario->getEmail());
                },
                'required' => true,
                'placeholder' => 'Seleccione un usuario',
                'query_builder' => function (UsuarioRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('u')
                        ->innerJoin('u.tipoUsuario', 't')
                        ->where('t.codigo = :tipo')
                        ->setParameter('tipo', 'cliente');

                    // Si hay un usuario actual, incluirlo en la consulta
                    if (isset($options['data']) && $options['data']->getUsuario()) {
                        $qb->orWhere('u.id = :currentUserId')
                           ->setParameter('currentUserId', $options['data']->getUsuario()->getId());
                    }

                    return $qb->orderBy('u.email', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cliente::class,
            'is_new' => false,
        ]);
    }
}