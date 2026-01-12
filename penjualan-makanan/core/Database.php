<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $stmt;
    
    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
    
    // METODE UTAMA: query() yang bisa menerima parameter
    public function query($sql, $params = null) {
        try {
            $this->stmt = $this->conn->prepare($sql);
            
            // Jika ada parameter, execute dengan parameter
            if ($params !== null) {
                if (!is_array($params)) {
                    $params = [$params];
                }
                
                // Bind parameters jika ada
                foreach ($params as $key => $value) {
                    if (is_int($key)) {
                        // Parameter positional (?)
                        $param = $key + 1;
                    } else {
                        // Parameter named (:id)
                        $param = $key;
                    }
                    
                    if (is_int($value)) {
                        $type = PDO::PARAM_INT;
                    } elseif (is_bool($value)) {
                        $type = PDO::PARAM_BOOL;
                    } elseif (is_null($value)) {
                        $type = PDO::PARAM_NULL;
                    } else {
                        $type = PDO::PARAM_STR;
                    }
                    
                    $this->stmt->bindValue($param, $value, $type);
                }
                
                $this->stmt->execute();
                return $this->stmt;
            } else {
                // Untuk pattern lama: return $this
                return $this;
            }
        } catch (PDOException $e) {
            die("Query Error: " . $e->getMessage() . "<br>SQL: " . $sql);
        }
    }
    
    // Metode untuk pattern lama (tanpa parameter di query())
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    // Execute untuk pattern lama
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            die("Execute Error: " . $e->getMessage());
        }
    }
    
    // Method fetch untuk pattern lama
    public function fetch() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    // Method fetchAll untuk pattern lama
    public function fetchAll() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    // Method fetch dengan parameter (pattern baru)
    public function fetchRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Method fetchAll dengan parameter (pattern baru)
    public function fetchAllRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Prepare statement
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->conn->rollBack();
    }
}
?>