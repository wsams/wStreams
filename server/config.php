<?php

// See LICENSE for licensing.

// This is the root of your music directory.
$musicDir = "/absolute/path/to/the/root/of/your/music/directory";

// Full web URL to the root of your web accessible music directory.
// It should be what ever $musicDir is.
$musicUrl = "https://www.example.com/path/to/web/accessible/root/of/your/music/directory";

// Allowed music types.
$musicTypes = array("mp3", "xspf", "m3u");

// The $tmpDir is used for building zips when downloading music.
// You should clean this directory periodically. It will create
// a downloadAlbum directory with hashes to albums you've downloaded.
// Upon successful downloads, the temporary files will be deleted,
// but if a download is stopped early it won't be cleaned up.
$tmpDir = "/tmp";

// An array of possible cover art image file names.
// If cover.jpg is not found, other types of images will be
// displayed in order of relevance based on the filename.
$albumCovers = array("cover.jpg");
