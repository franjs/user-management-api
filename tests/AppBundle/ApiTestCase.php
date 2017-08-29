<?php

namespace Tests\AppBundle;

use AppBundle\Entity\Group;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DomCrawler\Crawler;


class ApiTestCase extends KernelTestCase
{
    /**
     * @var ClientInterface
     */
    private static $staticClient;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Middleware that pushes history data to an ArrayAccess container.
     */
    private static $staticHistory;

    /**
     * @var array
     */
    private static $staticContainer;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var ResponseAsserter
     */
    private $responseAsserter;

    /**
     * @var ORMExecutor
     */
    private $fixtureExecutor;

    /**
     * @var ContainerAwareLoader
     */
    private $fixtureLoader;

    /**
     * HandlerStack
     */
    private static $handler;

    public static function setUpBeforeClass()
    {
        self::$handler = HandlerStack::create();
        self::$handler->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            $path = $uri->getPath();

            if (strpos($path, '/api') === 0) {
                return $request->withUri($uri->withPath('/app_test.php'.$path));
            }

            return $request;
        }));

        self::$staticContainer = [];
        self::$staticHistory = Middleware::history(self::$staticContainer);
        self::$handler->push(self::$staticHistory);

        $baseUri = getenv('TEST_BASE_URL');

        self::$staticClient = new Client([
            'base_uri' => $baseUri,
            'http_errors' => false,
            'handler' => self::$handler
        ]);

        self::bootKernel();
    }

    protected function setUp()
    {
        $this->client = self::$staticClient;

        $this->purgeDatabase();
    }

    /**
     * Clean up Kernel usage in this test.
     */
    protected function tearDown()
    {
        // purposefully not calling parent class, which shuts down the kernel
    }

    /**
     * Adds a new fixture to be loaded.
     *
     * @param FixtureInterface $fixture
     */
    protected function addFixture(FixtureInterface $fixture)
    {
        $this->getFixtureLoader()->addFixture($fixture);
    }

    /**
     * Executes all the fixtures that have been loaded so far.
     */
    protected function executeFixtures()
    {
        $this->getFixtureExecutor()->execute($this->getFixtureLoader()->getFixtures());
    }

    /**
     * @return ORMExecutor
     */
    private function getFixtureExecutor()
    {
        if (!$this->fixtureExecutor) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();
            $this->fixtureExecutor = new ORMExecutor($entityManager, new ORMPurger($entityManager));
        }
        return $this->fixtureExecutor;
    }

    /**
     * @return ContainerAwareLoader
     */
    private function getFixtureLoader()
    {
        if (!$this->fixtureLoader) {
            $this->fixtureLoader = new ContainerAwareLoader(self::$kernel->getContainer());
        }
        return $this->fixtureLoader;
    }

    /**
     * Clean up database
     */
    protected function purgeDatabase()
    {
        $em = $this->getEntityManager();
        $purger = new ORMPurger($em);
        $purger->purge();
    }

    protected function getService($id)
    {
        /** KernelInterface $kernel */
        return self::$kernel->getContainer()->get($id);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $em = $this->getService('doctrine')->getManager();

        return $em;
    }

    protected function onNotSuccessfulTest(\Throwable $e)
    {
        $lastResponse = count(self::$staticContainer);

        if ($lastResponse) {
            $this->printDebug('');
            $this->printDebug('<error>Failure!</error> when making the following request:');
            $this->printLastRequestUrl();
            $this->printDebug('');

            if (empty(self::$staticContainer[$lastResponse - 1]['response'])) {
                $this->printDebug(
                    sprintf(
                        '<error>Guzzle client can’t establish a connection to the server at %s</error>',
                        getenv('TEST_BASE_URL')
                    )
                );
            } else {
                $this->debugResponse(self::$staticContainer[$lastResponse - 1]['response']);
            }
        }

        throw $e;
    }

    protected function printLastRequestUrl()
    {
        $lastRequest = count(self::$staticContainer);

        if ($lastRequest) {
            $this->printDebug(
                sprintf('<comment>%s</comment>: <info>%s</info>',
                    self::$staticContainer[$lastRequest - 1]['request']->getMethod(),
                    self::$staticContainer[$lastRequest - 1]['request']->getUri()
                )
            );
        } else {
            $this->printDebug('No request was made.');
        }
    }

    protected function assertJsonResponse(ResponseInterface $response) {
        $header = $response->getHeader('Content-Type');
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertEquals('application/json', $header[0]);
    }

    protected function debugResponse(ResponseInterface $response)
    {
        $this->printDebug($this->getStartLineAndHeaders($response));
        $body = (string) $response->getBody();

        $contentType = $response->getHeader('Content-Type');

        if ($contentType[0] == 'application/json' || strpos($contentType[0], '+json') !== false) {
            $data = json_decode($body);
            if ($data === null) {
                // invalid JSON!
                $this->printDebug($body);
            } else {
                // valid JSON, print it pretty
                $this->printDebug(json_encode($data, JSON_PRETTY_PRINT));
            }
        } else {
            // the response is HTML - see if we should print all of it or some of it
            $isValidHtml = strpos($body, '</body>') !== false;

            if ($isValidHtml) {
                $this->printDebug('');
                $crawler = new Crawler($body);

                // very specific to Symfony's error page
                $isError = $crawler->filter('#traces-0')->count() > 0
                    || strpos($body, 'looks like something went wrong') !== false;
                if ($isError) {
                    $this->printDebug('There was an Error!!!!');
                    $this->printDebug('');
                } else {
                    $this->printDebug('HTML Summary (h1 and h2):');
                }

                // finds the h1 and h2 tags and prints them only
                foreach ($crawler->filter('h1, h2')->extract(array('_text')) as $header) {
                    // avoid these meaningless headers
                    if (strpos($header, 'Stack Trace') !== false) {
                        continue;
                    }
                    if (strpos($header, 'Logs') !== false) {
                        continue;
                    }

                    // remove line breaks so the message looks nice
                    $header = str_replace("\n", ' ', trim($header));
                    // trim any excess whitespace "foo   bar" => "foo bar"
                    $header = preg_replace('/(\s)+/', ' ', $header);

                    if ($isError) {
                        $this->printErrorBlock($header);
                    } else {
                        $this->printDebug($header);
                    }
                }

                $profilerUrl = $response->getHeader('X-Debug-Token-Link');

                if ($profilerUrl) {
                    $fullProfilerUrl = (string) $response->getHeader('Host').$profilerUrl[0];
                    $this->printDebug('');
                    $this->printDebug(sprintf(
                        'Profiler URL: <comment>%s</comment>',
                        $fullProfilerUrl
                    ));
                }

                // an extra line for spacing
                $this->printDebug('');
            } else {
                $this->printDebug($body);
            }
        }
    }

    /**
     * Print a message out - useful for debugging
     *
     * @param $string
     */
    protected function printDebug($string)
    {
        if ($this->output === null) {
            $this->output = new ConsoleOutput();
        }
        $this->output->writeln($string);
    }

    /**
     * @param MessageInterface $message
     * @return string
     */
    protected function getStartLineAndHeaders(MessageInterface $message)
    {
        return $this->getStartLine($message) . $this->getHeadersAsString($message);
    }

    /**
     * @param MessageInterface $message
     * @return string
     */
    protected function getStartLine(MessageInterface $message)
    {
        if ($message instanceof RequestInterface) {
            return trim($message->getMethod())
                . ' HTTP/' . $message->getProtocolVersion();
        } elseif ($message instanceof ResponseInterface) {
            return 'HTTP/' . $message->getProtocolVersion() . ' '
                . $message->getStatusCode() . ' '
                . $message->getReasonPhrase();
        } else {
            throw new \InvalidArgumentException('Unknown message type');
        }
    }

    /**
     * @param MessageInterface $message
     * @return string
     */
    protected function getHeadersAsString(MessageInterface $message)
    {
        $result  = '';
        foreach ($message->getHeaders() as $name => $values) {
            $result .= "\r\n{$name}: " . implode(', ', $values);
        }

        return $result;
    }

    /**
     * Print a debugging message out in a big red block
     *
     * @param $string
     */
    protected function printErrorBlock($string)
    {
        if ($this->formatterHelper === null) {
            $this->formatterHelper = new FormatterHelper();
        }
        $output = $this->formatterHelper->formatBlock($string, 'bg=red;fg=white', true);

        $this->printDebug($output);
    }

    /**
     * @return ResponseAsserter
     */
    protected function asserter()
    {
        if ($this->responseAsserter === null) {
            $this->responseAsserter = new ResponseAsserter();
        }
        return $this->responseAsserter;
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    protected function responseData(ResponseInterface $response)
    {
        $data = json_decode((string) $response->getBody(), true);

        return $data;
    }

    /**
     * @param $username
     * @param array $headers
     * @return array
     */
    protected function getAuthorizedHeaders($username, $headers = array())
    {
        $token = $this->getService('lexik_jwt_authentication.encoder')
            ->encode(['username' => $username]);

        $headers['Authorization'] = 'Bearer '.$token;

        return $headers;
    }

    /**
     * @return Group
     */
    protected function createGroup()
    {
        $group = new Group();
        $group->setName('Group Test');

        $em = $this->getEntityManager();
        $em->persist($group);
        $em->flush();

        return $group;
    }

    /**
     * @return User
     */
    protected function createUser()
    {
        $user = new User();
        $user->setUsername('test');
        $user->setPassword('test-password');
        $user->setEmail('test@test.com');
        $user->setName('User Test');

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @return User
     */
    protected function createUserAdmin()
    {
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setPassword('admin');
        $userAdmin->setEmail('admin@test.com');
        $userAdmin->setName('User Admin');
        $userAdmin->addRole('ROLE_ADMIN');

        $em = $this->getEntityManager();
        $em->persist($userAdmin);
        $em->flush();

        return $userAdmin;
    }

    protected function createUserData()
    {
        $data = [
            'username' => 'username',
            'password' => 'password',
            'email' => 'test@test.com',
            'name' => 'test name',
            'roles' => [
                'ROLE_USER'
            ]
        ];

        return $data;
    }

    /**
     * @param User $user
     * @param Group $group
     * @return User
     */
    protected function assignUserToGroup(User $user, Group $group)
    {
        $user->assignTo($group);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}