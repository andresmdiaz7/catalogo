<?php

namespace App\Form;

use App\Entity\Cliente;
use App\Entity\Pedido;
use App\Entity\EstadoPedido;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class PedidoFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cliente', EntityType::class, [
                'class' => Cliente::class,
                'choice_label' => 'razonSocial',
                'required' => false,
                'placeholder' => 'Todos los clientes',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function ($er) use ($options) {
                    return $er->createQueryBuilder('c')
                        ->where('c.vendedor = :vendedor')
                        ->setParameter('vendedor', $options['vendedor'])
                        ->orderBy('c.razonSocial', 'ASC');
                },
            ])
            ->add('nombreCliente', TextType::class, [
                'required' => false,
                'label' => 'Nombre/Razón Social',
                'attr' => [
                    'placeholder' => 'Buscar por nombre de cliente',
                    'class' => 'form-control'
                ]
            ])
            ->add('estado', EnumType::class, [
                'class' => EstadoPedido::class,
                'choice_label' => fn(EstadoPedido $estado) => $estado->getLabel(),
                'choice_attr' => fn(EstadoPedido $estado) => [
                    'data-description' => $estado->getDescription(),
                    'data-badge-class' => $estado->getBadgeClass(),
                    'data-icon' => $estado->getIcon()
                ],
                'required' => false,
                'placeholder' => 'Todos los estados',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('fechaDesde', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'label' => 'Desde'
            ])
            ->add('fechaHasta', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'label' => 'Hasta'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'method' => 'GET',
            'csrf_protection' => false,
            'vendedor' => null,
        ]);
    }
}
