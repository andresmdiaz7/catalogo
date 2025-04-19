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

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Usuario) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findByEmail(string $email): ?Usuario
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByTipo(TipoUsuario $tipoUsuario)
    {
        return $this->createQueryBuilder('u')
            ->where('u.tipoUsuario = :tipo')
            ->setParameter('tipo', $tipoUsuario)
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

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