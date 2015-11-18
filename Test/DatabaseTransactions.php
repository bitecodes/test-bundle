<?php

namespace Fludio\TestBundle\Test;

trait DatabaseTransactions
{
    /**
     * @beforeClass
     */
    public static function clearDatabase()
    {
        self::runCommand('doctrine:schema:drop --force --env=test');
        self::runCommand('doctrine:schema:create --env=test');
        self::runCommand('doctrine:schema:update --force --env=test');
    }

    /**
     * @before
     */
    public function startTransaction()
    {
        $this->client->getContainer()->get('doctrine')->getManager()->beginTransaction();
    }

    /**
     * @after
     */
    public function rollback()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $em->rollback();
        $em->close();
    }
}