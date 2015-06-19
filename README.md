#EZ PDO
Querying made simple & safe with PDO

##Installation
Install the composer package (https://packagist.org/packages/sanderheijselaar/ezpdo)
or download and include the ezPdo.php file in your project

##Usage
Create new SimplePDO instance or use the existing one:

	use SanderHeijselaar\EzPdo\EzPdo;

    $dbh = new EzPdo(
		EzPdo::DB_TYPE_MYSQL, 
		'localhost', 
		'db_name', 
		'db_user', 
		'db_pass');

Drop an existing table:

    $sql = "
        DROP TABLE IF EXISTS `person`;
    ";

    $result = $dbh->update($sql,array());
    var_export($result);

Create a new table:

    $sql = "
        CREATE TABLE `person` (
            `id`  smallint UNSIGNED NOT NULL AUTO_INCREMENT ,
            `name`  char(32) NOT NULL ,
            `email`  char(64) NOT NULL ,
            `phone`  char(16) NOT NULL ,
            PRIMARY KEY (`id`)
        );
    ";

    $result = $dbh->update($sql,array());
    var_export($result);

Insert rows into the new table:

    $sql = "
        INSERT INTO 
			`person` (`name`, `email`, `phone`) 
		VALUES 
			('Simon', 'simon@simon.com', '+31612345678');
    ";

    $result = $dbh->insert($sql,array());
    var_export($result);

    $sql = "
        INSERT INTO 
			person` (`name`, `email`, `phone`) 
		VALUES 
			('Sander', 'sander@sander.com', '+31612345678');
    ";

    $result = $dbh->insert($sql,array());
    var_export($result);

    $sql = "
        INSERT INTO 
			`person` (`name`, `email`, `phone`) 
		VALUES 
			('Barry', 'barry@barry.com', '+31612345678');
    ";

    $result = $dbh->insert($sql,array());
    var_export($result);

Retrieve all rows:

    $sql = "
        SELECT 
			* 
		FROM 
			`person` 
		ORDER BY 
			`id` ASC;
    ";

    $result = $dbh->getResults($sql,array());
    var_export($result);

Update a row by id:

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

    $result = $dbh->update($sql, $sqlData);
    var_export($result);

Retrieve by column:

    $sql = "
        SELECT `phone` FROM `person` ORDER BY `id` ASC;
    ";

    $result = $dbh->getCol($sql,array());
    var_export($result);

Delete a row by id:

    $sql = "
        DELETE FROM `person` WHERE `id` = :id
    ";
    $sqlData = array(
        'id'    => 2,
    );

    $result = $dbh->delete($sql, $sqlData);
    var_export($result);

Select a column by id:

    $sql = "
        SELECT `phone` FROM `person` WHERE `id` = :id;
    ";
    $sqlData = array(
        'id'    => 1,
    );

    $result = $dbh->getVar($sql, $sqlData);
    var_export($result);

Select multiple rows using the WHERE ... IN (...) statement with an array:

    $sql = "
        SELECT `id` FROM `person` WHERE `phone` IN (:phone);
    ";
    $sqlData = array(
        'phone' => array(
            '+31612345677',
            '+31612345678',
            '+31612345679',
            '+31688888888',
        ),
    );

    $result = $dbh->getCol($sql, $sqlData);
    var_export($result);

Make a query that throws an EzPdoException:

	try {
	    $sql = "
	        SELECT `phone` FROM `ERROR` WHERE `id` = :id;
	    ";
	    $sqlData = array(
	        'id'    => 1,
	    );
	
	    $result = $dbh->getVar($sql, $sqlData);
	    var_export($result);
	} catch (Sheijselaar\EzPdo\EzPdoException $ex) {
	    var_export($ex->getMessage());
	}



**Licensed under MIT**
