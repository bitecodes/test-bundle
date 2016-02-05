<?php

namespace Fludio\TestBundle\Test;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TestCase extends WebTestCase
{
    /**
     * @var Client
     */
    public $client;

    /**
     * @var EntityManager
     */
    public $em;
    /**
     * @var Application
     */
    protected static $application;
    /**
     * @var Factory
     */
    protected $factory;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->factory = $this->client->getContainer()->get('factrine');
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @param $uri
     * @param array $headers
     * @return TestCase
     */
    public function get($uri, $headers = [])
    {
        $this->client->request('GET', $uri, [], [], $headers);

        return $this;
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestCase
     */
    public function post($uri, $data = [], $headers = [])
    {
        $this->client->request('POST', $uri, $data, [], $headers);

        return $this;
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestCase
     */
    public function patch($uri, $data = [], $headers = [])
    {
        $this->client->request('PATCH', $uri, $data, [], $headers);

        return $this;
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestCase
     */
    public function put($uri, $data = [], $headers = [])
    {
        $this->client->request('PUT', $uri, $data, [], $headers);

        return $this;
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestCase
     */
    public function delete($uri, $data = [], $headers = [])
    {
        $this->client->request('DELETE', $uri, $data, [], $headers);

        return $this;
    }

    /**
     * @param $method
     * @param $uri
     * @param $content
     * @return TestCase
     */
    public function json($method, $uri, $content)
    {
        $this->client->request($method, $uri, [], [], [], $content);

        return $this;
    }

    /**
     * @param $route
     * @param array $parameters
     * @param bool $referenceType
     * @return string
     */
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->getContainer()->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * @param $code
     * @return TestCase
     */
    public function seeStatusCode($code)
    {
        $this->assertEquals($code, $this->client->getResponse()->getStatusCode());

        return $this;
    }

    /**
     * @return TestCase
     */
    public function seeJsonResponse()
    {
        $response = $this->client->getResponse();
        $this->assertJson($response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        return $this;
    }

    /**
     * @param $dataKey
     * @param int $count
     * @return TestCase
     */
    public function seeJsonHas($dataKey, $count = 1)
    {
        $content = $this->client->getResponse()->getContent();

        $data = json_decode($content, true);

        $dataKeyArray = explode('.', $dataKey);

        foreach ($dataKeyArray as $key) {
            $data = $data[$key];
        }

        $this->assertEquals($count, count($data));

        return $this;
    }

    /**
     * @param $data
     * @return TestCase
     */
    public function seeInJson($data)
    {
        $content = $this->client->getResponse()->getContent();

        $jsonData = json_decode($content, true);

        $message = sprintf("Could not verify that json contains %s", json_encode($data));

        $this->assertTrue($this->has(key($data), $data[key($data)], $jsonData), $message);

        return $this;
    }

    /**
     * @param $data
     * @return TestCase
     */
    public function seeNotInJson($data)
    {
        $content = $this->client->getResponse()->getContent();

        $jsonData = json_decode($content, true);

        $message = sprintf("Json contains %s", json_encode($data));

        $this->assertTrue(!$this->has(key($data), $data[key($data)], $jsonData), $message);

        return $this;
    }

    /**
     * @param $entity
     * @param $criteria
     * @return TestCase
     */
    public function seeInDatabase($entity, $criteria)
    {
        $count = $this->getDatabaseResult($entity, $criteria);

        $this->assertGreaterThan(0, $count, sprintf(
            'Unable to find row in database table [%s] that matched attributes [%s].', $entity, json_encode($criteria)
        ));

        return $this;
    }

    /**
     * @param $entity
     * @param $criteria
     * @return TestCase
     */
    public function seeNotInDatabase($entity, $criteria)
    {
        $count = $this->getDatabaseResult($entity, $criteria);

        $this->assertEquals(0, $count, sprintf(
            'Found row in database table [%s] that matched attributes [%s].', $entity, json_encode($criteria)
        ));

        return $this;
    }

    /**
     * @param $entity
     * @param $criteria
     * @return mixed
     */
    protected function getDatabaseResult($entity, $criteria)
    {
        $qb = $this->em
            ->createQueryBuilder()
            ->select('COUNT(e)')
            ->from($entity, 'e');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("e.{$field} = :{$field}")->setParameter($field, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param $key
     * @param $value
     * @param $data
     * @return bool
     */
    private function has($key, $value, $data)
    {
        foreach ($data as $k => $v) {
            if ($k == $key && $v == $value) {
                return true;
            } elseif (is_array($v)) {
                if ($this->has($key, $value, $v)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $command
     * @return string|StreamOutput
     * @throws \Exception
     */
    protected static function runCmd($command)
    {
        $client = self::createClient();
        $kernel = $client->getKernel();

        $app = new Application($kernel);
        $app->setAutoExit(false);

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $app->run($input, $output);

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        if (strpos(strtolower($output), 'exception') !== false) {
            throw new \PHPUnit_Framework_AssertionFailedError($output);
        }

        $client->getContainer()->get('doctrine')->getManager()->close();
        $kernel->shutdown();

        return $output;
    }

    /**
     * @return Application
     */
    protected static function getApplication()
    {
        if (null === self::$application) {
            $client = static::createClient();

            self::$application = new Application($client->getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }
}