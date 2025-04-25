<?php

namespace App\Repository;

use App\Entity\Usuario;
use App\Entity\TipoUsuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UsuarioRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    /**
     * Actualiza la contraseña de un usuario
     * @param PasswordAuthenticatedUserInterface $user Usuario a actualizar
     * @param string $newHashedPassword Nueva contraseña hasheada
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Usuario) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }


    /**
     * Busca un usuario por email
     * @param string $email Email del usuario a buscar
     * @return Usuario|null Usuario encontrado o null si no existe
     * 
     * TODO: Mejorar la logica de la búsqueda por email
     *       - Ver si esto sirve para armar el padron de usuarios en el panel de administración
     *       - Si no sirve, mejorar la logica para que sirva para el padron de usuarios
     */
    public function findByEmail(string $email): ?Usuario
    {
        return $this->findOneBy(['email' => $email]);
    }


    /**
     * Busca usuarios por tipo de usuario
     * @param TipoUsuario $tipoUsuario Tipo de usuario a buscar
     * @return array Lista de usuarios encontrados
     * 
     * TODO: Mejorar la logica de la búsqueda por tipo de usuario
     *       - Ver si esto sirve para armar el padron de usuarios en el panel de administración
     *       - Si no sirve, mejorar la logica para que sirva para el padron de usuarios
     */
    public function findByTipo(TipoUsuario $tipoUsuario)
    {
        return $this->createQueryBuilder('u')
            ->where('u.tipoUsuario = :tipo')
            ->setParameter('tipo', $tipoUsuario)
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica si un email ya existe en la base de datos
     * @param string $email Email a verificar
     * @param int|null $excludeId ID del usuario a excluir de la búsqueda
     * @return bool True si el email existe, false en caso contrario
     */
    public function existeEmail(string $email, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', $email);
        
        if ($excludeId) {
            $qb->andWhere('u.id != :id')
               ->setParameter('id', $excludeId);
        }
        
        return (bool) $qb->getQuery()->getSingleScalarResult();
    }
} 