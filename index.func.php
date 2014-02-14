<?php

########## NAMES, FILTERS, JSON
	function fix_name($name) {
		$name = str_replace('->', '_', $name);
		$name = str_replace('/', ', ', $name);
		$name = preg_replace("~[\\\/:*?\"<>|]~", ' ', $name);
		$name = preg_replace("~ \s* ~", ' ', $name);
		return $name;
	}

	function get_file_ext($path) {
		$ext = substr_replace($path, null, 0, strrpos($path, '.') + 1);
		return $ext;
	}

	function prepare_for_url($part) {
		$part = str_replace(" ", "%20", $part);
		return $part;
	}
	
	function simplexml_implode ($glue = '', $var){
		if ($var) {
			foreach ($var AS $value)
				$array[]=strval($value);

			return implode($glue, $array);
		} else 
			return false;
	}

	function filter($val, $mode) {
		if ($mode == 'alb') {
			$val = str_ireplace('(Single)', null, $val);
			$val = str_ireplace('(Explicit)', null, $val);
			$val = str_ireplace('(Radio Single)', null, $val);
			// $val = str_ireplace('(Soundtrack)', null, $val);
			// $val = str_ireplace('(Radio Edit)', null, $val);
			// $val = str_ireplace('(Bonus Track Version)', null, $val);
		}

		if ($mode == 'track') {
			$val = str_ireplace('(Instrumental)', null, $val);
		}

		return trim($val);
	}

	function formatJsonAnswer($jsonAnswer = null) {
		if (!$jsonAnswer)
			$jsonAnswer->error["code"] = 1;

		$jsonAnswer = json_encode($jsonAnswer);

		if ($_GET['callback'])
			$jsonAnswer = $_GET['callback'].'('.$jsonAnswer.')';

		return $jsonAnswer;
	}



########## UPDATE METADATA, TAGS, CONSTRUCT SHELL
	function update_meta($meta = null, $update = null) {
		if ($update) {
			foreach ($update as $tag => $value) {
				if (!trim($value))
					continue; 

				if ($tag == 'genre')
					$value = ucfirst($value);

				// m4a, mp3
				if ($tag == 'album' OR $tag == 'artist' OR $tag == 'genre' OR $tag == 'title' OR $tag == 'track' OR $tag == 'date' OR $tag == 'comment') {
					$meta['m4a'][$tag] = $value;
					$meta['mp3'][$tag] = $value;
				}
			}
		}

		return $meta;
	}

	function embed_cover($song_path, $cover_path) {
		$shell = 'AtomicParsley.exe "' . $song_path . '" --artwork "' . $cover_path . '" --output "' . $song_path . '.coverart"';
		shell_exec($shell);

		if (is_file($song_path . '.coverart') == true) {
			unlink($song_path);
			rename($song_path . '.coverart', $song_path);
			return true;
		}
		return false;
	}

	function embed_lyrics($song_path, $lyrics_path) {

		$lyrics = file_get_contents($lyrics_path);
		// $lyrics = str_replace("\r\n", "\r", $lyrics);
		// $lyrics = preg_replace("~(?:\s)+~", ' ', $lyrics);
		// $lyrics = str_replace('"', '\'', $lyrics);

		if ($lyrics AND  strlen($lyrics) > 24) {
			// $shell = 'AtomicParsley.exe "' . $song_path . '" --lyrics "' . $lyrics . '" --output "' . $song_path . '.lyrics"';
			$shell = 'AtomicParsley.exe "' . $song_path . '" --lyricsFile "' . $lyrics_path . '" --output "' . $song_path . '.lyrics"';
			shell_exec($shell);
		}

		if (is_file($song_path . '.lyrics') == true) {
			unlink($song_path);
			rename($song_path . '.lyrics', $song_path);
			return true;
		}
		return false;
	}

	function update_tags($song_path, $meta) {
		$shell = shell_construct($song_path, $meta);
		$ext = get_file_ext($song_path);
		
		if ($shell)
			shell_exec($shell);

		if (is_file($song_path.'.'.$ext)) {
			unlink($song_path);
			rename($song_path.'.'.$ext, $song_path);
		}
	}

	function shell_construct($song_path, $meta) {
		$ext = get_file_ext($song_path);

		if (!SHELL OR SHELL == 'shell') {
			foreach ($meta AS $key => $value) {
				if ($value)
					$metadata .= '-metadata '.$key.'="'.str_replace('"', '\'', $value).'" ';
			}
			$metadata = str_replace("\r\n", " ", $metadata);

			if ($metadata AND $song_path)
				$shell = 'ffmpeg.exe -i "'.$song_path.'" -acodec copy '.$metadata.' "'.$song_path.'.'.$ext.'" -y';
		}

		elseif (SHELL == 'powershell') {
			foreach ($meta AS $key => $value) {
				if ($value)
					$metadata .= "-metadata ".$key."='".str_replace("'", "''", $value)."' ";
			}
			$metadata = str_replace('>', '">"', $metadata);
			$metadata = str_replace('&', '"&"', $metadata);
			$metadata = str_replace("\r\n", "'`r`n'", $metadata);
			$song_path = str_replace('&', '"&"', $song_path);
            $song_path = str_replace("'", "''", $song_path);

			if ($metadata AND $song_path)
				$shell = "powershell ".realpath('.' . DIR_DELIM) . DIR_DELIM."ffmpeg.exe -i '".$song_path."' -acodec copy ".$metadata." '".$song_path.".".$ext."' -y < NUL";
		}
		
		elseif (SHELL == 'unixshell') {

		}

		return $shell;
	}



########## CURL/FSOCKS HTTP REUESTS
	function getParseOptions($artist, $title, $albumm = null) {
		$jsonArr['song']   = $title;
		$jsonArr['album']  = $albumm;
		$jsonArr['artist'] = $artist;

		// Массив возможных опций
		$options = array(
			'', 			// Запрос по умолчанию
			'noalbum',		// Запрос без учета альбома
		);

		// перебираем запросы с различными опциями и создаем массив $correlationIndex
		foreach ($options as $key => $opt) {
			$meta[$key] = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short', $opt);
			$correlationIndex[$key] = getCorrelationIndex($jsonArr, $meta[$key]);
		}

		// Находим ключ максимального значения $correlationIndex
		$key = array_search(max($correlationIndex),$correlationIndex); 

		$result = array(
			'option'       => $options[$key], 
			'correlation'  => $correlationIndex[$key], 
			'key'          => $key, 
			'options'      => $options, 
			'correlations' => $correlationIndex,
			'meta'         => $meta,
		);
		// print_r ($result); exit;
		return $result;
	}
	
	function getCorrelationIndex($jsonArr, $meta) {
		// echo $meta['title'].' - '.$jsonArr['song'] . '<br>';
		// echo $meta['album'].' - '.$jsonArr['album'] . '<br>';
		// echo $meta['artist'].' - '.$jsonArr['artist'] . '<br>';
		$title  = similar_text(strtolower($meta['title']),   strtolower($jsonArr['song']),   $index['title']);
		$album  = similar_text(strtolower($meta['album']),   strtolower($jsonArr['album']),  $index['album']);
		$artist = similar_text(strtolower($meta['artist']),  strtolower($jsonArr['artist']), $index['artist']);

		similar_text($meta['artist'], 'Various Artists', $index['artist_var']);

		if ($index['artist_var'] > $index['artist'])
			$index['artist'] = 50;

		$intex['ttl'] = round( ($index['title']*1.1 + $index['album']*0.8 + $index['artist']*1.1) / 3 , 2);

		return $intex['ttl'];
	}

	function parse_metadata_gracenote($artist = null, $title = null, $albumm = null, $mode = 'long', $options = null) {
		$albumm = filter($albumm, 'alb');
		$title = filter($title, 'track');
		$genreId = $mode == 'short' ? 0 : 1;

		$body_text = null;
		if (strlen(trim($artist)))
			$body_text .= '<TEXT TYPE="ARTIST">'.trim(htmlspecialchars($artist)).'</TEXT>';

		if (strlen(trim($albumm)) AND stristr($options, 'noalbum') == false)
			$body_text .= '<TEXT TYPE="ALBUM_TITLE">'.trim(htmlspecialchars($albumm)).'</TEXT>';

		if (strlen(trim($title)))
			$body_text .= '<TEXT TYPE="TRACK_TITLE">'.trim(htmlspecialchars($title)).'</TEXT>';

		if ($body_text) {
			if ($mode == 'long') {
				$curl_post = '<QUERIES>
					<LANG>eng</LANG>
					<AUTH>
						<CLIENT>'.GRACENOTE_CLIENT_ID.'</CLIENT>
						<USER>'.GRACENOTE_USER_ID.'</USER>
					</AUTH>
					<QUERY CMD="ALBUM_SEARCH">
						<MODE>SINGLE_BEST</MODE>
						'.$body_text.'
						<OPTION>
							<PARAMETER>SELECT_EXTENDED</PARAMETER>
							<VALUE>MOOD,TEMPO,ARTIST_OET</VALUE>
						</OPTION>
						<OPTION>
							<PARAMETER>SELECT_DETAIL</PARAMETER>
							<VALUE>GENRE:3LEVEL,MOOD:2LEVEL,TEMPO:3LEVEL,ARTIST_ORIGIN:4LEVEL,ARTIST_ERA:2LEVEL,ARTIST_TYPE:2LEVEL</VALUE>
						</OPTION>
					</QUERY>
				</QUERIES>';
			} else {
				$curl_post = '<QUERIES>
					<LANG>eng</LANG>
					<AUTH>
						<CLIENT>'.GRACENOTE_CLIENT_ID.'</CLIENT>
						<USER>'.GRACENOTE_USER_ID.'</USER>
					</AUTH>
					<QUERY CMD="ALBUM_SEARCH">
						<MODE>SINGLE_BEST</MODE>
						'.$body_text.'
					</QUERY>
				</QUERIES>';
			}

			// $ch = curl_init('https://c'.substr_replace(GRACENOTE_CLIENT_ID, null, strpos(GRACENOTE_CLIENT_ID, '-')).'.web.cddbp.net/webapi/xml/1.0/');
			$ch = curl_init(GRACENOTE_HOST);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // false for SSL work
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // false for SSL work
			curl_setopt($ch, CURLOPT_POST, true);
			if (CURL_PROXY != '') {
				curl_setopt($ch, CURLOPT_PROXY, CURL_PROXY);
			}

			// CURL SHORT
			// curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_short); 
			// $data_short = curl_exec($ch);
			// if ($data_short)
			// 	$xml_short = simplexml_load_string($data_short);

			// CURL LONG
			curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post); 
			$data_long = curl_exec($ch);
			if ($data_long)
				$xml_long = simplexml_load_string($data_long);

			curl_close($ch);
		}

		if ($xml_long->RESPONSE->attributes()->STATUS == 'OK') {
			$meta['title'] 		= (string)$xml_long->RESPONSE->ALBUM->TRACK->TITLE;

			if (
				    (string)$xml_long->RESPONSE->ALBUM->ARTIST != 'Various Artists'
				AND (string)$xml_long->RESPONSE->ALBUM->ARTIST != 'Hybrid/Various Artists'
			){
				$meta['artist'] 	= (string)$xml_long->RESPONSE->ALBUM->ARTIST;
			} else {
				$meta['artist'] 	= (string)$xml_long->RESPONSE->ALBUM->TRACK->ARTIST;
			}

			$meta['album'] 		= (string)$xml_long->RESPONSE->ALBUM->TITLE;
			$meta['track'] 		= (string)$xml_long->RESPONSE->ALBUM->TRACK->TRACK_NUM;
			
			$meta['genre'] 		= (string)$xml_long->RESPONSE->ALBUM->GENRE[$genreId];
			
			// Genre
			// $count = count($xml_long->RESPONSE->ALBUM->GENRE) - 1;
			// $meta['genre'] 		= (string)$xml_long->RESPONSE->ALBUM->GENRE[$count];
			// if (!$meta['genre']) {
				// $meta['genre'] 		= (string)$xml_long->RESPONSE->ALBUM->GENRE[1];
			// }
			
			$meta['date'] 		= (string)$xml_long->RESPONSE->ALBUM->DATE;
			$meta['TR_GENRE'] 	= simplexml_implode('->', $xml_long->RESPONSE->ALBUM->GENRE);
			$meta['TR_MOOD']	= simplexml_implode('->', $xml_long->RESPONSE->ALBUM->TRACK->MOOD);
			$meta['TR_TEMPO']	= simplexml_implode('->', $xml_long->RESPONSE->ALBUM->TRACK->TEMPO);
			$meta_EXT['ART_ERA'] 		= simplexml_implode('->', $xml_long->RESPONSE->ALBUM->ARTIST_ERA);
			$meta_EXT['ART_ORIGIN'] 	= simplexml_implode('->', $xml_long->RESPONSE->ALBUM->ARTIST_ORIGIN);
			$meta_EXT['ART_TYPE'] 		= simplexml_implode('->', $xml_long->RESPONSE->ALBUM->ARTIST_TYPE);
		}

		foreach ($meta as $key => $val)
			$comment .= '['.$key.'] => '.$val.';'."\r\n";

		if ($comment)
			$comment.= '###############'."\r\n";

		foreach ($meta_EXT as $key => $val)
			$comment .= '['.$key.'] => '.$val.';'."\r\n";

		$meta['comment'] = $comment;

		return $meta;
	}


	function parse_lyrics($song_art, $song_name, $check = false) {
		$song_art = prepare_for_url($song_art);
		$song_name = prepare_for_url($song_name);

		$lyrics_url = 'http://lyrics.wikia.com/api.php?func=getSong&fmt=xml&artist='.$song_art.'&song='.$song_name;
		$lyrics_xml = simplexml_load_file($lyrics_url);


        if ($lyrics_xml->lyrics != 'Not found') {
            // $lyrics_page_url = (string)$lyrics_xml->url;
            $lyrics_page_url = urldecode((string)$lyrics_xml->url);
            $lyrics_page_url = str_replace('?', '%3F', $lyrics_page_url);
        }

        if ($lyrics_page_url)
            $lyrics = file_get_contents($lyrics_page_url);

        if ($lyrics) {
        	$lyrics_page_header = substr_replace($lyrics, null, 0, strpos($lyrics, "class=\"WikiaPageHeader\""));
        	$lyrics_page_header = substr_replace($lyrics_page_header, null, 0, strpos($lyrics_page_header, "<h1>") + strlen('<h1>'));
        	$lyrics_page_header = substr_replace($lyrics_page_header, null, strpos($lyrics_page_header, "</h1>"));

            $lyrics = substr_replace($lyrics, null, 0, strpos($lyrics, "<div class='lyricbox'>"));
            $lyrics = substr_replace($lyrics, null, 0, strpos($lyrics, '&#'));
            $lyrics = substr_replace($lyrics, null, strpos($lyrics, "<div class='rtMatcher'>"));
            if (strstr($lyrics, '<!--'))
                $lyrics = substr_replace($lyrics, null, strpos($lyrics, '<!--'));

            $lyrics = html_entity_decode($lyrics);
            $lyrics = str_replace("<br />", "\r\n", $lyrics);
            $lyrics = str_replace("&#39;", "'", $lyrics);
            $lyrics = strip_tags($lyrics);

            if (stristr($lyrics, 'Unfortunately, we are not licensed to display') == true) {
                $lyrics = substr_replace($lyrics, null, strpos($lyrics, 'Unfortunately, we are not licensed to display'));
                $lyrics = trim($lyrics);
                $lyrics .= "\r\n\r\n" . "--- not full lyrics ---";
            }
            $lyrics = trim($lyrics);
        }

        if (strlen($lyrics) <= 24)
            $lyrics = null;

        if ($check == true) {
            $lyrics_length = strlen($lyrics);
            $lyrics_height = substr_count(str_replace("\r\n\r\n", "\r\n", trim($lyrics)), "\r\n");
            // $lyrics_short = substr_replace($lyrics, null, start)

            return array($lyrics, $lyrics_url, $lyrics_page_url, $lyrics_page_header, $lyrics_length, $lyrics_height);
        } else {
            return array($lyrics);
        }
	}



########## DOWNLOADING, CONVERSION
	function download_curl($url, $path) {
		if (IS_DOWNLOAD == false) {
			return 200;
		}

		$ch = curl_init(str_replace(" ","%20",$url)); //Here is the file we are downloading, replace spaces with %20
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		// curl_setopt($ch, CURLOPT_FILE, $fp); // here it sais to curl to just save it
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if (CURL_PROXY != '') {
			curl_setopt($ch, CURLOPT_PROXY, CURL_PROXY);
		}
		
		$data = curl_exec($ch);//get curl response
		$curl_info = curl_getinfo($ch);

		if ($curl_info['http_code'] == 200) {
			$fp = fopen ($path, 'w+');
			fwrite($fp, $data); //write curl response to file
			fclose($fp);
		}

		curl_close($ch);
		return $curl_info['http_code'];
	}

	function convert_track($song_path, $song_path_conv) {
		$shell = 'ffmpeg -i "'.$song_path.'" -acodec libmp3lame -ab 128k "'.$song_path_conv.'" -y';

		if ($shell)
			shell_exec($shell);

		if (is_file($song_path_conv)) {
			unlink($song_path);
			return true;
		}

		return false;
	}

	function convert_cover($cover_path, $cover_path_conv) {
		image_resize($cover_path, $cover_path_conv);

		if (is_file($cover_path_conv)) {
			unlink($cover_path);
			return true;
		}

		return false;
	}



########## STATIONS, PLAYLISTS
	function station_update($station_id, $station_song_path) {
		$in_station = false;
		$station = 'stations/'.$station_id;

		// If file not exist - create it and write first line (station-id)
		if (!is_file($station)) {
			$f = fopen($station, 'w');
			fwrite($f, '#ID:'.$station_id."\r\n");
			fclose($f);
		}

		// read file for specific station
		$station_arr = file($station);

		// checking if track exist
		foreach ($station_arr AS $line) {
			if (stristr($line, $station_song_path))
				$in_station = true;
		}

		// if track not exist - add it to station playlist
		if (!$in_station) {
			$f = fopen($station, 'a');
			fwrite($f, $station_song_path."\r\n");
			fclose($f);
		}
	}

	function station_backup($station_id) {
		// $stationBak = 'stations/backup/'.$station_id;
		$dir = scandir('stations/backup/', 1);
		foreach ($dir as $st) {
			if (strstr($st, $station_id)) {
				$stationBak = 'stations/backup/'.$st;
				break;
			}
		}

		if (is_file($stationBak)) 
			$stationBakCount = count(file($stationBak));
		else
			$stationBakCount = 0;

		$stationSys = 'stations/'.$station_id;
		$stationSysCount = count(file($stationSys));
		
		if ($stationSysCount >= $stationBakCount + 10) {
			copy('stations/'.$station_id, 'stations/backup/'.$station_id.'.'.date("Y-m-d_H-i-s").'.bak');
		}
	}

	function station_checkPath($playlistName, $station_song_path) {
		$in_station = false;
		$station = 'stations/' . $playlistName;

		// Check if dest playlist exists
		if (is_file(DOWNLOAD_FOLDER . DIR_DELIM . $playlistName) != true) {
			return false;
		}

		// checking if track already in playlist
		$station_arr = file($station);
		foreach ($station_arr AS $line) {
			if (stristr($line, $station_song_path))
				$in_station = true;
		}

		if ($in_station) {
			return true;
		} else {
			return false;
		}
	}

	function station_get_id($path) {
		$station_arr = file($path);
		$station_id = null;
		if ($station_arr AND stristr($station_arr[0], '#ID:'))
			$station_id = trim(str_replace('#ID:', null, $station_arr[0]));

		return $station_id;
	}


	function getPlaylistNames($str, $prefix = null) {
		if (stristr($str, '->')) {
			$strArr = explode('->', $str);
		} elseif (strlen($str) != 0) {
			$strArr[0] = $str;
		} else {
			return false;
		}

		$playlistNames = array();
		foreach ($strArr AS $key => $item) {
			for ($i = 0; $i <= $key; $i++) {
				if ($playlistNames[$key]) {
					$playlistNames[$key] .= '_';
				}
				$playlistNames[$key] .= $strArr[$i];
			}
		}
		foreach ($playlistNames AS $key => $item) {
			$playlistNames[$key] = $path . $prefix . fix_name($item) . '.m3u';
		}

		return $playlistNames;
	}

	function makePlaylists($playlistNames, $station_song_path, $path = null) {
		if (empty($playlistNames)) {
			return false;
		}

		foreach ($playlistNames AS $playlistName) {
			if ( station_checkPath($playlistName, $station_song_path) == false ) {
				// backup system station file 
				station_backup($playlistName);

				// update station playlist in system folder
				station_update($playlistName, $station_song_path);

				// Checking playlist's folder
				$playlistPath = DOWNLOAD_FOLDER . DIR_DELIM . $path . DIR_DELIM . $playlistName;

				if (!is_dir(dirname($playlistPath))) {
					mkdir(dirname($playlistPath), 0777, true);
				}

				// replacing station's playlist
				if (is_file($playlistPath)) {
					unlink($playlistPath);
				}
				copy('stations/' . $playlistName, $playlistPath);
			}
		}	
	}
























##############################
##### DEPRECATED #############
##############################
	/** /
	function parse_metadata($song_art, $song_name) {
		$song_art = prepare_for_url($song_art);
		$song_name = prepare_for_url($song_name);
		if (strstr($song_name, '('))
			$song_name = substr_replace($song_name, null, strpos($song_name, '('));

		$track_url = 'http://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key='.LASTFM_KEY.'&autocorrect=1&artist='.$song_art.'&track='.$song_name;
		$track_xml = simplexml_load_file($track_url);

		if ($track_xml->attributes()->status == 'ok') {
			@$meta['title'] 	= (string)$track_xml->track->name;
			@$meta['artist'] 	= (string)$track_xml->track->artist->name;
			@$meta['album'] 	= (string)$track_xml->track->album->title;
			@$meta['track'] 	= (string)$track_xml->track->album->attributes()->position;
			@$meta['genre'] 	= (string)$track_xml->track->toptags->tag[0]->name;
			$nodes['alb_mbid'] 	= (string)$track_xml->track->album->mbid;

			if ($nodes['alb_mbid']) {
				$alb_url 		= 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key='.LASTFM_KEY.'&mbid='.$nodes['alb_mbid'];
				$alb_xml 		= simplexml_load_file($alb_url);
			}
		}

		if ($alb_xml AND $alb_xml->attributes()->status == 'ok') {
			$meta['date'] 		= preg_replace("~.*([0-9]{4}).*~", "$1", (string)$alb_xml->album->releasedate);
		}

		// $meta = get_meta($meta);

		return $meta;
	}
	/**/

	/** /
	function parse_metadata_by_link($artist, $song, $album) {
		$link = get_lyrics_link($artist, $song);

		$album = strtolower($album);
		$album = preg_replace("~ [[:space:]] *~", ' ', $album);
		$album = preg_replace("~[^A-z\s]~", '', $album);

		if ($link) {
			$data = file_get_contents($link);

			// IDv3
			$metadata = substr_replace($data, null, 0, stripos($data, 'Song details</TH>'));
			$metadata = substr_replace($metadata, null, 0, stripos($metadata, '</TR>') + 5);
			$metadata = substr_replace($metadata, null, stripos($metadata, '</TABLE>'));
			$metadata = trim($metadata);
			$metadata = explode('</TR>', $metadata);
			foreach ($metadata AS $tr) {
				$tr = explode("</TD>", trim($tr));

				if (trim(strip_tags($tr[0])) == 'Title')
					$meta['Title'] = trim(strip_tags($tr[1]));

				if (trim(strip_tags($tr[0])) == 'Artist')
					$meta['Artist'] = trim(strip_tags($tr[1]));

				if (trim(strip_tags($tr[0])) == 'Album') {
					$tr[1] = str_replace('<BR>', '$RAPLACER$', $tr[1]);
					$meta['Album_tmp'] = strip_tags($tr[1]);
					$meta['Album_tmp'] = preg_replace("~\s+~", ' ', $meta['Album_tmp']);
					$meta['Album_tmp'] = explode('$RAPLACER$', $meta['Album_tmp']);

					foreach ($meta['Album_tmp'] as $key => $Album_tmp) {
						$Album_tmp = strtolower($Album_tmp);
						$Album_tmp = preg_replace("~ [[:space:]] *~", ' ', $Album_tmp);
						$Album_tmp = preg_replace("~[^A-z\s]~", '', $Album_tmp);
						if (strstr($Album_tmp, $album) OR strstr($album, $Album_tmp)) {
							$meta['Album_last'] = $meta['Album_tmp'][$key];
							break;
						}
					}
					if (!$meta['Album_last'])
						$meta['Album_last'] = $meta['Album_tmp'][count( $meta['Album_tmp']) - 1];

					$meta['Album'] = substr_replace($meta['Album_last'], null, strpos($meta['Album_last'], '['));
					
					$meta['Year'] = substr_replace($meta['Album_last'], null, 0, strpos($meta['Album_last'] , '[') + 1);
					$meta['Year'] = substr_replace($meta['Year'], null, strpos($meta['Year'] , ']'));
					$meta['TrackNo'] = trim(substr_replace($meta['Album_last'], null, 0, stripos($meta['Album_last'] , 'Track') + strlen('Track') ));
					// $meta['Album'] = strip_tags($meta['Album']);
				}

				if (trim(strip_tags($tr[0])) == 'Genre')
					$meta['Genre'] = trim(strip_tags($tr[1]));
			}

			// Song lyrics
			$lyrics = substr_replace($data, null, 0, stripos($data, 'Song lyrics</TH>'));
			// $lyrics = substr_replace($lyrics, null, 0, stripos($lyrics, '</TR>') + 5);
			$lyrics = substr_replace($lyrics, null, 0, stripos($lyrics, '</DIV>') + 6);
			$lyrics = substr_replace($lyrics, null, stripos($lyrics, '</TABLE>'));
			$lyrics = substr_replace($lyrics, null, stripos($lyrics, 'song_lyrics_credits>') + strlen('song_lyrics_credits>'));
			$lyrics = str_replace("\r", "", $lyrics);
			$lyrics = str_replace("\n", "", $lyrics);
			$lyrics = str_replace("<BR>", "\r\n", $lyrics);
			$lyrics = trim(strip_tags($lyrics));
			if (stristr($lyrics, 'This artist does not want'))
				$lyrics = null;
			// if ($lyrics)
				// $meta['Lyrics'] = $lyrics;

			// $meta = get_meta($meta);
		}

		return array($meta, $lyrics);
	}
	/**/


	/** /
	function get_meta($meta) {
		$meta = update_meta(null, 	array(
			'album' => trim($meta['Album']), 
			'artist' => trim($meta['Artist']), 
			'genre' => trim($meta['Genre']), 
			'title' => trim($meta['Title']), 
			'track' => trim($meta['TrackNo']), 
			'date' => trim($meta['Year']) 
		));

		return $meta;
	}

	function get_meta_comment() {

	};
	/**/


//	function get_lyrics_link($song_art, $song_name) {
//		global $debug;
//		// $song_art = 'The Offspring';
//		// $song_name = 'Dirty Magic';
//		// $song_art = 'Edward Sharpe & The Magnetic Zeros';
//		// $song_name = 'Home';
//
//		if ($song_art AND $song_name) {
//			$song_art = str_replace("'", "\'", $song_art);
//			$song_name = str_replace("'", "\'", $song_name);
//			if (strstr($song_name, '('))
//			$song_name = substr_replace($song_name, null, strpos($song_name, '('));
//
//			// delete unusefull info
//			$song_name = str_replace('Single Version', null, $song_name);
//
//			$search_host = 'https://www.google.ru/';
//			$search_query = 'search?q=site:letssingit.com '.$song_art.' - '.$song_name.'';
//			$search_url = str_replace('&', '%26', $search_host.$search_query);
//			$search_url = str_replace('+', '%2B', $search_url);
//			$search_url = str_replace(' ', '+', $search_url);
//
//			##### Geting cookies
//				# $search_query = null;
//				# $ch = curl_init($search_host);
//				# curl_setopt($ch, CURLOPT_TIMEOUT, 30);
//				# curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//				# curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//
//				# curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // for SSL work
//				# curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // for SSL work
//				# 
//				# curl_setopt($ch,CURLOPT_HEADER,true);
//				# curl_setopt($ch,CURLOPT_NOBODY,true);
//
//				# $data = curl_exec($ch);
//				# $data = explode("\r\n", $data);
//				# $cookies = null;
//				# foreach ($data AS $val) {
//				# 	if (strstr($val, 'Set-Cookie:')) {
//				# 		if ($cookies)
//				# 			$cookies .= '; ';
//				# 		$cookies .= trim(str_replace('Set-Cookie: ', null, $val));
//				# 	}
//				# }
// 				# // echo($cookies);
//				# curl_close($ch);
//
//			##### parsing http://artists.letssingit.com link from google
//				$ch = curl_init($search_url);
//				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//
//				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // for SSL work
//				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // for SSL work
//				// curl_setopt($ch,CURLINFO_HEADER_OUT,true);
//
//				$headers = array(
//					'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0',
//					'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
//					'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
//					'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
//					'Accept-Encoding: deflate',
//					// 'Cookie: '.$cookie.'',
//					'Connection: keep-alive'
//				); 
//
//				$link = null;
//				$data = curl_exec($ch);
//
//				// echo($data);
//
//				########################################
//				########## WORD PROCESSOR - полнещая каша, сам хер когда разберусь = )
//					$data = substr_replace($data, null, 0, strpos($data, '<ol>') + strlen('<ol>'));
//					$data = substr_replace($data, null, strpos($data, '</ol>'));
//					$data = explode('<li class="g">', $data);
//					foreach ($data AS $key => $val) {
//						$val = substr_replace($val, null, 0, strpos($val, '<h3 class="r">'));
//						
//						$data_src[$key] = substr_replace($val, null, 0, strpos($val, 'http://artists.letssingit.com'));
//						$data_src[$key] = substr_replace($data_src[$key], null, strpos($data_src[$key], '"'));
//						if (strstr($data_src[$key], '&'))
//							$data_src[$key] = substr_replace($data_src[$key], null, strpos($data_src[$key], '&'));
//
//						$val = substr_replace($val, null, strpos($val, '</h3>'));
//						$val = strip_tags($val);
//						$val = preg_replace("~[^\w-]~", ' ', $val);
//						$val = preg_replace("~[\s]+~", ' ', $val);
//						if (!$val OR !strstr($val, '-')) {
//							unset($data[$key]);
//							unset($data_src[$key]);
//						} else {
//							$data[$key] = strtolower(' '.$val.' ');
//						}
//					}
//
//					$song_name_arr = preg_replace("~[^\w-]~", ' ', $song_name);
//					$song_name_arr = preg_replace("~[\s]+~", ' ', $song_name_arr);
//					$song_name_arr = str_replace('&', 'amp', $song_name_arr);
//
//					// Оцениваем релевантнасть по 3м вхождениям
//					foreach ($data as $key => $value) {
//						// $data_count[$key] += substr_count_array($value, $song_name_arr);
//						$data_count[$key] += substr_count($value, strtolower('- '.$song_name_arr.' lyrics') );
//						$data_count[$key] += substr_count($value, strtolower($song_name_arr.' lyrics') );
//						$data_count[$key] += substr_count($value, strtolower($song_name_arr) );
//					}
//
//					foreach ($data_src as $key => $value) {
//						if (!$link) {
//							$link = $value;
//							$link_key = $key;
//						}
//						
//						if (!$max_count)
//							$max_count = $data_count[$key];
//
//						if ($data_count[$key] > $max_count) {
//							$link = $value;
//							$max_count = $data_count[$key];			
//							$link_key = $key;
//						}
//					}
//
//					/**/
//					if ($debug) {
//						echo('<pre>');
//						print_r ($data);
//						print_r ($data_count);
//						print_r ($data_src);
//						die();
//					}
//					/**/
//					if (LOG) {
//						if (strstr(LOG, '/') AND !is_dir(dirname(LOG)))
//							mkdir(dirname(LOG), 0755, true);
//
//						$log .= '##### '.$song_art.' - '.$song_name."\r\n";
//						$log .= $link."\r\n";
//						$log .= serialize(array('#'.$link_key.'#'.$max_count, date("Y-m-d H:i:s"), $data, $data_count, $data_src))."\r\n";
//						error_log($log, 3, LOG);
//					}
//				##########
//				########################################
//
//				# if (strstr($data, 'artists.letssingit.com')) {
//				# 	$link = substr_replace($data, null, 0, strpos($data, 'http://artists.letssingit.com'));
//				# 	$link = substr_replace($link, null, strpos($link, '"'));
//				# 	if (strstr($link, '&'))
//				# 		$link = substr_replace($link, null, strpos($link, '&'));
//				# }
//
//			if (strstr($link, 'artists.letssingit.com'))
//				return $link;
//		}
//
//		return false;
//	}
?>