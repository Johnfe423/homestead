<?php

/**
 * Singleton Factory class for creating PDO objects based on
 * PHPWS database configuration. Somewhat of a wrapper
 * class for our current situation.
 * 
 * @author jbooker
 * @package homestead
 */
class PdoFactory {
    
    static $factory;
    
    private $pdo;
    
    /**
     * Returns a PdoFactory instance
     * @return PdoFactory $pdo A PdoInstance object
     */
    public static function getInstance()
    {
        if (!isset(self::$factory)) {
            self::$factory = new PdoFactory(); 
        }
        
        return self::$factory;
    }
  
    /**
     * Returns a PDO object which is connected to the current database
     * @return $pdo A PDO instance, connected to the current DB
     */
    public static function getPdoInstance()
    {
        $pdoFactory = self::getInstance();

        return $pdoFactory->getPdo();
    }

    
    private function __construct()
    {
        if (!defined('PHPWS_DSN')) {
            throw new Exception('Database connection DSN is not set.');
        }
        $dsn_array = \Database::parseDSN(PHPWS_DSN);
        
        // creates dbtype, dbpass, dbuser, dbname, dbport, dbhost
        extract($dsn_array);
        
        
        $dsn = $this->createDsn($dbtype, $dbhost, $dbname);
        
        $this->pdo = new PDO($dsn, $dbuser, $dbpass, array(PDO::ATTR_PERSISTENT => true));
    }
    
    public function getPdo()
    {
        return $this->pdo;
    }
    
    private function createDsn($dbType, $host, $dbName)
    {
        return "$dbType:" . ($host != '' ? "host=$host" : '') . ";dbname=$dbName";
    }
}

?>
