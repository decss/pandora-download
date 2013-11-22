// ==UserScript==
// @name        Pandora download
// @namespace   decss@miromax.org
// @description Some part of code tooken from http://userscripts.org/users/469064 - Pandora Freemium plugin
// @include     http://*pandora.com/*
// @include     https://*pandora.com/*
// @require     http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js
// @require     https://raw.github.com/kvz/phpjs/master/functions/var/serialize.js
// @require     https://raw.github.com/jbrooksuk/jQuery-Timer-Plugin/master/jquery.timer.js
// @version     0.1
// ==/UserScript==


//this.$ = this.jQuery = jQuery.noConflict(true);

// this is a handle to the timer event, used to determine when to hide the drop-down menu
var freemiumClearHandle;
var json_url = 'http://test/pandora/index.php';

var log = function(log_data) {
	GM_log(log_data);
	// unsafeWindow.console.log(log_data);
	console.log(log_data);
}

log('NOTICE: console is running ...');



/////////////////////////////////////
// SERVER STATUS LISTENER
/////////////////////////////////////
	// VARS
		var timer;
	
	// Evaluating menu
		listenerMenu = '\
		<style>\
			#listenerMenu { \
				background:black; \
				height:305px; \
				min-width:280px; \
				position:fixed; top:120px; right:0px; \
				border:1px solid white; padding:1px; \
				font-size:10px; line-height:14px; \
				font-family:\'lucida console\', Arial; color:white;\
			} \
			#statusButton { \
				cursor:pointer; width:25px !important; \
			} \
			#listenerContent {line-height:11px; }\
			#listenerContent table {border-collapse:collapse; margin-bottom:2px; }\
			#listenerContent table td {border:1px solid gray; padding:0px 4px 0px 2px; }\
			.requestTrackDataButtom {float:right; margin:4px 6px 0 0; }\
			#listenerStatusBar { \
				background: none repeat scroll 0 0 #282828; \
				bottom: 0; left: 0; right: 0; \
				position: absolute; \
				/*padding:0 2px;*/ \
			} \
			#listenerStatusBar>div {width:50%; float:left; } \
			.s {color:silver;} \
			.g {color:gray;} \
			.dg {color:#414141;} \
            .lyrics-cont {\
                padding-left:5px;\
                height:22px; \
                width:295px; \
                overflow:hidden;\
            }\
            .lyrics-cont:hover {\
                height:auto;\
                max-height:400px;\
                overflow:scroll;\
                background: none repeat scroll 0 0 black;\
                position: absolute;\
                z-index: 99;\
            }\
		</style>\
		<div id="listenerMenu">\
			<!--div id="statusText" 	style="float:left; width:140px; border: 1px solid silver; "></div-->\
			<input id="statusInput" type="hidden" name="" value="0" />\
			<div style="clear: both; "></div>\
			<div id="listenerContent"></div>\
			<div id="listenerStatusBar"> \
				<div id="requestStatus">&nbsp;</div> \
				<div id="serverStatus" style="width:40%; ">&nbsp;</div> \
				<div id="statusButton"></div>\
			</div>\
		</div>';
		$('#body').append(listenerMenu);
		// updating menu status, button, input...
		// $('#listenerMenu').ready(function() {
		lstnStatus = GM_getValue('lstnStatus', false);

		if (lstnStatus == false) {
			listenerStatusChange('disabled');		
		} else {
			listenerStatusChange('enabled');
			listenerTimerStart();
		}
		// });

	// Start/stop timer controls
		$('#listenerStart').live('click', function() {
			GM_setValue('lstnStatus', true);
			listenerStatusChange('enabled');
			listenerTimerStart();
		});
		$('#listenerStop').live('click', function() {
			GM_setValue('lstnStatus', false);
			listenerStatusChange('disabled');
			listenerStatusBarChange('run first');
			listenerTimerStop();
		});

	// Timer controls
		function listenerTimerStart(delay = 3000) {
			timer = $.timer(delay, function() {
				listenRequestServerStatus();
			});
		}
		function listenerTimerStop() {
			if (timer) {
				timer.stop();
			}
		}

	// Status changer
		function listenerStatusChange(status) {
			if (status == 'disabled') {
				statusText    = '<div style="background:gray; ">disabled</div>';
				statusButton  = '<div id="listenerStart" style="color: red; padding:0px 1px;">off</div>';
				statusInput   = 0;
			} 
			else if (status == 'enabled') {
				statusText    = '<div style="background:gray; ">enabled, wait ...</div>';
				statusButton  = '<div id="listenerStop" style="color: green; padding:0px 1px;">on</div>';
				statusInput   = 0;
			} 
			else if (status == 'return_empty') {
				statusText    = '<div style="background:red; ">return_empty</div>';
				statusInput   = 0;
			}
			else if (status == 'return_error') {
				statusText    = '<div style="background:red; ">return_error</div>';
				statusInput   = 0;
			}
			else if (status == 'return_yes') {
				statusText    = '<div style="background:green; ">server UP</div>';
				statusInput   = 1;
			}
			else if (status == 'return_baddata') {
				statusText    = '<div style="background:red; ">bad data</div>';
				statusInput   = 0;
			}
			else if (status == 'parse_error') {
				statusText    = '<div style="background:red; ">parse_error</div>';
				statusInput   = 0;
			}

			// if (statusText)
				// $('#statusText').html(statusText);

			listenerStatusBarChange(statusText, 'server');

			if (statusButton)
				$('#statusButton').html(statusButton);
			
			if (!statusInput)
				statusInput = 0;
			$('#statusInput').val(statusInput);
		}

	// Check sattus
		function listenerCheckServerStatus() {
			if ($('#statusInput').val() == 1) {
				return true;
			} else {
				return false;
			}
		}

	// Server status listener
		function listenRequestServerStatus() {
			listenerStatusChange('enabled');
			// listenerStatusChange('return_empty');
			GM_xmlhttpRequest ({
				method: 'GET',
				url: json_url + '?question=here?',
				onload: function (response) {
					var jsonAnswer = $.parseJSON(response.responseText);
					
					if (!jsonAnswer) {
						listenerStatusChange('return_empty');

					} else if (jsonAnswer['error'] == 1) {
						listenerStatusChange('return_error');

					} else {
						if (jsonAnswer['answer'] == 'yes') {
							listenerStatusChange('return_yes');
						} else {
							listenerStatusChange('return_baddata');
						}
					}
				},
				onerror: function() {
					listenerStatusChange('parse_error');
				}
			});
		}


	// Request track data
		function listenerRequestTrackData() {
			if (listenerCheckServerStatus() == false) {
				listenerStatusBarChange('run first');
				return false;
			}
			
			listenerStatusBarChange('loading...');
			listenerClearTrackData();

			ti = getNowViewingTrackInfo();

			jsonArr = new Array();
			jsonArr['song'] = ti.song;
			jsonArr['album'] = ti.album;
			jsonArr['artist'] = ti.artist;
			// jsonArr['url_track'] = (trackUrl);
			jsonArr['station_id'] = detectStationID();
			jsonArr['station_name'] = detectStationName();
			
			log(json_url + '?question=sing_short&jsonArr=' + encodeURIComponent(serialize(jsonArr)));
			
			var resp = GM_xmlhttpRequest ({
				method: 'GET',
				url: json_url + '?question=sing_short&jsonArr=' + encodeURIComponent(serialize(jsonArr)),
				onload: function (response) {
					var jsonAnswer = $.parseJSON(response.responseText);
					if (!jsonAnswer) {
						log('ERROR 100. Answer is empty');
						listenerStatusBarChange('Error');
					}
					else if (jsonAnswer['error'] == 1) {
						log('ERROR 101. Error flag returhed');
						listenerStatusBarChange('Error');
					} else {
						// if success
						listenerEvaluateTrackData(jsonArr, jsonAnswer);
						listenerStatusBarChange('done!');
					}
				},
				onerror: function() {
					log('ERROR 102. Request ended with onError');
					listenerStatusBarChange();
				}
			});

		}

	// Evaluate
		function listenerStatusBarChange(status, src = 'request') {


			if (src == 'request') {
				status = 'Status: ' + status;
				$('#requestStatus').text(status);
			}
			if (src == 'server') {
				$('#serverStatus').html(status);
			}
		}
		function listenerClearTrackData() {
			$('#listenerContent').html('');
		}

		function listenerEvaluateTrackData(jsonArr, jsonAnswer) {
            console.log(jsonAnswer);
			listenerClearTrackData();
			key = jsonAnswer['key'];

			genreLine = jsonAnswer['metaBest']['comment'];
			if (genreLine && genreLine.indexOf('TR_GENRE] => ') != -1) {
				genreLine = genreLine.substring(genreLine.indexOf('TR_GENRE] => ') + 'TR_GENRE] => '.length, genreLine.length);
				genreLine = genreLine.substring(0, genreLine.indexOf(';'));
			}

			song = ''; album = ''; artist = '';
			for (k in jsonAnswer['metaResp']) {
				if (k != key) {
					divClass = 'class="dg"';
				} else {
					divClass = '';
				}

				song   += '<div ' + divClass + '>' + jsonAnswer['metaResp'][k]['title']  + '</div>';
				album  += '<div ' + divClass + '>' + jsonAnswer['metaResp'][k]['album']  + '</div>';
				artist += '<div ' + divClass + '>' + jsonAnswer['metaResp'][k]['artist'] + '</div>';

			}

            if (jsonAnswer['lyrics_url'] != undefined && jsonAnswer['lyrics_url'].length > 10) {
            	jsonAnswer['lyrics_url'] = '<a href="' + jsonAnswer['lyrics_url'] +      '" target="_blank">lyrics xml url</a>';
            } else {
            	jsonAnswer['lyrics_url'] = 'lyrics xml url';
            }
            
            if (jsonAnswer['lyrics_page_url'] != undefined && jsonAnswer['lyrics_page_url'].length > 10) {
            	jsonAnswer['lyrics_page_url'] = '<a href="' + jsonAnswer['lyrics_page_url'] + '" target="_blank">lyrics page url</a>';
            } else {
            	jsonAnswer['lyrics_page_url'] = 'lyrics page url';
            }

            if (jsonAnswer['lyrics_page_header'] != undefined && jsonAnswer['lyrics_page_header'].length > 3) {
            	// jsonAnswer['lyrics_page_header'] = '<a href="' + jsonAnswer['lyrics_page_header'] + '" target="_blank">lyrics page url</a>';
            } else {
            	jsonAnswer['lyrics_page_header'] = '';
            }

			if (jsonAnswer['lyrics'] != undefined && jsonAnswer['lyrics'].length > 10) {
            	jsonAnswer['lyrics'] = jsonAnswer['lyrics'].replace(/\r\n/g, "<br />").replace(/\n/g, "<br />");
            } else {
            	jsonAnswer['lyrics'] = '--- empty ---';
            }

			// listenerContent
				html = '\
				<table>\
					<tr><td class="g">  </td><td class="g">song</td></tr>\
					<tr><td class="g">P:</td><td class="s">' + jsonArr['song'] + '</td></tr>\
					<tr><td class="g">G:</td><td>' + song + '</td></tr>\
					\
					<tr><td class="g">  </td><td class="g">album</td></tr>\
					<tr><td class="g">P:</td><td class="s">' + jsonArr['album'] + '</td></tr>\
					<tr><td class="g">G:</td><td>' + album + '</td></tr>\
					\
					<tr><td class="g">  </td><td class="g">artist</td></tr>\
					<tr><td class="g">P:</td><td class="s">' + jsonArr['artist'] + '</td></tr>\
					<tr><td class="g">G:</td><td>' + artist + '</td></tr>\
				</table>\
				<table>\
					<tr>\
						<td class="g" colspan="2">' + genreLine + '</td>\
					</tr>\
					<!--tr>\
						<td class="g">metaBest :</td><td>' + jsonAnswer['metaBest']['genre'] + '</td>\
					</tr-->\
					<tr>\
						<td class="g">metaBest:</td><td>' + jsonAnswer['metaBest']['genre'] + '</td>\
					</tr>\
					<tr>\
						<td class="g">year :</td><td>' + jsonAnswer['metaBest']['date'] + '</td>\
					</tr>\
					<tr>\
						<td class="g">trackNo:</td><td>' + jsonAnswer['metaBest']['track'] + '</td>\
					</tr>\
				</table>\
				' + jsonAnswer['correlations'][0] + ' / ' + jsonAnswer['correlations'][1] + ' <br /><br />\
                <table>\
                    <tr><td class="g" style="width:45px">url:</td><td style="width:300px">' + jsonAnswer['lyrics_url']         + '</td></tr>\
                    <tr><td class="g" style="width:45px">url:</td><td style="width:300px">' + jsonAnswer['lyrics_page_url']    + '</td></tr>\
                    <tr><td class="g" style="width:45px">Art:Ttl:</td><td style="width:300px">' + jsonAnswer['lyrics_page_header'] + '</td></tr>\
                    <tr><td class="g">length:</td><td>' + jsonAnswer['lyrics_length'] + ' / ' + jsonAnswer['lyrics_height']    + '</td></tr>\
                    <tr style="height:23px;"><td class="g">lyrics:</td><td><div class="lyrics-cont">' + jsonAnswer['lyrics']   + '</td></tr>\
                </table>\
				';
				$('#listenerContent').append(html);
		}


(function () {
    removeAdvertisementsPanel();
	createDropDownMenu();
	populateMenus();
	makeLinks();
    allowTrackInfoToBeCopiedToClipboard();
    extendPlayTime();
    detectSongPlayed('_');

    $('.thumbUpButton a').bind('click', function() {
		downloadCurrentTrack('like');
	});
	/*
	$('.thumbUp a').each(function() {
	    $(this).on('click', function() {
	    	// downloadCurrentTrack();
			alert("test");
	    });
	})
	*/
})();


function detectSongPlayed(lastKSN) {
    var ti = getNowPlayingTrackInfo();
    if (ti.keySafeName != lastKSN) {
    	
    	listenerRequestTrackData();

        allowLyricsToBeCopiedToClipboard();
		updateBrowserTitleWithTrackInfo(ti);
	    registerCurrentTrack(ti);
	    if (GM_getValue('freemium_playExtendedCount', 0) == -1) {
			console.log('Song played after maximum extensions (3). User must be interacting. So, we can resume extending play.');
			GM_setValue('freemium_playExtendedCount', 0);
			extendPlayTime();
	    }
    }
    setTimeout(function () { detectSongPlayed(ti.keySafeName); }, 750);
}

function detectStationID() {
	stationID = undefined;
	url = document.location.href;
	url_part = 'station/play/';
	if (url.indexOf(url_part) != -1)
		stationID = url.substring(url.indexOf(url_part) + url_part.length, url.length);

	if (stationID != undefined && stationID)
		return stationID;
	else
		return false;
}

function detectStationName() {
	stationList = document.getElementById('stationList').getElementsByClassName('stationListItem');
	for (k in stationList) {
		if (stationList[k].className.indexOf('selected') != -1) {
			
			stationNode = stationList[k].getElementsByClassName('stationNameText');
			stationName = stationNode[0].innerHTML;
			if (stationName)
				return stationName;
		}
	}
}


var tracks = {};
function registerCurrentTrack(ti) {
	$.each(unsafeWindow.$.jPlayer.prototype.instances, function(i, el) {
		if (el.data('jPlayer').status.srcSet && !trackExists(el.data('jPlayer').status.src)) {
			tracks[ti.keySafeName] = { src: el.data('jPlayer').status.src, artSrc: getNowPlayingArtSrc(ti.keySafeName) };
			return false;
		}
	});
}




		function downloadCurrentTrack(elem) {
			var ti = getNowViewingTrackInfo();
			if (tracks[ti.keySafeName]) {
				initiateTrackDownload(ti, elem);
			} else {
				var npti = getNowPlayingTrackInfo();
				if (ti.song == npti.song && ti.artist == npti.artist && ti.album == npti.album) {
					var npRemainingTime = runtimeToSeconds($('.progress .remainingTime').text());
					// It can take a few seconds for the player to get the song duration and we need it to determine the current track from the jPlayer media.  So, we'll keep scanning for the track time to load.
					if (!npRemainingTime) {
						alert('No tracks have been load yet.');
						return;
					}
					var jp = unsafeWindow.$.jPlayer,
						npLen = runtimeToSeconds($('.progress .elapsedTime').text()) + npRemainingTime,
						thisSongDiff,
						lastSongDiff = 100000,
						srcStr;
					$.each(jp.prototype.instances, function(i, el) {
						if (el.data('jPlayer').status.srcSet)
						{
							thisSongDiff = Math.abs(npLen - el.data('jPlayer').status.duration);
							if (thisSongDiff < lastSongDiff)
								srcStr = el.data('jPlayer').status.src;
							lastSongDiff = thisSongDiff;
						}
					});
					if (srcStr) {
						tracks[ti.keySafeName] = { src: srcStr, artSrc: getNowPlayingArtSrc(ti.keySafeName) };
						initiateTrackDownload(ti, elem);
					}
					else
						alert('Unable to locate URL of current track. Allow track to play for a few seconds and try again.');
				}
				else
					alert('Unable to download this track. The URL wasn\'t registered. Try letting tracks play a little longer before skipping ahead. If this keeps happening, please report the problem.');
			}
		}

		function initiateTrackDownload(trackInfo, elem) {
			// var	trackUrl = tracks[trackInfo.keySafeName].src.replace(/(.+)access.+\?(.+)/g, '$1access/' + encodeURIComponent(trackInfo.filename) + '?$2'), 
			var	trackUrl = tracks[trackInfo.keySafeName].src.replace(/(.+)access.+\?(.+)/g, '$1access/' + (trackInfo.filename) + '?$2'), 
				artSrc = tracks[trackInfo.keySafeName].artSrc;
				
			jsonArr = new Array();
			jsonArr['song']         = trackInfo.song;
			jsonArr['album']        = trackInfo.album;
			jsonArr['artist']       = trackInfo.artist;
			jsonArr['url_track']    = (trackUrl);
			jsonArr['station_id']   = detectStationID();
			jsonArr['station_name'] = detectStationName();
			jsonArr['elem']         = elem;

			
			if (artSrc && GM_getValue('freemium__downloadart', false))
				jsonArr['url_cover'] = (artSrc);
			
			log('NOTICE: Sending request for track "' + jsonArr['artist'] + ' - ' + jsonArr['song'] + '"...');
			log(json_url + '?jsonArr=' + encodeURIComponent(serialize(jsonArr)));
			/**/
				GM_xmlhttpRequest ({
					method: 'GET',
					url: json_url + '?jsonArr=' + encodeURIComponent(serialize(jsonArr)),
					onload: function (response) {
						var jsonAnswer = $.parseJSON(response.responseText);
						if (!jsonAnswer) {
							log('ERROR 005: Bad responce data.');
						}
						else if (jsonAnswer['error'] == 1) {
							log('ERROR 002: Data parsed but download function execute is failed.');
							return false;
						} else {
							// song
							if (jsonAnswer['song']['code'] == 'exist')
								log('NOTICE: Song alredy exist');
							else if(jsonAnswer['song']['code'] == 200) {
								log('SUCCESS: Song successfully downloaded');
								log(jsonAnswer['meta']);
							} else
								log('ERROR 003: Pandora FTP server have blocked our connection for song download (wiht a page status: ' + jsonAnswer['song']['code'] + ')');

							// Cover
							if (jsonAnswer['cover']['code'] == 'exist')
								log('NOTICE: Cover alredy exist');
							else if(jsonAnswer['cover']['code'] == 'canceled')
								log('NOTICE: Cover downloading was disabled by user');
							else if(jsonAnswer['cover']['code'] == 200)
								log('SUCCESS: Cover successfully downloaded');
							else
								log('ERROR 004: Pandora FTP server have blocked our connection for cover download (wiht a page status: ' + jsonAnswer['cover']['code'] + ')');

						}
					},
					// onprogress: function () {log('onprogress');},
					// onreadystatechange: function() {log('onreadystatechange');},
					// onabort: function() {log('onabort');}, 
					onerror: function() {
						log('ERROR 001: Looks like Apache is not responding. Track info: ' + trackInfo.filename + '.');
					}
				});
			/** /
				// $.getJSON couldn't process response due GM @grant options is set!
				$.getJSON(json_url + '?jsonArr=' + encodeURIComponent(serialize(jsonArr)) + '&callback=?', function(jsonAnswer) {
					if (!jsonAnswer) {
						log('ERROR 005: Bad responce data.');
					}
					else if (jsonAnswer['error'] == 1) {
						log('ERROR 002: Data parsed but download function execute is failed.');
						return false;
					} else {
						// song
						if (jsonAnswer['song']['code'] == 'exist')
							log('NOTICE: Song alredy exist');
						else if(jsonAnswer['song']['code'] == 200) {
							log('SUCCESS: Song successfully downloaded');
							log(jsonAnswer['meta']);
						} else
							log('ERROR 003: Pandora FTP server have blocked our connection for song download (wiht a page status: ' + jsonAnswer['song']['code'] + ')');

						// cover
						if (jsonAnswer['cover']['code'] == 'exist')
							log('NOTICE: Cover alredy exist');
						else if(jsonAnswer['cover']['code'] == 200)
							log('NOTICE: Cover downloading was disabled by user');
						else if(jsonAnswer['cover']['code'] == 200)
							log('SUCCESS: Cover successfully downloaded');
						else
							log('ERROR 004: Pandora FTP server have blocked our connection for cover download (wiht a page status: ' + jsonAnswer['cover']['code'] + ')');

					}

				})
				.success(function() { log('NOTICE: Request successfuly sent.'); })
				// .error(function() { log('ERROR 001: Looks like Apache is not responding. Track info: ' + trackInfo.filename + '.'); })
				.complete(function() { log('NOTICE: AJAX reuest completely sent.'); });
			/**/
		}

		function download(src, filename, mime) {
			if ($.browser.mozilla) { // Works with Firefox Nightly as of 21.0a1 (2/12/2013)
				GM_xmlhttpRequest({
					method: 'GET',
					url: src,
					onload: function (respDetails) {
						var binResp = customBase64Encode(respDetails.responseText);
						var firstChild = document.querySelector('body *');
						var tag = document.createElement('a');
						tag.href = 'data:' + mime + ';base64,' + binResp;
						tag.download = filename;
						tag.target = '_blank';
						firstChild.parentNode.insertBefore(tag, firstChild);
						tag.click();
						firstChild.parentNode.removeChild(tag);
					},
					overrideMimeType: 'text/plain; charset=x-user-defined'
				});
			}
			else { // Works with Chrome as of 24.0.1312.57m (2/13/2013)
			    var	firstChild = document.querySelector('body *'),
			        tag = document.createElement('a');
			    tag.href = src;
			    tag.download = filename;
			    tag.target = '_blank';
			    firstChild.parentNode.insertBefore(tag, firstChild);
			    tag.click();
			    firstChild.parentNode.removeChild(tag);
			}
		}




function trackExists(src) {
	for (key in tracks) {
		if (tracks[key].src == src)
			return true;
	}
	return false;
}

function keySafeTrackFilename(ti) {
	var safeStr = (ti.song + ti.artist).toLowerCase();
	return '_' + safeStr.replace(/[^A-Za-z0-9]/g, '');
}

function isPandoraOne() {
	return $('DIV.logosubscriber:visible').length;
}

function runtimeToSeconds(runtime) {
	try {
		runtime = $.trim(runtime.replace('-', ''));
		var timeParts = runtime.split(':');
		return (safeParseInt(timeParts[0]) * 60) + safeParseInt(timeParts[1]);
	}
	catch (err) {
		return 0;
	}
}

function safeParseInt(str) {
	var val = parseInt(str);
	return (isNaN(val) ? 0 : val);
}

function getNowViewingTrackInfo() {
	return constructTrackInfo('#trackInfo', 'A.songTitle', 'A.artistSummary', 'A.albumTitle');
}

function getNowPlayingTrackInfo() {
	return constructTrackInfo('#playerBar', 'A.playerBarSong', 'A.playerBarArtist', 'A.playerBarAlbum');
}

function constructTrackInfo(selTrackInfo, selSong, selArtist, selAlbum, artSrc) {
	var	$trackInfo = $(selTrackInfo + ':first'),
		trackInfo = new Object();
	trackInfo.song = $.trim($trackInfo.find(selSong + ':first').text());
	trackInfo.artist = $.trim($trackInfo.find(selArtist + ':first').text());
	trackInfo.album = $.trim($trackInfo.find(selAlbum + ':first').text());
	trackInfo.keySafeName = keySafeTrackFilename(trackInfo);
	// TODO: use el.data('jPlayer').status.formatType for filename instead (test with free Pandora first ... m4a)
	trackInfo.filename = ((trackInfo.artist + ' - ' + trackInfo.album + ' - ' + trackInfo.song).replace('/', '')) + (isPandoraOne() ? '.mp3' : '.m4a');
	trackInfo.artFilename = (trackInfo.artist + ' - ' + trackInfo.album).replace('/', '') + '.jpg';
	return trackInfo;
}

// Reliably getting the album art has turned out to be tricky. When a track first starts playing, the text
// describing the track gets updated, but there is sometimes a delay before the image source is updated.
// This results in the album art for the previous track being associated with the current track. To solve the
// problem, I'm going to wait until the album art changes by comparing the current source with the last one.
// It's not airtight, but hopefully it will work most of the time.
var lastArtSrc = null; // seed the last art source value so there's a difference when the first art appears
function getNowPlayingArtSrc(keySafeName, callCount) {
	// since we're going to keep running this routine to check for the source change, we need to know how
	// many times we've checked. If this is the first time running, then we can let the caller register
	// the art source. We'll also use the count to limit how long we'll look for the album art, in case of
	// failure.
	callCount || (callCount = 0);

	// fetch the art album source
	var	src = null,
		$img = $('IMG.playerBarArt:first');
	if ($img.length) {
		src = $.trim($img.attr('src'));
		if (src.indexOf('no_album_art') != -1)
			src = null;
	}

	if (src != lastArtSrc) { // detect new album art shown
		lastArtSrc = src;
		if (callCount > 0) // for increased reliability, we'll only update the tracks register if getNowPlayingArtSrc() called itself
			tracks[keySafeName].artSrc = src;
	}
	else {
		if (callCount == 0) // if the old art source is still showing, we want to return null to the initial caller
			src = null;
		if (callCount < 33) // stop checking after 5 seconds, assume failure
			setTimeout(function () { getNowPlayingArtSrc(keySafeName, ++callCount); }, 150); // wait a little while and then check again for the updated art source
	}

	return src; // the return value is only useful for the initial caller (example: registerCurrentTrack()) to initialize artSrc to null or, if art source is already updated, the actual src
}

function getNowPlayingStation() {
	return $.trim($('.stationChangeSelectorNoMenu:first').text());
}




function stringToID(unsafeString) {
	return '_id_' + unsafeString.replace(/ |\.|-|_/g, '');
}

// http://emilsblog.lerch.org/2009/07/javascript-hacks-using-xhr-to-load.html
function customBase64Encode(inputStr) {
	var bbLen = 3,
		enCharLen = 4,
		inpLen = inputStr.length,
		inx = 0,
		jnx,
		keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'
			+ '0123456789+/=',
		output = '',
		paddingBytes = 0,
		bytebuffer = new Array(bbLen),
		encodedCharIndexes = new Array(enCharLen);

	while (inx < inpLen) {
		for (jnx = 0; jnx < bbLen; ++jnx) {
			// Throw away high-order byte, as documented at:
			// https://developer.mozilla.org/En/Using_XMLHttpRequest#Handling_binary_data
			if (inx < inpLen)
				bytebuffer[jnx] = inputStr.charCodeAt(inx++) & 0xff;
			else
				bytebuffer[jnx] = 0;
		}

		/*--- Get each encoded character, 6 bits at a time.
			index 0: first 6 bits
			index 1: second 6 bits
				(2 least significant bits from inputStr byte 1
				+ 4 most significant bits from byte 2)
			index 2: third 6 bits
				(4 least significant bits from inputStr byte 2
				+ 2 most significant bits from byte 3)
			index 3: forth 6 bits (6 least significant bits from inputStr byte 3)
		*/
		encodedCharIndexes[0] = bytebuffer[0] >> 2;
		encodedCharIndexes[1] = ((bytebuffer[0] & 0x3) << 4) | (bytebuffer[1] >> 4);
		encodedCharIndexes[2] = ((bytebuffer[1] & 0x0f) << 2) | (bytebuffer[2] >> 6);
		encodedCharIndexes[3] = bytebuffer[2] & 0x3f;

		//--- Determine whether padding happened, and adjust accordingly.
		paddingBytes = inx - (inpLen - 1);
		switch (paddingBytes) {
			case 1:
				// Set last character to padding char
				encodedCharIndexes[3] = 64;
				break;
			case 2:
				// Set last 2 characters to padding char
				encodedCharIndexes[3] = 64;
				encodedCharIndexes[2] = 64;
				break;
			default:
				// No padding - proceed
				break;
		}

		// Now grab each appropriate character out of our keystring,
		// based on our index array and append it to the output string.
		for (jnx = 0; jnx < enCharLen; ++jnx)
			output += keyStr.charAt(encodedCharIndexes[jnx]);
	}

	return output;
}




// ###########################################
// ###### CONTROLS ###########################
// ###########################################
	function downloadArtOptionChanged() {
		if ($(this).attr('checked') == 'checked')
			GM_setValue('freemium__downloadart', true);
		else
			GM_deleteValue('freemium__downloadart');
	}

	function createDropDownMenu() {
		// If user menu is not using all of its available horizontal space, then we'll shrink it
		// so that our new menu will not have an awkward space to the right of it.
		var $userMenu = $('#brandingBar .rightcolumn .user_menu');
		var userMenuCSSWidth = $userMenu.css('width');
		$userMenu.css('width', 'auto');
		if ($userMenu.width() > parseInt(userMenuCSSWidth))
			$userMenu.css('width', userMenuCSSWidth);

		// Add our custom menu
		$('#brandingBar .rightcolumn').append(
			'<style type="text/css"> .adSupported-layout #adLayout, .adSupported-layout .footerContainer {width: 800px !important; } .adSupported-layout .contentContainer {width: 800px !important; float: none !important; } #trackInfo .contents .downloadButton {float:right; background: url("data:image/png;base64,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 YAAAOpgAABdvkl/FRgAAAwBQTFRFAAAA////+/v85OXo4+Tn1Nbb0NPaTlZmf4WRfoSQiI6aoaaw ub3FwsbOyMvRxsnP1dfb+Pn7Q0xcT1dmWmJxWWFwZW17b3eFZ257cnmGeoGOe4KPipCbiY+anaOu nKKtoqewq7C5tLnCvcLL19zlvsLJvMDHu7/Gw8fO1dng1Njf6Ozz5+vyTldmWGFwZm57cXmGcHiF b3eEc3qGfIOPfoWRfYSQho2ZhYyYfoWQkZijkJeimJ+qm6KtmqGsmaCrlZullJqkn6WvnqSunaOt p623sLbAuL7IrbK6v8TMyc7WyczR7vH27fD1WmNxWWJwcnqGcXmFe4OPpay2n6WuuL/JoaewoKav u8HKusDJvMLLx83W3+Xu3ePsv8PJz9PZ7fH37PD26+/16u706e3z4OTq9ff69Pb58/X46+3wvcPL zdPbxszUxcvT4Obu2+Hp1tzk4efvys/WxsvSxcrR3OHo6O305uvy5erx5Onw8PP37/L23uHlkJii mqKssLfAr7a/wsnS5+zy5uvx0dXa7vL37fH26+/06u7z4+fsusHJ3uXt3eTs2+LqvsTL2uDn0dfe 4+nw4ujvvcTL2N/m197l5evx6/D16e7z0tba5+vv8fT39/n79vj69Pb46u3v////AAAAAAAAAAAA AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAArDP5agAAAKF0 Uk5T//////////////////////////////////////////////////////////////////////// //////////////////////////////////////////////////////////////////////////// /////////////////////////////////////////////////////////////////wCxoIKGAAAC D0lEQVR42qTU2VsSURjH8aMtVLZxWIyWgRahGicRLLWSSmgXkKwYp3pbJFDEU6dlOJJlHVLJyjpT nhZb+FO7mDsfr6bPze953uf53r6oGXGuiSI7W1ucad0QQeHfc/POzP0JI5i/79Q9QPDNOUDwwDlA MOYcIDBN0zRNjDFuv2WuxWhffRkYsBcQqKqqqip2u903s+paLgdWXwYH7QUEnHPOOeacvzg+yxOa luAxzmMVfqpS6YvhGF8K8Fkf7nvOR3zY95mPaFrPdTsCBFJKKSWWUsp8aso7M+NdDLxezAQ/4e9l fHciM/UlILUdEycS0v/2V/8rqf34603aESBgjDHGMGOMJZPBEOehYP5ksL/nwiZWjheqwwfLgdH4 WPVwN/t5/ow3ORovVENJOwIEQgghBBZCiO4DiiKEojzxX9vmeblfGFEhooYRNaJCGNGvWq+yVzGi QiiKHQECy7Isy8KWZZ32Fq5kJyezS/WMnw/7j1p6zrJyup7jnovLu/M3dr074k9xz5vlbMqOAAGl lFKKMcZ7NlLW5nK1ddKzXfRc1zTVc5TmdD1Ht/rwloedAXxsXy+9pLk2p+wIEBBCCCHFYrE4NE1I o1RqELKwQhZWCKnXCKnV6zXCx4tpSmrF8XSaFEqloYYdAYLbzgGC9879bx3++MGpRxHU8fTQHWce P+tALeu3O/yJV9c1/w0A0C7qII4nYmsAAAAASUVORK5CYII=") no-repeat; cursor: pointer; height: 22px; margin-top: 5px; width: 61px; }  #trackInfo .buttons {width: 64px !important; } #trackInfo .info DIV#dllinks {padding: 4px 10px 0 0; white-space: normal; } #dllinks .dllink {-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=20)"; cursor: pointer; filter: alpha(opacity=20); height: 16px; margin: 5px 0 0 5px; opacity: 0.2; -webkit-transition: opacity 0.4s; -moz-transition: opacity 0.4s; -o-transition: opacity 0.4s; -ms-transition: opacity 0.4s; transition: opacity 0.4s; width: 16px; } #dllinks:hover .dllink {-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=100)"; filter: alpha(opacity=100); opacity: 1; } #freemium .section-item {color: #000 !important; font-size: 13px !important; font-weight: bold !important; margin-left: 12px; text-shadow: none !important; } </style> <div style="display: inline; float: right;"> <div style="position: relative; display: block; float: left;"> <div id="freemium" class="user_activator" style="border-left: 1px solid transparent; border-right: 1px solid transparent; cursor: pointer; float: right; margin: -6px 4px 0; max-width: 140px; padding: 6px 10px 8px; position: relative;"> <div style="background: url(\'/img/user-menu-down-arrow.png\') no-repeat scroll right center transparent; float: right; height: 13px; width: 10px; display: block;"></div> <div style="display: block; float: left;">Freemium</div> <div id="freemium_menu" class="dd_container" style="visibility: hidden; display: none; position: absolute; right: -1px; top: 30px; z-index: 1;"> <form id="fmform"> <ul> <li class="menu" style="padding-bottom: 14px; padding-top: 7px;"> <div class="section-item">Options:</div> <br /><br /> <div style="color: #000 !important; font-size: 13px !important; margin-left: 12px; text-shadow: none !important;"> <label title="Filenames for artwork will be incorrect for older browsers"><input type="checkbox" id="freemium_download_art" /> Download artwork</label> &nbsp; </div> </li> </ul> </form> </div> </div> </div> <div style="display: block; float: left;">&nbsp; | &nbsp; </div> </div>'
		);
		// Roll our own simple menu events that depend on mousing out of the menu to hide it (or
		// never mousing onto it after clicking the menu item)
		$('#freemium').click(function() {
			$('#freemium_menu').show().css('visibility', 'visible');
			freemiumRollOut();
		});
		$('#freemium_menu').mouseout(function() { freemiumRollOut(); });
		$('#freemium').mouseover(function() { clearTimeout(freemiumClearHandle); });

		$('.freemium-menu-action').click(function(e) {
			hideFreemiumMenu();
			eval($(this).attr('freemium-action'));
			e.preventDefault();
			return false;
		});
	}

	function freemiumRollOut() {
		clearTimeout(freemiumClearHandle);
		freemiumClearHandle = setTimeout(function() {
			hideFreemiumMenu();
		}, 1000);
	}

	function hideFreemiumMenu() {
		$('#freemium_menu').hide();
	}

	function populateMenus() {
		var idtxt, alttxt, disabled;

		var downloadArtOpt = GM_getValue('freemium__downloadart', false);
		if (downloadArtOpt)
			$('#freemium_download_art').attr('checked', 'checked');
		$('#freemium_download_art').click(downloadArtOptionChanged);
	}

	function makeLinks() {
		addDownloadLink();
		addDownloadLink2();
	}

	function addDownloadLink() {
		$('#trackInfo .contents').append(
			$('<div class="downloadButton" />').click(function(){
				downloadCurrentTrack('button');
			})
		);
	}

	function addDownloadLink2() {
		$('#trackInfo .contents').append(
			$('<button class="requestTrackDataButtom">track</button>').click(listenerRequestTrackData)
		);
	}




// ###########################################
// ###### ADVERTISMENTS ######################
// ###########################################
	function removeAdvertisementsPanel() {
		$('#ad_container').remove();
	}

	function allowLyricsToBeCopiedToClipboard() {
		$('.lyricsText')
			.removeClass('unselectable')
			.removeAttr('unselectable')
			.removeAttr('style')
			.prop('onmousedown', null)
			.prop('onclick', null)
			.prop('ondragstart', null)
			.prop('onselectstart', null)
			.prop('onmouseover', null);
	}

	function allowTrackInfoToBeCopiedToClipboard() {
		$('#trackInfo').removeClass('unselectable');
	}

	function updateBrowserTitleWithTrackInfo(ti) {
		if (ti.song.length && ti.artist.length && ti.album.length)
			document.title = ti.song + ' by ' + ti.artist + ' on ' + ti.album;
		else {
			var station = getNowPlayingStation();
			if (station.length)
				document.title = station + ' on Pandora';
			else
				document.title = 'Pandora';
		}
	}

	GM_setValue('freemium_playExtendedCount', 0);
	function extendPlayTime() {
		var playExtendedCount = GM_getValue('freemium_playExtendedCount', 0);
		var $stillListeningEl = $('A.still_listening');
	    if ($stillListeningEl.length) {
	        if (playExtendedCount > -1 && playExtendedCount < 3) {
	            $stillListeningEl[0].click();
	            playExtendedCount++;
	            console.log('play extended ' + playExtendedCount + ' time(s)');
	        }
	        else
	            playExtendedCount = -1;
			GM_setValue('freemium_playExtendedCount', playExtendedCount);
	    }
	    if (playExtendedCount > -1)
	        setTimeout(extendPlayTime, 2000);
	}






