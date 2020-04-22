<?php
class Plus_JobExecutor extends Plus_DaemonService {

	const STATUS_RUNNING = 'Plus_JobExecutor_Running';
	const STATUS_JOB_COUNT = 'Plus_JobExecutor_Job_Count';
	const STATUS_MEMORY_USAGE = 'Plus_JobExecutor_Memory_Usage';

	private $queue;

	function __construct() {
		if (!is_callable("apc_fetch"))
			die("Plus_JobExecutor needs apc extension!");
	}

	public function start() {

		$preInstance = (apc_fetch(self::STATUS_RUNNING) == 'RUNNING');
		if ($preInstance) die();

		apc_store(self::STATUS_RUNNING, 'RUNNING');
		apc_store(self::STATUS_JOB_COUNT, 0);
		apc_store(self::STATUS_MEMORY_USAGE, 0);

		$this->queue = new Plus_JobQueue();
		parent::start();

	}

	public function stop() {
		apc_store(self::STATUS_RUNNING, 'STOP');
	}


	public function status() {

		return array(
			'RUNNING' => apc_fetch(self::STATUS_RUNNING),
			'INFO' => apc_fetch(self::STATUS_JOB_COUNT) . " jobs executed",
			'MEM_USAGE' => apc_fetch(self::STATUS_MEMORY_USAGE),
		);

	}

	public function stopCondition() {
		return (apc_fetch(self::STATUS_RUNNING) != 'RUNNING');
	}

	public function run() {

		$job = $this->queue->get();
		if ($job != null) {
			$job->execute();
			unset($job);
			apc_inc(self::STATUS_JOB_COUNT, 1);
			apc_store(self::STATUS_MEMORY_USAGE, memory_get_usage());
			return true;
		}
		return false;

	}

}