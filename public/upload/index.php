<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
require_once("xmlHandler.class");
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<? virtual("/meta.ssi"); ?>

<script type="text/javascript">

//var gPostServices = new Array("Twitpic","yfrog");
var gPostServices = new Array("yfrog");

function setfocus()
{
    document.getElementById('message').focus();
}

function limittext(limitField, limitCount, limitNum) {
    if (limitField.value.length > limitNum) {
        limitField.value = limitField.value.substring(0, limitNum);
    } else {
        limitCount.value = limitNum - limitField.value.length;
    }
}

function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}

function beforeLoad(valie)
{
    setfocus();

    rewriteLinks();

    var username = readCookie('username');
    var password = readCookie('password');
    var remember = readCookie('remember');
    var postservice = readCookie('postservice');

    if (username!=null && username!="")
    {
        document.uploadform.username.value = username;
    }
    if (password!=null && password!="")
    {
        document.uploadform.password.value = password;
    }
    if (remember!=null && remember!="" && remember)
    {
        document.uploadform.remember.checked = remember;
    }
    if (postservice!=null && postservice!="")
    {
        if(postservice == "Twitpic")
        {
            eraseCookie('postservice');
            document.uploadform.postservice.value = gPostServices[0];
        }
        document.uploadform.postservice.value = postservice;
    }
    else
    {
        document.uploadform.postservice.value = gPostServices[0]; //Default post service
    }

    setInnerText(document.getElementById('label-post-service'),  document.uploadform.postservice.value);
}

function beforeSubmit(value)
{
    document.uploadform.hpassword.value =
        document.uploadform.password.value;

    document.uploadform.hmessage.value =
        document.uploadform.message.value;

    document.uploadform.postservice.value =
        getInnerText(document.getElementById('label-post-service'));

    if (document.uploadform.remember.checked)
    {
        //set cookies
        createCookie('username', document.uploadform.username.value,   365);
        createCookie('password', document.uploadform.password.value,   365);
        createCookie('remember', document.uploadform.remember.checked, 365);
        createCookie('postservice', document.uploadform.postservice.value, 365);
    }
    else
    {
        //reset cookies
        eraseCookie('username');
        eraseCookie('password');
        eraseCookie('remember');
        eraseCookie('postservice');
    }

    return true;
}

function rewriteLinks(value)
{
    var links = document.getElementsByTagName("a");

    for (var i=0; i<links.length; i++)
    {
        if (links[i].href.match(/.*tvivo.espectrale\.com.*/gi))
        {
            links[i].target = "_blank";
        }
    }
}

function getInnerText(obj)
{
    var hasInnerText =
        (document.getElementsByTagName("body")[0].innerText != undefined) ? true : false;

    if (hasInnerText)
    {
        return obj.innerText;
    }
    else
    {
        return obj.textContent;
    }
}

function setInnerText(obj, str)
{
    var hasInnerText =
        (document.getElementsByTagName("body")[0].innerText != undefined) ? true : false;

    if (hasInnerText)
    {
        obj.innerText = str;
    }
    else
    {
        obj.textContent = str;
    }
}

function changePostService(obj)
{
    var idx = 0;
    var currValue = getInnerText(obj);

    for (key in gPostServices)
    {
        idx++;
        if (currValue == gPostServices[key])
        {
            if (idx >= gPostServices.length)
            {
                idx = 0;
            }
            break;
        }
    }

    setInnerText(obj, gPostServices[idx]);
}

</script>

</head>

<body onload="javscript:beforeLoad()">
<div id="wrap">

<div id="top"></div>

<div id="content">

    <div class="header">
        <? virtual("/header.ssi"); ?>
    </div>

    <div class="breadcrumbs">
        <a href="/">Home</a> &middot; <span class="quotation">&ldquo;We must become the change we want to see.&rdquo;</span>
    </div>

<div class="middle_upload">
<!-- **************************************************************** -->
<form name='uploadform' method='post' action='post.php' onSubmit="beforeSubmit();">
<table width="99%">
<tr><td valign="top">
<h2 style="color:red;">Twitpic appears to be blocking network traffic from <span style="color:navy;">@tvivo</span> for some unknown reason. Please use <span style="color:#FF8C00;">yFrog</span> instead. Sorry for any inconvenience.</h2>
<h2>The Image to post with <label id="label-post-service" for="post-service" style="cursor: pointer;color:#3198d3;border-bottom: 1px dashed;" title="Click to change" accesskey="A" onclick="changePostService(this);">yfrog</label></h2>

<div><textarea name="message" id="message" rows=4 cols=38 tabindex="1" onKeyDown="limittext(this.form.message,this.form.countdown,100);"
onKeyUp="limittext(this.form.message,this.form.countdown,100);"></textarea><br />
<input type="hidden" name="hmessage" />
<input name="postservice" id="post-service" type="hidden" />
<input readonly type="text" name="countdown" size="3" value="100" /><font size="2"> characters left.</font>
<input type="checkbox" name="notweet" value="1" tabindex="2" /> Do not post to Twitter
</div>

<div>
<?

if($_POST['rss'])
{
    $xh = new xmlHandler();
    $nodeNames = array("PHOTO:THUMBNAIL", "PHOTO:IMGSRC", "TITLE");
    $xh->setElementNames($nodeNames);
    $xh->setStartTag("ITEM");
    $xh->setVarsDefault();
    $xh->setXmlParser();
    $xh->setXmlData(stripslashes($_POST['rss']));
    $pData = $xh->xmlParse();
    $br = 0;

    // Preview "tray": draw shadowed square thumbnails of size 48x48
    foreach($pData as $e) {
        echo "<img src='".$e['photo:imgsrc']."?size=320'>\r\n";
        break; //first one
    }

    // Image request queue: add image requests for base image & clickthrough
    foreach($pData as $e) {
        // use a thumbnail if you don't want exif (saves space)
        // thumbnail requests are clamped at 144 pixels
        // (negative values give square-cropped images)
        $small = $e['photo:thumbnail']."?size=120";
        $large = $e['photo:imgsrc']."?size=800";

        //echo '<input type=hidden name='.'"'.$small.'"'.' />';
        echo '<input type="hidden" name='.'"'.$large.'"'.' />';

        break; //first one
    }
}
else
{
    echo "Sorry, but no pictures were received.";
}
?>
</div>

<div class='h'>
<input type=submit value="Publish" tabindex="6" />&nbsp;
<input type=button value="Cancel" onclick="javascript:document.location.href='minibrowser:close';" tabindex="7" />
<br/>
</div>
</td>
<td width="15%"></td>
<td valign="top">
<h2>Twitter Login</h2>
<div class="logon">
<font size="2">Username</font> <br /><input type="text" name="username" maxlength="30" tabindex="3" /> <br />
<font size="2">Password</font> <br /><input type="password" name="password" maxlength="30" tabindex="4" /> <br />
<input type="hidden" name="hpassword" />
<input type="checkbox" name="remember" value="1" tabindex="5" />
<font size="2">Remember me next time</font> </div>
<div><font size="1">* We do not store your password or any other information on our server.</font></div>

<br />
<div class="ppinner">
<a href="http://tvivo.espectrale.com/contacts.html" target="_blank" title="PayPal - The safer, easier way to pay online."><img src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG_global.gif" border="0" alt="PayPal - The safer, easier way to pay online." /></a>
</div>

</td></tr>
</table>

</form>
<!-- **************************************************************** -->
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
