<?php
class Plus_JobQueue extends Plus_Queue {

	function __construct() {
		parent::__construct("job");
	}

}