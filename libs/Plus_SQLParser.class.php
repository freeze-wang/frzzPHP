<?php
class Plus_SQLParser {

	var $handle = null;
	public static $querysections = array('alter', 'create', 'drop',
		'select', 'delete', 'insert',
		'update', 'from', 'where',
		'limit', 'order');
	public static $operators = array('=', '<>', '<', '<=', '>', '>=',
		'like', 'clike', 'slike', 'not',
		'is', 'in', 'between');
	public static $types = array('character', 'char', 'varchar', 'nchar',
		'bit', 'numeric', 'decimal', 'dec',
		'integer', 'int', 'smallint', 'float',
		'real', 'double', 'date', 'datetime',
		'time', 'timestamp', 'interval',
		'bool', 'boolean', 'set', 'enum', 'text');
	public static $conjuctions = array('by', 'as', 'on', 'into',
		'from', 'where', 'with');
	public static $funcitons = array('avg', 'count', 'max', 'min',
		'sum', 'nextval', 'currval', 'concat',
	);
	public static $reserved = array('absolute', 'action', 'add', 'all',
		'allocate', 'and', 'any', 'are', 'asc',
		'ascending', 'assertion', 'at',
		'authorization', 'begin', 'bit_length',
		'both', 'cascade', 'cascaded', 'case',
		'cast', 'catalog', 'char_length',
		'character_length', 'check', 'close',
		'coalesce', 'collate', 'collation',
		'column', 'commit', 'connect', 'connection',
		'constraint', 'constraints', 'continue',
		'convert', 'corresponding', 'cross',
		'current', 'current_date', 'current_time',
		'current_timestamp', 'current_user',
		'cursor', 'day', 'deallocate', 'declare',
		'default', 'deferrable', 'deferred', 'desc',
		'descending', 'describe', 'descriptor',
		'diagnostics', 'disconnect', 'distinct',
		'domain', 'else', 'end', 'end-exec',
		'escape', 'except', 'exception', 'exec',
		'execute', 'exists', 'external', 'extract',
		'false', 'fetch', 'first', 'for', 'foreign',
		'found', 'full', 'get', 'global', 'go',
		'goto', 'grant', 'group', 'having', 'hour',
		'identity', 'immediate', 'indicator',
		'initially', 'inner', 'input',
		'insensitive', 'intersect', 'isolation',
		'join', 'key', 'language', 'last',
		'leading', 'left', 'level', 'limit',
		'local', 'lower', 'match', 'minute',
		'module', 'month', 'names', 'national',
		'natural', 'next', 'no', 'null', 'nullif',
		'octet_length', 'of', 'only', 'open',
		'option', 'or', 'order', 'outer', 'output',
		'overlaps', 'pad', 'partial', 'position',
		'precision', 'prepare', 'preserve',
		'primary', 'prior', 'privileges',
		'procedure', 'public', 'read', 'references',
		'relative', 'restrict', 'revoke', 'right',
		'rollback', 'rows', 'schema', 'scroll',
		'second', 'section', 'session',
		'session_user', 'size', 'some', 'space',
		'sql', 'sqlcode', 'sqlerror', 'sqlstate',
		'substring', 'system_user', 'table',
		'temporary', 'then', 'timezone_hour',
		'timezone_minute', 'to', 'trailing',
		'transaction', 'translate', 'translation',
		'trim', 'true', 'union', 'unique',
		'unknown', 'upper', 'usage', 'user',
		'using', 'value', 'values', 'varying',
		'view', 'when', 'whenever', 'work', 'write',
		'year', 'zone', 'eoc');

	public static $startparens = array('{', '(');
	public static $endparens = array('}', ')');
	public static $tokens = array(',', ' ');
	private $query = '';

	public static function Tokenize($sqlQuery, $cleanWhitespace = true) {

		if ($cleanWhitespace === true) {
			$sqlQuery = ltrim(preg_replace('/[\\s]{2,}/', ' ', $sqlQuery));
		}

		$regex = '(';
		$regex .= '(?:--|\\#)[\\ \\t\\S]*';
		$regex .= '|(?:<>|<=>|>=|<=|==|=|!=|!|<<|>>|<|>|\\|\\||\\||&&|&|-';
		$regex .= '|\\+|\\*(?!\/)|\/(?!\\*)|\\%|~|\\^|\\?)';
		$regex .= '|[\\[\\]\\(\\),;`]|\\\'\\\'(?!\\\')|\\"\\"(?!\\"")';
		$regex .= '|".*?(?:(?:""){1,}"';
		$regex .= '|(?<!["\\\\])"(?!")|\\\\"{2})';
		$regex .= '|\'.*?(?:(?:\'\'){1,}\'';
		$regex .= '|(?<![\'\\\\])\'(?!\')';
		$regex .= '|\\\\\'{2})';
		$regex .= '|\/\\*[\\ \\t\\n\\S]*?\\*\/';
		$regex .= '|(?:[\\w:@]+(?:\\.(?:\\w+|\\*)?)*)';
		$regex .= '|[\t\ ]+';
		$regex .= '|[\.]';
		$regex .= '|[\s]';
		$regex .= ')'; # end group
		preg_match_all('/' . $regex . '/smx', $sqlQuery, $result);
		return $result[0];
	}


	public static function ParseString($sqlQuery, $cleanWhitespace = true) {

		$tokens = self::Tokenize($sqlQuery, $cleanWhitespace);
		$tokenCount = count($tokens);
		$queryParts = array();
		if (isset($tokens[0]) === true) {
			$section = $tokens[0];
		}

		for ($t = 0; $t < $tokenCount; $t++) {

			if (in_array($tokens[$t], self::$startparens)) {

				$sub = $handle->readsub($tokens, $t);
				$handle->query[$section] .= $sub;

			} else {

				if (in_array(strtolower($tokens[$t]), self::$querysections) && !isset($handle->query[$tokens[$t]])) {
					$section = strtolower($tokens[$t]);
				}

				if (!isset($handle->query[$section]))
					$handle->query[$section] = '';
				$handle->query[$section] .= $tokens[$t];
			}
		}

		return $handle;
	}


	private function readsub($tokens, &$position) {

		$sub = $tokens[$position];
		$tokenCount = count($tokens);
		$position++;
		while (!in_array($tokens[$position], self::$endparens) && $position < $tokenCount) {

			if (in_array($tokens[$position], self::$startparens)) {
				$sub .= $this->readsub($tokens, $position);
				$subs++;
			} else {
				$sub .= $tokens[$position];
			}
			$position++;
		}
		$sub .= $tokens[$position];
		return $sub;

	}


	public function getCountQuery($optName = 'count') {

		$temp = $this->query;

		$temp['select'] = 'select count(*) as `' . $optName . '` ';
		if (isset($temp['limit'])) {
			unset($temp['limit']);
		}

		return implode(null, $temp);

	}


	public function getLimitedCountQuery() {
		$this->query['select'] = 'select count(*) as `count` ';
		return implode('', $this->query);
	}


	public function getSelectStatement() {
		return $this->query['select'];
	}


	public function getFromStatement() {
		return $this->query['from'];
	}


	public function getWhereStatement() {

		return $this->query['where'];
	}


	public function getLimitStatement() {
		return $this->query['limit'];
	}


	public function get($which) {
		if (!isset($this->query[$which]))
			return false;
		return $this->query[$which];
	}


	public function getArray() {
		return $this->query;
	}

}
