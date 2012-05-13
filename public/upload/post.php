<?php

$tvivoVersionString = "Tvivo/1.0";

if( ini_get('safe_mode') )
{
    //error_log("PHP safe mode.");
}
else
{
    //error_log("PHP unsafe mode.");
}

//error_log("FILES Object: ".implode("--",$_FILES));
//error_log(print_r($_POST, 1));

$srvErrorPage = "http://".$_SERVER['SERVER_NAME']."/upload/error.php";
$sayToPicasa  = $srvErrorPage."?msg=".urlencode("Internal Error, unknown reason.");
$xmlMediaUrl  = "";
$curlresult   = "";

//Parse Form data
$dbmessage  = htmlspecialchars($_POST['hmessage'], ENT_QUOTES, 'UTF-8');
$message  = stripslashes($_POST['hmessage']);
$notweet  = $_POST['notweet'];
$username = $_POST['username'];
$password = $_POST['hpassword'];
$remember = $_POST['remember'];
//upload service selector
$postservice = $_POST['postservice'];

// store uploaded images by name
if($_FILES)
{
    // make a random directory name for storage
    $imgcache = "imgcache";
    $dirname = "/home/okertanov/public_html/tvivo.espectrale.com/public";

    $localuploadok = false;
    $serviceuploadok = false;

    foreach($_FILES as $key => $file)
    {
        if (!empty($file))
        {
            // for this demo we default to {MD5}.jpg (easier to make secure):
            $tmpfile  = $file['tmp_name'];
            $fdate = strftime("%Y%m%d%H%M%S");
            $fname = $fdate.'-'.md5_file($tmpfile).'.jpg';
            $localfn  = $dirname."/".$imgcache."/".$fname;

            if (move_uploaded_file($tmpfile, $localfn))
            {
                //error_log("move_uploaded_file ok.");
                chmod($localfn, 0644);
                $localuploadok = true;
            }
            else
            {
                //Local fileop fails
                error_log("Upload: Internal Error with move_uploaded_file: //$tmpfile//$localfn//");
            }
        }
        else
        {
            //Empty _FILES case
            error_log("Upload: Internal Error empty key/file: //$key//$file//");
        }
    }

    //Post
    if ( $localuploadok )
    {
        //Direct local image url
        $imgUrl = "http://".$_SERVER['SERVER_NAME']."/$imgcache/$fname";

        //Normalize Message
        $message = preg_replace("/^@/", " @", $message);

        //Prepare RST request
        $postfields = array();
        $postfields['username'] = $username;
        $postfields['password'] = $password;
        $postfields['message']  = $message;
        $postfields['media']    = '@'.$localfn;

        if ('Twitpic' == $postservice)
        {
            //Select the method
            if ( $notweet == '1' )
            {
                $method = 'http://twitpic.com/api/upload';
            }
            else
            {
                $method = 'http://twitpic.com/api/uploadAndPost';
            }
        }
        elseif ('yfrog' == $postservice)
        {
            //Select the method
            if ( $notweet == '1' )
            {
                $method = 'http://yfrog.com/api/upload';
            }
            else
            {
                $method = 'http://yfrog.com/api/uploadAndPost';
            }

            //yfrog developer key
            $postfields['key'] = '02DFNOTWbd30b84607cdc68ca3f31887b8e6ae6c';
        }
        else
        {
            //TODO: handle invalid upload service here
            $method = "";
        }

        //--------------------
        function DoUpload()
        {
            global $fdate, $method, $postfields, $serviceuploadok,  $sayToPicasa, $srvErrorPage;

            //DEDUG:
            $lhandle = fopen("/tmp/curl-$fdate.txt", "w");

            //Initialize and run the curl
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 35);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $method);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($curl, CURLOPT_VERBOSE, 1);
            curl_setopt($curl, CURLOPT_STDERR, $lhandle);

            $curlresult = curl_exec($curl);
            $curlError = curl_errno($curl);
            curl_close($curl);

            //DEBUG:
            fclose($lhandle);

            //Parse XML responce
            try
            {
                //Normalize xml "n" bug
                $curlresult = preg_replace("/\\n/", "", $curlresult);

                $rxml = new SimpleXMLElement($curlresult);

                $xmlStatus   = (string)$rxml['stat'];
                $xmlMediaUrl = (string)$rxml->mediaurl;
                $xmlErrCode  = (string)$rxml->err['code'];
                $xmlErrMsg   = (string)$rxml->err['msg'];

                //Post results back to Picasa
                if ($xmlStatus == 'fail')
                {
                    error_log("Post: Got valid XML responce with the FAIL state: ".$xmlStatus."//".$xmlErrCode."//".$xmlErrMsg."//".$xmlMediaUrl);
                    $sayToPicasa = $srvErrorPage."?code=$xmlErrCode&msg=".urlencode("$xmlErrMsg");
                }
                else
                {
                    //error_log("Post: OK.");
                    $serviceuploadok = true;
                    $sayToPicasa = $xmlMediaUrl;
                }
            }
            catch (Exception $e)
            {
                $xmlErrCode = '9990';
                error_log("Post: Error($curlError) parsing service XML responce: ".$curlresult."XML ($curlresult) error: ".$e->getMessage());
                $sayToPicasa =  $srvErrorPage."?msg=".urlencode("Error($curlError) parsing service XML responce.")." XML ($curlresult) error: ".$e->getMessage();
            }

            return array($serviceuploadok,$xmlErrCode);
        } //function DoUpload()
        //--------------------

        //Do the Upload
        list($r1,$r2) = DoUpload();
        if( !$r1 ) //if error
        {
            if( '1003' == $r2 || '9990' ==$r2 )
            {
                error_log("Post: Got Posting Error with the $r2 state - resubmitting...");
                list($r1s2,$r2s2) = DoUpload();
                if( !$r1s2 ) //if error
                {
                    if( '1003' == $r2s2 || '9990' ==$r2s2 )
                    {
                        error_log("Post: Got Posting Error with the $r2 state - resubmitting again...");
                        sleep(2);
                        list($r1s3,$r2s3) = DoUpload();
                    }
                }
            }
        }
    }
    else
    {
        //Handle upload to server error as the internal error
        error_log("Upload: Internal Error while uploading Image to server.");
        $sayToPicasa = $srvErrorPage."?msg=".urlencode("Internal Error while uploading Image to server.");
    }

}
else
{
    error_log("Upload: Error - nothing to Post or malformed Picasa request.");
    $sayToPicasa = $srvErrorPage."?msg=".urlencode("Error - Nothing to Post or malformed Picasa request.");
}

#Single REPLY Point
echo $sayToPicasa;

#Update DB
$dbhostname = "localhost";
$dbusername = "tvivo";
$dbpassword = "[password]";
$dbdatabase = "tvivodb";
$dbtable    = "uploads";

$dbFields = "username, password_md5, notweet, remember, message, uploadservice, remote_addr, local_filepath, service_filepath, picasa_ua, local_swmodel, local_upload_ok, service_upload_ok,  local_upload_status, service_upload_status";
$dbValues = "'$username'".", "."'".(strlen($password)?md5($password):"")."'".", ".
            ($notweet=='1'?1:0).", ".($remember=='1'?1:0).", ".
            "'$dbmessage'".", "."'$postservice'".", ".
            "'".$_SERVER['REMOTE_ADDR']."'".", "."'$fname'".", "."'$xmlMediaUrl'".", ".
            "'".$_SERVER['HTTP_USER_AGENT']."'".", "."'$tvivoVersionString'".", ".
            ($localuploadok?"'Y'":"'N'").", ".($serviceuploadok?"'Y'":"'N'").", ".
            "'$sayToPicasa'".", "."'$curlresult'";

$dbcon = mysql_connect($dbhostname,$dbusername,$dbpassword) or error_log("Database: Can't connect to $dbdatabase - ".mysql_error());
if ($dbcon)
{
    mysql_select_db($dbdatabase) or error_log("Database: Can't select - ".mysql_error());
    $dbquery = "INSERT INTO $dbtable ($dbFields) VALUES ($dbValues)";
    $dbres   = mysql_query($dbquery) or error_log("Database: Can't query - ".mysql_error());
    mysql_close($dbcon);
}

#Mail statistic
$rn = "\r\n";
$mailDate   = strftime("%c");
$DBG_SERVER = $_SERVER;
$DBG_POST   = $_POST;
$DBG_SERVER['HTTP_COOKIE'] = "*******";
$DBG_POST['hpassword']     = "*******";

$to   = "Espectrale Webmaster <webmaster@espectrale.com>";
$subj = "Tvivo activity: new post";
$hdr  = "Content-type: text/plain; charset=utf-8".$rn;
$hdr .= "Content-transfer-encoding: 8bit".$rn;
$hdr .= "From: Tvivo Mailer <www-data@crater.espectrale.com>".$rn;
$msg  = "$mailDate"." // ";
$msg .= "Post information:".$rn;
$msg .= "Upload status local/service: ".($localuploadok?"OK":"failed")."/".($serviceuploadok?"OK":"failed").$rn;
$msg .= "Database update: ".($dbres?"OK":"failed: ".mysql_error()).".".$rn;
$msg .= "Remote Host: ".gethostbyaddr($_SERVER['REMOTE_ADDR']).$rn;
$msg .= "".$rn;
$msg .= "https://twitter.com/$username";
$msg .= "    (".($notweet?"no twitt":"twitt").", ".($remember?"remember":"no remember").")".$rn;
$msg .= $message.$rn;
$msg .= "Uploading via ".$postservice.$rn;
$msg .= "".$rn;
$msg .= "$sayToPicasa".$rn;
$msg .= "$imgUrl".$rn;
$msg .= "".$rn;
/*
$msg .= "DEBUG information:".$rn;
$msg .= print_r($DBG_SERVER, 1).$rn;
$msg .= print_r($DBG_POST,   1).$rn;
*/

//if ( !$localuploadok || !$serviceuploadok )
{
    mail($to, $subj, $msg, $hdr) or error_log("Mail: Can't send mail to ".$to); 
}

?>
