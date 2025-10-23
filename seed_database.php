<?php
header('Content-type: text/plain');
try {
	set_time_limit(0);

	$db = new mysqli('localhost');
	$db->query("
		CREATE TABLE IF NOT EXISTS `planes` (
			`hex` BINARY(6) NOT NULL,
			`reg` VARCHAR(14) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
			`type` CHAR(4) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
			`description` VARCHAR(64) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
			PRIMARY KEY (`hex`) USING BTREE
		)
		COLLATE='utf8mb4_unicode_ci'
		ENGINE=InnoDB;
	");

	echo "Downloading repo\n";
	file_put_contents('tar1090-db.zip', file_get_contents('https://github.com/wiedehopf/tar1090-db/archive/refs/heads/master.zip'));

	echo "Extracting repo\n";
	$zip = new ZipArchive();
	$res = $zip->open('tar1090-db.zip', ZipArchive::RDONLY);
	if($res === true) {
		$zip->extractTo('.');
		$zip->close();
	} else {
		echo "Zip extract failed with code: $res\n";
		exit($res);
	}

	echo "Expanding files\n";
	foreach(glob('tar1090-db-master/db/*.js') as $filename) {
		$basename = basename($filename);
		file_put_contents($basename . 'on', gzdecode(file_get_contents($filename)));
	}

	echo "Importing files\n";
	$files = json_decode(file_get_contents('files.json'));

	$db->query('TRUNCATE TABLE planes');

	$inserted = 0;
	foreach($files as $prefix) {
		echo "Loading file $prefix\n";
		$json = json_decode(file_get_contents("$prefix.json"));
		foreach($json as $suffix => $data) {
			if($suffix == 'children') {
				continue;
			}
			unset($data[2]); //bitmask of flags
			$data[1] = trim($data[1]);
			$data[3] = trim($data[3]);
			if($data[1] == 'RV-12') {
				$data[1] = 'RV12';
			}
			try {
				$db->execute_query("INSERT INTO `planes` (`hex`, `reg`, `type`, `description`) VALUES (?, ?, ?, ?)", [$prefix . $suffix, ...$data]);
				$inserted += $db->affected_rows;
			} catch(mysqli_sql_exception $e) {
				echo "{$e->getMessage()} on $prefix$suffix\n";
			}
		}
	}
	echo "Inserted $inserted rows\n";
} catch(Throwable $e) {
	print_r($e);
} finally {
	unlink('tar1090-db.zip');
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator('tar1090-db-master', FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST,
	);
	foreach($files as $file) {
		$fn = $file->isDir() ? 'rmdir' : 'unlink';
		$fn($file->getRealPath());
	}
	rmdir('tar1090-db-master');
	foreach(glob('*.json') as $filename) {
		unlink($filename);
	}
}
