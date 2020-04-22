<?php
class Plus_ParamUtil {

	public static function buildParam($paramArray) {
		return base64_encode(http_build_query($paramArray));
	}

	public static function parseParam($p) {
		parse_str(base64_decode($p), $params);
		return $params;
	}

}