<?php

namespace App\Twig;

use App\Entity\Cliente;
use App\Repository\SliderRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SliderExtension extends AbstractExtension
{
    private $sliderRepository;
    private $security;

    public function __construct(SliderRepository $sliderRepository, Security $security)
    {
        $this->sliderRepository = $sliderRepository;
        $this->security = $security;
    }

    /**
     * Enlista las funciones que se pueden usar en las vistas y la función que se ejecuta
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_sliders_por_ubicacion', [$this, 'getSlidersPorUbicacion']),
        ];
    }

    /**
     * Obtiene los sliders activos para una ubicación específica
     * @param string $ubicacionCodigo El código de la ubicación
     * @return Slider[] Los sliders activos para la ubicación
     */
    public function getSlidersPorUbicacion(string $ubicacionCodigo)
    {
        $user = $this->security->getUser();
        $cliente = null;
        
        // Solo pasar el cliente si el usuario es un Cliente
        if ($user instanceof Cliente) {
            $cliente = $user;
        }
        
        // Busca los sliders activos para la ubicación y el cliente si existe
        return $this->sliderRepository->findSlidersPorUbicacion($ubicacionCodigo, $cliente);
    }
}
