<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    protected $faker;
    protected $hasher;
    protected $users = [];

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager)
    {
        //1 admin
        $admin = new User();

        $admin->setUsername('Admin')
            ->setEmail("administrateur@gmail.com")
            ->setPassword($this->hasher->hashPassword($admin, 'password'))
            ->setRoles(['ROLE_ADMIN']);

        $manager->persist($admin);

        // 3 users
        for($i = 0; $i < 3; $i++) {
            $user = new User();

            $user->setUsername("username$i")
                ->setEmail("username$i@gmail.com")
                ->setPassword($this->hasher->hashPassword($user, 'password'))
                ->setRoles(['ROLE_USER']);

            $manager->persist($user);

            $this->users[] = $user;
        }

        // 10 tasks
        for($i = 1; $i <= 10; $i++) {
            $task = new Task;

            $task->setCreatedAt(new DateTimeImmutable())
                ->setUpdatedAt(new DateTimeImmutable())
                ->setTitle("Tâche $i")
                ->setContent("Contenu de la tâche $i")
                ->toggle(mt_rand(0, 1))
                ->setUser($this->faker->randomElement($this->users));

            $manager->persist($task);
        }

        $manager->flush();
    }
}
