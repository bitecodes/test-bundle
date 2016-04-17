<?php

namespace BiteCodes\TestBundle\Test;

trait DatabaseTransactions
{
    /** @beforeClass */
    public static function clearDatabase()
    {
        self::runCmd('doctrine:database:drop --force --env=test');
        self::runCmd('doctrine:database:create --env=test');
        self::runCmd('doctrine:schema:create --env=test');
    }

    /** @before */
    public function startTransaction()
    {
        $this->getContainer()->get('doctrine')->getManager()->beginTransaction();
    }

    /** @after */
    public function rollback()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->rollback();
        $em->close();
    }
}