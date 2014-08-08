<?php
function to_translit($st) {
	$st = preg_replace("/[^0-9a-zA-Zа-яА-Я.-_)(]/", '-', $st);
	$st = str_replace('^', '-', $st);
	$st=strtr($st,	"абвгдеёзийклмнопрстуфхъыэ",
					"abvgdeeziyklmnoprstufh'ie");
	$st=strtr($st,	"АБВГДЕЁЗИЙКЛМНОПРСТУФХЪЫЭ",
					"ABVGDEEZIYKLMNOPRSTUFH'IE");
	$st=strtr($st, 
		array(
		"ж"=>"zh", "ц"=>"ts", "ч"=>"ch", "ш"=>"sh", 
		"щ"=>"shch","ь"=>"", "ю"=>"yu", "я"=>"ya",
		"Ж"=>"ZH", "Ц"=>"TS", "Ч"=>"CH", "Ш"=>"SH", 
		"Щ"=>"SHCH","Ь"=>"", "Ю"=>"YU", "Я"=>"YA",
		"ї"=>"i", "Ї"=>"Yi", "є"=>"ie", "Є"=>"Ye")
	);
	return $st;
}

##########################
### USER FUNCTIONS #######
##########################

#	list($usrID, $usrLogin, $usrActive, $usrEmail, $usrName) = array(checkUser());
#	$usrActive = TRUE		$usrID = null
#	$usrActive = FALSE		$usrID = 19
function checkUser(){
	global $prefix, $usrID, $usrLogin, $usrEmail, $usrName, $usrActive;
	if ($usrID == 0) {
		$usrID = null; $usrLogin = null; $usrEmail = null; $usrName = null; $usrActive = null;
		$key=strip_tags($_COOKIE['key']);
		if (strlen($key) >= 1) {
			$select=mysql_query("SELECT `id`, `login`, `name`, `email`, `active` FROM `".$prefix."users` WHERE `key`='".$key."'");
			if ($select AND mysql_num_rows($select) >= 1) {
				$row = mysql_fetch_object($select);
				$usrID = $row->id;
				$usrLogin = $row->login;
				$usrEmail = $row->email;
				$usrName = $row->name;
				if ($row->active == 1) {$usrActive = TRUE;} else {$usrActive = FALSE;}
			} else {$usrID = null;}
			$select = null; $row = null; $key = null;
		} else {$usrID = null;}
	}
	return (array($usrID, $usrLogin, $usrActive, $usrEmail, $usrName));
}


#	Y M D H I S			- Год Месяц День Часы Менуты Секунды с ведущими нулями
#	y m d h i s			- Год Месяц День Часы Менуты Секунды без ведущих нулей
#	mon moni mons		- Месяц Месяца Меч		(январЬ) (январЯ) (Янв)
#	wday wdayi wdays	- День В день Дн		(Среда) (Среду) (СР)
function showDate($date=null, $mask='d moni'){
	if (stristr($date, '0000') == TRUE) {return null;}
	if ($date == null) {$date = date("Y.m.d H:i:s");}
	$Y = substr($date, 0, 4);	$y = substr($Y, 2, 4);
	$M = substr($date, 5, 2);	if ($M < 10) {$m = str_replace('0', null, $M);} else {$m = $M;}
	$D = substr($date, 8, 2);	if ($D < 10) {$d = str_replace('0', null, $D);} else {$d = $D;}
	$H = substr($date, 11, 2);	if ($H < 10) {$h=str_replace('0', null, $H);} else {$h = $H;}
	$I = substr($date, 14, 2);	$i = $I;
	$S = substr($date, 17, 2);	$s = $S;

	$getdate = getdate(mktime($H, $I, $S, $M, $D, $Y));
	$DaysOfWeek = array("Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота");
	$DaysOfWeek_i = array("Воскресенье", "Понедельник", "Вторник", "Среду", "Четверг", "Пятницу", "Субботу");
	$DaysOfWeek_s = array("ВС", "ПН", "ВТ", "СР", "ЧТ", "ПТ", "СБ");
	$wd = $getdate['wday'];		$wday = $DaysOfWeek[$getdate['wday']];
	if ($wd == 0) {$wd = 7;}	$wdays = $DaysOfWeek_s[$getdate['wday']];
								$wdayi = $DaysOfWeek_i[$getdate['wday']];

	$month = array("Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь");
	$months = array("Янв", "Фев", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сен", "Окт", "Ноя", "Дек");
	$monthi = array("Января", "Февраля", "Марта", "Апреля", "Мая", "Июня", "Июля", "Августа", "Сентября", "Октября", "Ноября", "Декабря");
	$mon = $month[$m-1];		$moni = $monthi[$m-1];
								$mons = $months[$m-1];

	$mask = str_replace('moni', $moni, $mask);
	$mask = str_replace('mons', $mons, $mask);
	$mask = str_replace('mon', $mon, $mask);
	$mask = str_replace('wdays', $wdays, $mask);
	$mask = str_replace('wdayi', $wdayi, $mask);
	$mask = str_replace('wday', $wday, $mask);
	$mask = str_replace('wd', $wd, $mask);
	$mask = str_replace('Y', $Y, $mask);
	$mask = str_replace('M', $M, $mask);
	$mask = str_replace('D', $D, $mask);
	$mask = str_replace('H', $H, $mask);
	$mask = str_replace('I', $I, $mask);
	$mask = str_replace('S', $S, $mask);
	$mask = str_replace('y', $y, $mask);
	$mask = str_replace('m', $m, $mask);
	$mask = str_replace('d', $d, $mask);
	$mask = str_replace('h', $h, $mask);
	$mask = str_replace('i', $i, $mask);
	$mask = str_replace('s', $s, $mask);
	return ($mask);
}

# $dots_add [TRUE, FALSE] - добавлять $dots в конец текста, даже если он короче $len.
function cropStr($txt, $len = 100, $dots = null, $dots_add = FALSE){
	if (strlen(strip_tags($txt)) > $len OR $dots_add != FALSE) {
		$len = intval($len); $txt = trim($txt);
		$kz = 0; $a=null; $t_len=null; $pos=0; $offset=0; $ignore_tags=null; $ots=null; $matches=null; $k=null; $tag=null; $t_nm=null;
		if(strlen(strip_tags($txt)) > $len+7){
			while ($a<=$len-10) {
				$t_len = strlen(substr_replace($txt, null, $len+$t_len)) - strlen(strip_tags(substr_replace($txt, null, $len+$t_len)));
				$a = strlen(strip_tags(substr_replace($txt, null, $len+$t_len)));
				if($kz >= 80) {break;} $kz++;
			}
			$txt=substr_replace($txt, null, $t_len+$len+7);
			if (strripos($txt, '>') < strripos($txt, '<') AND strripos($txt, '<') - strripos($txt, '>')>=5) {
				$txt=substr_replace($txt, null, strripos($txt, '<'));
			}

			if($len>150){$offset = $t_len + $len-50;}
			elseif($len>50){$offset = $t_len + $len-30;}
			else{$offset = $t_len + $len;}
			if($pos==0){$pos=@strripos($txt, '. ', $offset);}
			if($pos==0){$pos=@strripos($txt, '.', $offset);}
			if($pos==0){$pos=@strripos($txt, ' ', $offset);}
			if($pos==0){$pos=$t_len + $len;}
			$txt=substr_replace($txt, null, $pos+1);
			$txt=trim($txt);
			$ignore_symb = array(',');
			if (in_array($txt[strlen($txt)-1], $ignore_symb) == TRUE) {$txt = substr_replace($txt, null, strlen($txt)-1);}
	    }
		$txt .= $dots;

		$ignore_tags = array('img', 'br', 'hr');
		if (preg_match_all("/<(\/?)(\w+)/", $txt, $matches, PREG_SET_ORDER)) {
			$ots = array();
			foreach ($matches as $k => $tag) {
				$t_nm = strtolower($tag[2]);
				if ($tag[1]) {
					if (end($ots) == $t_nm) array_pop($ots);
				} else {
					if (!in_array($t_nm, $ignore_tags)) array_push($ots, $t_nm);
				}
			};
			while ($tag = array_pop($ots)) {
				$txt .= "</$tag>";
			}
		}
	}
	return $txt;
}

# $mode = 1 - любое из слов в $search
# $mode = 3 - точная  фраза в $search
# $search - слова через " " или фраза
# внутр. переменная $clr$ заменяется на цвет
function markStr($text, $search, $mode = 3) {
	$text = str_replace("\r\n", ' ', $text);
	$text = str_replace("\t", ' ', $text);
	$text = str_replace('  ', ' ', $text);
	if ($mode == 1) {
		$str = split(' ', $search);
	} else {
		$str[] = $search;
	}
	while (stristr($search, '  ') == TRUE) {$search = str_ireplace('  ', ' ', $search);}
	$mark_color = array('#FFFF80', '#80FF80', '#80FFFF', '#0080FF', '#A080FF', '#A05080', '#FF8080', '#C0C0C0');
	$mark_t1 = '<span style="background: $clr$; font-weight: bold;">';
	$mark_t2 = '</span>';
	$text_res = null;
	$text_mark = $text;
	for ($i = 0; $i<count($str); $i++) {
		if (strlen($str[$i]) >= 1) {
			if ($text_res != null) {$text_mark = $text_res;}
			$text_res = null; $k = 0;
			while (stristr($text_mark, $str[$i]) == TRUE) {
				$pos = stripos($text_mark, $str[$i]);
				# $replace для сохранения заглавных букв
				$replace = substr_replace($text_mark, null, 0, $pos);
				$replace = substr_replace($replace, null, strlen($str[$i]));
				$pos_t2 = stripos($text_mark, '>', $pos);
				$pos_t1 = strripos(substr_replace($text_mark, null, $pos_t2 + strlen($str[$i])), '<');
				# если слово между < и > ИЛИ перед словом >, а после < ИЛИ слово перед > но < спереди нету
				if (($pos > $pos_t1 AND intval($pos_t2) != 0) OR ($text_mark{$pos-2} == '>' AND $text_mark{$pos+strlen($str[$i])} == '<') OR (intval($pos_t1) == 0 AND intval($pos_t2) != 0)) {
					$text_res .= substr_replace($text_mark, $str[$i], $pos);
				} else {
					$text_res .= substr_replace($text_mark, str_ireplace('$clr$', $mark_color[ (($i<=count($mark_color))?$i:0) ], $mark_t1).$replace.$mark_t2, $pos);
				}
				$text_mark = substr_replace($text_mark, null, 0, $pos+strlen($str[$i]));
				# Защита от зацикливания
				if ($k >= strlen($str[$i])*50) {break;}
				$k++;
			}
			$text_res .= $text_mark;
		}
	}
	return $text_res;
}

# Пример массива 'таблица: столбец, столбец, столбец'
# $tables[0] = 'content2: title, full_text, date';
# $tables[1] = 'news_parse: title';
# $tables[35] = 'DDD: dddd1, dddd2, dddd3, dddd4';
function search_db($tables, $search, $mode = 1) {
	while (stristr($search, '  ') == TRUE) {
		$search = str_ireplace("  ", " ", $search);
	}
	$tables = array_values($tables);
	for ($i=0; $i<count($tables); $i++) {
		list($table[$i], $fields[$i]) = split(':', $tables[$i]);
		$field[$i] = split(',', trim($fields[$i]));

		for ($k=0; $k<count($field[$i]); $k++) {
			if (strlen(trim($field[$i][$k])) > 0 ) {
				$field[$i][$k] = trim($field[$i][$k]);
			} else {unset($field[$i]); unset($table[$i]);}
		}

		for ($k=0; $k<count($table[$i]); $k++) {
			if (strlen(trim($table[$i][$k])) > 0) {
				$table[$i][$k] = trim($table[$i][$k]);
			} else {unset($table[$i]); unset($field[$i]);}
		}
	}
	$table = array_values($table);
	$field = array_values($field);

	for ($i = 0; $i<count($table); $i++) {
		if ($mode == 1) {
			$query_s = null;
			$str = split(' ', $search);
			for ($m=0; $m<count($str); $m++) {
				if (strlen(trim($str[$m])) >= 1) {
   		    		for ($k=0; $k<count($field[$i]); $k++) {
						if ($query_s != null) {$query_s .= ' OR ';}
						$query_s .= '`'.$field[$i][$k].'` LIKE \'%'.addslashes($str[$m]).'%\'';
					}
				}
			}
		} elseif ($mode == 2) {
			$query_s = null;
			$str = split(' ', $search);
			$query_s .= '(';
			for ($m=0; $m<count($str); $m++) {
				if (strlen(trim($str[$m])) >= 1) {
   		    		for ($k=0; $k<count($field[$i]); $k++) {
   		    			if ($m != 0 AND $mm != $m) {$query_s .= ') AND (';}
   		    			elseif($k != 0 ) {$query_s .= ' OR ';}
						$query_s .= '`'.$field[$i][$k].'` LIKE \'%'.addslashes($str[$m]).'%\'';
						$mm = $m;
					}
				}
			}
			$query_s .= ')';
		} else {
			$query_s = null;
			$str = $search;
    		for ($k=0; $k<count($field[$i]); $k++) {
				if ($query_s != null) {$query_s .= ' OR ';}
				$query_s .= '`'.$field[$i][$k].'` LIKE \'%'.addslashes($str).'%\'';
			}
		}
		$result[$i] = mysql_query('SELECT `id` FROM `'.$table[$i].'` WHERE  '.$query_s.' LIMIT 0, 1000');
	}

	$id = null;
	for ($i=0; $i<count($result); $i++) {
		$row = null;
		while($row = mysql_fetch_object($result[$i])) {
			if ($id[$i] != null)  {$id[$i] .= ',';}
			$id[$i] .= $row->id;
		}
	}
	return (array($tables, $id));
}

# pager
function get_pager($table, $where) {
		global $self, $query_str;
		// $table = $prefix.'main';
		// $where = '';
		$pg = array(10, 20, 50);
		// $default = 0;

		if ($where AND !stristr($where, 'where'))
			$where = 'WHERE '.$where;
		$query = "SELECT COUNT(`id`) FROM `".$table."` ".$where;
		$sel = mysql_query($query);
		
		$disp = ((intval($_GET['disp']) == 0)?$pg[intval($default)]:intval($_GET['disp']));
		$count = ceil(mysql_result($sel, 0) / $disp);

		$p = ((intval($_GET['p']) == 0 OR intval($_GET['p']) > $count)?1:intval($_GET['p']));
		$links_page = null; $links_disp = null; $limit = null;
		for ($i=1; $i<=$count; $i++) {
			if ($links_page != null) {$links_page .= ' | ';}
			if ($i == $p) {
				$links_page .= '<b>'.$i.'</b>';
			} else {
				$links_page .= '<a href="'.$base_dir.'/'.$self.getQueryStr($query_str, 'p', $i).'">'.$i.'</a>';
			}
		}
		if ($p > 1) {$links_prev = '<a href="'.$base_dir.'/'.$self.getQueryStr($query_str, 'p', ($p-1)).'">Назад</a>';}
		else {$links_prev = '';}
		if ($p < $count) {$links_next = '<a href="'.$base_dir.'/'.$self.getQueryStr($query_str, 'p', ($p+1)).'">Вперед</a>';}
		else {$links_next = '';}

		for ($i=0; $i<=count($pg); $i++) {
			if($links_disp != null) {$links_disp .= ' | ';}
			if ($pg[$i] == $disp) {
				$links_disp .= '<b>'.$pg[$i].'</b>';
			} else {
				$links_disp .= '<a href="'.$base_dir.'/'.$self.getQueryStr($query_str, 'disp', $pg[$i]).'">'.$pg[$i].'</a>';
			}
			if ($i == count($pg)) {
				if ($disp == 99999) {
					$links_disp .= '<b>Все</b>';
				} else {
					$links_disp .= '<a href="'.$base_dir.'/'.$self.getQueryStr($query_str, 'disp', '99999').'">Все</a>';
				}
			}
		}
		$limit = ' LIMIT '.(($p-1)*$disp).', '.$disp;
		$links_page = '<b>Страница:</b> '.$links_page.'';
		$links_disp = '<b>Показывать:</b> '.$links_disp.'';
		$links['page'] = $links_page;
		$links['disp'] = $links_disp;
		$links['prev'] = $links_prev;
		$links['next'] = $links_next;
		return array($limit, $links);
}



##########################
### SYSTEM FUNCTIONS #####
##########################

function linkCode($str) {
	$str=bin2hex($str); $str=$str[1].$str[3].$str.$str[strlen($str)-4].$str[strlen($str)-2];
	return($str);
}
function linkDecode($str) {$str=substr($str, 2); $str=substr($str, 0, strlen($str)-2); $str=hex2bin($str); return($str);}
function linkCheck($str){
	if(strlen($str) > 7 AND $str[0]==$str[3] AND $str[1]==$str[5] AND $str[strlen($str)-2]==$str[strlen($str)-6] AND $str[strlen($str)-1]==$str[strlen($str)-4]){return TRUE;}
	else{return FALSE;}
}/*
function hex2bin($hexdata){
    for ($i=0; $i<strlen($hexdata); $i+=2) {$bindata .= chr(hexdec(substr($hexdata,$i,2)));} return $bindata;
}
*/
function checkEmail($email){
	if (preg_match('/^([a-z0-9_]|\\-|\\.)+'.'@'.'(([a-z0-9_]|\\-)+\\.)+'.'[a-z0-9]{2,4}$/i', $email)) {return TRUE;} else {return FALSE;}
}


# $var - имя переменной или массив имен array("var1", "var2");
# $val - значение (даже если var - массив) или массив;
# $amp - если TRUE то & преобразуется в &apm;
function getQueryStr($query_str = null, $var = null, $val = null, $amp = FALSE){
	if ($query_str == null) {global $query_str;}
	$query_str_n = str_replace('&amp;', '&', $query_str);
	if (strlen($query_str_n) >= 2 AND $query_str_n[0] != '?') {
		$query_str_n = '?'.$query_str_n;
	}
	if (is_array($var) == TRUE) {
		for ($i=0; $i<=count($var); $i++) {
			if (is_array($val) == TRUE) {
				$val_a = $val[$i];
			} else {$val_a = $val;}
			$query_str_n = sub_getQueryStr($query_str_n, $var[$i], $val_a);
		}
	} else {
		$query_str_n = sub_getQueryStr($query_str_n, $var, $val);
	}
	if($query_str_n[0] == '&') {$query_str_n[0]='?';}
	if ($amp == TRUE) {
		$query_str_n = str_replace('&', '&amp;', $query_str_n);
	}
	return($query_str_n);
}

function sub_getQueryStr($query_str, $var, $val){
	if ($var!=null) {
		if (strlen($query_str) >= 1) {
			if (stristr($query_str, '?'.$var.'=') == TRUE) {
				if ($val == null) {$replace = null;} else {$replace = '?'.$var.'='.$val;}
				if(stristr($query_str, '&') == FALSE) {
					$query_str = $replace;
				} else {
					$query_str = substr_replace($query_str, $replace, stripos($query_str, '?'.$var.'='), stripos($query_str, '&', stripos($query_str, '?'.$var.'=')+strlen('?'.$var.'=')));
				}
			}
			elseif(stristr($query_str, '&'.$var.'=')==TRUE){
				$pos= stripos($query_str, '&', stripos($query_str, '&'.$var.'=')+1)-stripos($query_str, '&'.$var.'=');
				if($pos<=0){$pos=strlen(substr_replace($query_str, null, 0, strripos($query_str, '&'.$var.'=')));}
				if ($val == null) {$replace = null;} else {$replace = '&'.$var.'='.$val;}
				$query_str = substr_replace($query_str, $replace, stripos($query_str, '&'.$var.'='), $pos);
			}
			else{
				if ($val!=null) {$query_str.='&'.$var.'='.$val;}
			}
		}else{
			if ($val!=null) {$query_str='?'.$var.'='.$val;}
		}
	}
	return($query_str);
}


# $url - путь к картинке
# $x, $y - ширина и высота новой картинки
# $force_resize - FALSE - сохранять пропорции, TRUE - нет
# $name_new - новое имя (или путь) картинки для сохранения 'dir', 'dir/', 'dir/image.gif'
# $name_dem - добавлять ли размеры к имени файла (при TRUE разделитель "_") img_120x60.jpg
# $flip - [null, 'hor', 'ver'] - вертикальная, горизонтальная ориентация картинки
/*
function image_resize1 ($url, $x=null, $y=null, $force_resize=FALSE, $name_new=null, $name_dem=FALSE) {
	# Определяем тип ссылки на файл (локальная или URL)
	$path = null;
	if (stristr($url, 'http:') == TRUE) {
		$headers = @get_headers($url);
		if (stristr($headers[0], '200') == TRUE) {
			$path = $url;
		}
	} elseif (is_file($url) == TRUE OR file_exists($url) == TRUE) {
		$path = realpath($url);
	}

	if ($path != null) {
		$file = basename($url);
		$file_ext = substr_replace($file, null, 0, strripos($file, '.')+1);
		$file_ext = strtolower($file_ext);
		$file_name = substr_replace($file, null, strripos($file, '.'));

		# Если $name_new - папка типа 'files/img' вместо типа 'files/img/pic.jpg' - добавляем имя файла из $url
		if ($name_new != null AND (stristr($name_new, '.jpg') OR stristr($name_new, '.jpeg') OR stristr($name_new, '.gif') OR stristr($name_new, '.png') OR stristr($name_new, '.bmp'))) {
			if ($name_new{strlen($name_new)-1} != '/') {$name_new .= '/';}
			$name_new = $name_new.$file_name.'.'.$file_ext;
		}

		# Проверяем есть ли нужная картинка
		$t_path = dirname($name_new);
		$t_file = basename($name_new);
		$t_file_ext = substr_replace($t_file, null, 0, strripos($t_file, '.')+1);
		$t_file_ext = strtolower($t_file_ext);
		$t_file_name = substr_replace($t_file, null, strripos($t_file, '.'));

		if ($name_dem == FALSE AND file_exists($name_new)) {
			return ($name_new);
		}
		if ($name_dem == TRUE AND file_exists($t_path.'/'.$t_file_name.'_'.$x.'x'.$y.'.'.$t_file_ext)) {
			return ($t_path.'/'.$t_file_name.'_'.$x.'x'.$y.'.'.$t_file_ext);
		}

		if ($url{0} == '/' OR $url{0} == '\\') {$url = substr_replace($url, null, 0, 1);}
	}

	if ($path != null) {
		if ($file_ext == 'jpg' OR $file_ext == 'jpeg') {$img = imagecreatefromjpeg($path);}
		if ($file_ext == 'gif') {$img = imagecreatefromgif($path);}
		if ($file_ext == 'png') {$img = imagecreatefrompng($path);}
		if ($file_ext == 'bmp') {$img = imagecreatefromwbmp($path);}

		if ($img) {
			# Определяем тип ссылки на новый файл (локаотный или URL)
			if ($name_new == null) {
				$path_new = $url;
			} else {
				$path_new = $name_new;
			}
			if ($path_new{0} == '/' OR $path_new{0} == '\\') {$path_new = substr_replace($path_new, null, 0, 1);}

			# Создаем новый путь и имя для файла
			if (stristr($path_new, 'http:') == TRUE) {
				$path_new = str_replace('http://', null, $path_new);
				$path_new = substr_replace($path_new, null, 0, stripos($path_new, '/')+1);
				#$path_new = dirname($path_new);
			}

			$path_new_dir = dirname($path_new);
			$path_new_file = basename($path_new);
			if ($path_new_dir == '.') {$path_new_dir = null;} else {$path_new_dir .= '/';}
			
			$path_new_file_ext = substr_replace($path_new_file, null, 0, strripos($path_new_file, '.')+1);
			$path_new_file_ext = strtolower($path_new_file_ext);
			$path_new_file_name = substr_replace($path_new_file, null, strripos($path_new_file, '.'));
			
			if ($name_dem == TRUE) {
				$path_new_file_name = $path_new_file_name.'_'.$x.'x'.$y;
			}
			$img_new_name = $path_new_dir.''.$path_new_file_name.'.'.$path_new_file_ext;

			@mkdir(dirname($path_new), 0777, TRUE);

			# Геометрия
			$size_x = imagesx($img);
			$size_y = imagesy($img);
			if ($x == null) {$x = $size_x;}
			if ($y == null) {$y = $size_y;}
			$kx = $size_x/$x;
			$ky = $size_y/$y;

			# Рисуем новое изображение
			if ($force_resize == TRUE)  {
				if ($kx < $ky) {
					$size_x_new = floor($x);
					$size_y_new = floor($size_y/$kx);
				} else {
					$size_x_new = floor($size_x/$ky);
					$size_y_new = floor($y);
				}
				$offset_x = -round(($size_x_new - $x)/2, 0);
				$offset_y = -round(($size_y_new - $y)/2, 0);
				$img_new = imagecreatetruecolor($x, $y);
			} else {
				if ($kx > $ky) {
					$size_x_new = floor($x);
					$size_y_new = floor($size_y/$kx);
				} else {
					$size_x_new = floor($size_x/$ky);
					$size_y_new = floor($y);
				}
				$offset_x = 0; $offset_y = 0;
				$img_new = imagecreatetruecolor($size_x_new, $size_y_new);
			}

			$color = imageColorAllocate($img_new, 255, 255, 255);
			imagefill($img_new, 0, 0, $color);
			imagecopyresampled($img_new, $img, $offset_x, $offset_y, 0, 0, $size_x_new, $size_y_new, $size_x, $size_y);

			if ($path_new_file_ext == 'jpg' OR $path_new_file_ext == 'jpeg') {imagejpeg($img_new, $img_new_name);;}
			if ($path_new_file_ext == 'gif') {imagegif($img_new, $img_new_name);}
			if ($path_new_file_ext == 'png') {imagepng($img_new, $img_new_name);;}
			if ($path_new_file_ext == 'bmp') {imagewbmp($img_new, $img_new_name);;}
			imagedestroy($img_new);
			return ($img_new_name);
		}
	}
}
*/

function path_to_real ($img_real) {
	/** /
	global $script_path;
	$img_real = substr_replace($img_real, null, 0, strlen($script_path));
	$img_real = preg_replace('/^(\\'.DIR_DELIM.'www\\'.DIR_DELIM.') /ix', null, $img_real);
	$img_real = str_replace('\\', '/', $img_real);
	/**/
	$img_real = substr_replace($img_real, null, 0, strlen($_SERVER['DOCUMENT_ROOT']));
	$img_real = str_replace(DIR_DELIM, '/', $img_real);
	/** /
	echo($img_real.'<hr>');
	/**/
	return $img_real;
}

function image_resize ($path, $path_new = null, $x = null, $y = null, $name_dem = FALSE, $force_resize = FALSE, $scale_dis = FALSE, $replace = FALSE) {

	// $script_path = dirname(realpath('./'));
	// $script_path = realpath('../');

	##### Путь, имя, расширение базовой картинки
		if (!strstr($path, ':'.DIR_DELIM)) { // Если путь не абсолютный
			if (strstr($path, '/')) // если в $path указан путь, обрезаем его
				$path_name = substr_replace($path, null, 0, strrpos($path, '/') + 1);
			else
				$path_name = $path;

			$pos = strrpos($path_name, '.');
			$path_extn = substr_replace($path_name, null, 0, $pos + 1);
			$path_name = substr_replace($path_name, null, $pos);
			$path_path = realpath(dirname($path));
			$path_real = $path_path.DIR_DELIM.$path_name.'.'.$path_extn;
			$path_extn_lc = strtolower($path_extn);
		} else { // Если путь абсолютный
			$pos = strrpos($path, '.');
			$path_extn = substr_replace($path, null, 0, $pos + 1);
			$path_name = substr_replace($path, null, 0, strrpos($path, DIR_DELIM) + 1);
			$path_name = substr_replace($path_name, null, strrpos($path_name, '.'));
			$path_path = realpath(dirname($path));
			$path_real = $path;
			$path_extn_lc = strtolower($path_extn);
		}


	##### Путь, имя, расширение новой картинки
		if ($path_new) {
			// Если путь абсолютный D:\folder\file.ext
			if (strstr($path_path, ':'.DIR_DELIM)) {
				$path_new_name = substr_replace($path_new, null, 0, strrpos($path_new, DIR_DELIM) + 1);

				if (!is_dir($path_new_name))
					mkdir ($path_new_name, 0777, TRUE);

				$pos = strrpos($path_new_name, '.');
				$path_new_extn = substr_replace($path_new_name, null, 0, $pos + 1);
				$path_new_name = substr_replace($path_new_name, null, $pos);
				$path_new_path = realpath(dirname($path_new));
			}
			// Если новый путь - папка/файл
			elseif (strstr($path_new, '/') AND preg_match('/ \. (jpg|jpeg|gif|png|bmp){1} $ /ix', $path_new)) {
				$path_new_name = substr_replace($path_new, null, 0, strrpos($path_new, '/') + 1);

				if (!is_dir(dirname($path_new)))
					mkdir (dirname($path_new), 0777, TRUE);

				$pos = strrpos($path_new_name, '.');
				$path_new_extn = substr_replace($path_new_name, null, 0, $pos + 1);
				$path_new_name = substr_replace($path_new_name, null, $pos);
				$path_new_path = realpath(dirname($path_new));
			} 
			// Если новый путь - папка
			elseif (!preg_match('/ \. (jpg|jpeg|gif|png|bmp){1} $ /ix', $path_new)) {

				if (!is_dir($path_new))
					mkdir ($path_new, 0777, TRUE);

				$path_new_extn = null;
				$path_new_name = null;
				$path_new_path = realpath($path_new);
			}
			// Если новый путь - файл
			elseif (preg_match('/ \. (jpg|jpeg|gif|png|bmp){1} $ /ix', $path_new)) {
				$pos = strrpos($path_new, '.');
				$path_new_extn = substr_replace($path_new, null, 0, $pos + 1);
				$path_new_name = substr_replace($path_new, null, $pos);
				$path_new_path = null;
			}
		}


	##### EXIT если файл $path не найден
		if (!is_file($path_path.DIR_DELIM.$path_name.'.'.$path_extn)) return $path;

	##### Путь, имя, расширение результата
		$img_extn = $path_new_extn ? $path_new_extn : $path_extn;
		$img_name = $path_new_name ? $path_new_name : $path_name;
		$img_path = $path_new_path ? $path_new_path : $path_path;
		$img_demn = ($name_dem === TRUE AND ($x OR $y)) ? '_'.$x.'x'.$y : null;
		$img_demn = ($name_dem !== TRUE AND $name_dem == TRUE ) ? $name_dem : $img_demn;
		$img_real = $img_path.DIR_DELIM.$img_name.$img_demn.'.'.$img_extn;
		$img_extn_lc = strtolower($img_extn);

	##### EXIT если новый файл существует и $path != $path_new (не перезапись старой картинки)
		if (!($name_dem AND (!$x AND !$y)) AND is_file($img_real) AND ($path != $path_new) ) return path_to_real ($img_real);

	##### Загружаем картинку
		if ($path_extn_lc == 'jpg' OR $path_extn_lc == 'jpeg') {$img = imagecreatefromjpeg($path);}
		if ($path_extn_lc == 'gif') {$img = imagecreatefromgif($path);}
		if ($path_extn_lc == 'png') {$img = imagecreatefrompng($path);}
		if ($path_extn_lc == 'bmp') {$img = imagecreatefromwbmp($path);}

		if ($img) {
			##### Геометрия
				$size_x = imagesx($img);
				$size_y = imagesy($img);
				if (!$x) {$x = $size_x;}
				if (!$y) {$y = $size_y;}
				$kx = $size_x/$x;
				$ky = $size_y/$y;

				// Если запрещено увеличивать картинку
				if ($force_resize == false AND $scale_dis == 'up' AND $x > $size_x AND $y > $size_y) {
					$x = $size_x; $y = $size_y;
				}

				if ($replace AND !($x == $size_x AND  $y == $size_y) )
					unlink($path);

			##### Если заданные размеры совпадают с реальными размерами картинки
				if ($x == $size_x AND $y == $size_y) {
					// Если совпадают расширения начальной и конечной картинки то копируем
					if ($img_extn_lc == $path_extn_lc) {
						copy($path_real, $img_real);
						imagedestroy($img);
						return path_to_real ($img_real);
					} 
					// Если расширения не совпадают создаем новую картинку
					else {
						// PNG -> GIF
						if ($path_extn_lc = 'png' AND $img_extn_lc == 'gif') {
							$img_new = imageCreateTrueColor($x, $y);
							$trans_ind = imageColorAllocate($img_new, 255, 255, 255);
							imagefill($img_new, 0, 0, $trans_ind);
							imagecolortransparent($img_new, $trans_ind);
							imageCopyResampled($img_new, $img, 0, 0, 0, 0, $x, $y, $x, $y);
							imagegif($img_new, $img_real);
							imagedestroy($img_new);
						} else {
							if ($img_extn_lc == 'jpg' OR $img_extn_lc == 'jpeg') {imagejpeg($img, $img_real, 90);}
							if ($img_extn_lc == 'gif') {imagegif($img, $img_real);}
							if ($img_extn_lc == 'png') {imagepng($img, $img_real, 7);}
							if ($img_extn_lc == 'bmp') {imagewbmp($img, $img_real);}
						}
						imagedestroy($img);
						return path_to_real ($img_real);
					}
				}

			##### Новое изображение
				if ($force_resize)  {
					// Если запрещено увеличивать картинку
					if ($scale_dis == 'up' AND $x > $size_x AND $y > $size_y) {
							$size_x_new = $size_x;
							$size_y_new = $size_y;
					} else {
						if ($kx < $ky) {
							$size_x_new = floor($x);
							$size_y_new = floor($size_y/$kx);
						} else {
							$size_x_new = floor($size_x/$ky);
							$size_y_new = floor($y);
						}
					}
					$offset_x = -round(($size_x_new - $x)/2, 0);
					$offset_y = -round(($size_y_new - $y)/2, 0);
					$create['x'] = $x;
					$create['y'] = $y;
				} else {
					if ($kx > $ky) {
						$size_x_new = floor($x);
						$size_y_new = floor($size_y/$kx);
					} else {
						$size_x_new = floor($size_x/$ky);
						$size_y_new = floor($y);
					}
					$offset_x = 0; $offset_y = 0;
					$create['x'] = $size_x_new;
					$create['y'] = $size_y_new;
				}

				if ($path_extn_lc != 'gif') 
					$img_new = imageCreateTrueColor($create['x'], $create['y']);
				else
					$img_new = imageCreate($create['x'], $create['y']);

			##### Сохраняем прозрачность если обе картинки PNG или GIF
				if ( ($path_extn_lc == 'png' OR $path_extn_lc == 'gif') AND ($img_extn_lc == 'png' OR $img_extn_lc == 'gif') ) {
					if ($path_extn_lc == 'png') {
						// PNG
						if ($img_extn_lc == 'png') {
							// PNG -> PNG
							imageAlphaBlending($img_new, false);
							imageSaveAlpha($img_new, true);
							$trans_ind = imageColorAllocatealpha($img_new, 255, 255, 255, 127);		// опционально
							imagefill($img_new, 0, 0, $trans_ind);									// опционально
						} else {
							// PNG -> GIF
							$trans_ind = imageColorAllocate($img_new, 255, 255, 255);
							imagefill($img_new, 0, 0, $trans_ind);
							imagecolortransparent($img_new, $trans_ind);
						}
					} elseif ($path_extn_lc == 'gif') {
						// GIF
						$trans_ind = imageColorTransparent($img);
						if ($trans_ind >= 0) {
							$trans_col = imagecolorsforindex($img, $trans_ind);
							$trans_ind = imageColorAllocate($img_new, $trans_col['red'], $trans_col['green'], $trans_col['blue']);
							imagefill($img_new, 0, 0, $trans_ind);
							imageColorTransparent($img_new, $trans_ind);
						}
					} 
				} else {
					// Создаем белый фон
					$color = imageColorAllocate($img_new, 255, 255, 255);
					imagefill($img_new, 0, 0, $color);
				}

				imageCopyResampled($img_new, $img, $offset_x, $offset_y, 0, 0, $size_x_new, $size_y_new, $size_x, $size_y);

				if ($img_extn_lc == 'jpg' OR $img_extn_lc == 'jpeg') {imagejpeg($img_new, $img_real);}
				if ($img_extn_lc == 'gif') {imagegif($img_new, $img_real);}
				if ($img_extn_lc == 'png') {imagepng($img_new, $img_real);}
				if ($img_extn_lc == 'bmp') {imagewbmp($img_new, $img_real);}

				imagedestroy($img);
				imagedestroy($img_new);

				return  path_to_real ($img_real);
		}

		imagedestroy($img);
		return $path;
}



