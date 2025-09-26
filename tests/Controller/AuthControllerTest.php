<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Login');
    }

    public function testRegisterPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Register');
    }

    public function testSuccessfulRegistration(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[username]' => 'testuser',
            'registration_form[email]' => 'test@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        $client->submit($form);
        
        // Should redirect to lobby after successful registration
        $this->assertResponseRedirects('/lobby');
    }

    public function testRegistrationWithInvalidData(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[username]' => '', // Empty username
            'registration_form[email]' => 'invalid-email',
            'registration_form[plainPassword][first]' => '123', // Too short
            'registration_form[plainPassword][second]' => '456', // Mismatch
            'registration_form[agreeTerms]' => false,
        ]);

        $client->submit($form);
        
        // Should stay on registration page with errors
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.form-error');
    }

    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();
        
        // Create a test user
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findByUsernameOrEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->willReturn(true);

        $client->getContainer()->set(UserRepository::class, $userRepository);
        $client->getContainer()->set(UserPasswordHasherInterface::class, $passwordHasher);

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $client->submit($form);
        
        // Should redirect to lobby after successful login
        $this->assertResponseRedirects('/lobby');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $client->submit($form);
        
        // Should stay on login page with error
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testLogout(): void
    {
        $client = static::createClient();
        
        // Create a test user and log them in
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $client->loginUser($user);

        $client->request('GET', '/logout');
        
        // Should redirect to home page
        $this->assertResponseRedirects('/');
    }

    public function testAuthenticatedUserRedirectedFromLogin(): void
    {
        $client = static::createClient();
        
        // Create a test user and log them in
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $client->loginUser($user);

        $client->request('GET', '/login');
        
        // Should redirect to lobby
        $this->assertResponseRedirects('/lobby');
    }

    public function testAuthenticatedUserRedirectedFromRegister(): void
    {
        $client = static::createClient();
        
        // Create a test user and log them in
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $client->loginUser($user);

        $client->request('GET', '/register');
        
        // Should redirect to lobby
        $this->assertResponseRedirects('/lobby');
    }
}
