<?php
namespace SanderHeijselaar\EzPdo;

/**
 * ezPDO is a class for easy & save DB access
 *
 * @version 0.3.0
 */
class EzPdo
{
    protected $PDO;

    protected $dbh;

    protected $dbtype;
    protected $dbhost;
    protected $dbname;
    protected $dbuser;
    protected $dbpassword;
    protected $dbcharset;
    protected $dbpersistent;

    protected $result;
    protected $lastError;
    protected $rowsAffected;

    protected $last_query;
    protected $last_params;

    protected $errorHandling;

    protected $cachedResults = array();
    protected $cacheResults = false;

    const DB_TYPE_MYSQL      = 'mysql';
    const DB_TYPE_SQLITLE    = 'sqlite';
    const DB_TYPE_POSTGRESQL = 'postgresql';

    const ERROR_HANDLING_THROW = 'throw';
    const ERROR_HANDLING_DUMP  = 'dump';

    /**
     * Constructor of the class
     *
     * @param string $dbtype type of database/ mysql,sqlite etc
     * @param string $dbhost host of the database. localhost etc
     * @param string $dbname name of the database.
     * @param string $dbuser name of the database user
     * @param string $dbpassword password of the database user
     * @param bool   $dbpersistent Create a persistent connection or not
     * @param string $dbcharset charset of the database connection. utf8, latin1 etc
     */
    public function __construct($dbtype, $dbhost, $dbname, $dbuser, $dbpassword, $dbpersistent=false, $dbcharset='utf8', $PDO='PDO')
    {
        $this->PDO          = $PDO;
        $this->dbtype       = strtolower($dbtype);
        $this->dbhost       = $dbhost;
        $this->dbname       = $dbname;
        $this->dbuser       = $dbuser;
        $this->dbpassword   = $dbpassword;
        $this->dbpersistent = (bool) $dbpersistent;
        $this->dbcharset    = $dbcharset;

        $this->errorHandling = self::ERROR_HANDLING_THROW;

        $this->checkDbType();
    }

    /**
     * Destructor of the class
     */
    public function __destruct()
    {
        $this->dbh = null;
    }

    /**
     * Get the self::$PDO
     *
     * @return string
     */
    public function getPdo()
    {
        return $this->PDO;
    }

    /**
     * Get the self::$PDO
     *
     * @param string $PDO
     */
    public function setPdo($PDO)
    {
        $this->PDO = $PDO;
    }

    /**
     * Function for insert type queries
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @return bool|int on succes returns the last insertId when available or true and false when errors occurred
     */
    public function insert($query, $params)
    {
        if (!$this->query($query, $params, 'insert'))
        {
            $this->handleErrors();
        }
        else
        {
            return $this->dbh->lastInsertId() ? $this->dbh->lastInsertId() : true;
        }

        return false;
    }

    /**
     * Function for update type queries
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @return bool|int on succes returns the number of rows affected and false when errors occurred
     */
    public function update($query, $params)
    {
        if (!$this->query($query, $params, 'update'))
        {
            $this->handleErrors();
        }
        else
        {
            return $this->rowsAffected;
        }

        return false;
    }

    /**
     * Function for delete type queries
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @return bool|int on succes returns the number of rows affected and false when errors occurred
     */
    public function delete($query, $params)
    {
        if (!$this->query($query, $params, 'delete'))
        {
            $this->handleErrors();
        }
        else
        {
            return $this->rowsAffected;
        }

        return false;
    }

    /**
     * Function for retrieving multiple rows
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @return array returns the resultset of the query
     */
    public function getResults($query, $params)
    {
        if (!$this->query($query, $params, 'select'))
        {
            $this->handleErrors();
        }
        else
        {
            return $this->result;
        }
    }

    /**
     * Function for retrieving a single row
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @param int|string $rownr the number or the row which needs to be retrieved
     * @return array returns the resultset of the query
     */
    public function getRow($query, $params, $rownr=0)
    {
        if (!$this->query($query, $params, 'select'))
        {
            $this->handleErrors();
        }
        else
        {
            if (!empty($this->result[$rownr]))
            {
                return $this->result[$rownr];
            }
            else
            {
                return array();
            }
        }
    }

    /**
     * Function for retrieving a single colum of multiple rows
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @param int|string $colindex the index or the name of the column which need to be retrieved
     * @return array returns the resultset of the query
     */
    public function getCol($query, $params, $colindex=0)
    {
        if (!$this->query($query, $params, 'select'))
        {
            $this->handleErrors();
        }

        $results = array();
        if (!empty ($this->result[0][$colindex]))
        {
            return $this->result[0][$colindex];
        }
        else
            if (count($this->result) > $colindex)
            {
                $counter = 0;
                foreach (array_keys($this->result[0]) as $colname)
                {
                    if ($counter == $colindex)
                    {
                        $colindex = $colname;
                    }
                    $counter++;
                }
            }
            else
            {
                return $results;
            }

        foreach ($this->result as $row)
        {
            $results[] = $row[$colindex];
        }
        return $results;
    }

    /**
     * Function for retrieving a single colum of a single row
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @param int|string $rownr the number or the row which needs to be retrieved
     * @param int|string $colindex the index or the name of the column which need to be retrieved
     * @return int|string|float returns the value of the query
     */
    public function getVar($query, $params, $rownr=0, $colindex=0)
    {
        if (!$this->query($query, $params, 'select'))
        {
            $this->handleErrors();
        }
        else
        {
            if (empty($this->result[$rownr]))
            {
                return false;
            }

            if (!empty($this->result[$rownr][$colindex]))
            {
                return $this->result[$rownr][$colindex];
            }
            else
                if (count($this->result[$rownr]) < $colindex)
                {
                    return false;
                }

            $counter = 0;
            foreach($this->result[$rownr] as $col)
            {
                if ($counter == $colindex)
                {
                    return $col;
                }
                $counter++;
            }
        }
    }

    /**
     * Function for setting the cache on or off.
     *
     * @param boolean $bool
     */
    public function cacheResults($bool)
    {
        $this->cacheResults = (bool) $bool;

        // Clear cache when cache is turned off
        if ($this->cacheResults === false) {
            $this->cachedResults = array();
        }
    }

    /**
     * Method
     *
     * @param type $query
     * @param type $params
     */
    public function returnParsedQuery($query, $params)
    {
        $this->preProcessParamsArray($query, $params);

        foreach($params as $param_name => $param_value)
        {
            if (is_int($param_value))
            {
                $query = str_replace(':' . $param_name, $params[$param_name], $query);
            } else
                if (is_null($param_value))
                {
                    $query = str_replace(':' . $param_name, 'NULL', $query);
                }
                else
                {
                    $query = str_replace(':' . $param_name, "'" . str_replace("'", "\'", $params[$param_name]) . "'", $query);
                }
        }

        return $query;
    }

    /**
     * Set the class up to dump errors
     */
    public function dumpErrors()
    {
        $this->errorHandling = self::ERROR_HANDLING_DUMP;
    }

    /**
     * Set the class up for throwing errors
     */
    public function throwErrors()
    {
        $this->errorHandling = self::ERROR_HANDLING_THROW;
    }

    /**
     * Check if the given database type driver is available
     */
    protected function checkDbType()
    {
        // Need a local instance of the class var
        $PDO =& $this->PDO;

        $drivers = $PDO::getAvailableDrivers();

        if (!in_array($this->dbtype, $drivers))
        {
            dump("Error! " . $this->dbtype . " driver not available");
            exit();
        }
    }

    /**
     * Create a connection to the database
     */
    protected function connect()
    {
        if (!$this->dbh)
        {
            // Need a local instance of the class var
            $PDO =& $this->PDO;

            try
            {
                $driverOption = array();
                $driverOption[$PDO::ATTR_PERSISTENT] = $this->dbpersistent;

                $this->dbh = new $PDO($this->dbtype . ':host=' . $this->dbhost . ';dbname=' . $this->dbname, $this->dbuser, $this->dbpassword, $driverOption);
                $this->dbh->setAttribute($PDO::ATTR_ERRMODE, $PDO::ERRMODE_EXCEPTION);

                $this->query('SET NAMES :dbcharset;', array('dbcharset' => $this->dbcharset), 'update');
                $this->query('SET session character_set_server = :dbcharset;', array('dbcharset' => $this->dbcharset), 'update');
            }
            catch(\PDOException $e)
            {
                $this->lastError = $e->getmessage();
                $this->handleErrors();
            }
        }
    }

    /**
     * Execute the given query
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @param string $type type of query. select, update, delete or insert
     * @return bool returns true when the query was executed correctly and false when errors occurred
     */
    protected function query($query, $params, $type)
    {
        if (!$this->dbh)
        {
            $this->connect();
        }

        if ('select' == $type)
        {
            return $this->querySelect($query, $params);
        }

        return $this->queryInsertUpdateDelete($query, $params);
    }

    /**
     * Execute the given select query
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @return bool returns true when the query was executed correctly and false when errors occurred
     */
    protected function querySelect($query, $params)
    {
        // Need a local instance of the class var
        $PDO =& $this->PDO;

        // Check if cache is enabled and if the result is already in cache
        if ($this->cacheResults === true && array_key_exists(md5($query . serialize($params)), $this->cachedResults) === true) {
            $this->result = $this->cachedResults[md5($query . serialize($params))];
            return true;
        }


        // Select queries
        if ($query == $this->last_query && serialize($params) == $this->last_params)
        {
            // Results of this query have been cached.
            return true;
        }

        try
        {
            $this->preProcessParamsArray($query, $params);

            $stmt = $this->dbh->prepare($query);

            $this->bindParams($stmt, $params);

            $stmt->execute();
        }
        catch(\PDOException $e)
        {
            $this->lastError = $e->getmessage();
            $stmt = null;

            return false;
        }

        try
        {
            $result = array();
            while($row = $stmt->fetch($PDO::FETCH_ASSOC))
            {
                $result[] = $row;
            }
        }
        catch(\PDOException $e)
        {
            $stmt = null;
            $this->lastError = $e->getmessage();

            return false;
        }

        // Update result
        $this->result = $result;

        // Keep track of the last query for debug..
        $this->last_query  = $query;
        $this->last_params = serialize($params);

        // Cache results when caching is enabled
        if ($this->cacheResults === true) {
            $this->cachedResults[md5($query . serialize($params))] = $this->result;
        }

        $stmt = null;

        return true;
    }

    /**
     * Execute the given insert, udate & delete queries
     *
     * @param string $query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @return bool returns true when the query was executed correctly and false when errors occurred
     */
    protected function queryInsertUpdateDelete($query, $params)
    {
        // Insert, update and delete queries
        try
        {
            $this->preProcessParamsArray($query, $params);

            $stmt = $this->dbh->prepare($query);

            $this->bindParams($stmt, $params);

            $stmt->execute();

            $this->rowsAffected = $stmt->rowCount();
        }
        catch(\PDOException $e)
        {
            $this->lastError = $e->getmessage() . "\n\n<strong>Query</strong>:\n" . $query . "\n\n<strong>Params</strong>:\n" . var_export($params, true);

            return false;
        }

        // Clear result
        $this->result = array();

        // Keep track of the last query for debug..
        $this->last_query = $query;

        return true;
    }

    /**
     * This function converts array's to multiple single values.
     * This way a query like WHERE field IN (:arrayData) can be used where the :arrayData is an array with data
     *
     * @param string $query The SQL query
     * @param array $params All the params used by the query
     */
    protected function preProcessParamsArray(&$query, &$params)
    {
        $newParams = array();

        foreach ($params as $paramName => &$param)
        {
            if (is_array($param) === false)
            {
                continue;
            }
            $newParams[$paramName] = array();

            foreach ($param as $paramCounter => $paramItem)
            {
                $newParams[$paramName][$paramName . '_' . $paramCounter] = $paramItem;
            }
        }
        unset($param);

        if (! empty($newParams))
        {
            foreach (array_keys($newParams) as $oldParemName)
            {
                unset($params[$oldParemName]);

                $params = array_merge($params, $newParams[$oldParemName]);

                $query = str_replace(':' . $oldParemName, ':' . implode(', :', array_keys($newParams[$oldParemName])), $query);
            }
        }
    }

    /**
     * Function for binding parameters to a query
     *
     * @param PDOStatement $stmt PDOStatement object of the query
     * @param array $params array where the key is parameter name and the value is the parameter value
     * @return bool returns true when the parameters where succesfully binded to the query and false when errors occurred
     */
    protected function bindParams(&$stmt, $params)
    {
        if (!is_array($params))
        {
            return false;
        }

        // Need a local instance of the class var
        $PDO =& $this->PDO;

        try
        {
            foreach($params as $param_name => $param_value)
            {
                if (is_int($param_value))
                {
                    $stmt->bindParam(':' . $param_name, $params[$param_name], $PDO::PARAM_INT);
                } else
                    if (is_null($param_value))
                    {
                        $stmt->bindParam(':' . $param_name, $params[$param_name], $PDO::PARAM_NULL);
                    }
                    else
                    {
                        $stmt->bindParam(':' . $param_name, $params[$param_name], $PDO::PARAM_STR);
                    }
            }
        }
        catch(\PDOException $e)
        {
            $this->lastError = $e->getmessage();
            $this->handleErrors();
        }
    }

    /**
     * The function for handleing errors
     */
    protected function handleErrors()
    {
        list($file, $line) = $this->getBacktrace();

        if ($this->errorHandling === self::ERROR_HANDLING_THROW) {
            throw new EzPdoException($file . ':' . $line . "\n" . $this->lastError);
        }

        die('<pre><strong>' . $file . ':' . $line . "</strong>\n" . $this->lastError . '</pre>');
    }

    protected function getBacktrace()
    {
        $data = debug_backtrace();
        array_shift($data);
        array_shift($data);
        return array($data[0]['file'], $data[0]['line']);
    }


} // END CLASS

// Extend \Excetion for the EzPdo class
class EzPdoException extends \Exception {

}
