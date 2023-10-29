<?php

namespace App\Repository;

use App\Entity\EstadoCuentaUsuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EstadoCuentaUsuario>
 *
 * @method EstadoCuentaUsuario|null find($id, $lockMode = null, $lockVersion = null)
 * @method EstadoCuentaUsuario|null findOneBy(array $criteria, array $orderBy = null)
 * @method EstadoCuentaUsuario[]    findAll()
 * @method EstadoCuentaUsuario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstadoCuentaUsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EstadoCuentaUsuario::class);
    }

//    /**
//     * @return EstadoCuentaUsuario[] Returns an array of EstadoCuentaUsuario objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EstadoCuentaUsuario
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
