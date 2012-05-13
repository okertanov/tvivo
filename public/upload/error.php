<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
require_once("xmlHandler.class");

function getStatusCodeMessage($status)
{
      $codes = Array(
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Long',
      415 => 'Unsupported Media Type',
      416 => 'Requested Range Not Satisfiable',
      417 => 'Expectation Failed',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Timeout',
      505 => 'HTTP Version Not Supported'
      );

      return (isset($codes[$status])) ? $codes[$status] : 'Unknown HTTP Error '.$status;
}
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<? virtual("/meta.ssi"); ?>
</head>

<body>
<div id="wrap">

<div id="top"></div>

<div id="content">

    <div class="header">
        <? virtual("/header.ssi"); ?>
    </div>

    <div class="breadcrumbs">
        <a href="/">Home</a> &middot; <a href="#">Error</a> &middot; <span class="quotation">&ldquo;Without music, life would be an error.&rdquo;</span>
    </div>

<div class="middle_upload">
<?
    if (isset($_GET['code']))
    {
        if ( (int)$_GET['code'] > 1000  )
        {
            echo '<h2>Upload service error:</h2>';
            echo '<div class="errormsg">'.strip_tags($_GET['code'])." -- ".strip_tags($_GET['msg']).'</div>';
        }
        else
        {
            echo '<h2>HTTP error:</h2>';
            echo '<div class="errormsg">'.strip_tags($_GET['code'])." -- ".getStatusCodeMessage((int)$_GET['code']).'</div>';
        }
    }
    else
    {
        echo '<h2>Internal service error:</h2>';
        echo '<div class="errormsg">'.strip_tags($_GET['msg']).'</div>';
    }
?>
</div> <!--middle-->

<div class="right_upload">
</div>

<div id="clear"></div>
</div>

<div id="bottom"></div>

</div> <!-- Wrap -->

<div id="footer"><? virtual("/footer.ssi"); ?></div>

<? virtual("/google-analytics.ssi"); ?>

</body>
</html>
