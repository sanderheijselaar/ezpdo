<?php

define('DBHOST', 'localhost');
define('DBNAME', 'dbname');
define('DBUSER', 'dbuser');
define('DBPASS', '123');

use SanderHeijselaar\EzPdo\EzPdo;

class EzPdoTest extends PHPUnit_Framework_TestCase
{
    protected $dbh;

    public function testSelectAll()
    {
        $sql = "
            SELECT
                *
            FROM
                `person`
            ORDER BY
                `id` ASC;
        ";

        $actual = $this->dbh->getResults($sql, array());
        $expected = array(
            0 => array(
                'id' => '1',
                'name' => 'Simon',
                'email' => 'simon@simon.com',
                'phone' => '+31612345678',
            ),
            1 => array(
                'id' => '2',
                'name' => 'Sander',
                'email' => 'sander@sander.com',
                'phone' => '+31612345678',
            ),
            2 => array(
                'id' => '3',
                'name' => 'Barry',
                'email' => 'barry@barry.com',
                'phone' => '+31612345678',
            ),
        );

        $this->assertEquals($expected, $actual, "testSelectAll() didn't return the expected data");
    }

    public function testUpdate()
    {
        $sql = "
            UPDATE
                `person`
            SET
                `phone` = :phone
            WHERE
                `id` = :id
        ";
        $sqlData = array(
            'id'    => 1,
            'phone' => '+31688888888',
        );

        $actual = $this->dbh->update($sql, $sqlData);
        $expected = 1;
        $this->assertEquals($expected, $actual, "testUpdate() didn't return the right amount of updated records");


        $sql = "
            SELECT
                `phone`
            FROM
                `person`
            WHERE
                `id` = :id;
        ";
        $sqlData = array(
            'id'    => 1,
        );

        $actual = $this->dbh->getVar($sql, $sqlData);
        $expected = '+31688888888';
        $this->assertEquals($expected, $actual, "testUpdate() didn't return the right updated phone number");
    }

    public function testGetCol()
    {
        $sql = "
            SELECT
                `phone`
            FROM
                `person`
            ORDER BY
                `id` ASC;
        ";

        $actual = $this->dbh->getCol($sql,array());
        $expected = array(
            0 => '+31612345678',
            1 => '+31612345678',
            2 => '+31612345678',
        );
        $this->assertEquals($expected, $actual, "testGetCol() didn't return the expected phone numbers");
    }

    public function testDelete()
    {
        $sql = "
            DELETE FROM
                `person`
            WHERE
                `id` = :id
        ";
        $sqlData = array(
            'id'    => 2,
        );

        $actual = $this->dbh->delete($sql, $sqlData);
        $expected = 1;
        $this->assertEquals($expected, $actual, "testDelete() didn't return the right amount of deleted records");

        $sql = "
            SELECT
                *
            FROM
                `person`
            ORDER BY
                `id` ASC;
        ";

        $actual = $this->dbh->getResults($sql, array());
        $expected = array(
            0 => array(
                'id' => '1',
                'name' => 'Simon',
                'email' => 'simon@simon.com',
                'phone' => '+31612345678',
            ),
            1 => array(
                'id' => '3',
                'name' => 'Barry',
                'email' => 'barry@barry.com',
                'phone' => '+31612345678',
            ),
        );

        $this->assertEquals($expected, $actual, "testDelete() didn't return the expected data");
    }

    public function testEzPdoException()
    {
        $sql = "
            SELECT `phone` FROM `ERROR` WHERE `id` = :id;
        ";
        $sqlData = array(
            'id'    => 1,
        );

        $this->setExpectedException('SanderHeijselaar\EzPdo\EzPdoException');

        $this->dbh->getVar($sql, $sqlData);
    }

    public function testReturnParsedQuery()
    {
        // Int test
        $expected = "SELECT `phone` FROM `person` WHERE `id` = 1;";

        $sql = "SELECT `phone` FROM `person` WHERE `id` = :id;";
        $sqlData = array(
            'id'    => 1,
        );

        $actual = $this->dbh->returnParsedQuery($sql, $sqlData);

        $this->assertEquals($expected, $actual, "testReturnParsedQuery() didn't return the expected string");

        // String test
        $expected = "SELECT `id` FROM `person` WHERE `name` = 'sander\'s';";

        $sql = "SELECT `id` FROM `person` WHERE `name` = :name;";
        $sqlData = array(
            'name' => "sander's",
        );

        $actual = $this->dbh->returnParsedQuery($sql, $sqlData);

        $this->assertEquals($expected, $actual, "testReturnParsedQuery() didn't return the expected string (2)");

        // Array test
        $expected = "SELECT * FROM `person` WHERE `id` IN (1, '2');";
        
        $sql = "SELECT * FROM `person` WHERE `id` IN (:ids);";
        $sqlData = array(
            'ids' => array(1,'2'),
        );

        $actual = $this->dbh->returnParsedQuery($sql, $sqlData);

        $this->assertEquals($expected, $actual, "testReturnParsedQuery() didn't return the expected string (3)");
    }

    /**
     * SetUp & TearDown
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dbh = new EzPdo(EzPdo::DB_TYPE_MYSQL, DBHOST, DBNAME, DBUSER, DBPASS);

        $sql = "
            DROP TABLE IF EXISTS `person`;
        ";

        $this->dbh->update($sql,array());

        $sql = "
            CREATE TABLE `person` (
                `id`  smallint UNSIGNED NOT NULL AUTO_INCREMENT ,
                `name`  char(32) NOT NULL ,
                `email`  char(64) NOT NULL ,
                `phone`  char(16) NOT NULL ,
                PRIMARY KEY (`id`)
            );
        ";
        $this->dbh->update($sql,array());

        $sql = "
            INSERT INTO
                `person` (`name`, `email`, `phone`)
            VALUES
                ('Simon', 'simon@simon.com', '+31612345678'),
                ('Sander', 'sander@sander.com', '+31612345678'),
                ('Barry', 'barry@barry.com', '+31612345678')
        ";
        $this->dbh->insert($sql,array());
    }

}
