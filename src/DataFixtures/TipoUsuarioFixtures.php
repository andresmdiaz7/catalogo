<?php
// src/DataFixtures/TipoUsuarioFixtures.php

namespace App\DataFixtures;

use App\Entity\TipoUsuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TipoUsuarioFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $tipos = [
            ['codigo' => 'admin', 'nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['codigo' => 'cliente', 'nombre' => 'Cliente', 'descripcion' => 'Acceso al área de clientes'],
            ['codigo' => 'vendedor', 'nombre' => 'Vendedor', 'descripcion' => 'Acceso al área de vendedores'],
            ['codigo' => 'responsable_logistica', 'nombre' => 'Responsable de Logística', 'descripcion' => 'Acceso al área de logística'],
        ];

        foreach ($tipos as $tipoData) {
            $tipo = new TipoUsuario();
            $tipo->setCodigo($tipoData['codigo']);
            $tipo->setNombre($tipoData['nombre']);
            $tipo->setDescripcion($tipoData['descripcion']);
            $tipo->setActivo(true);

            $manager->persist($tipo);
            $this->addReference('tipo_usuario_' . $tipoData['codigo'], $tipo);
        }

        $manager->flush();
    }
}