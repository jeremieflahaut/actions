<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SecurityControllerTest extends WebTestCase
{

    /**
     * @var AbstractDatabaseTool
     */
    protected $databaseTool;

    protected $client;

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

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function testLoginPageStatusCode()
    {
        $this->client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testDisplayLogin()
    {
        $this->client->request('GET', '/login');

        $this->assertSelectorTextContains('h1', 'Connectes toi');
        $this->assertSelectorNotExists('.alert.alert-danger');
    }

    public function testLoginWithBadCredentials()
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            'username' => 'fakeusername',
            'password' => 'fakepassword'
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Connectes toi.');
        $this->assertSelectorExists('.alert.alert-danger');
    }

    public function testSuccessfullLogin()
    {
        $this->databaseTool->loadFixtures([AppFixtures::class]);

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            'username' => 'Admin',
            'password' => 'password'
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects(
            "/tasks",
            Response::HTTP_FOUND);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testSuccessfullLogout()
    {
        $this->databaseTool->loadFixtures([AppFixtures::class]);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->find(1);

        $this->login($this->client, $user);

        $this->client->request('GET', '/logout');

        // $urlGenerator = static::getContainer()->get(UrlGenerator::class);
        // $url = $urlGenerator->generate('app_login');

        $this->assertResponseRedirects(
            "http://localhost/login",
            Response::HTTP_FOUND);
        $this->client->followRedirect();
    }
}
