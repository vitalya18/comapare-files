<?php

define('DEBUG', false);

/* 0 - var_dump; 1 - echo */
function dd($data, $type = 0) {
	if (DEBUG == false)
		return;
	if ($type == 0 && is_array($data))
		echo '<pre>';
	if ($type == 0)
		var_dump($data);
	else
		echo $data;
	if ($type == 0 &&is_array($data))
		echo '</pre>';
}

class DiffFiles {

	//private $files = [];
	private $data = [];

	private $currentRow = 1;

	private $result = [];

	private function checkExisting($path) {
		return file_exists($path);
	}

	function __construct($files) {
		$this->addFiles($files);
	}

	public function addFiles($files) {

		$this->clear();

		if (!is_array($files))
			throw new Exception('Send array');

		if (count($files) != 2)
			throw new Exception('Send 2 files path');

        foreach ($files as $path) {
            if (!$this->checkExisting($path)) {
				$this->clear();
                throw new Exception('Path not validate');
            }

			$this->data[] = explode("\n", file_get_contents($path));
        }

		return $this;
	}

	public function clear() {
		$this->data = [];
		$this->currentRow = 1;
		$this->result = [];
	}

	/* Search coincidence in $data */
	private function search($string, $data, $from)
	{
		return array_search($string, array_splice($data, $from));
	}

	private function addResult($type, $row)
	{
		$this->result[] = [
			'index' => $this->currentRow++,
			'type' => $type,
			'row' => $row
		];
	}

	private function countFirstOverlap($fCursor, $sCursor, $firstFile, $secondFile)
	{
		$tempFCursor = $fCursor + 1;
		$firstOverlap = null;
		while ($tempFCursor < count($firstFile)) {
			$willBe = $this->search($firstFile[$tempFCursor], $secondFile, $sCursor);
			if ($willBe !== FALSE && $willBe != null) {
				$firstOverlap = $willBe;
				break;
			}
			++$tempFCursor;
		}
		dd('firstOverlap = ' . $firstOverlap, 1);

		return $firstOverlap;
	}

	function compare() {
		$firstFile = $this->data[0];
		$secondFile = $this->data[1];

		$fCursor = 0;
		$sCursor = 0;

		while(true) {
            if ($firstFile[$fCursor] == $secondFile[$sCursor]) {
				$this->addResult('', $firstFile[$fCursor]);
				++$fCursor;
				++$sCursor;
            }
			else {
				$searchIndex = $this->search($firstFile[$fCursor], $secondFile, $sCursor);
				$searchIndexInFirst = $this->search($firstFile[$fCursor], $firstFile, $fCursor + 1);
				dd($firstFile[$fCursor] . ' ' . $fCursor . ' ' . $sCursor . ' ' . $searchIndex . ' ' . $searchIndexInFirst, 1);

				$firstOverlap = $this->countFirstOverlap($fCursor, $sCursor, $firstFile, $secondFile);

				if ($firstOverlap != null)
				{
					$whenInFirstFile = $this->search($secondFile[$firstOverlap], $firstFile, $fCursor);
					dd(' in first = ' . $whenInFirstFile, 1);

					if ($whenInFirstFile === FALSE || $firstOverlap == $whenInFirstFile)
					{
						$this->addResult('-', $firstFile[$fCursor]);
						++$fCursor;
						dd('<br>', 1);
						continue;
					}
				}

				if ($searchIndex === FALSE)
				{
					if (!isset($firstFile[$fCursor]))
						$this->addResult('+', $secondFile[$sCursor]);
					else if (!isset($secondFile[$sCursor]))
						$this->addResult('-', $firstFile[$fCursor]);
					else if (isset($firstFile[$fCursor]) && isset($secondFile[$sCursor]))
						$this->addResult('*', "$firstFile[$fCursor]|$secondFile[$sCursor]");

					++$fCursor;
					++$sCursor;
				}
				else
				{
					if ($searchIndexInFirst !== FALSE)
					{
						$this->addResult('-', $firstFile[$fCursor]);
						++$fCursor;
					}

					if ($searchIndex >= $fCursor || $searchIndex < $fCursor)
					{
						$searchIndex = $this->search($firstFile[$fCursor], $secondFile, 0);

						while($searchIndex > $sCursor)
						{
							$this->addResult('+', $secondFile[$sCursor]);
							++$sCursor;
						}
					}
				}
			}

			dd('<br>', 1);

			if (!isset($firstFile[$fCursor]) && !isset($secondFile[$sCursor]))
				break;
		}

		return $this;
	}

	function output() {
		echo '<table>';
		foreach ($this->result as $data)
		{
			echo "<tr>
				<td style='min-width: 50px'>{$data['index']}</td>
				<td style='min-width: 50px'>{$data['type']}</td>
				<td style='min-width: 50px'>{$data['row']}</td>
			</tr>";
		}
		echo '</table>';
	}

}

$test1 = [
	'./data/first.txt',
	'./data/second.txt'
];

$test2 = [
	'./data/_first.txt',
	'./data/_second.txt'
];

$diffFiles = new DiffFiles($test1);
$diffFiles->compare()->output();

echo '<br><br><br><br>';

$diffFiles->addFiles($test2)->compare()->output();