<?
/*
 * OpenTracker
 * revised 10-Sep-2004
 * revised 4-Mar-2008: fixed null tests (thanks to Kevin Dion)
 */

function bdecode($str) {
	$pos = 0;
	return bdecode_r($str, $pos);
}

function bdecode_r($str, &$pos) {
	$strlen = strlen($str);
	if (($pos < 0) || ($pos >= $strlen)) {
		return null;
	}
	else if ($str[$pos] == 'i') {
		$pos++;
		$numlen = strspn($str, '-0123456789', $pos);
		$spos = $pos;
		$pos += $numlen;
		if (($pos >= $strlen) || ($str[$pos] != 'e')) {
			return null;
		}
		else {
			$pos++;
			return intval(substr($str, $spos, $numlen));
		}
	}
	else if ($str[$pos] == 'd') {
		$pos++;
		$ret = array();
		while ($pos < $strlen) {
			if ($str[$pos] == 'e') {
				$pos++;
				return $ret;
			}
			else {
				$key = bdecode_r($str, $pos);
				if (is_null($key)) {
					return null;
				}
				else {
					$val = bdecode_r($str, $pos);
					if (is_null($val)) {
						return null;
					}
					else if (!is_array($key)) {
						$ret[$key] = $val;
					}
				}
			}
		}
		return null;
	}
	else if ($str[$pos] == 'l') {
		$pos++;
		$ret = array();
		while ($pos < $strlen) {
			if ($str[$pos] == 'e') {
				$pos++;
				return $ret;
			}
			else {
				$val = bdecode_r($str, $pos);
				if (is_null($val)) {
					return null;
				}
				else {
					$ret[] = $val;
				}
			}
		}
		return null;
	}
	else {
		$numlen = strspn($str, '0123456789', $pos);
		$spos = $pos;
		$pos += $numlen;
		if (($pos >= $strlen) || ($str[$pos] != ':')) {
			return null;
		}
		else {
			$vallen = intval(substr($str, $spos, $numlen));
			$pos++;
			$val = substr($str, $pos, $vallen);
			if (strlen($val) != $vallen) {
				return null;
			}
			else {
				$pos += $vallen;
				return $val;
			}
		}
	}
}

function bencode($var) {
	if (is_int($var)) {
		return 'i' . $var . 'e';
	}
	else if (is_array($var)) {
		if (count($var) == 0) {
			return 'de';
		}
		else {
			$assoc = false;
			foreach ($var as $key => $val) {
				if (!is_int($key)) {
					$assoc = true;
					break;
				}
			}
			if ($assoc) {
				ksort($var, SORT_REGULAR);
				$ret = 'd';
				foreach ($var as $key => $val) {
					$ret .= bencode($key) . bencode($val);
				}
				return $ret . 'e';
			}
			else {
				$ret = 'l';
				foreach ($var as $val) {
					$ret .= bencode($val);
				}
				return $ret . 'e';
			}
		}
	}
	else {
		return strlen($var) . ':' . $var;
	}
}
?>