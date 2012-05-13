<?php

$dbhostname = "localhost";
$dbusername = "tvivo";
$dbpassword = "[password]";
$dbdatabase = "tvivodb";
$dbtable    = "downloadstat";

if (!isset($_REQUEST["file"]))
{
    error_log("Statistic: Invalid request.");
    header("HTTP/1.0 404 Not Found");
    exit;
}

//$filename = mysql_real_escape_string($_REQUEST['file']);
$filename = $_REQUEST['file'];
$serverpath = $_SERVER['DOCUMENT_ROOT']."/"; //path of this file
$fullPath = $serverpath.$filename; //path to download file

$filetypes = array("png","pbz");

$filetype = substr($filename, -3);

function curPageURL()
{
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80")
    {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
    }
    else
    {
        $pageURL .= $_SERVER["SERVER_NAME"];
    }
    return $pageURL;
}

function reportHttpError($ec)
{
    $_GET['code'] = $ec;
    $spath = $_SERVER['DOCUMENT_ROOT']."/"; //path of this file
    include($spath."/upload/error.php");
}

if (! in_array($filetype, $filetypes))
{
    error_log("Statistic: Invalid download type ".$filetype);
    header("HTTP/1.0 404 Not Found");
    reportHttpError(404);
    exit;
}

if ( preg_match("/\.\./i", $fullPath ) )
{
    error_log("Statistic: Invalid path symbols ".$fullPath);
    header("HTTP/1.0 404 Not Found");
    reportHttpError(404);
    exit;
}


if(! file_exists($fullPath))
{
    error_log("Statistic: File not exists ".$fullPath);
    header("HTTP/1.0 404 Not Found");
    reportHttpError(404);
    exit;
}

if ($fd = fopen ($fullPath, "r"))
{

    $dbcon = mysql_connect($dbhostname,$dbusername,$dbpassword) or error_log("Statistic: Can't connect to $dbdatabase - ".mysql_error());
    if ($dbcon)
    {
        mysql_select_db($dbdatabase) or error_log("Statistic: Can't select - ".mysql_error());

        //add download stat
        $result = mysql_query("SELECT COUNT(*) AS countfile FROM $dbtable WHERE filename='" . $filename . "'");
        $data = mysql_fetch_array($result);
        $q = "";

        if ($data['countfile'] > 0)
        {
            $q = "UPDATE $dbtable SET stats = stats + 1 WHERE filename = '" . $filename . "'";
        }
        else
        {
            $q = "INSERT INTO $dbtable (filename, stats) VALUES ('" . $filename . "', 1)";
        }

        $statresult = mysql_query($q);

        mysql_close($dbcon);
    }

    //the next part outputs the file
    $fsize = filesize($fullPath);
    $path_parts = pathinfo($fullPath);

    header("Content-type: application/octet-stream");
    header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
    header("Content-length: $fsize");
    header("Pragma: no-cache");// HTTP/1.0
    header("Cache-Control: no-cache, must-revalidate");// HTTP/1.1

    while(!feof($fd)) {
        $buffer = fread($fd, 2048);
        echo $buffer;
    }

    fclose ($fd);
}
else
{
    error_log("Statistic: Can't open file ".$fullPath);
    header("HTTP/1.0 404 Not Found");
    reportHttpError(404);
    exit;
}

?>

