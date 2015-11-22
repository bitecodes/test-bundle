<?php

namespace Fludio\TestBundle\Test;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
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
        $this->client  = static::createClient();
        $this->factory = $this->client->getContainer()->get('fludio_factory.factory');
        $this->em      = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function get($uri, $headers = [])
    {
        $this->client->request('GET', $uri, [], [], $headers);

        return $this;
    }

    public function post($uri, $data = [], $headers = [])
    {
        $this->client->request('POST', $uri, $data, [], $headers);

        return $this;
    }

    public function patch($uri, $data = [], $headers = [])
    {
        $this->client->request('PATCH', $uri, $data, [], $headers);

        return $this;
    }

    public function delete($uri, $data = [], $headers = [])
    {
        $this->client->request('DELETE', $uri, $data, [], $headers);

        return $this;
    }

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->client->getContainer()->get('router')->generate($route, $parameters, $referenceType);
    }

    public function seeStatusCode($code)
    {
        $this->assertEquals($code, $this->client->getResponse()->getStatusCode());

        return $this;
    }

    public function seeJsonResponse()
    {
        $response = $this->client->getResponse();
        $this->assertJson($response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        return $this;
    }

    public function seeJsonHas($dataKey, $count = 1)
    {
        $content = $this->client->getResponse()->getContent();

        $data = json_decode($content, true);

        $dataKeyArray = explode('.', $dataKey);

        foreach($dataKeyArray as $key) {
            $data = $data[$key];
        }

        $this->assertEquals($count, count($data));

        return $this;
    }

    public function seeJsonContains($data, $count = 1)
    {
        $content = $this->client->getResponse()->getContent();

        $jsonData = json_decode($content, true);

        $this->assertTrue($this->has(key($data), $data[key($data)], $jsonData));

        return $this;
    }

    public function seeInDatabase($entity, $criteria)
    {
        $count = $this->getDatabaseResult($entity, $criteria);

        $this->assertGreaterThan(0, $count, sprintf(
            'Unable to find row in database table [%s] that matched attributes [%s].', $entity, json_encode($criteria)
        ));

        return $this;
    }

    public function seeNotInDatabase($entity, $criteria)
    {
        $count = $this->getDatabaseResult($entity, $criteria);

        $this->assertEquals(0, $count, sprintf(
            'Found row in database table [%s] that matched attributes [%s].', $entity, json_encode($criteria)
        ));

        return $this;
    }

    protected function getDatabaseResult($entity, $criteria)
    {
        $qb = $this->em
            ->createQueryBuilder()
            ->select('COUNT(e)')
            ->from($entity, 'e');

        foreach($criteria as $field => $value) {
            $qb->andWhere("e.{$field} = :{$field}")->setParameter($field, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }


    private function has($key, $value, $data)
    {
        foreach($data as $k => $v) {
            if($k == $key && $v == $value) {
                return true;
            } elseif(is_array($v)) {
                if($this->has($key, $value, $v)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function runCmd($command)
    {
        $command = sprintf('%s --quiet', $command);

        return self::getApplication()->run(new StringInput($command));
    }

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