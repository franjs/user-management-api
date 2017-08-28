<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;


/**
 * UserRepository
 */
class UserRepository extends EntityRepository
{
    public function findByGroup(int $groupId)
    {
        $qb = $this->createQueryBuilder('user')
            ->select('user')
            ->where(':groupId MEMBER OF user.groups')
            ->setParameter('groupId', $groupId)
            ->getQuery();

        return $qb->getResult();
    }
}
