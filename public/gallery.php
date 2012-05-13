<?php // UberGallery by, Chris Kankiewicz (http://www.ubergallery.net)

// Customize your gallery by changing the following variables. If a variable
// is contained within quotes make sure you don't delete the quotes.

$galleryTitle   = "@Tvivo Gallery";             // Gallery title
$galleryDir     = "imgcache";           // Original images directory (No trailing slash!)
$thumbsDir      = "imgcache/thumbs";            // Thumbnails directory (No trailing slash!)
$cacheDir       = "imgcache/cache";
$logFile        = "../log/gallery-log.txt";     // Directory/Name of log file
$thumbSize      = 100;                          // Thumbnail width/height in pixels
$imgPerPage     = 64;                           // Images per page (0 disables pagination)
$cacheExpire    = 5;                            // Frequency (in minutes) of cache refresh
$formatted      = true;                         // Set to false to output bare (no styles) page
$verCheck       = false;                        // Set to true to enable update notifications


// *** DO NOT EDIT ANYTHING BELOW HERE UNLESS YOU ARE A PHP NINJA ***

$version = "1.6.1"; // Gallery version

if ($_GET['page']) {
    // Sanitize input and set current page
    $currentPage = (integer) $_GET['page'];
} else {
    $currentPage = 1;
}


// Create log file if it does not exist, otherwise open log for writing
if (!file_exists($logFile)) {
    $log = fopen($logFile, "a");
    fwrite($log,date("Y-m-d")." @ ".date("H:i:s")."  CREATED: $logFile\r\n\r\n");
} else {
    $log = fopen($logFile, "a");
}

// Create image directory if it doesn't exist
if (!file_exists($galleryDir)) {
    mkdir($galleryDir);
    fwrite($log,date("Y-m-d")." @ ".date("H:i:s")."  CREATED: $galleryDir\r\n");
}

// Create thumbnail directory if it doesn't exist
if (!file_exists($thumbsDir)) {
    mkdir($thumbsDir);
    fwrite($log,date("Y-m-d")." @ ".date("H:i:s")."  CREATED: $thumbsDir\r\n");
}

// Clean up thumbnail directory
if ($dirHandle = opendir($thumbsDir)) {
    while (($file = readdir($dirHandle)) !== false) {
        if (isImage("$thumbsDir/$file")) {
            $size = getimagesize("$thumbsDir/$file");
            if (!file_exists("$galleryDir/$file") || $size[0] !== $thumbSize) {
                unlink("$thumbsDir/$file");
                fwrite($log,date("Y-m-d")." @ ".date("H:i:s")."  REMOVED: $thumbsDir/$file\r\n");
            }
        }
    }
    closedir($dirHandle);
}

// Alcohol! The cause of, and solution to, all of life's problems!

// Create array from gallery directory
if ($dirHandle = opendir($galleryDir)) {
    while (($file = readdir($dirHandle)) !== false) {
        if (isImage("$galleryDir/$file")) {
            $images[] = $file;
        }
    }
    closedir($dirHandle);

    arsort($images, SORT_NUMERIC);
}

// Page varriables
$totalImages = count($images);
if ($imgPerPage <= 0 || $imgPerPage >= $totalImages) {
    $imgStart = 0;
    $imgEnd = $totalImages;
    $totalPages = 1;
} elseif ($imgPerPage > 0 && $imgPerPage < $totalImages) {
    $totalPages = ceil($totalImages / $imgPerPage);
    if ($_GET['page'] < 1) {
        $currentPage = 1;
    } elseif ($_GET['page'] > $totalPages) {
        $currentPage = $totalPages;
    } else {
        $currentPage = (integer) $_GET['page'];
    }
    $imgStart = ($currentPage - 1) * $imgPerPage;
    $currentPage * $imgPerPage > $totalImages ? $imgEnd = $totalImages : $imgEnd = $currentPage * $imgPerPage;
}

?>

<?php if($formatted): ?>
<?php substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? ob_start("ob_gzhandler") && $gzip = 1 : ob_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title><?php echo $galleryTitle; ?> | Powered by, UberGallery</title>
  <link rel="shortcut icon" href="images/images.png" />
  <link rel="stylesheet" type="text/css" href="css/ubergallery.css" />
  <link rel="stylesheet" type="text/css" href="css/colorbox.css" />
  <script type="text/javascript" src="js/jquery.js"></script>
  <script type="text/javascript" src="js/jquery.colorbox.js"></script>
  <script type="text/javascript">
  $(document).ready(function(){
    $("a[rel='colorbox']").colorbox({maxWidth: "90%", maxHeight: "90%", opacity: ".5"});
    <?php if($_GET['img']): ?>
      $('#img-<?php echo (integer) $_GET['img']; ?>').click();
    <?php endif; ?>
  });
  </script>
</head>
<?php flush() ?>
<body>
<?php endif; ?>

<?php
// *** START PAGE CACHING ***

// Create cache directory if it doesn't exist
if (!file_exists($cacheDir) && $cacheExpire > 0) {
    mkdir($cacheDir);
}
$cacheFile = "$cacheDir/page$currentPage-cached.html";
$cacheTime = $cacheExpire * 60;

// Serve from the cache if it is younger than $cacheTime
if (file_exists($cacheFile) && time() - $cacheTime < filemtime($cacheFile) && $cacheExpire > 0) {
    include($cacheFile);
    echo "<!-- Cached page: created ".date('H:i:s', filemtime($cacheFile))." / expires ".date('H:i:s', (filemtime($cacheFile)) + $cacheTime)." -->\n";
} else {
    ob_start();

    // Opening markup
    echo("<!-- Start UberGallery v$version - Created by, Chris Kankiewicz <http://www.ChrisKankiewicz.com> -->\r\n");
    echo("<div id=\"gallery-wrapper\">\r\n");
    echo("  <div id=\"gallery-constraint\">\r\n");
    echo("    <div id=\"gallery-header\">$galleryTitle</div>\r\n");
    echo("    <div id=\"gallery-images\" class=\"clearfix\">\r\n");

    for ($x = $imgStart; $x < $imgEnd; $x++) {
        $filePath = "$galleryDir/$images[$x]";

        // Convert file name and extension for processing
        if (ctype_upper(pathinfo($filePath,PATHINFO_EXTENSION))
        || strpos(basename($filePath),' ') !== false
        || strpos($filePath,'.jpeg') !== false) {

            $source = "$filePath";
            $fileParts = pathinfo($filePath); // Create array of file parts
            $ext = $fileParts['extension']; // Original extension
            $name = basename($filePath, ".$ext"); // Original file name without extension
            $dir = $fileParts['dirname']; // Directory path

            // Change extension to all lowercase
            if (ctype_upper($ext)) {
                $ext = strtolower($ext);
            }

            // Convert .jpeg to .jpg
            if ($ext == 'jpeg') {
                $ext = 'jpg';
            }

            // Replace spaces with underscores
            if (strpos($name,' ') !== false) {
                $extOld = $fileParts['extension'];
                $name = str_replace(' ','_',basename($filePath, ".$extOld"));
            }

            $destination = "$dir/$name.$ext";

            // Rename file and array element
            if (rename($source,"$dir/$name.tmp")) {
                if (rename("$dir/$name.tmp",$destination)) {
                    $images[$x] = "$name.$ext";
                    fwrite($log,date("Y-m-d")." @ ".date("H:i:s")."  RENAMED: $source to $destination\r\n");
                }
            }
        }

        $filePath = "$galleryDir/$images[$x]";
        $thumbPath = "$thumbsDir/$images[$x]";

        // Create thumbnail if it doesn't already exist
        if (!file_exists("$thumbPath")) {
            createThumb("$filePath","$thumbPath",$thumbSize);
            fwrite($log,date("Y-m-d")." @ ".date("H:i:s")."  CREATED: $thumbsDir/$images[$x]\r\n");
        }
        // Create XHTML compliant markup
        $noExt = substr($images[$x],0,strrpos($images[$x],'.'));
        $altText = str_replace("_"," ",$noExt);
        echo "      <a href=\"$filePath\" title=\"$altText\" id=\"img-$x\" rel=\"colorbox\"><img src=\"$thumbPath\" alt=\"$altText\"/></a>\r\n";
    }
    echo("    </div>\r\n");

    // Version check and notification
    if ($verCheck) {
        $verInfo = @file("http://www.ubergallery.net/version-check.php?ver=$version");
        $verInfo = @implode($verInfo);
        if ($verInfo == "upgrade") {
            echo("    <div id=\"uber-notice\" class=\"clearfix\">A new version of UberGallery is availabe. <a href=\"http://www.ubergallery.net\" target=\"_blank\">Get the latest version here</a>.</div>");
        } elseif ($verInfo == "development") {
            echo("    <div id=\"uber-notice\" class=\"clearfix\">This is a development version of UberGallery.</div>\r\n");
        }
    }

    echo("    <div id=\"uber-footer\" class=\"clearfix\">\r\n");

    // If pagination enabled, create page navigation
    if ($imgPerPage > 0 && $imgPerPage < $totalImages) {
        $pageName = basename($_SERVER["PHP_SELF"]); // Get current page file name
        echo("      <ul id=\"uber-pagination\" style=\"margin: 0 !important; padding: 0 !important;\">\r\n");

        // Pageination title
        echo("        <li class=\"title\">Page $currentPage of $totalPages</li>\r\n");

        // Previous arrow
        $previousPage = $currentPage - 1;
        echo("        <li".($currentPage > 1 ? "><a href=\"$pageName?page=$previousPage\" title=\"Previous Page\">&lt;</a>" : " class=\"inactive\">&lt;")."</li>\r\n");

        // Page links
        for ($x = 1; $x <= $totalPages; $x++) {
            echo("        <li".($x == $currentPage ? " class=\"current-page\">$x" : "><a href=\"$pageName?page=$x\" title=\"Page $x\">$x</a>")."</li>\r\n");
        }

        // Next arrow
        $nextPage = $currentPage + 1;
        echo("        <li".($currentPage < $totalPages ? "><a href=\"$pageName?page=$nextPage\" title=\"Next Page\">&gt;</a>" : " class=\"inactive\">&gt;")."</li>\r\n");

        echo("      </ul>\r\n");
    }

    // Closing markup
    echo("      <div id=\"credit\">Powered by, <a href=\"http://www.ubergallery.net\">UberGallery</a></div>\r\n");

    echo("    </div>\r\n");
    echo("  </div>\r\n");
    echo("</div>\r\n");
    echo("<!-- Page $currentPage of $totalPages -->\r\n");

    echo("<!-- End UberGallery - Licensed under the MIT License <http://creativecommons.org/licenses/MIT/> -->\r\n");

    fclose($log); // Close log

    if ($cacheExpire > 0) {
        // Cache the output to a file
        $fp = fopen($cacheFile, 'w');
        fwrite($fp, ob_get_contents());
        fclose($fp);
        ob_end_flush(); // Send the output to the browser
    }
}

?>

<?php if($formatted): ?>
<?php if ($gzip == "1") echo("<!-- Page served with gzip compression -->\r\n"); ?>
</body>
</html>
<?php endif; ?>

<?php
// *** START FUNCTIONS ***

function createThumb($source,$dest,$thumb_size) {
    // Create thumbnail, modified from function found on http://www.findmotive.com/tag/php/
    $size = getimagesize($source);
    $width = $size[0];
    $height = $size[1];

    if ($width > $height) {
        $x = ceil(($width - $height) / 2 );
        $width = $height;
    } elseif($height > $width) {
        $y = ceil(($height - $width) / 2);
        $height = $width;
    }

    $new_im = ImageCreatetruecolor($thumb_size,$thumb_size);

    @$imgInfo = getimagesize($source);

    if ($imgInfo[2] == IMAGETYPE_JPEG) {
        $im = imagecreatefromjpeg($source);
        imagecopyresampled($new_im,$im,0,0,$x,$y,$thumb_size,$thumb_size,$width,$height);
        imagejpeg($new_im,$dest,75); // Thumbnail quality (Value from 1 to 100)
    } elseif ($imgInfo[2] == IMAGETYPE_GIF) {
        $im = imagecreatefromgif($source);
        imagecopyresampled($new_im,$im,0,0,$x,$y,$thumb_size,$thumb_size,$width,$height);
        imagegif($new_im,$dest);
    } elseif ($imgInfo[2] == IMAGETYPE_PNG) {
        $im = imagecreatefrompng($source);
        imagecopyresampled($new_im,$im,0,0,$x,$y,$thumb_size,$thumb_size,$width,$height);
        imagepng($new_im,$dest);
    }
}

function isImage($fileName) {
    // Verifies that a file is an image
    if ($fileName !== '.' && $fileName !== '..') {
        @$imgInfo = getimagesize($fileName);

        $imgType = array(
            IMAGETYPE_JPEG,
            IMAGETYPE_GIF,
            IMAGETYPE_PNG,
        );

        if (in_array($imgInfo[2],$imgType))
        return true;
        return false;
    }
}

// EOF

?>
