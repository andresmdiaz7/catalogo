<?php

namespace App\Form;

use App\Entity\Articulo;
use App\Entity\PedidoDetalle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PedidoDetalleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('articulo', EntityType::class, [
                'class' => Articulo::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione un artículo',
                'attr' => ['class' => 'form-select']
            ])
            ->add('cantidad', NumberType::class, [
                'label' => 'Cantidad',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0.01,
                    'step' => 0.01
                ]
            ])
            ->add('porcentajeDescuento', NumberType::class, [
                'label' => '% Descuento',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.01
                ]
            ])
        ;

        // Actualizar el precio cuando se selecciona un artículo
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            
            if (isset($data['articulo']) && $data['articulo']) {
                $articulo = $form->get('articulo')->getData();
                if ($articulo) {
                    $data['precioUnitario'] = $articulo->getPrecio();
                    $event->setData($data);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PedidoDetalle::class,
        ]);
    }
} 