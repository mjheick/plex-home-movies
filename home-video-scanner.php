<?php
/**
 * To be run as a cron. This creates symlinks of files with the following conditions:
 * - Creates a symlink with a filename that is the 'quick' hash of the file with the extension matching the source, all lowercased
 * - NFO files with the relevant information, if the NFO does not exist for source file
 * - a yml file that can be used to CRUD nfo data
 */

/* Variables */
$source_folder = '/mnt/usb14/Archive';
$destination_folder = '/mnt/usb14/Home-Video';
$desination_filename = 'home-video-scanner.yml';
$file_extensions = ['3g2', '3gp', 'avi', 'mov', 'mp4', 'mpg', 'mts', 'webm', 'wmv'];
$files = [];

/* A recursive deep-scanning directory function */
function scan_folder($dir = '')
{
	$files = [];
	if (substr($dir, strlen($dir), 1) != '/')
	{
		$dir = $dir . '/';
	}
	/* Check if this is a directory. If not, return "nothing" */
	if (!is_dir($dir)) {
		return [];
	}
	$all_files = dir($dir);
	while (false !== ($entry = $all_files->read())) {
		if (($entry == '.') || ($entry == '..'))
		{
			continue;
		}
		if (is_dir($dir . $entry))
		{
			$sub_files = [];
			$sub_files = scan_folder($dir . $entry);
			$files = array_merge($files, $sub_files);
		}
		else
		{
			$files[] = $dir . $entry;
		}
	}
	$all_files->close();
	return $files;
}

/* A quick file md5 function. Read the first 40kb and return a md5 of that data */
function quick_file_hash($file = '')
{
	if (!is_readable($file))
	{
		return null;
	}
	$fp = fopen($file, 'rb');
	$data = fread($fp, 40960);
	fclose($fp);
	return md5($data);
}


/* Scan the $source_folder */
$all_files = scan_folder($source_folder);
/* cull $all_files to only files that contain $file_extensions */
$files = [];
foreach ($all_files as $file)
{
	/* Extract the file extension */
	$pathinfo = pathinfo($file);
	if (isset($pathinfo['extension']))
	{
		$extension = strtolower($pathinfo['extension']);
		if (in_array($extension, $file_extensions))
		{
			$files[] = $file;
		}
	}
}
unset($all_files);

/* Create a hash/files array so we know which files are considered duplicates */
$hash = [];
foreach ($files as $file)
{
	$sum = quick_file_hash($file);
	if (array_key_exists($sum, $hash))
	{
		$hash[$sum][] = $file;
	} else {
		$hash[$sum] = [$file];
	}
}

/* Set up our persistent storage and load it up */
$storage = [];
if (!file_exists($destination_folder . '/' . $desination_filename))
{
	file_put_contents($destination_folder . '/' . $desination_filename, yaml_emit($storage));
}
$storage = yaml_parse(file_get_contents($destination_folder . '/' . $desination_filename));

/**
 * Loop through what we have, write it to persistent storage and make our first nfo files if we need to
 * Each entry needs to have the following:
 * md5:
 *   uniqueid: whatever the key is
 *   title: <filename>
 *   premiered: earliest file date/time
 *   country: unknown (unless known)
 *   director: unknown (unless known)
 *   actors: []
 */
foreach ($hash as $key => $files)
{
	/* Check if the nfo file exists. If not, do stuff */
	$nfo_file = $destination_folder . '/' . $key . '.nfo';
	if (file_exists($nfo_file))
	{
		continue;
	}

	/* Check if we've known of this file before and have chosen not to show it in plex via 'unlist' boolean */
	if (isset($storage[$key]['unlist']) && $storage[$key]['unlist'])
	{
		continue;
	}

	/* make the symlink if it doesn't exist */
	$pathinfo = pathinfo($files[0]);
	$extension = strtolower($pathinfo['extension']);
	$symlink = $destination_folder . '/' . $key . '.' . $extension;
	if (!file_exists($symlink)) 
	{
		symlink($files[0], $symlink);
	}

	/* Make the nfo file */
	$nfo_title = 'CHANGEME-' . $pathinfo['basename']; /* The filename will have to do for now */
	$nfo_uniqueid = $key; /* md5 hash is unique enough */
	$nfo_released = '';

	/* This goes through and picks the earliest file time and uses that initially as the "date" */
	$stat = stat($files[0]);
	$mtime = $stat['mtime'];
	foreach ($files as $f)
	{
		$stat = stat($f);
		if ($mtime > $stat['mtime'])
		{
			$mtime = $stat['mtime'];
		}
	}
	$nfo_released = date('Y-m-d', $mtime);

	$nfo_contents = 
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
		'<movie>' . "\n" .
		'  <uniqueid type="home" default="true">' . $nfo_uniqueid . '</uniqueid>' . "\n" .
		'  <title>' . $nfo_title . '</title>' . "\n" .
		'  <premiered>' . $nfo_released . '</premiered>' . "\n" .
		'  <country>unknown</country>' . "\n" .
		'  <director>unknown</director>' . "\n" .
		'</movie>' . "\n";
	file_put_contents($nfo_file, $nfo_contents);

	/* Verify what we wrote is valid XML */
	$xml = XMLReader::open($nfo_file);
	$xml->setParserProperty(XMLReader::VALIDATE, true);
	if (!$xml->isValid())
	{
		echo "$nfo_file is not valid xml :(\n";
		unlink($nfo_file);
		continue;
	}

	/* Add to persistent storage */
	$storage[$key] = [
		'uniqueid' => $key,
		'title' => $nfo_title,
		'premiered' => $nfo_released,
		'country' => 'unknown',
		'director' => 'unknown',
		'actors' => [],
	];
}

/* Write out the persistent storage */
file_put_contents($destination_folder . '/' . $desination_filename, yaml_emit($storage));
