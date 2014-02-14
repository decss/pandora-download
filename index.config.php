<?php
set_time_limit(300);
define('DIR_DELIM', DIRECTORY_SEPARATOR);												//
define('LASTFM_KEY', '03368a415f180be6c8cbf507a694a5c9');								//
define('GRACENOTE_CLIENT_ID', '12913664-3EAEA72CC91CA7F0C8E26D056A234C16');				//
define('GRACENOTE_USER_ID', '259327967408811593-57E2EF519E7F64662EC811A806A36061');		//
define('GRACENOTE_HOST', 'https://208.72.242.176/webapi/xml/1.0/');						//
define('CURL_PROXY', '');																// ['', '127.0.0.1:8080'], '' - don't use proxy

define('DOWNLOAD_FOLDER', 'E:' . DIR_DELIM . 'Music' . DIR_DELIM . 'pandora-maintest-3');
define('SHELL', 'powershell');				// ['shell' (''), 'powershell', 'unixshell'] shell using

define('IS_DOWNLOAD',     true);            //
define('IN_TRACK_EXT', 	  'm4a');			// track extension pandora returns, default 'm4a'
define('OUT_TRACK_EXT',	  'm4a');			// track extension to be converted
define('IN_COVER_EXT',	  'jpg');			// cover extension pandora returns, default 'jpg'
define('OUT_COVER_EXT',	  'jpg');			// cover extension to be converted

define('EMBED_COVERART',  true);			// [true, false] embed coverart to file
define('EMBED_LYRICS',    true);			// [true, false] embed lyrics to file
define('PARSE_TAGS',      'remote');		// [false, 'local', 'remote'] parse additional tags (genre, year, track-no, etc) from web
define('PARSE_LYRICS',    true);			// [false, true] parse lyrics
define('PARSE_PLAYLISTS', true);            // [true, false] 
define('CORRELATION',     60);			    // int

define('PARSE_PLAYLISTS_GENRE', true);      //
define('PARSE_PLAYLISTS_MOOD',  true);      //
define('PARSE_PLAYLISTS_TEMPO', true);      //

define('PLAYLISTS_GENRE_PATH',  '');        //
define('PLAYLISTS_MOOD_PATH',   '');        //
define('PLAYLISTS_TEMPO_PATH',  '');        //

define('LOG', 'log/get_lyrics_link.log');	// path to log file (set bool false to cancel) fe 'log/get_lyrics_link.log'
?>