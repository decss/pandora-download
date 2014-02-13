<?php
ini_set("error_reporting", "E_ALL ^E_NOTICE");
##### README ###########################
########################################
# 1. BUGS
# "year" tag cant' be writen into .mp3 file
#
#

////// TODO: Use all artists from TRACK->ARTIST if last one consists of more than one artist
////// TODO: Fix station id getter function (JS plugin)



##### CONFIG ###########################
########################################
	define('CONFIG_FILE', './index.config.php');
	
	if (!is_readable(CONFIG_FILE)) {
		$config = <<<HEREDOC
<?php
set_time_limit(300);
define('DIR_DELIM', DIRECTORY_SEPARATOR);												//
define('LASTFM_KEY', '03368a415f180be6c8cbf507a694a5c9');								//
define('GRACENOTE_CLIENT_ID', '12913664-3EAEA72CC91CA7F0C8E26D056A234C16');				//
define('GRACENOTE_USER_ID', '259327967408811593-57E2EF519E7F64662EC811A806A36061');		//
define('GRACENOTE_HOST', 'https://208.72.242.176/webapi/xml/1.0/');						//
define('CURL_PROXY', '');																// ['', '127.0.0.1:8080'], '' - don't use proxy

define('DOWNLOAD_FOLDER', 'E:' . DIR_DELIM . 'Music' . DIR_DELIM . 'pandora-maintest-2');
define('SHELL', 'powershell');				// ['shell' (''), 'powershell', 'unixshell'] shell using

define('IN_TRACK_EXT', 	'm4a');				// track extension pandora returns, default 'm4a'
define('OUT_TRACK_EXT',	'm4a');				// track extension to be converted
define('IN_COVER_EXT',	'jpg');				// cover extension pandora returns, default 'jpg'
define('OUT_COVER_EXT',	'jpg');				// cover extension to be converted

define('EMBED_COVERART',  true);			// [true, false] embed coverart to file
define('EMBED_LYRICS',    true);			// [true, false] embed lyrics to file
define('PARSE_TAGS',      'remote');		// [false, 'local', 'remote'] parse additional tags (genre, year, track-no, etc) from web
define('PARSE_LYRICS',    true);			// [false, true] parse lyrics
define('PARSE_PLAYLISTS', true);			// [true, false] 

define('LOG', 'log/get_lyrics_link.log');	// path to log file (set bool false to cancel) fe 'log/get_lyrics_link.log'
?>
HEREDOC;
		file_put_contents(CONFIG_FILE, $config);
		// $fh = fopen(CONFIG_FILE, 'w+');
		// fwrite($hf, $config);
		// fclose($fh);
	}

	require_once CONFIG_FILE;







##### REQUIRE ##########################
########################################
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

		$meta['metaBest'] = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', $response['option']);

		// foreach ($response['correlations'] as $correlationIndex) {
			// $meta['correlationIndex'][] = $correlationIndex;
		// }
		// if ($meta['correlationIndex'] < 60) {
			// $meta['long'] = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', 'noalbum');
			// $meta['correlationIndex2'] = getCorrelationIndex($jsonArr, $meta['long']);
		// }
		// print_r($jsonArr); print_r($meta); exit;

        // Lyrics
        list($lyrics, $lyrics_url, $lyrics_page_url, $lyrics_page_header, $lyrics_length, $lyrics_height) = parse_lyrics($jsonArr['artist'], $jsonArr['song'], true);
        
        $meta['lyrics']             = $lyrics;
        $meta['lyrics_url']         = $lyrics_url;
        $meta['lyrics_page_url']    = $lyrics_page_url;
        $meta['lyrics_page_header'] = $lyrics_page_header;
        $meta['lyrics_length']      = $lyrics_length;
        $meta['lyrics_height']      = $lyrics_height;

        // echo '<pre>';
        // echo print_r($response);
		// echo print_r($meta);
		echo formatJsonAnswer($meta);
		exit;
	}





##### BODY #############################
########################################
	$jsonStr = strip_tags($_GET['jsonArr']);

	if ($jsonStr) {
		$jsonArr = unserialize($jsonStr);
		unset($flag);



		/**/ ### PREPARING VARIABLES
			$song_art    = fix_name($jsonArr['artist']);
			$song_alb    = fix_name($jsonArr['album']);
			$song_name   = fix_name($jsonArr['song']);
			$cover_name  = $song_alb;

			$song_folder = $song_art . DIR_DELIM . $song_alb;

			$lyrics_path = DOWNLOAD_FOLDER . DIR_DELIM . $song_folder . DIR_DELIM . 'lyrics' . DIR_DELIM . $song_name . '.txt';
			$song_path   = DOWNLOAD_FOLDER . DIR_DELIM . $song_folder . DIR_DELIM . $song_name . '.' . IN_TRACK_EXT;
			$cover_path  = DOWNLOAD_FOLDER . DIR_DELIM . $song_folder . DIR_DELIM . $cover_name . '.' . IN_COVER_EXT;

			$song_path_conv  = substr_replace($song_path, OUT_TRACK_EXT, strrpos($song_path, IN_TRACK_EXT));
			$cover_path_conv = substr_replace($cover_path, OUT_COVER_EXT, strrpos($cover_path, IN_COVER_EXT));

			$station_id   = $jsonArr['station_id'];
			$station_name = $jsonArr['station_name'];
			/**/



		/**/ ### CHECKING DIRS
			// if download folder does not exist - then create
			if (!is_dir(DOWNLOAD_FOLDER))
				mkdir(DOWNLOAD_FOLDER, 755);

			if (!is_dir(DOWNLOAD_FOLDER . DIR_DELIM . $song_folder))
				mkdir(DOWNLOAD_FOLDER . DIR_DELIM . $song_folder, 755, true);
			/**/



		/**/ ### DOWNLOADING TRACK AND COVER FILES
			if(file_exists($song_path_conv) == false) {
				$jsonAnswer->song['code'] = download_curl($jsonArr['url_track'], $song_path);
			} else {
				$jsonAnswer->song['code'] = 'exist';
			}

			if($jsonArr['url_cover'] AND file_exists($cover_path_conv) == false) {
				$jsonAnswer->cover['code'] = download_curl($jsonArr['url_cover'], $cover_path);
			} elseif ($jsonArr['url_cover'] AND file_exists($cover_path_conv) == true) {
				$jsonAnswer->cover['code'] = 'exist';
			} else { 
				$jsonAnswer->cover['code'] = 'canceled';
			}
			/**/



		/**/ ### CONVERTING TRACK/COVER AND CLEARING THE PATH
			if ($jsonAnswer->cover['code'] == 200 AND IN_COVER_EXT != OUT_COVER_EXT) {
				$flag['cover_conv'] = convert_cover($cover_path, $cover_path_conv);
			}

			if ($jsonAnswer->song['code'] == 200 AND IN_TRACK_EXT != OUT_TRACK_EXT) {
				$flag['track_conv'] = convert_track($song_path, $song_path_conv);
			}

			if (is_file($cover_path_conv) == false) {
				$cover_path_conv = null;
			}

			if (is_file($song_path_conv) == false) {
				$song_path_conv = null;
			}



		/**/ ### PARSING TAGS
			if ($jsonAnswer->song['code'] == 200) {

				// Parsing tags
				if (PARSE_TAGS) {
					$meta = update_meta(null, array(
						'artist' => $jsonArr['artist'],
						'album' => $jsonArr['album'],
						'title' => $jsonArr['song']
					));

					if (PARSE_TAGS == 'remote') {
						// gracenote
						$withAlbum = true;
						/**/

						$options = getParseOptions($jsonArr['artist'], $jsonArr['song'], $jsonArr['album']);

						$meta_short = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short', $options['option']);
						$meta_long  = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', $options['option']);
						// $correlationIndex = getCorrelationIndex($jsonArr, $meta_short);
						$correlationIndex = $options['correlation'];

						// if ($correlationIndex < 60) {
							// $meta_short = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short', 'noalbum');
							// $meta_long = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', 'noalbum');
							// $correlationIndex = getCorrelationIndex($jsonArr, $meta_short);
						// }

						if (
							$correlationIndex >= 60
							AND !stristr($meta_short['genre'], 'Soundtrack')
							AND !stristr($meta_short['genre'], 'Original Film/TV Music')
						) {
							$meta = update_meta($meta, array(
								'genre' => $meta_short['genre'], 
								'date' => $meta_short['date'],
								'track' => $meta_short['track'],
								'comment' => $meta_long['comment'] . '[StationID] => ' . $station_id.'; '
							));
						}
						// print_r ($meta); exit;
						/** /
						$meta_tmp = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', true);
						
						$correlationIndex = getCorrelationIndex($jsonArr, $meta_tmp);
						if ($correlationIndex < 60) {
							$meta_tmp = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', false);
							$correlationIndex = getCorrelationIndex($jsonArr, $meta_tmp);
						}

						if ($correlationIndex >= 60) {
							$meta = update_meta($meta, array('genre' => $meta_tmp['genre'], 
															 'date' => $meta_tmp['date'],
															 'track' => $meta_tmp['track'],
															 'comment' => $meta_tmp['comment'].'[StationID] => '.$station_id.'; ')
									);
							
							if (
								!stristr($meta_tmp['genre'], 'Soundtrack')
							) {
								$meta_tmp = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short');
								$meta = update_meta($meta, array('genre' => $meta_tmp['genre'])
										);
							}
						}
						/**/
					}


				}

				// updating tags
				if ( $flag['track_conv']) {
					update_tags($song_path_conv, $meta[OUT_TRACK_EXT]);
				} else {
					update_tags($song_path_conv, $meta[IN_TRACK_EXT]);
				}

				// embedding cover art 
				if (EMBED_COVERART == true AND ($jsonAnswer->cover['code'] == 200 OR $jsonAnswer->cover['code'] == 'exist') AND $cover_path_conv) {
					embed_cover($song_path_conv, $cover_path_conv);
				}

				// downloading lyrics
				if (PARSE_LYRICS) {
					list($lyrics) = parse_lyrics($jsonArr['artist'], $jsonArr['song'], false);

					if (!$lyrics)
						$lyrics = $lyrics_tmp;

					if ($lyrics) {
						if (!is_dir(dirname($lyrics_path)))
							mkdir(dirname($lyrics_path), 0755, true);

						file_put_contents($lyrics_path, $lyrics);
					}
				}

				// embedding lyrics
				if (EMBED_LYRICS == true AND is_file($lyrics_path)) {
					embed_lyrics($song_path_conv, $lyrics_path);
				}
			}
			
			$jsonAnswer->error["code"] = 0;
			/**/




		/**/ ### PLAYLISTS
			if (PARSE_PLAYLISTS AND ($jsonAnswer->song['code'] === 200  OR $jsonAnswer->song['code'] === 'exist') 
				// AND $jsonArr['elem'] == 'like'
			) {
				// checking station's folders
				if (!is_dir('stations'))
					mkdir('stations', 0755);
				
				if (!is_dir('stations/backup'))
					mkdir('stations/backup', 0755);

				// creating relative song path for the playlist
				if ($flag['track_conv'] === false)
					$station_song_path = $song_folder . DIR_DELIM . $song_name . '.' . IN_TRACK_EXT;
				else
					$station_song_path = $song_folder . DIR_DELIM . $song_name . '.' . OUT_TRACK_EXT;

				// STATION'S PLAYLISTS
					if ( isset($station_id) AND station_checkPath($station_id, $station_song_path) == false ) {
						// backup system station file 
						station_backup($station_id);

						// update station playlist in system folder
						station_update($station_id, $station_song_path);

						// selecting all station playlists in pandora download folder
						$dir = scandir(DOWNLOAD_FOLDER);
						foreach ($dir AS $file) {
							if (stristr($file, '.m3u'))
								$stations[] = $file;
						}

						// searching for the station with the same station-id
						if ($stations) {
							foreach ($stations AS $station) {
								if ($station_id == station_get_id(DOWNLOAD_FOLDER . DIR_DELIM . $station)) {
									unlink(DOWNLOAD_FOLDER . DIR_DELIM . $station);
								}
							}
						}

						// replacing station's playlist
						copy('stations/'.$station_id, DOWNLOAD_FOLDER . DIR_DELIM . $station_name . '.m3u');
					}


				// GENRE'S PLAYLISTS
					if (!$meta_short) {
						$options = getParseOptions($jsonArr['artist'], $jsonArr['song'], $jsonArr['album']);

						$meta_short = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short', $options['option']);
						$meta_long  = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', $options['option']);
						// $correlationIndex = getCorrelationIndex($jsonArr, $meta_short);
						$correlationIndex = $options['correlation'];

						// if ($correlationIndex < 60) {
							// $meta_short = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short', 'noalbum');
							// $meta_long = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', 'noalbum');
							// $correlationIndex = getCorrelationIndex($jsonArr, $meta_short);
						// }
					}

					// Station file name
					$station_id_genre = fix_name('Genre - ' . $meta_short['genre']);
					if ( station_checkPath($station_id_genre, $station_song_path) == false ) {

						if ($meta_short AND $correlationIndex >= 60) {

							// backup system station file 
							station_backup($station_id_genre);

							// update station playlist in system folder
							station_update($station_id_genre, $station_song_path);

							// replacing station's playlist
							if (is_file(DOWNLOAD_FOLDER . DIR_DELIM . $station_id_genre . '.m3u')) {
								unlink(DOWNLOAD_FOLDER . DIR_DELIM . $station_id_genre . '.m3u');
							}
							copy('stations/'.$station_id_genre, DOWNLOAD_FOLDER . DIR_DELIM . $station_id_genre . '.m3u');
						}
					}

			}
			/**/
	}




##### BODY #############################
########################################
	echo formatJsonAnswer($jsonAnswer);
	exit;




/*
::test request::

url:
c12913664.web.cddbp.net

reuest:
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
</QUERIES>
*/

?>