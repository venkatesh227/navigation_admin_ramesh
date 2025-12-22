<?php
//To get list of records
function getDataList($table, $where = '')
{

	$datalist = array();

	if ($where != '') $where = " where " . $where;
	else $where = " where 1";

	$data_sql = "select * from " . $table . " " . $where;

	$data_res = mysqli_query($GLOBALS['conn'], $data_sql);

	$data_count = mysqli_num_rows($data_res);

	if ($data_count > 0) {

		while ($row = mysqli_fetch_assoc($data_res)) {

			$datalist[] = $row;
		}
	}

	return $datalist;
}
function generateRandomString($length = 6)
{
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
//To get single record
function getData($table, $where)
{

	$data = array();

	if ($where != '') $where = " where " . $where;

	//echo '<br>';

	$data_sql = "select * from " . $table . " " . $where;

	$data_res = mysqli_query($GLOBALS['conn'], $data_sql);

	$data_count = mysqli_num_rows($data_res);

	if ($data_count == 1) {

		$data = mysqli_fetch_assoc($data_res);
	}

	return $data;
}

// To get no of duplicate records
function getDuplicate($table, $where = '')
{

	if ($where != '')
		$sql = "select * from " . $table . " where " . $where;
	else
		$sql = "select * from " . $table;

	$res = mysqli_query($GLOBALS['conn'], $sql);


	if (mysqli_num_rows($res)) return true;
	else return false;
}

// To delete data
function deleteData($table, $where)
{

	$sql = "delete from " . $table . " where " . $where;

	if (@mysqli_query($GLOBALS['conn'], $sql)) {
		//$_SESSION['message']="Deleted Record Succesfully ..";
	}
}

// Email Validation
function valEmail($email)
{

	$pat = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/";

	return preg_match($pat, $email);
}

function addRecord($table, $data)
{

	$colums = '';
	$values = '';

	foreach ($data as $k => $v) {

		$colums .= $k . ",";

		$values .= "'" . mysqli_real_escape_string($GLOBALS['conn'], trim($v)) . "',";
	}

	$colums = rtrim($colums, ',');

	$values = rtrim($values, ',');

	$sql = "insert into " . $table . "(" . $colums . ") values(" . $values . ")";

	if (@mysqli_query($GLOBALS['conn'], $sql)) {

		//$_SESSION['message']="Added Record Succesfully ..";	

		return @mysqli_insert_id($GLOBALS['conn']);
	} else die(mysqli_error($GLOBALS['conn']));
}

function updateRecord($table, $data, $id)
{

	$set = '';

	foreach ($data as $k => $v) {

		$set .= $k . "='" . mysqli_real_escape_string($GLOBALS['conn'], trim($v)) . "',";
	}

	$set = rtrim($set, ',');

	$sql = "update " . $table . " set " . $set . " where id='" . $id . "'";

	if (@mysqli_query($GLOBALS['conn'], $sql)) {

		//$_SESSION['message']="Updated Record Succesfully ..";	

		return $id;
	} else die(mysqli_error($GLOBALS['conn']));
}

function datepattrn($a)
{

	if ($a != "") {
		$b = substr($a, 5, 2); // month

		$c = substr($a, 7, 1); // '-'

		$d = substr($a, 8, 2); // day

		$e = substr($a, 4, 1); // '-'

		$f = substr($a, 0, 4); // year

		$g = $d . "/" . $b . "/" . $f;
	} else
		$g = "";

	return $g;
}



// Change date format from dd/mm/yyyy to yyyy/mm/dd

function dmyToymd($a)
{

	if ($a != "") {
		$b = substr($a, 3, 2); // month

		$c = substr($a, 2, 1); // '-'

		$d = substr($a, 0, 2); // day

		$e = substr($a, 5, 1); // '-'

		$f = substr($a, 6, 4); // year

		$g = $f . "-" . $b . "-" . $d;
	} else
		$g = "";

	return $g;
}


// Change date format from yyyy/mm/dd HH:ii:ss to dd/mm/yyyy HH:ii:ss

function ymdTodmy($a)
{

	if ($a != "") {
		$b = substr($a, 5, 2); // month

		$c = substr($a, 7, 1); // '-'

		$d = substr($a, 8, 2); // day

		$e = substr($a, 4, 1); // '-'

		$f = substr($a, 0, 4); // year

		$h = substr($a, 11, 8);

		$g = $d . "-" . $b . "-" . $f . " " . $h;
	} else
		$g = "";

	return $g;
}

function redirect($url)
{

	if (headers_sent()) {

?>
		<html>

		<head>
			<script language="javascript" type="text/javascript">
				<!-- 
				window.parent.document.location = '<?php print($url); ?>';
				//
				-->
			</script>
		</head>

		</html>
<?php
		exit;
	} else {

		header("Location: " . $url);
		exit;
	}
}

function pagination($query, $per_page = 10, $page = 1, $url = '?')
{

	$query = "SELECT COUNT(*) as `num` FROM {$query}";
	$row = mysqli_fetch_assoc(mysqli_query($GLOBALS['conn'], $query));
	$total = $row['num'];
	$adjacents = "2";

	$page = ($page == 0 ? 1 : $page);
	$start = ($page - 1) * $per_page;

	$prev = $page - 1;
	$next = $page + 1;
	$lastpage = ceil($total / $per_page);
	$lpm1 = $lastpage - 1;

	$pagination = "";
	if ($lastpage > 1) {
		$pagination .= "<ul class='pagination'>";
		$pagination .= "<li class='details'>Page $page of $lastpage</li>";
		if ($lastpage < 7 + ($adjacents * 2)) {
			for ($counter = 1; $counter <= $lastpage; $counter++) {
				if ($counter == $page)
					$pagination .= "<li><a class='current'>$counter</a></li>";
				else
					$pagination .= "<li><a href='{$url}page=$counter'>$counter</a></li>";
			}
		} elseif ($lastpage > 5 + ($adjacents * 2)) {
			if ($page < 1 + ($adjacents * 2)) {
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
					if ($counter == $page)
						$pagination .= "<li><a class='current'>$counter</a></li>";
					else
						$pagination .= "<li><a href='{$url}page=$counter'>$counter</a></li>";
				}
				$pagination .= "<li class='dot'>...</li>";
				$pagination .= "<li><a href='{$url}page=$lpm1'>$lpm1</a></li>";
				$pagination .= "<li><a href='{$url}page=$lastpage'>$lastpage</a></li>";
			} elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
				$pagination .= "<li><a href='{$url}page=1'>1</a></li>";
				$pagination .= "<li><a href='{$url}page=2'>2</a></li>";
				$pagination .= "<li class='dot'>...</li>";
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
					if ($counter == $page)
						$pagination .= "<li><a class='current'>$counter</a></li>";
					else
						$pagination .= "<li><a href='{$url}page=$counter'>$counter</a></li>";
				}
				$pagination .= "<li class='dot'>..</li>";
				$pagination .= "<li><a href='{$url}page=$lpm1'>$lpm1</a></li>";
				$pagination .= "<li><a href='{$url}page=$lastpage'>$lastpage</a></li>";
			} else {
				$pagination .= "<li><a href='{$url}page=1'>1</a></li>";
				$pagination .= "<li><a href='{$url}page=2'>2</a></li>";
				$pagination .= "<li class='dot'>..</li>";
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
					if ($counter == $page)
						$pagination .= "<li><a class='current'>$counter</a></li>";
					else
						$pagination .= "<li><a href='{$url}page=$counter'>$counter</a></li>";
				}
			}
		}

		if ($page < $counter - 1) {
			$pagination .= "<li><a href='{$url}page=$next'>Next</a></li>";
			$pagination .= "<li><a href='{$url}page=$lastpage'>Last</a></li>";
		} else {
			$pagination .= "<li><a class='current'>Next</a></li>";
			$pagination .= "<li><a class='current'>Last</a></li>";
		}
		$pagination .= "</ul>\n";
	}


	return $pagination;
}
function getTextAreaData($str = '')
{

	if ($str != '') {

		$form_text = trim($str);
		$form_text = nl2br($form_text);
		$form_text = stripslashes($form_text);
	} else
		$form_text = '';

	return $form_text;
}

function make_thumb($src, $dest, $desired_width)
{

	/* read the source image */
	$source_image = imagecreatefromjpeg($src);
	$width = imagesx($source_image);
	$height = imagesy($source_image);

	/* find the "desired height" of this thumbnail, relative to the desired width  */
	$desired_height = floor($height * ($desired_width / $width));

	/* create a new, "virtual" image */
	$virtual_image = imagecreatetruecolor($desired_width, $desired_height);

	/* copy source image at a resized size */
	imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

	/* create the physical thumbnail image to its destination */
	imagejpeg($virtual_image, $dest, 100);
}

function make_thumb_all($src, $dest, $desired_width, $ext)
{

	/* read the source image */

	$ext = strtolower($ext);

	if ($ext == 'jpg' || $ext == 'jpeg')

		$source_image = imagecreatefromjpeg($src);

	if ($ext == 'png')

		$source_image = imagecreatefrompng($src);

	if ($ext == 'gif')

		$source_image = imagecreatefromgif($src);

	$width = imagesx($source_image);
	$height = imagesy($source_image);

	/* find the "desired height" of this thumbnail, relative to the desired width  */
	$desired_height = floor($height * ($desired_width / $width));

	/* create a new, "virtual" image */
	$virtual_image = imagecreatetruecolor($desired_width, $desired_height);

	/* copy source image at a resized size */
	imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

	/* create the physical thumbnail image to its destination */

	if ($ext == 'png')

		imagepng($virtual_image, $dest);

	else

		imagejpeg($virtual_image, $dest);
}

function no_to_words($no)
{
	$words = array('0' => '', '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine', '10' => 'ten', '11' => 'eleven', '12' => 'twelve', '13' => 'thirteen', '14' => 'fouteen', '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen', '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty', '30' => 'thirty', '40' => 'fourty', '50' => 'fifty', '60' => 'sixty', '70' => 'seventy', '80' => 'eighty', '90' => 'ninty', '100' => 'hundred &', '1000' => 'thousand', '100000' => 'lakh', '10000000' => 'crore');
	if ($no == 0)
		return ' ';
	else {
		$novalue = '';
		$highno = $no;
		$remainno = 0;
		$value = 100;
		$value1 = 1000;
		while ($no >= 100) {
			if (($value <= $no) && ($no  < $value1)) {
				$novalue = $words["$value"];
				$highno = (int)($no / $value);
				$remainno = $no % $value;
				break;
			}
			$value = $value1;
			$value1 = $value * 100;
		}
		if (array_key_exists("$highno", $words))
			return $words["$highno"] . " " . $novalue . " " . no_to_words($remainno);
		else {
			$unit = $highno % 10;
			$ten = (int)($highno / 10) * 10;
			return $words["$ten"] . " " . $words["$unit"] . " " . $novalue . " " . no_to_words($remainno);
		}
	}
}

function moneyFormatIndiaUnsigned($n, $d = 2)
{

	$n = number_format($n, $d, '.', '');
	$n = strrev($n);

	if ($d) $d++;
	$d += 3;

	if (strlen($n) > $d)
		$n = substr($n, 0, $d) . ','
			. implode(',', str_split(substr($n, $d), 2));

	return strrev($n);
}

function moneyFormatIndia($amount, $include_decimals = true): string
{
	list($number, $decimal) = explode('.', sprintf('%.2f', floatval($amount)));

	$sign = $number < 0 ? '-' : '';

	$number = abs($number);

	for ($i = 3; $i < strlen($number); $i += 3) {
		$number = substr_replace($number, ',', -$i, 0);
	}

	if ($include_decimals)
		return $sign . $number . '.' . $decimal;
	else
		return $sign . $number;
}

function  getDatesBetween2Dates($startTime, $endTime)
{
	$day = 86400;
	$format = 'd-m-Y';
	$startTime = strtotime($startTime);
	$endTime = strtotime($endTime);
	$numDays = round(($endTime - $startTime) / $day) + 1;
	$days = array();

	for ($i = 0; $i < $numDays; $i++) {
		$days[] = date($format, ($startTime + ($i * $day)));
	}

	return $days;
}

function getSpecialStrip($str)
{

	return htmlspecialchars(stripslashes(trim($str)));
}

function force_download($filename)
{
	$filedata = @file_get_contents($filename);

	// SUCCESS
	if ($filedata) {
		// GET A NAME FOR THE FILE
		$basename = basename($filename);

		// THESE HEADERS ARE USED ON ALL BROWSERS
		header("Content-Type: application-x/force-download");
		header("Content-Disposition: attachment; filename=$basename");
		header("Content-length: " . (string)(strlen($filedata)));
		header("Expires: " . gmdate("D, d M Y H:i:s", mktime(date("H") + 2, date("i"), date("s"), date("m"), date("d"), date("Y"))) . " GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

		// THIS HEADER MUST BE OMITTED FOR IE 6+
		if (FALSE === strpos($_SERVER["HTTP_USER_AGENT"], 'MSIE ')) {
			header("Cache-Control: no-cache, must-revalidate");
		}

		// THIS IS THE LAST HEADER
		header("Pragma: no-cache");

		// FLUSH THE HEADERS TO THE BROWSER
		flush();

		// CAPTURE THE FILE IN THE OUTPUT BUFFERS - WILL BE FLUSHED AT SCRIPT END
		ob_start();
		echo $filedata;
	}

	// FAILURE
	else {
		die("ERROR: UNABLE TO OPEN $filename");
	}
}

function refineInput($data)
{
	return mysqli_real_escape_string($GLOBALS['conn'], trim($data));
}

function refineOutput($data)
{
	return htmlspecialchars(stripslashes(trim($data)));
}

function getNextId($table)
{

	$sql = "select max(id) as id from " . $table;

	$res = mysqli_query($GLOBALS['conn'], $sql);

	$rec = mysqli_fetch_assoc($GLOBALS['conn'], $res);

	return $rec['id'] + 1;
}

function removeParagraphs($str)
{

	$str = str_replace("<p>", "", $str);
	$str = str_replace("</p>", "", $str);

	return $str;
}

function get_len_of_word($str, $number)
{
	$array_str = explode(" ", $str);

	if (isset($array_str[$number])) {

		return implode(" ", array_slice($array_str, 0, $number));
	}
	return $str;
}

if (function_exists('grk_Datetime_Since') === FALSE) {
	function grk_Datetime_Since($From, $To = '', $Prefix = '', $Suffix = ' ago', $Words = array())
	{
		#   Est-ce qu'on calcul jusqu'� un moment pr�cis ? Probablement pas, on utilise maintenant
		if (empty($To) === TRUE) {
			$To = time();
		}

		#   On va s'assurer que $From est num�rique
		if (is_int($From) === FALSE) {
			$From = strtotime($From);
		};

		#   On va s'assurer que $To est num�rique
		if (is_int($To) === FALSE) {
			$To = strtotime($To);
		}

		#   On a une erreur ?
		if ($From === FALSE or $From === -1 or $To === FALSE or $To === -1) {
			return FALSE;
		}

		#   On va cr�er deux objets de date
		$From = new DateTime(@date('Y-m-d H:i:s', $From), new DateTimeZone('GMT'));
		$To   = new DateTime(@date('Y-m-d H:i:s', $To), new DateTimeZone('GMT'));

		#   On va calculer la diff�rence entre $From et $To
		if (($Diff = $From->diff($To)) === FALSE) {
			return FALSE;
		}

		#   On va merger le tableau des noms (par d�faut, anglais)
		$Words = array_merge(array(
			'year'      => 'year',
			'years'     => 'years',
			'month'     => 'month',
			'months'    => 'months',
			'week'      => 'week',
			'weeks'     => 'weeks',
			'day'       => 'day',
			'days'      => 'days',
			'hour'      => 'hour',
			'hours'     => 'hours',
			'minute'    => 'minute',
			'minutes'   => 'minutes',
			'second'    => 'second',
			'seconds'   => 'seconds'
		), $Words);

		#   On va cr�er la cha�ne maintenant
		if ($Diff->y > 1) {
			$Text = $Diff->y . ' ' . $Words['years'];
		} elseif ($Diff->y == 1) {
			$Text = '1 ' . $Words['year'];
		} elseif ($Diff->m > 1) {
			$Text = $Diff->m . ' ' . $Words['months'];
		} elseif ($Diff->m == 1) {
			$Text = '1 ' . $Words['month'];
		} elseif ($Diff->d > 7) {
			$Text = ceil($Diff->d / 7) . ' ' . $Words['weeks'];
		} elseif ($Diff->d == 7) {
			$Text = '1 ' . $Words['week'];
		} elseif ($Diff->d > 1) {
			$Text = $Diff->d . ' ' . $Words['days'];
		} elseif ($Diff->d == 1) {
			$Text = '1 ' . $Words['day'];
		} elseif ($Diff->h > 1) {
			$Text = $Diff->h . ' ' . $Words['hours'];
		} elseif ($Diff->h == 1) {
			$Text = '1 ' . $Words['hour'];
		} elseif ($Diff->i > 1) {
			$Text = $Diff->i . ' ' . $Words['minutes'];
		} elseif ($Diff->i == 1) {
			$Text = '1 ' . $Words['minute'];
		} elseif ($Diff->s > 1) {
			$Text = $Diff->s . ' ' . $Words['seconds'];
		} else {
			$Text = '1 ' . $Words['second'];
		}

		return $Prefix . $Text . $Suffix;
	}
}

function getQueryDataList($sql)
{

	$data = array();

	$res = mysqli_query($GLOBALS['conn'], $sql);
	$count = mysqli_num_rows($res);

	if ($count > 0) {

		while ($row = mysqli_fetch_assoc($res)) {

			$data[] = parseArray($row);
		}
	}

	return $data;
}

function getQueryData($sql)
{

	$data = array();

	$res = mysqli_query($GLOBALS['conn'], $sql);

	$count = mysqli_num_rows($res);

	if ($count > 0) $data = mysqli_fetch_assoc($res);

	return parseArray($data);
}

function parseArray($arr)
{

	return $arr;
}

function randomPassword()
{

	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass = array(); //remember to declare $pass as an array
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < 7; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass); //turn the array into a string
}

function toFixed($number, $decimals)
{
	return number_format($number, $decimals, ".", "");
}

function randomNumber($length)
{

	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass = array(); //remember to declare $pass as an array
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < $length; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass); //turn the array into a string
}

function subArray($from_index, $to_index, $array)
{

	$sub_array = array();
	for ($i = $from_index; $i <= $to_index; $i++) {
		array_push($sub_array, $array[$i]);
	}
	return $sub_array;
}

function ymdtTodmyt($date_time)
{
	return date("d-m-Y H:i a", strtotime($date_time));
}

function converToTz($time = "", $toTz = '', $fromTz = '')
{
	// timezone by php friendly values
	$date = new DateTime($time, new DateTimeZone($fromTz));
	$date->setTimezone(new DateTimeZone($toTz));
	$time = $date->format('d-m-Y h:i A');
	return $time;
}

function sendEmail($from, $to, $subject, $msg)
{

	$headers  = "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
	$headers .= "From: " . trim($subject) . " <" . $from . ">\r\n";

	mail($to, $subject, $msg, $headers);
}

function getRandomNumber($digits)
{
	$min = pow(10, $digits - 1);
	$max = pow(10, $digits) - 1;
	return mt_rand($min, $max);
}

function convertExcelDate($date)
{

	$sap_date = explode("-", $date);
	$sap_date = ($sap_date['2'] + 2000) . "-" . $sap_date[0] . "-" . $sap_date[1];

	return $sap_date;
}

function checkIsAValidDate($myDateString)
{
	return (bool)strtotime($myDateString);
}
function daysAgo($date)
{
	$now = strtotime(date('Y-m-d')); // or your date as well
	$your_date = strtotime($date);
	$datediff = $now - $your_date;

	return round($datediff / (60 * 60 * 24));
}

/**
 * Verifies a password against a stored hash.
 * Supports legacy MD5 hashes and automatically upgrades them to Bcrypt.
 *
 * @param string $inputPassword The plain text password provided by the user.
 * @param string $storedHash The hash stored in the database.
 * @param int $userId The user ID, used for updating the hash if an upgrade is needed.
 * @param mysqli $conn The database connection.
 * @return bool True if the password is valid, False otherwise.
 */
function verifyUserPassword($inputPassword, $storedHash, $userId, $conn)
{
    // 1. Check if the stored hash is a modern hash (Bcrypt, Argon2, etc.)
    if (password_verify($inputPassword, $storedHash)) {
        // Password is valid and already using a modern algorithm.
        // Optional: Check if rehash is needed (e.g. if algorithm options changed)
        if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newHash, $userId);
                $stmt->execute();
                $stmt->close();
            }
        }
        return true;
    }

    // 2. Check if the stored hash is a legacy MD5 hash
    // MD5 hashes are 32 hexadecimal characters.
    if (preg_match('/^[a-f0-9]{32}$/i', $storedHash)) {
        if (md5($inputPassword) === $storedHash) {
            // Password is valid (legacy). Upgrade to Bcrypt immediately.
            $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newHash, $userId);
                $stmt->execute();
                $stmt->close();
            }
            return true;
        }
    }

    // 3. Password invalid
    return false;
}

function getDashboardStats($conn) {
    $today = date("Y-m-d");
    $stats = [
        'attendance_today' => [],
        'route_assignments' => [],
        'employee_counts' => [],
        'timeline' => []
    ];

    // 1. Attendance Today
    $attSql = "SELECT e.name, a.clock_in, a.clock_out 
               FROM attendance a 
               JOIN employees e ON a.user_id = e.user_id 
               WHERE a.date = '$today' 
               ORDER BY a.clock_in DESC";
    $attRes = $conn->query($attSql);
    if ($attRes) {
        while ($row = $attRes->fetch_assoc()) {
            $stats['attendance_today'][] = $row;
            // Add to timeline
            if ($row['clock_in']) {
                $stats['timeline'][] = [
                    'type' => 'checkin',
                    'time' => $row['clock_in'], // keep as H:i:s for sorting
                    'title' => $row['name'] . ' checked in',
                    'desc' => 'Clocked in at ' . date("h:i A", strtotime($row['clock_in'])),
                    'status' => 'primary'
                ];
            }
        }
    }

    // 2. Assigned Routes
    $routeSql = "SELECT e.name as emp_name, r.name as route_name 
                 FROM assign_routes ar 
                 JOIN employees e ON ar.employee_id = e.id 
                 JOIN routes r ON ar.route_id = r.id 
                 WHERE '$today' BETWEEN ar.start_date AND ar.end_date";
    $routeRes = $conn->query($routeSql);
    if ($routeRes) {
        while ($row = $routeRes->fetch_assoc()) {
            $stats['route_assignments'][] = $row;
            // Add to timeline
            $stats['timeline'][] = [
                'type' => 'assignment',
                'time' => '09:00:00', // Default morning time for route assignment
                'title' => $row['emp_name'] . ' assigned to ' . $row['route_name'],
                'desc' => 'Scheduled for today',
                'status' => 'info'
            ];
        }
    }

    // 3. Employee Counts (based on member_reports i.e. visits)
    // Assuming member_reports tracks visits/tasks done.
    // If 'member_reports' table has 'created_at', we use that.
    $countSql = "SELECT e.name, COUNT(mr.id) as visit_count, MAX(mr.created_at) as last_visit 
                 FROM member_reports mr 
                 JOIN employees e ON mr.employee_id = e.id 
                 WHERE DATE(mr.created_at) = '$today' 
                 GROUP BY mr.employee_id";
    $countRes = $conn->query($countSql);
    if ($countRes) {
        while ($row = $countRes->fetch_assoc()) {
            $timeVal = $row['last_visit'] ? date("h:i A", strtotime($row['last_visit'])) : '-';
            $stats['employee_counts'][] = [
                'name' => $row['name'],
                'count' => $row['visit_count'],
                'time' => $timeVal
            ];

            // Add latest action to timeline
            /* 
            // Optional: Add every single visit to timeline? Might be too much.
            // Let's just add a summary or assume check-ins are enough for now.
            // But let's add the visits to timeline to make it look busy.
            */ 
        }
    }
    
    // Add specific visits to timeline (limit to recent 20)
    $visitSql = "SELECT e.name, mr.clinic_name, mr.created_at 
                 FROM member_reports mr 
                 JOIN employees e ON mr.employee_id = e.id 
                 WHERE DATE(mr.created_at) = '$today' 
                 ORDER BY mr.created_at DESC LIMIT 20";
    $visitRes = $conn->query($visitSql);
    if ($visitRes) {
        while($row = $visitRes->fetch_assoc()) {
             $stats['timeline'][] = [
                'type' => 'visit',
                'time' => date("H:i:s", strtotime($row['created_at'])),
                'title' => $row['name'] . ' visited ' . $row['clinic_name'],
                'desc' => 'Visit completed',
                'status' => 'success'
            ];
        }
    }

    // Sort timeline by time DESC
    usort($stats['timeline'], function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    return $stats;
}
?>