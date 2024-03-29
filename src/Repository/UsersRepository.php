<?php

namespace App\Repository;

use App\Entity\Clients;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Users>
 *
 * @method Users|null find($id, $lockMode = null, $lockVersion = null)
 * @method Users|null findOneBy(array $criteria, array $orderBy = null)
 * @method Users[]    findAll()
 * @method Users[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsersRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Users::class);
    }

    public function save(Users $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Users $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Users) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    /**
     * Méthode pour paginer les resultats des utilisateurs
     *
     * @param  int $page
     * @param  int $limit
     * 
     * @return array
     */
    public function findAllPaginated(int $page, int $limit)
    {
        $qb = $this->createQueryBuilder('b')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

            return $qb->getQuery()->getResult();
    }

    /**
     * Méthode pour paginer les resultats des utilisateurs liés à un client
     *
     * @param  int $page
     * @param  int $limit
     * @param Clients $client
     * 
     * @return array
     */
    public function findAllPaginatedwithClient(int $page, int $limit, Clients $client)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.client = :val')
            ->setParameter('val', $client)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

            return $qb->getQuery()->getResult();
    }

    /**
     * Méthode pour récupérer les informations d'un utilisateurs d'un client
     *
     * @param Users $user
     * @param Clients $client
     * 
     * @return null|Users
     */
    public function findUserWithClient(Users $user, Clients $client)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.id = :value')
            ->andWhere('u.client = :val')
            ->setParameter('value', $user)
            ->setParameter('val', $client);

            return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Users[] Returns an array of Users objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Users
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
