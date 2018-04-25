<?php

class pdoDB
{
    // Private statics to hold the connection
    private static $dbConnection = null;

    // Make the next 2 functions private to prevent normal
    // Class instantiation
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Return DB connection or create initial connection
     * @return object (PDO)
     * @access public
     */
    public static function getConnection()
    {
        // If there isn't a connection already then create one
        if (!self::$dbConnection) {
            try {
                // Connection options to include using exception mode
                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                );
                // Pass in the options as the last parameter so pdo uses exceptions
                self::$dbConnection = new PDO('sqlite:./db/it_musicNew.sqlite',
                    '',
                    '',
                    $options);
            } catch (PDOException $e) {
                // In a production system you would log the error not display it
                echo $e->getMessage();
            }
        }
        // Return the connection
        return self::$dbConnection;
    }

}