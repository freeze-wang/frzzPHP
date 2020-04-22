<?php
class Plus_SQLJob implements Plus_Job {

	private $sql;
	private $params;
	private $dataSouceIndex;

	function __construct($sql, $params = null, $dataSouceIndex = 0) {
		$this->sql = $sql;
		$this->params = $params;
		$this->dataSouceIndex = $dataSouceIndex;
	}

	public function execute() {
		$freeSQL = new Plus_FreeSQL();
		$freeSQL->_sql($this->sql, $this->params, $this->dataSouceIndex)->_execute();
	}

}