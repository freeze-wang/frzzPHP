<?php
abstract class Plus_DaemonService {

	protected $interval = 1;

	public abstract function stopCondition();

	public abstract function run();

	public abstract function status();

	public function start() {

		ignore_user_abort(TRUE);
		set_time_limit(0);

		if (is_callable("fastcgi_finish_request"))
			fastcgi_finish_request();

		do {
			if (!$this->run())
				sleep($this->interval);
		} while (!$this->stopCondition());

	}


}