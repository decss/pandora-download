<?php
ini_set("error_reporting", E_ALL ^ E_NOTICE);
define('CONFIG_FILE', './index.config.php');




// TODO: Use all artists from TRACK->ARTIST if last one consists of more than one artist




##### CONFIG TEMPLATE###################
########################################
    if (!is_readable(CONFIG_FILE)) {
        $config = <<<HEREDOC
<?php
set_time_limit(300);
define('DIR_DELIM', DIRECTORY_SEPARATOR);

define('LASTFM_KEY',          '');
define('GRACENOTE_CLIENT_ID', '');
define('GRACENOTE_USER_ID',   '');
define('GRACENOTE_HOST',      'https://208.72.242.176/webapi/xml/1.0/');
define('CURL_PROXY',          '');                                       // ['', '127.0.0.1:8080'], '' - don't use proxy

define('IS_DOWNLOAD',     true);            //
define('DOWNLOAD_FOLDER', 'E:' . DIR_DELIM . 'Music' . DIR_DELIM . 'pandora-maintest-3');
define('SHELL',           'powershell');    // ['shell' (''), 'powershell', 'unixshell'] shell using
define('POWERSHELL_PATH', '%SYSTEMROOT%\System32\WindowsPowerShell\v1.0\\');

define('IN_TRACK_EXT',    'm4a');           // track extension pandora returns, default 'm4a'
define('OUT_TRACK_EXT',   'm4a');           // track extension to be converted
define('IN_COVER_EXT',    'jpg');           // cover extension pandora returns, default 'jpg'
define('OUT_COVER_EXT',   'jpg');           // cover extension to be converted
define('CORRELATION',     60);              // int 0-100

define('EMBED_COVERART',  true);            // [true, false] embed coverart to file
define('EMBED_LYRICS',    true);            // [true, false] embed lyrics to file
define('PARSE_TAGS',      'remote');        // [false, 'local', 'remote'] parse additional tags (genre, year, track-no, etc) from web
define('PARSE_LYRICS',    true);            // [false, true] parse lyrics
define('PARSE_PLAYLISTS', true);            // [true, false] 

define('PARSE_PLAYLISTS_GENRE', true);      //
define('PARSE_PLAYLISTS_MOOD',  true);      //
define('PARSE_PLAYLISTS_TEMPO', true);      //

define('PLAYLISTS_GENRE_PATH',  '');        //
define('PLAYLISTS_MOOD_PATH',   '');        //
define('PLAYLISTS_TEMPO_PATH',  '');        //

define('LOG', 'log/get_lyrics_link.log');   // path to log file (set bool false to cancel) fe 'log/get_lyrics_link.log'
?>
HEREDOC;
        file_put_contents(CONFIG_FILE, $config);
    }




##### REQUIRE ##########################
########################################
    require_once CONFIG_FILE;
    require_once 'index.func.php';
    require_once 'require/functions.php';




##### API FUNCTIONS ####################
########################################
    if (strip_tags($_GET['question']) == 'here?') {
        $jsonAnswer['answer'] = 'yes';
        
        echo formatJsonAnswer($jsonAnswer);
        exit;
    }

    if (strip_tags($_GET['question']) == 'sing_short' AND $_GET['jsonArr']) {
        $jsonArr = unserialize($_GET['jsonArr']);

        $response = getParseOptions($jsonArr['artist'], $jsonArr['song'], $jsonArr['album']);

        $meta['key']          = $response['key'];
        $meta['correlations'] = $response['correlations'];
        $meta['metaResp']     = $response['meta'];

        $meta['metaBest'] = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 
                                                     'long', $response['option']);

        // Lyrics
        list($lyrics, $lyrics_url, $lyrics_page_url, $lyrics_page_header, $lyrics_length, $lyrics_height) = parse_lyrics($jsonArr['artist'], 
                                                                                 $jsonArr['song'], 
                                                                                 true, 
                                                                                 $jsonArr);
        
        $meta['lyrics']             = $lyrics;
        $meta['lyrics_url']         = $lyrics_url;
        $meta['lyrics_page_url']    = $lyrics_page_url;
        $meta['lyrics_page_header'] = $lyrics_page_header;
        $meta['lyrics_length']      = $lyrics_length;
        $meta['lyrics_height']      = $lyrics_height;

        echo formatJsonAnswer($meta);
        exit;
    }




##### BODY #############################
########################################
    $jsonStr = strip_tags($_GET['jsonArr']);
    if ($jsonStr) {
        $jsonArr = unserialize($jsonStr);
    }

    $title    = trim($jsonArr['song']);
    $album    = trim($jsonArr['album']);
    $artist   = trim($jsonArr['artist']);
    $trackUrl = trim($jsonArr['url_track']);
    $coverUrl = trim($jsonArr['url_cover']);
    $options  = array(
                    'stationId'     => trim($jsonArr['station_id']),
                    'stationName'   => trim($jsonArr['station_name']),
                    'actionElement' => trim($jsonArr['elem'])
                );

    if ($title AND $album AND $artist AND $trackUrl) {
        $jsonAnswer = initDownload($title, $artist, $album, $trackUrl, $coverUrl, $options);
    }

    echo formatJsonAnswer($jsonAnswer);
    exit;






?>