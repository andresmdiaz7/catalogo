<?php
namespace App\Form;

use App\Entity\SliderArchivo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SliderArchivoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('archivo', FileType::class, [
                'label' => 'Imagen',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Por favor sube una imagen válida (JPG, PNG, GIF, WEBP)',
                    ])
                ],
            ])
            ->add('archivoMobile', FileType::class, [
                'label' => 'Imagen para móvil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Por favor sube una imagen válida (JPG, PNG, GIF, WEBP)',
                    ])
                ],
            ])
            ->add('orden', IntegerType::class, [
                'label' => 'Orden'
            ])
            ->add('urlDestino', UrlType::class, [
                'label' => 'URL de destino',
                'required' => false
            ])
            ->add('textoAlternativo', TextType::class, [
                'label' => 'Texto alternativo',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SliderArchivo::class,
        ]);
    }
}
