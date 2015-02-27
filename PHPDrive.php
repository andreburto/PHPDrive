<?php

define("PHPDRIVE_SQLITE", 1);
define("PHPDRIVE_SQLITE3", 2);
define("PHPDRIVE_MYSQLI", 3);

interface IPHPDriveDatabase {
    public function WriteQuery($sql=null);
    public function ReadQuery($sql=null);
    public function CloseConn();
    public function OpenConn($database=null);
}

class PHPDriveSqlite implements IPHPDriveDatabase {
    public $CONN;
    public $ERRMSG;

    function __construct() { }

    function __destruct() {
        if (isset($this->CONN)) { $this->CloseConn(); }
    }

    public function WriteQuery($sql=null) {
        $res = sqlite_exec($this->CONN, $sql);
        if ($res == false) {
            $this->ERRMSG = "Sqlite2 could not write data";
            return false;
        }
        return $res;
    }

    public function ReadQuery($sql=null) {
        $rows = array();
        $res = sqlite_query($this->CONN, $sql, SQLITE_ASSOC, $this->ERRMSG);
        if (isset($this->ERRMSG)) { return false; }
        if ($res == false) {
            $this->ERRMSG = "Sqlite2 query failed";
            return false;
        }
        if (sqlite_num_rows($res) === 0) {
            $this->ERRMSG = "Sqlite2 returned no results";
            return false;
        }
        while($row = sqlite_fetch_array($res, SQLITE_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function CloseConn() {
        sqlite_close($this->CONN);
    }

    public function OpenConn($database=null) {
        $this->CONN = sqlite_open($database, 0666);
    }
}

class PHPDriveSqlite3 implements IPHPDriveDatabase {
    public $CONN;
    public $ERRMSG;

    function __construct() { }

    function __destruct() {
        if (isset($this->CONN)) { $this->CloseConn(); }
    }

    public function WriteQuery($sql=null) {
        $res = $this->CONN->exec($sql);
        if ($res == false) {
            $this->ERRMSG = "Sqlite3 could not write data";
            return false;
        }
        return $res;
    }

    public function ReadQuery($sql=null) {
        $rows = array();
        $res = $this->CONN->query($sql);
        if (is_object($res) == false) {
            $this->ERRMSG = "Sqlite3 found no results";
            return false;
        }
        while($row = $res->fetchArray()) {
            $rows[] = $row;
        }
        if (count($rows) == 0) {
            $this->ERRMSG = "Sqlite3 looped no results";
            return false;
        }
        return $rows;
    }

    public function CloseConn() {
        $this->CONN->close();
    }

    public function OpenConn($database=null) {
        $this->CONN = new Sqlite3($this->FILE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    }
}

class PHPDrive {
    // Class-wide variables
    public $FILE;
    public $ERRMSG;
    protected $DBOBJ;

    // The SQL used below
    protected $SELECTALL = "SELECT * FROM fs WHERE FILE = '%s' ORDER BY UPDATED DESC";
    protected $SELECTONE = "SELECT * FROM fs WHERE FILE = '%s' ORDER BY UPDATED DESC LIMIT 1";
    protected $CREATE = "CREATE TABLE fs (FILE TEXT,DATA TEXT,CREATED DATETIME,UPDATED DATETIME)";
    protected $INSERT = "INSERT INTO fs (FILE,DATA,CREATED,UPDATED) VALUES ('%s','%s',%s,%s)";
    protected $DELETE = "DELETE FROM fs WHERE FILE = '%s'";
    
    function __construct($database=null, $dbtype=null) {
        // Set the file
        if ($database == null) {
            $this->FILE = sprintf("%s.db", date("YmdHis"));
        } else {
            if (strtolower(substr($database, -3)) != ".db") {
                $database .= ".db";
            }
            $this->FILE = $database;
        }
        // Set DB type
        if ($dbtype == null) {
            $this->DBOBJ = $this->SelectDatabaseType();
        } else {
            switch($dbtype) {
                case PHPDRIVE_SQLITE:
                    $this->DBOBJ = new PHPDriveSqlite();
                    break;
                case PHPDRIVE_SQLITE3:
                    $this->DBOBJ = new PHPDriveSqlite3();
                    break;
            }
        }
        // Check for the file
        if (is_file($this->FILE)) {
            // Connect to the database
            $this->DBOBJ->OpenConn($this->FILE);
        } else {
            // Connect to the database
            $this->DBOBJ->OpenConn($this->FILE);
            // Create the table
            $this->DBOBJ->WriteQuery($this->CREATE);
        }
    }
    
    function __destruct() {
        if (isset($this->DBOBJ)) { $this->DBOBJ->CloseConn(); }
        $this->DBOBJ = null;
    }
    
    protected function SelectDatabaseType() {
        if (function_exists("sqlite_open")) {
            return new PHPDriveSqlite();
        } else {
            return new PHPDriveSqlite3();
        }
    }

    public function GetFile($name=null) {
        if ($name == null) {
            $this->ERRMSG = "No file name provided";
            return false;
        }
        $rows = $this->DBOBJ->ReadQuery(sprintf($this->SELECTALL, $name));
        return $rows;
    }
    
    public function GetLatestFile($name=null) {
        if ($name == null) {
            $this->ERRMSG = "No file name provided";
            return false;
        }
        $rows = $this->DBOBJ->ReadQuery(sprintf($this->SELECTONE, $name));
        return $rows[0];
    }
    
    public function CreateFile($name=null, $data=null) {
        if ($name == null) {
            $this->ERRMSG = "Missing file name to create a file";
            return false;
        }
        if ($data == null) { $data = ""; }
        $res = $this->DBOBJ->WriteQuery(sprintf($this->INSERT,$name,$data,time(),time()));
        if ($res == false) {
            $this->ERRMSG = "Could not insert data";
            return false;
        }
        return true;
    }
    
    public function UpdateFile($name=null, $data=null) {
        if ($name == null) {
            $this->DBOBJ->ERRMSG = "Missing file name to create a file";
            return false;
        }
        if ($data == null) { $data = ""; }
        $file = $this->GetLatestFile($name);
        if (is_array($file) == false) {
            return $this->CreateFile($name, $data);
        }
        $res = $this->DBOBJ->WriteQuery(sprintf($this->INSERT,$name,$data,$file['CREATED'],time()));
        if ($res == false) {
            $this->ERRMSG = "Could not insert data";
            return false;
        }
        return true;
    }
    
    public function DeleteFile($name=null) {
        if ($name == null) {
            $this->ERRMSG = "Missing file name to delete a file";
            return false;
        }
        $res = $this->DBOBJ->WriteQuery(sprintf($this->DELETE, $name));
        if ($res == false) {
            $this->ERRMSG = "Could not delete file";
            return false;
        }
        return true;
    }
}

?>