<?php
class Plus_Validator {

	public static function validate($rules, $inValidFunction) {

		if ($rules && is_array($rules)) {
			foreach ($rules as $fieldName => $validationRule) {
				$value = array_key_exists($fieldName, $_REQUEST) ? $_REQUEST[$fieldName] : false;
				$require = array_key_exists('require', $validationRule) ? $validationRule['require'] : true; //默认被校验的字段都是必须的
				$requireMessage = array_key_exists('require_message', $validationRule) ? $validationRule['require_message'] : "$fieldName required."; //默认被校验的字段都是必须的
				if ($require && !$value) {
					$inValidFunction($fieldName, $requireMessage);
					return false;
				}
				$pattern = array_key_exists('pattern', $validationRule) ? $validationRule['pattern'] : false;
				$errorMessage = array_key_exists('error_message', $validationRule) ? $validationRule['error_message'] : "$fieldName does not match the pattern:[$pattern]."; //默认被校验的字段都是必须的
				if ($pattern && !preg_match($pattern, $value)) {
					$inValidFunction($fieldName, $errorMessage);
					return false;
				}
			}
		}
		return true;

	}

}