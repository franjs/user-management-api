<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class LoadUserData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setPassword('admin');
        $userAdmin->setEmail('admin@test.com');
        $userAdmin->setName('User Admin');
        $userAdmin->addRole('ROLE_ADMIN');

        $manager->persist($userAdmin);
        $manager->flush();
    }
}
