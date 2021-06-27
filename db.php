<?php

class db
{
    private string $dbHost;
    private string $dbName;
    private string $dbUsername;
    private string $dbPassword;

    public PDO $dbcon;

    /**
     * db constructor.
     * @param string $dbHost
     * @param string $dbName
     * @param string $dbUsername
     * @param string $dbPassword
     */
    public function __construct(
        string $dbHost = 'localhost',
        string $dbName = 'aventus',
        string $dbUsername = 'root',
        string $dbPassword = 'root'
    )
    {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUsername = $dbUsername;
        $this->dbPassword = $dbPassword;

        $this->init();
    }

    /**
     * @return PDO|null
     */
    private function init(): ?PDO
    {
        try {
            $this->dbcon = new PDO(
                "mysql:host=$this->dbHost;dbname=$this->dbName", $this->dbUsername, $this->dbPassword
            );
            $this->dbcon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return null;
        }

        return $this->dbcon;
    }
}