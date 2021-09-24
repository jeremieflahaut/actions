<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TaskControllerTest extends WebTestCase
{
    /**
     * @var AbstractDatabaseTool
     */
    protected $databaseTool;

    protected $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function login(KernelBrowser $client, User $user)
    {
        /** @var Session */
        $session = $client->getContainer()->get('session');
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session->set('_security_main', serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }

    public function testTaskList()
    {
        $this->databaseTool->loadFixtures([AppFixtures::class]);

        $this->client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful();
    }

    public function testTaskListLogin()
    {
        $this->databaseTool->loadFixtures([AppFixtures::class]);

        // Retrieve the session of the user with id = 1
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->find(1);
        $this->login($this->client, $user);

        $this->client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateTaskWithConnectedUser()
    {
        $this->databaseTool->loadFixtures([AppFixtures::class]);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->find(1);
        $this->login($this->client, $user);

        $crawler = $this->client->request('GET', '/tasks/create');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => 'Nouvelle tâche',
            'task[content]' => 'Description nouvelle tâche'
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert.alert-success');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findLastTask();
        $this->assertEquals('Nouvelle tâche', $task->getTitle());
        $this->assertEquals('Description nouvelle tâche', $task->getContent());
        $this->assertEquals(1, $task->getUser()->getId());
    }

    public function testCreateTaskWithoutConnectedUser()
    {
        $this->databaseTool->loadFixtures([AppFixtures::class]);

        $crawler = $this->client->request('GET', '/tasks/create');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]' => 'Nouvelle tâche',
            'task[content]' => 'Description nouvelle tâche'
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert.alert-success');

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $task = $taskRepository->findLastTask();
        $this->assertEquals('Nouvelle tâche', $task->getTitle());
        $this->assertEquals('Description nouvelle tâche', $task->getContent());
        $this->assertNull($task->getUser());
    }

    public function testDeleteTask()
    {
        $this->databaseTool->loadFixtures([AppFixtures::class]);

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $lastTask = $taskRepository->findLastTask();
        $id = $lastTask->getId();

        $userRepository = static::getContainer()->get(UserRepository::class);
        if($lastTask->getUser()) {
            $user = $userRepository->find($lastTask->getUser()->getId());
        } else {
            $user = $userRepository->find(1);
        }
        $this->login($this->client, $user);

        $crawler = $this->client->request('GET', '/tasks/' . $lastTask->getId() . '/delete');

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert.alert-success');

        $task = $taskRepository->find($id);
        $this->assertNull($task);
    }
}
