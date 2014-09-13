<?php
##### REQUIRE ##########################
########################################
    ini_set("error_reporting", "E_ALL ^E_NOTICE");
    define('CONFIG_FILE', './index.config.php');
    require_once CONFIG_FILE;
    require_once 'index.func.php';
    require_once 'require/functions.php';




##### DEBUG ############################
########################################
/** /
$lyrics = parse_lyrics('Limp Bizkit', 'Rollin\'', true);
print_r($lyrics);
/**/

$jsonArr['song']      = 'The Girl';
$jsonArr['album']     = 'Bring Me Your Love';
$jsonArr['artist']    = 'City And Colour';
$jsonArr['url_track'] = 'http://audio-sv5-t1-3.pandora.com/access/Gotye - Hearts A Mess Remix EP - Hearts A Mess (Radio Mix).m4a?version=4&lid=946783457&token=NGuKPEd1MnURaLkZWPvhS5gc%2FUEGUMuXd%2FCyaQQrg4fwDhHUrFBjZQfxz%2BzC69C0cs3PVyQBp1r%2BDqFe2qmhbi1OERhBx9ZCOAsaPjFyWICO1y4bX9gf%2BGjU155Ze9xSlLdeovQiRLXlMHcZzGJBDHZzO0VpHicTBTRWMUAan75suDIXvVqC7Jh7M10LSXxH2HyNm5ZATtVVJp6EK23WO8OZksO3gxXjudBugg1q3NkqClx6IDCRzCdxnqrObCevV8nfFThVKY%2FD%2BY%2FZA%2BHbWLeTcR3euZ0M9%2BgMl8qqA9mIpSniDYVVPCT8MXc89Y%2FjZyoD%2FrdI15INPzk1BmaYm4toDHXjOfMR1whAFpzuX0u%2FZ9IoUNN%2BDg%3D%3D';
$filePath = 'test/song - ' . date('H:i:s') . '.m4a';
$filePath = realpath('test/song.m4a');


// $jsonArr['song']      = 'Center Of The Sun';
// $jsonArr['album']     = 'Chillout 04: The Ultimate Chillout';
// $jsonArr['artist']    = 'Conjure One';


echo '<pre>';
echo '<div style="font-size:11px;">';


/** /
// $shell = 'powershell D:\Servers\OpenServer\domains\test\pandora\ffmpeg.exe -i \'D:\Servers\OpenServer\domains\test\pandora\test\song.m4a\' -acodec copy -metadata title=\'title - 16:28:55\'  \'D:\Servers\OpenServer\domains\test\pandora\test\song.m4a.m4a\' -y < NUL';
// $shell = 'powershell whoami';
$shell = '%SYSTEMROOT%\System32\WindowsPowerShell\v1.0\powershell get-service < NUL';
// $shell = 'whoami';
$a = exec($shell, $out);
print_r($a);
print_r($out);

// $a = shell_exec($shell, $out);
// print_r($a);
// print_r($out);


/** /
echo '<h2>OPTIONS</h2>';
$options    = getParseOptions($jsonArr['artist'], $jsonArr['song'], $jsonArr['album']);
print_r($options);


/** /
echo '<h2>META SHORT</h2>';
$meta_short = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short', $options['option']);
print_r($meta_short);


/** /
echo '<h2>META LONG</h2>';
$meta_long  = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', $options['option']);
print_r($meta_long);


/**/
echo '<h2>LYRICS</h2>';
list($lyrics) = parse_lyrics($jsonArr['artist'], $jsonArr['song'], false, $jsonArr);
print_r($lyrics);


/** /
echo '<h2>DOWNLOAD</h2>';
echo 'file before: ' . is_file($filePath) . '<br />';
download_curl($jsonArr['url_track'], $filePath);
echo 'file after: ' . is_file($filePath) . '<br />';


/** /
echo '<h2>META GENERATE</h2>';
$meta = update_meta($meta, array(
            'artist'  => $meta_short['artist'],
            'album'   => $meta_short['album'],
            'title'   => $meta_short['title'],
            'genre'   => $meta_short['genre'], 
            'date'    => $meta_short['date'],
            'track'   => $meta_short['track'],
            'comment' => $meta_long['comment'] . '[StationID] => ' . $station_id.'; '
        ));
print_r($meta);


/** /
echo '<h2>UPDATING TAGS</h2>';
// update_tags($filePath, array('title' => 'title - ' . date("H:i:s")));
update_tags($filePath, $meta['m4a']);


/**/





exit;

header("refresh:30;url=test.php");


// $host = 'c5020672.web.cddbp.net';
$host = 'c12913664.web.cddbp.net';
// $host = '208.72.242.176';
$path = '/webapi/xml/1.0/';

// $headers = array('Content-Length', '388');

$post = '<QUERIES>
    <LANG>eng</LANG>
    <AUTH>
        <CLIENT>12913664-3EAEA72CC91CA7F0C8E26D056A234C16</CLIENT>
        <USER>259327967408811593-57E2EF519E7F64662EC811A806A36061</USER>
        <!--
        <CLIENT>5020672-2D2A8053EE6175CF662EBCCDCFE66394</CLIENT>
        <USER>262144001167669892-8637AC05D3409CAD3AB9AB90D6410F6D</USER>
        -->
    </AUTH>
    <QUERY CMD="ALBUM_SEARCH">
        <TEXT TYPE="ARTIST">flying lotus</TEXT>
        <TEXT TYPE="ALBUM_TITLE">until the quiet comes</TEXT>
        <TEXT TYPE="TRACK_TITLE">all in</TEXT>
    </QUERY>
</QUERIES>';


# # # # # # # # # # # # # # #
# # # # # # # # # # # # # # #
echo '<pre>';

$url = 'https://' . $host . $path;
// $url = $host . $path;
// $url = 'https://www.google.ru';

$ch = curl_init();

$curlOptArray = array(
CURLOPT_URL            => $url,
CURLOPT_RETURNTRANSFER => 1,
// CURLOPT_FOLLOWLOCATION => 1,
CURLOPT_POST           => true,
CURLOPT_POSTFIELDS     => $post,
CURLOPT_TIMEOUT        => 10,
// CURLOPT_FAILONERROR    => 1,
    // ssl
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_SSL_VERIFYHOST => false,
    // auth
// CURLOPT_HTTPAUTH       => CURLAUTH_ANY, 
// CURLOPT_USERPWD        => "$username:$password" 
// CURLOPT_PROXY          => '127.0.0.1:8080',
// CURLOPT_PROXY          => '190.0.9.202:8080',
    // debuging
CURLOPT_HEADER         => true,
CURLINFO_HEADER_OUT    => true
);

curl_setopt_array($ch, $curlOptArray);
$response = curl_exec($ch);
$responseInfo = curl_getinfo($ch);
$responseErr  = curl_error($ch);
$responseHeaders = trim(substr_replace($response, null, strpos($response, "\r\n\r\n")));
$responseBody    = trim(htmlspecialchars(substr_replace($response, null, 0, strpos($response, "\r\n\r\n"))));

print '::error<br />';
print_r ($responseErr);
print '<br />';

print '::request<br />';
print_r ($responseInfo);
// print_r ($responseInfo['request_header']);
print '<br />';

print '::response<br />';
print $responseHeaders;
print '<br />';
print $responseBody;

curl_close($ch);


// log
if (strlen($responseBody) < 1) {
  error_log(date("Y-m-d H:i:s") . " ::: FAILED \r\n", 3, 'access.log');
} else {
  error_log(date("Y-m-d H:i:s") . " ::: " . strlen($responseBody) . " \r\n", 3, 'access.log');
}




echo '</pre>';
exit;

// Исходящий заголовок из скрипта
// POST /webapi/xml/1.0/ HTTP/1.1
// Host: c12913664.web.cddbp.net
// Accept: */*
// Content-Length: 386
// Content-Type: application/x-www-form-urlencoded
# # # # # # # # # # # # # # #
# # # # # # # # # # # # # # #


/*
::test request::
c5020672.web.cddbp.net
reuest: 
POST /webapi/xml/1.0/ HTTP/1.1
Host: c5020672.web.cddbp.net

<QUERIES>
    <LANG>eng</LANG>
    <AUTH>
        <CLIENT></CLIENT>
        <USER></USER>
    </AUTH>
    <QUERY CMD="ALBUM_SEARCH">
        <TEXT TYPE="ARTIST">flying lotus</TEXT>
        <TEXT TYPE="ALBUM_TITLE">until the quiet comes</TEXT>
        <TEXT TYPE="TRACK_TITLE">all in</TEXT>
    </QUERY>
</QUERIES>
*/

?>