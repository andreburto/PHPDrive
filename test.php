<?php

header("Content-type: text/plain");

include_once("PHPDrive.php");

function check_test($val1, $val2, $msg) {
    global $count;
    try {
        if ($val1 !== $val2) { throw new Exception($msg); }
        printf("Success %s.\n", $count);
        return true;
    } catch (Exception $ex) {
        printf("Fail %s: %s.\n", $count, $ex->getMessage());
        return false;
    }
}

// TEST: Create file
function test1() {
    global $pd;
    return check_test(is_file($pd->FILE), true, "Database file does not exist");
}

// TEST: New file
function test2() {
    global $pd;
    return check_test($pd->CreateFile("test", "1"), true, "Could not create file");
}

// TEST: File content
function test3() {
    global $pd;
    $file1 = $pd->GetLatestFile("test");
    return check_test($file1['DATA'], "1", "File content fail");
}

// TEST: Update file
function test4() {
    global $pd;
    sleep(1);
    return check_test($pd->UpdateFile("test", "2"), true, "Update content fail");
}

// TEST: File content
function test5() {
    global $pd;
    $file2 = $pd->GetLatestFile("test");
    return check_test($file2['DATA'], "2", "Updated file content fail");
}

// TEST: File version count
function test6() {
    global $pd;
    $file3 = $pd->GetFile("test");
    return check_test(count($file3), 2, "Not all versions present");
}

// TEST: Delete file
function test7() {
    global $pd;
    return check_test($pd->DeleteFile("test"), true, "Could not delete");
}

// TEST SET 1
$pd = new PHPDrive("test1", PHPDRIVE_SQLITE);
$count = 0;
printf("Test sqlite\n");
// Perform tests
while(true) {
    $count++;
    $f = sprintf("test%s", $count);
    if (function_exists($f) == false) { break; }
    call_user_func($f);
}


// TEST SET 2
$pd = new PHPDrive("test2", PHPDRIVE_SQLITE3);
$count = 0;
printf("Test Sqlite3\n");
// Perform tests
while(true) {
    $count++;
    $f = sprintf("test%s", $count);
    if (function_exists($f) == false) { break; }
    call_user_func($f);
}

// Delete the file
//unlink($pd->FILE);

?>