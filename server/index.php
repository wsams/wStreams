<?php

// See LICENSE for licensing.

/**
 * Config
 */

require_once("../../wStreamsConfig/config.php");

/**
 * Functions
 */

function getMusicTypes() {
    $types = $GLOBALS['musicTypes']; 
    foreach ( $types as $k=>$type ) {
        $types[] = strtoupper($type);
        $types[] = strtolower($type);
    }
    return array_unique($types);
}

function getAlbumCoverTypes() {
    $types = $GLOBALS['albumCovers']; 
    foreach ( $types as $k=>$type ) {
        $types[] = strtoupper($type);
        $types[] = strtolower($type);
    }
    return array_unique($types);
}

function safeDir($dir) {
    if ( preg_match("/\.\./", $dir) ) {
        return false;
    }
    return true;
}

function json($input) {
    //return json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return preg_replace("/\\\\\//", "/", json_encode($input));
}

function escapeJs($input) {
    return preg_replace("/'/", "&#39;", $input);
}

/**
 * @returns Returns a byte in human readble form.
 */
function humanFilesize($size){
    if( is_file($size) ){
        $size = filesize($size);
    }
    if( $size == 0 ){
        $size = 1;
    }
    $filesizename = array("bytes", "kb", "mb", "gb", "tb", "pb", "eb", "zb", "yb");
    return round($size/pow(1000, ($i = floor(log($size, 1000)))), 2) . $filesizename[$i];
}

function tmp($tmplloc, $a_tmpldata) {
    $a_tmpl = file($tmplloc);

    foreach ($a_tmpl as $key=>$line) {
        foreach ($a_tmpldata as $tmplvar=>$tmplval) {
            $a_tmpl[$key] = rtrim(preg_replace("/;:".preg_quote($tmplvar,"/").":;/i",$tmplval,$a_tmpl[$key]),"\n");
        }

        ### Set variables not entered to nothing so they're not shown in the HTML.
        if (preg_match("/;:.*:;/",$a_tmpl[$key])) {
            $a_tmpl[$key] = preg_replace("/;:.*?:;/","",$a_tmpl[$key]);
        }

        if (preg_match("/ *<!--.*?--> */",$a_tmpl[$key])) {
            $a_tmpl[$key] = preg_replace("/ *<!--.*?--> */","",$a_tmpl[$key]);
        }
    }

    return implode("\n",$a_tmpl);
}

/**
 * @returns Returns an array of files for a directory.
 */
function getFileIndex($dir) {
    $output = Array();
    $curdir = getcwd();
    chdir($GLOBALS['musicDir'] . "/" . $dir);
    $musicTypes = implode(",", getMusicTypes());
    $a_files = glob("*.{" . $musicTypes . "}", GLOB_BRACE);
    foreach ( $a_files as $k=>$file ) {
        $index = Array();
        $index['file'] = $file;
        $filesize = humanFilesize($file);
        $index['filesize'] = $filesize; 
        $displayFile = preg_replace("/^(.+)\..+$/i", "\${1}", $file);
        $displayFile = preg_replace("/([a-z])([A-Z])/", "\${1} \${2}", $displayFile);
        $displayFile = preg_replace("/_+/", " ", $displayFile);
        $index['displayFile'] = $displayFile;
        $index['url'] = $GLOBALS['musicUrl'] . "/" . $dir . "/" . $file;
        $output[] = $index;
    }
    chdir($curdir);
    return $output;
}

function getDirIndex($dir) {
    $output = Array();
    $curdir = getcwd();
    chdir($GLOBALS['musicDir'] . "/" . $dir);
    $a_files = glob("*", GLOB_ONLYDIR);
    foreach ( $a_files as $k=>$file ) {
        $index = Array();
        $index['dir'] = $file;
        $displayDir = preg_replace("/([a-z])([A-Z])/", "\${1} \${2}", $file);
        $displayDir = preg_replace("/_+/", " ", $displayDir);
        $index['displayDir'] = $displayDir;
        $index['url'] = $GLOBALS['musicUrl'] . "/" . $dir . "/" . $file;
        if ( $dir == "" ) {
            $index['relDir'] = escapeJs($file);
        } else {
            $index['relDir'] = escapeJs($dir . "/" . $file);
        }
        $output[] = $index;
    }
    chdir($curdir);
    return $output;
}

function getPlaylist($dir) {
    $output = Array();
    $fileIndex = getFileIndex($dir);
    if ( is_array($fileIndex) && count($fileIndex) > 0 ) {
        foreach ( $fileIndex as $k=>$v ) {
            $entry = Array();
            $entry['title'] = (intval($k)+1) . ": " . $v['displayFile'];
            //$entry['title'] = $v['displayFile'];
            $entry['mp3'] = $v['url'];
            foreach ( getAlbumCoverTypes() as $k2=>$coverType ) {
                if ( file_exists("{$GLOBALS['musicDir']}/{$dir}/{$coverType}") ) {
                    $entry['poster'] = "{$GLOBALS['musicUrl']}/{$dir}/{$coverType}";
                    break;
                }
            }
            $output[] = $entry;
        }
    }
    if ( count($output) > 0 ) {
        return $output;
    }
    return false;
}

function getBreadcrumbs($dir) {
    $a_breadcrumbs = Array();
    $a_dir = preg_split("/\//", $dir);
    $breadcrumbs = "";
    $dirCnt = count($a_dir);
    $cnt = 0;
    $url = "";
    foreach ($a_dir as $k=>$breadcrumb) {
        $crumb = Array();
        if ($cnt === 0) {
            $url .= $breadcrumb;
        } else {
            $url .= "/{$breadcrumb}";
        }
        $enc_url = urlencode($url);
        if ($dirCnt === 1) {
            $crumb['url'] = $url;
            $crumb['text'] = $breadcrumb;
            $crumb['subdirs'] = getDirIndex($url);
        } else if ($cnt === ($dirCnt - 1)) {
            $crumb['url'] = $url;
            $crumb['text'] = $breadcrumb;
            $crumb['subdirs'] = getDirIndex($url);
        } else {
            // Have drop-down of all available directories under this directory.
            //$thelinks = getDropDownAlbums($url);
            $a_dir[$k] = "<span class='filesize_type'><span class=\"dropwrapper\"><a href=\"{$_SERVER['PHP_SELF']}?action=openIndex&amp;dir={$enc_url}\">{$breadcrumb}</a><div class=\"drop\">{$thelinks}</div><!--div.drop--></span><!--span.dropwrapper--></span><!--span.filesize_type-->";
            $crumb['url'] = $url;
            $crumb['text'] = $breadcrumb;
            $crumb['subdirs'] = getDirIndex($url);
        }
        $a_breadcrumbs[] = $crumb;
        $cnt++;
    }
    $breadcrumbs = implode(" / ", $a_dir);
    return $a_breadcrumbs;
}

function getAlbumCover($dir) {
    if ( !file_exists($GLOBALS['musicDir'] . "/" . $dir) ) {
        return false;
    }

    chdir($GLOBALS['musicDir'] . "/" . $dir);

    $albumCoverTypes = getAlbumCoverTypes();
    if ( is_array($albumCoverTypes) && count($albumCoverTypes) > 0 ) {
        foreach ( $albumCoverTypes as $k=>$cover ) {
            if ( file_exists($cover) ) {
                return $cover;
            }
        }
    }

    $pics = glob("*.{jpg,JPG,jpeg,JPEG,gif,GIF,png,PNG}", GLOB_BRACE);
    if ( is_array($pics) && count($pics) > 0 ) {
        foreach($pics as $k2=>$pic) {
            if ( preg_match("/Front/i", $pic) && !preg_match("/Small/i", $pic) ) {
                return $pic;
            }
            if ( preg_match("/Front/i", $pic) && !preg_match("/Small/i", $pic) ) {
                return $pic;
            }
            if ( preg_match("/Large/i", $pic) ) {
                return $pic;
            }
            if ( !preg_match("/Small/i", $pic) ) {
                return $pic;
            }
        }

        return $pics[0];
    }

    return false;
}

/**
 * Headers
 */

header("Access-Control-Allow-Origin: *");

/**
 * Verify PHP version is sufficient.
 */
if ( strnatcmp(phpversion(),'5.3.0') >= 0 ) { 
    // All good
} else { 
    $output['status'] = "fail";
    $output['message'] = "You must use PHP >=5.3.0";
    print(json($output));
    die();
}

/**
 * @returns Returns a json string of all files in a directory.
 */
if ( $_GET['action'] == "getFileIndex" ) {
    header("Content-type: application/json\r\n");
    $output = Array();
    if ( !safeDir($_GET['dir']) ) {
        $output['status'] = "fail";
        $output['message'] = "Could not get a file index for this directory.";
        $output['fileIndex'] = "";
    } else {
        if ( !file_exists($musicDir . "/" . $_GET['dir']) ) {
            $output['status'] = "fail";
            $output['message'] = "That directory does not exist.";
            print(json($output));
            die();
        }
        $output['status'] = "ok";
        $output['message'] = "Directory opened successfully.";
        $output['fileIndex'] = getFileIndex($_GET['dir']);
    }
    print(json($output));
    die();
}

/**
 * @returns Returns a json string of all directories in a directory.
 */
if ( $_GET['action'] == "getDirIndex" ) {
    header("Content-type: application/json\r\n");
    $output = Array();
    if ( !safeDir($_GET['dir']) ) {
        $output['status'] = "fail";
        $output['message'] = "Could not get a directory index for this directory.";
        $output['fileIndex'] = "";
    } else {
        if ( !file_exists($musicDir . "/" . $_GET['dir']) ) {
            $output['status'] = "fail";
            $output['message'] = "That directory does not exist.";
            print(json($output));
            die();
        }
        $output['status'] = "ok";
        $output['message'] = "Directory opened successfully.";
        $output['dirIndex'] = getDirIndex($_GET['dir']);
    }
    print(json($output));
    die();
}

if ( $_GET['action'] == "getPlayer" ) {
    header("Content-type: application/json\r\n");
    $output = Array();
    if ( !safeDir($_GET['dir']) ) {
        $output['status'] = "fail";
        $output['message'] = "Could not get a player for this directory.";
        $output['player'] = "";
    } else {
        if ( !file_exists($musicDir . "/" . $_GET['dir']) ) {
            $output['status'] = "fail";
            $output['message'] = "That directory does not exist.";
            print(json($output));
            die();
        }
        if ( count(glob($GLOBALS['musicDir'] . "/" . $_GET['dir'] . "/*.{" . implode(",", $GLOBALS['musicTypes']) . "}", GLOB_BRACE)) < 1 ) {
            $output['status'] = "fail";
            $output['message'] = "That directory does not exist.";
            print(json($output));
            die();
        }
        // We must unescape / because this JSON string is embedded in a value
        // that will be converted into a JSON string itself. And if we don't
        // do it, it will be returned to the client escaped and will break
        // the javascript.
        $playlist = preg_replace("/\\\\\//", "/", json(getPlaylist($_GET['dir'])));
        $tmp['playlist'] = $playlist;
        $player = tmp("tmpl/player.tmpl", $tmp);
        $output['status'] = "ok";
        $output['message'] = "Player created successfully.";
        $output['player'] = $player;
    }
    print(json($output));
    die();
}

if ( $_GET['action'] == "downloadAlbum" && $_GET['dir'] != "" ) {
    if ( !safeDir($_GET['dir']) ) {
        header("Content-type: application/json\r\n");
        $output['status'] = "fail";
        $output['message'] = "Could not download this album.";
        print(json($output));
    } else {
        if ( is_dir($musicDir . "/" . $_GET['dir']) ) {
            $theDir = preg_replace("/^.+\/(.+)$/i", "\${1}", $_GET['dir']);
            $curdir = getcwd();
            chdir($musicDir . "/" . $_GET['dir']);
            chdir("..");
            if ( !file_exists("{$tmpDir}/downloadAlbum") ) {
                mkdir("{$tmpDir}/downloadAlbum");
            }
            $md5 = md5(date("Y-m-dH:i:s") . microtime() . rand(0,999));
            mkdir("{$tmpDir}/downloadAlbum/{$md5}");
            exec("cp -Rf \"{$theDir}\" \"{$tmpDir}/downloadAlbum/{$md5}/{$theDir}\"");
            chdir("{$tmpDir}/downloadAlbum/{$md5}");
            exec("zip -r \"{$theDir}.zip\" \"{$theDir}\"");
            header('Content-Description: Download file');
            header("Content-type: applications/x-download");
            header("Content-Length: " . filesize("{$theDir}.zip"));
            header("Content-Disposition: attachment; filename=" . basename("{$theDir}.zip"));
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');
            header('Pragma: public');
            readfile("{$theDir}.zip");
            exec("rm -Rf {$tmpDir}/downloadAlbum/{$md5}");
            chdir($curdir);
        }
    }
    die();
}

if ( $_GET['action'] == "getMusicTypes" ) {
    header("Content-type: application/json\r\n");
    print(json(getMusicTypes()));
    die();
}

if ( $_GET['action'] == "getAlbumCover" ) {
    header("Content-type: application/json\r\n");
    if ( !safeDir($_GET['dir']) ) {
        $output['status'] = "fail";
        $output['message'] = "Could not find an album cover.";
        $output['cover'] = "";
        $output['coverUrl'] = "";
    }
    $cover = getAlbumCover($_GET['dir']);
    if ( $cover ) {
        $output['status'] = "ok";
        $output['message'] = "Here is your album cover.";
        $output['cover'] = getAlbumCover($_GET['dir']);
        $output['coverUrl'] = $GLOBALS['musicUrl'] . "/" . $_GET['dir'] . "/" . $cover;
    } else {
        $output['status'] = "fail";
        $output['message'] = "Could not find an album cover.";
        $output['cover'] = "";
        $output['coverUrl'] = "";
    }
    print(json($output));
    die();
}

if ( $_GET['action'] == "getBreadcrumbs" ) {
    header("Content-type: application/json\r\n");
    if ( !safeDir($_GET['dir']) ) {
        $output['status'] = "fail";
        $output['message'] = "Could not get breadcrumbs.";
        $output['breadcrumbs'] = "";
    } else {
        $output['status'] = "ok";
        $output['message'] = "Here are your breadcrumbs.";
        $output['breadcrumbs'] = getBreadcrumbs($_GET['dir']);
    }
    print(json($output));
    die();
}

if ( $_GET['action'] == "getPlaylist" ) {
    header("Content-type: application/json\r\n");
    if ( !safeDir($_GET['dir']) ) {
        $output['status'] = "fail";
        $output['message'] = "Could not get your playlist.";
        $output['playlist'] = "";
    } else {
        $playlist = getPlaylist($_GET['dir']);
        if ( $playlist ) {
            $output['status'] = "ok";
            $output['message'] = "Here is your playlist.";
            $output['playlist'] = getPlaylist($_GET['dir']);
        } else {
            $output['status'] = "fail";
            $output['message'] = "No playlist items.";
            $output['playlist'] = Array();
        }
    }
    print(json($output));
    die();
}
