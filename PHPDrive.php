<?php

interface IPHPDriveDatabase {
    public function WriteQuery($sql=null);
    public function ReadQuery($sql=null);
    public function OpenConn($db=null);
    public function CloseConn();
}

class PHPDriveSqlite implements IPHPDriveDatabase {
    public $CONN;
    public $ERRMSG;

    protected $SELECTALL = "SELECT * FROM fs WHERE FILE = '%s' ORDER BY UPDATED DESC";
    protected $SELECTONE = "SELECT * FROM fs WHERE FILE = '%s' ORDER BY UPDATED DESC LIMIT 1";
    protected $CREATE = "CREATE TABLE fs (FILE TEXT,DATA TEXT,CREATED DATETIME,UPDATED DATETIME)";
    protected $INSERT = "INSERT INTO fs (FILE,DATA,CREATED,UPDATED) VALUES ('%s','%s',%s,%s)";
    protected $DELETE = "DELETE FROM fs WHERE FILE = '%s'";

    function __construct($database=null) {

    }

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

    public function OpenConn($db=null) {
        $this->CONN = sqlite_open($db, 0666);
    }

    public function CloseConn() {
        sqlite_close($this->CONN);
    }
}

class PHPDrive {
    // Class-wide variables
    public $TYPE;
    public $FILE;
    public $CONN;
    public $ERRMSG;
    
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
            $this->TYPE = $this->SelectDatabaseType();
        } else {
            $this->TYPE = $dbtype;
        }
        // Check for the file
        if (is_file($this->FILE)) {
            // Connect to the database
            $this->OpenConn($this->FILE);
        } else {
            // Connect to the database
            $this->OpenConn($this->FILE);
            // Create the table
            $this->WriteQuery($this->CREATE);
        }
    }
    
    function __destruct() {
        if (isset($this->CONN)) { $this->CloseConn(); }
    }
    
    protected function SelectDatabaseType() {
        if (function_exists("sqlite_open")) {
            return "sqlite";
        } else {
            return "Sqlite3";
        }
    }
    
    protected function WriteQuery($sql=null) {
        if ($this->TYPE == "sqlite") {
            $res = sqlite_exec($this->CONN, $sql);
            if ($res == false) {
                $this->ERRMSG = "Sqlite2 could not write data";
                return false;
            }
        } else {
            $res = $this->CONN->exec($sql);
            if ($res == false) {
                $this->ERRMSG = "Sqlite3 could not write data";
                return false;
            }
        }
        return $res;
    }
    
    protected function ReadQuery($sql=null) {
        $rows = array();
        if ($this->TYPE == "sqlite") {
            $res = sqlite_query($this->CONN, $sql, SQLITE_ASSOC, $this->ERRMSG);
            if ($this->ERRMSG) { return false; }
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
        } else {
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
        }
        return $rows;
    }
    
    protected function OpenConn() {
        if ($this->TYPE == "sqlite") {
            $this->CONN = sqlite_open($this->FILE, 0666);
        } else {
            $this->CONN = new Sqlite3($this->FILE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        }
    }
    
    protected function CloseConn() {
        if ($this->TYPE == "sqlite") {
            sqlite_close($this->CONN);
        } else {
            $this->CONN->close();
        }
        $this->CONN = null;
    }
    
    public function GetFile($name=null) {
        if ($name == null) {
            $this->ERRMSG = "No file name provided";
            return false;
        }
        $rows = $this->ReadQuery(sprintf($this->SELECTALL, $name));
        return $rows;
    }
    
    public function GetLatestFile($name=null) {
        if ($name == null) {
            $this->ERRMSG = "No file name provided";
            return false;
        }
        $rows = $this->ReadQuery(sprintf($this->SELECTONE, $name));
        return $rows[0];
    }
    
    public function CreateFile($name=null, $data=null) {
        if ($name == null) {
            $this->ERRMSG = "Missing file name to create a file";
            return false;
        }
        if ($data == null) { $data = ""; }
        $res = $this->WriteQuery(sprintf($this->INSERT,$name,$data,time(),time()));
        if ($res == false) {
            $this->ERRMSG = "Could not insert data";
            return false;
        }
        return true;
    }
    
    public function UpdateFile($name=null, $data=null) {
        if ($name == null) {
            $this->ERRMSG = "Missing file name to create a file";
            return false;
        }
        if ($data == null) { $data = ""; }
        $file = $this->GetLatestFile($name);
        if (is_array($file) == false) {
            return $this->CreateFile($name, $data);
        }
        $res = $this->WriteQuery(sprintf($this->INSERT,$name,$data,$file['CREATED'],time()));
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
        $res = $this->WriteQuery(sprintf($this->DELETE, $name));
        if ($res == false) {
            $this->ERRMSG = "Could not delete file";
            return false;
        }
        return true;
    }
}

?>