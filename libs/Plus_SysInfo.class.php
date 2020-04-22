<?php
class Plus_SysInfo {

	public function getCPUInfo() {

		$fileContent = file_get_contents('/proc/cpuinfo');
		$processors = preg_split('/\s?\n\s?\n/', trim($fileContent));
		$cpus = array();
		foreach ($processors as $processor) {
			$cpu = array();
			$details = preg_split("/\n/", $processor, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($details as $detail) {
				$detailInfo = preg_split('/\s+:\s+/', trim($detail));
				if (count($detailInfo) == 2) {
					switch (strtolower($detailInfo[0])) {
						case 'processor':
							$cpu['LOAD'] = ($this->parseProcStat('cpu' . trim($detailInfo[1])));
							break;
						case 'model name':
						case 'cpu':
							$cpu['MODEL'] = $detailInfo[1];
							break;
						case 'cpu mhz':
						case 'clock':
							if ($detailInfo[1] > 0) //openSUSE fix
								$cpu['SPEED'] = $detailInfo[1];
							break;
						case 'cycle frequency [hz]':
							$cpu['SPEED'] = ($detailInfo[1] / 1000000);
							break;
						case 'cpu0clktck':
							$cpu['SPEED'] = (hexdec($detailInfo[1]) / 1000000); // Linux sparc64
							break;
						case 'l2 cache':
						case 'cache size':
							$cpu['CACHE'] = (preg_replace("/[a-zA-Z]/", "", $detailInfo[1]) * 1024);
							break;
						case 'bogomips':
						case 'cpu0bogo':
							$cpu['BOGO'] = $detailInfo[1];
							break;
						case 'flags':
							if (preg_match("/ vmx/", $detailInfo[1])) {
								$cpu['VIRT'] = "vmx";
							} else if (preg_match("/ svm/", $detailInfo[1])) {
								$cpu['VIRT'] = "svm";
							}
							break;
					}
				}
			}
			$cpus[] = $cpu;
		}

		var_dump($cpus);

	}

	private function parseProcStat($cpuline) {

		$load = 0;
		$load2 = 0;
		$total = 0;
		$total2 = 0;
		$fileContent = file_get_contents('/proc/stat');
		$lines = preg_split("/\n/", $fileContent, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($lines as $line) {
			if (preg_match('/^' . $cpuline . ' (.*)/', $line, $matches)) {
				$ab = 0;
				$ac = 0;
				$ad = 0;
				$ae = 0;
				sscanf($fileContent, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
				$load = $ab + $ac + $ad; // cpu.user + cpu.sys
				$total = $ab + $ac + $ad + $ae; // cpu.total
				break;
			}
		}
		sleep(1);
		$fileContent = file_get_contents('/proc/stat');
		$lines = preg_split("/\n/", $fileContent, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($lines as $line) {
			if (preg_match('/^' . $cpuline . ' (.*)/', $line, $matches)) {
				$ab = 0;
				$ac = 0;
				$ad = 0;
				$ae = 0;
				sscanf($fileContent, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
				$load2 = $ab + $ac + $ad; // cpu.user + cpu.sys
				$total2 = $ab + $ac + $ad + $ae; // cpu.total
				break;
			}
		}
		if ($total > 0 && $total2 > 0 && $load > 0 && $load2 > 0 && $total2 != $total && $load2 != $load) {
			return (100 * ($load2 - $load)) / ($total2 - $total);
		}
		return 0;

	}

}