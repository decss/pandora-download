<?php

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
$example = '
POST /webapi/xml/1.0/ HTTP/1.1
Host: c12913664.web.cddbp.net
Content-Length: 388

<QUERIES>
  <LANG>eng</LANG>
  <AUTH>
    <CLIENT>12913664-3EAEA72CC91CA7F0C8E26D056A234C16</CLIENT>
    <USER>259327967408811593-57E2EF519E7F64662EC811A806A36061</USER>
  </AUTH>
  <QUERY CMD="ALBUM_SEARCH">
    <TEXT TYPE="ARTIST">flying lotus</TEXT>
    <TEXT TYPE="ALBUM_TITLE">until the quiet comes</TEXT>
    <TEXT TYPE="TRACK_TITLE">all in</TEXT>
  </QUERY>
</QUERIES>';
*/

?>