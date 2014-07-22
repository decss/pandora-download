<?php
ini_set("error_reporting", "E_ALL ^E_NOTICE");
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
define('CURL_PROXY', 		  '');                                       // ['', '127.0.0.1:8080'], '' - don't use proxy

define('IS_DOWNLOAD',     true);            //
define('DOWNLOAD_FOLDER', 'E:' . DIR_DELIM . 'Music' . DIR_DELIM . 'pandora-maintest-3');
define('SHELL', 'powershell');				// ['shell' (''), 'powershell', 'unixshell'] shell using

define('IN_TRACK_EXT', 	  'm4a');			// track extension pandora returns, default 'm4a'
define('OUT_TRACK_EXT',	  'm4a');			// track extension to be converted
define('IN_COVER_EXT',	  'jpg');			// cover extension pandora returns, default 'jpg'
define('OUT_COVER_EXT',	  'jpg');			// cover extension to be converted
define('CORRELATION',     60);			    // int 0-100

define('EMBED_COVERART',  true);			// [true, false] embed coverart to file
define('EMBED_LYRICS',    true);			// [true, false] embed lyrics to file
define('PARSE_TAGS',      'remote');		// [false, 'local', 'remote'] parse additional tags (genre, year, track-no, etc) from web
define('PARSE_LYRICS',    true);			// [false, true] parse lyrics
define('PARSE_PLAYLISTS', true);			// [true, false] 

define('PARSE_PLAYLISTS_GENRE', true);		//
define('PARSE_PLAYLISTS_MOOD',  true);		//
define('PARSE_PLAYLISTS_TEMPO', true);		//

define('PLAYLISTS_GENRE_PATH',  '');		//
define('PLAYLISTS_MOOD_PATH',   '');		//
define('PLAYLISTS_TEMPO_PATH',  '');		//

define('LOG', 'log/get_lyrics_link.log');	// path to log file (set bool false to cancel) fe 'log/get_lyrics_link.log'
?>
HEREDOC;
		file_put_contents(CONFIG_FILE, $config);
	}




##### REQUIRE ##########################
########################################
	require_once CONFIG_FILE;
	require_once 'index.func.php';
	require_once 'require/functions.php';




##### DEBUG ############################
########################################
/** /
$lyrics = parse_lyrics('Limp Bizkit', 'Rollin\'', true);
print_r($lyrics);


exit;
/**/




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
        																		 true);
        
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
						$correlationIndex = $options['correlation'];

						if (
							$correlationIndex >= CORRELATION
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
						if (IS_DOWNLOAD) {
							file_put_contents($lyrics_path, $lyrics);
						}		
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
				if (!is_dir('stations')) {
					mkdir('stations', 0755);
				}
				
				if (!is_dir('stations/backup')) {
					mkdir('stations/backup', 0755);
				}

				// creating relative song path for the playlist
				if ($flag['track_conv'] === false) {
					$station_song_path = $song_folder . DIR_DELIM . $song_name . '.' . IN_TRACK_EXT;
				} else {
					$station_song_path = $song_folder . DIR_DELIM . $song_name . '.' . OUT_TRACK_EXT;
				}

				// STATION'S PLAYLISTS
					if ( isset($station_id) AND station_checkPath($station_id, $station_song_path) == false ) {
						// backup system station file 
						station_backup($station_id);

						// update station playlist in system folder
						station_update($station_id, $station_song_path);

						// selecting all station playlists in pandora download folder
						$dir = scandir(DOWNLOAD_FOLDER);
						foreach ($dir AS $file) {
							if (stristr($file, '.m3u')) {
								$stations[] = $file;
							}
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
						copy('stations/'.$station_id, DOWNLOAD_FOLDER . DIR_DELIM . trim($station_name) . '.m3u');
					}


				// GENRE, MOOD, TEMPO PLAYLISTS
					if (!$meta_long) {
						$options = getParseOptions($jsonArr['artist'], $jsonArr['song'], $jsonArr['album']);

						$meta_long  = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'long', $options['option']);
						$correlationIndex = $options['correlation'];
					}

					if ($correlationIndex >= CORRELATION) {
						// Genre
						if (PARSE_PLAYLISTS_GENRE) {
							$playlistNames = getPlaylistNames($meta_long['TR_GENRE'], '[G] ');
							makePlaylists($playlistNames, $station_song_path, PLAYLISTS_GENRE_PATH);
						}
						
						// Mood
						if (PARSE_PLAYLISTS_MOOD) {
							$playlistNames = getPlaylistNames($meta_long['TR_MOOD'], '[M] ');
							makePlaylists($playlistNames, $station_song_path, PLAYLISTS_MOOD_PATH);
						}
						
						// Tempo
						if (PARSE_PLAYLISTS_TEMPO) {
							$playlistNames = getPlaylistNames($meta_long['TR_TEMPO'], '[T] ');
							makePlaylists($playlistNames, $station_song_path, PLAYLISTS_TEMPO_PATH);
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
url: c12913664.web.cddbp.net
reuest: 
POST /webapi/xml/1.0/ HTTP/1.1
Host: c12913664.web.cddbp.net
Content-Length: 388

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