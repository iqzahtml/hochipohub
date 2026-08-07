<?php
/*
|--------------------------------------------------------------------------
| HochipoHub Database Connection
|--------------------------------------------------------------------------
| This file is included by:
| - includes/header.php
| - auth/*
| - seller/*
| - admin/*
| - ajax/*
| - root pages
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';

/*
|--------------------------------------------------------------------------
| MySQLi Connection
|--------------------------------------------------------------------------
*/

$conn = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

/*
|--------------------------------------------------------------------------
| Connection Error
|--------------------------------------------------------------------------
*/

if ($conn->connect_errno) {

    if (DEVELOPMENT_MODE) {

        die(
            "Database Connection Failed : "
            . $conn->connect_error
        );

    } else {

        die(
            "Unable to connect to database."
        );

    }

}

/*
|--------------------------------------------------------------------------
| Character Set
|--------------------------------------------------------------------------
*/

$conn->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/*
| Execute SELECT query
*/

function dbSelect($sql)
{
    global $conn;

    return $conn->query($sql);
}

/*
| Execute INSERT / UPDATE / DELETE
*/

function dbExecute($sql)
{
    global $conn;

    return $conn->query($sql);
}

/*
| Escape String
*/

function dbEscape($value)
{
    global $conn;

    return $conn->real_escape_string($value);
}

/*
| Last Insert ID
*/

function dbInsertId()
{
    global $conn;

    return $conn->insert_id;
}

/*
| Number of affected rows
*/

function dbAffectedRows()
{
    global $conn;

    return $conn->affected_rows;
}

/*
| Begin Transaction
*/

function dbBegin()
{
    global $conn;

    $conn->begin_transaction();
}

/*
| Commit
*/

function dbCommit()
{
    global $conn;

    $conn->commit();
}

/*
| Rollback
*/

function dbRollback()
{
    global $conn;

    $conn->rollback();
}

/*
|--------------------------------------------------------------------------
| Prepared Statement Helper
|--------------------------------------------------------------------------
*/

function dbPrepare($sql)
{
    global $conn;

    return $conn->prepare($sql);
}