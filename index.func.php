<?php



########## DOWNLOAD FUNCTION
    function initDownload($title, $artist, $album, $trackUrl, $coverUrl = null, $options = null) {

        /**/ ### PREPARING VARIABLES
            $song_name   = fix_name($title);
            $song_art    = fix_name($artist);
            $song_alb    = fix_name($album);
            $cover_name  = $song_alb;

            $song_folder = $song_art . DIR_DELIM . $song_alb;

            $lyrics_path = DOWNLOAD_FOLDER . DIR_DELIM . $song_folder . DIR_DELIM . 'lyrics' . DIR_DELIM . $song_name . '.txt';
            $song_path   = DOWNLOAD_FOLDER . DIR_DELIM . $song_folder . DIR_DELIM . $song_name . '.' . IN_TRACK_EXT;
            $cover_path  = DOWNLOAD_FOLDER . DIR_DELIM . $song_folder . DIR_DELIM . $cover_name . '.' . IN_COVER_EXT;

            $song_path_conv  = substr_replace($song_path, OUT_TRACK_EXT, strrpos($song_path, IN_TRACK_EXT));
            $cover_path_conv = substr_replace($cover_path, OUT_COVER_EXT, strrpos($cover_path, IN_COVER_EXT));

            $station_id   = $options['stationId'];
            $station_name = $options['stationName'];
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
                $jsonAnswer->song['code'] = download_curl($trackUrl, $song_path);
            } else {
                $jsonAnswer->song['code'] = 'exist';
            }

            if($coverUrl AND file_exists($cover_path_conv) == false) {
                $jsonAnswer->cover['code'] = download_curl($coverUrl, $cover_path);
            } elseif ($coverUrl AND file_exists($cover_path_conv) == true) {
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
            /**/



        /**/ ### PARSING TAGS
            if ($jsonAnswer->song['code'] == 200) {

                // Parsing tags
                if (PARSE_TAGS) {
                    $meta = update_meta(null, array(
                        'title'  => $title,
                        'artist' => $artist,
                        'album'  => $album
                    ));

                    if (PARSE_TAGS == 'remote') {
                        $parseOptions     = getParseOptions($artist, $title, $album);

                        $meta_short       = parse_metadata_gracenote($artist, $title, $album, 'short', $parseOptions['option']);
                        $meta_long        = parse_metadata_gracenote($artist, $title, $album, 'long', $parseOptions['option']);
                        $correlationIndex = $parseOptions['correlation'];

                        if (
                            $correlationIndex >= CORRELATION
                            AND !stristr($meta_short['genre'], 'Soundtrack')
                            AND !stristr($meta_short['genre'], 'Original Film/TV Music')
                        ) {
                            $meta = update_meta($meta, array(
                                'genre'   => $meta_short['genre'], 
                                'date'    => $meta_short['date'],
                                'track'   => $meta_short['track'],
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
                    $jsonArrLyrics = array(
                        'artist' => $artist,
                        'song'   => $title,
                        'album'  => $album
                    );
                    list($lyrics) = parse_lyrics($artist, $title, false, $jsonArrLyrics);

                    if (!$lyrics) {
                        $lyrics = $lyrics_tmp;
                    }

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
                // AND $options['actionElement'] == 'like'
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
                        $parseOptions = getParseOptions($artist, $title, $album);

                        $meta_long  = parse_metadata_gracenote($artist, $title, $album, 'long', $parseOptions['option']);
                        $correlationIndex = $parseOptions['correlation'];
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


        return $jsonAnswer;
    }



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
            foreach ($var AS $value) {
                $array[]=strval($value);
            }

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
    /** /
    // Experemental ::
    function enrichMetadataArray($meta, $metaNew, $replace = true)
    {
        if ($metaNew) {
            foreach ($metaNew as $tagName => $tagValue) {
                if ($replace == true OR ($replace == false AND $meta[$tagName] == null) ) {
                    $tagValue = trim($tagValue);
                    if (!$tagValue) {
                        continue;
                    }

                    $meta[$tagName] = $tagValue;
                }
            }
        }

        return $meta;
    }
    /**/


    function update_meta($meta = null, $update = null) {
        if ($update) {
            foreach ($update as $tag => $value) {
                if (!trim($value))
                    continue; 

                if ($tag == 'genre') {
                    $value = ucfirst($value);
                }

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
        
        if ($shell) {
            shell_exec($shell);
        }

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
                $shell = POWERSHELL_PATH . "powershell " 
                       . realpath('.' . DIR_DELIM) . DIR_DELIM . "ffmpeg.exe -i '" 
                       . $song_path . "' -acodec copy " . $metadata . " '" . $song_path . "." . $ext . "' -y < NUL";
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
            '',             // Запрос по умолчанию
            'noalbum',      // Запрос без учета альбома
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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
            //  $xml_short = simplexml_load_string($data_short);

            // CURL LONG
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post); 
            $data_long = curl_exec($ch);
            if ($data_long)
                $xml_long = simplexml_load_string($data_long);

            curl_close($ch);
        }

        if ($xml_long->RESPONSE->attributes()->STATUS == 'OK') {
            $meta['title']      = (string)$xml_long->RESPONSE->ALBUM->TRACK->TITLE;

            if (
                    (string)$xml_long->RESPONSE->ALBUM->ARTIST != 'Various Artists'
                AND (string)$xml_long->RESPONSE->ALBUM->ARTIST != 'Hybrid/Various Artists'
            ){
                $meta['artist']     = (string)$xml_long->RESPONSE->ALBUM->ARTIST;
            } else {
                $meta['artist']     = (string)$xml_long->RESPONSE->ALBUM->TRACK->ARTIST;
            }

            $meta['album']      = (string)$xml_long->RESPONSE->ALBUM->TITLE;
            $meta['track']      = (string)$xml_long->RESPONSE->ALBUM->TRACK->TRACK_NUM;
            
            $meta['genre']      = (string)$xml_long->RESPONSE->ALBUM->GENRE[$genreId];
            
            // Genre
            // $count = count($xml_long->RESPONSE->ALBUM->GENRE) - 1;
            // $meta['genre']       = (string)$xml_long->RESPONSE->ALBUM->GENRE[$count];
            // if (!$meta['genre']) {
                // $meta['genre']       = (string)$xml_long->RESPONSE->ALBUM->GENRE[1];
            // }
            
            $meta['date']       = (string)$xml_long->RESPONSE->ALBUM->DATE;
            $meta['TR_GENRE']   = simplexml_implode('->', $xml_long->RESPONSE->ALBUM->GENRE);
            $meta['TR_MOOD']    = simplexml_implode('->', $xml_long->RESPONSE->ALBUM->TRACK->MOOD);
            $meta['TR_TEMPO']   = simplexml_implode('->', $xml_long->RESPONSE->ALBUM->TRACK->TEMPO);
            $meta_EXT['ART_ERA']        = simplexml_implode('->', $xml_long->RESPONSE->ALBUM->ARTIST_ERA);
            $meta_EXT['ART_ORIGIN']     = simplexml_implode('->', $xml_long->RESPONSE->ALBUM->ARTIST_ORIGIN);
            $meta_EXT['ART_TYPE']       = simplexml_implode('->', $xml_long->RESPONSE->ALBUM->ARTIST_TYPE);
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


    function parse_lyrics($song_art, $song_name, $check = false, $jsonArr = null) {
        $song_art = prepare_for_url($song_art);
        $song_name = prepare_for_url($song_name);

        $lyrics_url = 'http://lyrics.wikia.com/api.php?func=getSong&fmt=xml&artist='.$song_art.'&song='.$song_name;
        $lyrics_xml = simplexml_load_file($lyrics_url);

            
        if ($lyrics_xml->lyrics == 'Not found' AND $jsonArr) {
            $options    = getParseOptions($jsonArr['artist'], $jsonArr['song'], $jsonArr['album']);
            $meta_short = parse_metadata_gracenote($jsonArr['artist'], $jsonArr['song'], $jsonArr['album'], 'short', $options['option']);
            $lyrics_url = 'http://lyrics.wikia.com/api.php?func=getSong&fmt=xml&artist='.$meta_short['artist'].'&song='.$meta_short['title'];
            $lyrics_xml = simplexml_load_file($lyrics_url);
        }

        if ($lyrics_xml->lyrics != 'Not found') {
            // $lyrics_page_url = (string)$lyrics_xml->url;
            $lyrics_page_url = urldecode((string)$lyrics_xml->url);
            $lyrics_page_url = str_replace('?', '%3F', $lyrics_page_url);
        }

        if ($lyrics_page_url) {
            $lyrics = file_get_contents($lyrics_page_url);
        }

        if ($lyrics) {
            // TODO: make parsing when links are displayed instead of lyrics
            $lyrics_page_header = substr_replace($lyrics, null, 0, strpos($lyrics, "class=\"WikiaPageHeader\""));
            $lyrics_page_header = substr_replace($lyrics_page_header, null, 0, strpos($lyrics_page_header, "<h1>") + strlen('<h1>'));
            $lyrics_page_header = substr_replace($lyrics_page_header, null, strpos($lyrics_page_header, "</h1>"));

            $lyrics = substr_replace($lyrics, null, 0, strpos($lyrics, "<div class='lyricbox'>"));
            $lyrics = substr_replace($lyrics, null, 0, strpos($lyrics, '&#'));
            if (strstr($lyrics, "<div class='rtMatcher'>")) {
                $lyrics = substr_replace($lyrics, null, strpos($lyrics, "<div class='rtMatcher'>"));
            }
            if (strstr($lyrics, '<!--')) {
                $lyrics = substr_replace($lyrics, null, strpos($lyrics, '<!--'));
            }

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 240);
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
        if (is_file(DOWNLOAD_FOLDER . DIR_DELIM . $playlistName) != true
            OR is_file(DOWNLOAD_FOLDER . DIR_DELIM . '_PLAYLISTS' . DIR_DELIM . $playlistName) != true) {
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

        $level = 0;
        foreach ($playlistNames AS $playlistName) {
            if ( station_checkPath($playlistName, $station_song_path) == false ) {
                // backup system station file 
                station_backup($playlistName);

                // update station playlist in system folder
                station_update($playlistName, $station_song_path);

                // Checking playlist's folder
                if ($level == 0) {
                    $playlistPath = DOWNLOAD_FOLDER . DIR_DELIM . $path . DIR_DELIM . $playlistName;
                } else {
                    $playlistPath = DOWNLOAD_FOLDER . DIR_DELIM . $path . DIR_DELIM . '_PLAYLISTS' . DIR_DELIM . $playlistName;
                }
                $level++;

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





