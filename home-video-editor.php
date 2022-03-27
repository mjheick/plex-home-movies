<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Configuration */
$storage = '/mnt/usb14/Home-Video/home-video-scanner.yml';
$data = yaml_parse(file_get_contents($storage));
$nfo_file_storage = '/mnt/usb14/Home-Video';
$mode = 'search';
$title = '';
$index = '';
$error = '';

/* If we're editing and have sent data lets make the necessary changes */
if (isset($_POST['editing']) && $_POST['editing'] == 'yes')
{
	/* We have to remake the nfo file. Set all incoming variables. If something is null we don't use it */
	$index = isset($_POST['index']) ? $_POST['index'] : null;
	$edit_title = isset($_POST['edit_title']) ? $_POST['edit_title'] : null;
	if (is_null($index) || is_null($edit_title)) /* required per the "spec" at https://kodi.wiki/view/NFO_files/Movies */
	{
		header("Location: " . $_SERVER['PHP_SELF'], 302);
		die();
	}
	$edit_premiered = isset($_POST['edit_premiered']) ? $_POST['edit_premiered'] : '';
	$edit_country = isset($_POST['edit_country']) ? $_POST['edit_country'] : 'unknown';
	$edit_director = isset($_POST['edit_director']) ? $_POST['edit_director'] : 'unknown';
	$edit_outline = isset($_POST['edit_outline']) ? $_POST['edit_outline'] : '';
	$edit_actors = isset($_POST['edit_actors']) ? explode(',', $_POST['edit_actors']) : [];
	if (count($edit_actors) === 0) { $edit_actors = []; }
	/* debug stuff */
	$debug_unlist = isset($_POST['debug_unlist']) ? $_POST['debug_unlist'] : null;
	$debug_badvideo = isset($_POST['debug_badvideo']) ? $_POST['debug_badvideo'] : null;
	$debug_badaudio = isset($_POST['debug_badaudio']) ? $_POST['debug_badaudio'] : null;

	/* Make the change in the master array */
	$data[$index]['title'] = $edit_title;
	$data[$index]['premiered'] = $edit_premiered;
	$data[$index]['country'] = $edit_country;
	$data[$index]['director'] = $edit_director;
	$data[$index]['outline'] = $edit_outline;
	$data[$index]['actors'] = $edit_actors;
	$data[$index]['debug'] = [];
	if ((!is_null($debug_unlist)) || (!is_null($debug_badvideo)) || (!is_null($debug_badaudio)))
	{
		if (!is_null($debug_unlist))
		{
			$data[$index]['debug']['unlist'] = true;
		}
		if (!is_null($debug_badvideo))
		{
			$data[$index]['debug']['badvideo'] = true;
		}
		if (!is_null($debug_badaudio))
		{
			$data[$index]['debug']['badaudio'] = true;
		}
	}
	/* Write the new YAML file */
	file_put_contents($storage, yaml_emit($data));

	/* Based on the master array, make the nfo */
	$nfo_released = date('Y-m-d', $data[$index]['premiered']);

	$nfo_actors = '';
	if (count($edit_actors) > 0)
	{
		$order = 0;
		foreach ($edit_actors as $a)
		{
			$nfo_actors = $nfo_actors .
				'  <actor>' . "\n" .
				'    <name>' . $a . '</name>' . "\n" .
				'    <role>' . $a . '</role>' . "\n" .
				'    <order>' . ($order) . '</order>' . "\n" .
				'  </actor>' . "\n";
			$order++;
		}
	}

	$nfo_contents = 
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
		'<movie>' . "\n" .
		'  <uniqueid type="home" default="true">' . $index . '</uniqueid>' . "\n" .
		'  <title>' . $data[$index]['title'] . '</title>' . "\n" .
		'  <premiered>' . $data[$index]['premiered'] . '</premiered>' . "\n" .
		'  <country>' . $data[$index]['country'] . '</country>' . "\n" .
		'  <director>' . $data[$index]['director'] . '</director>' . "\n" .
		'  <outline>' . $data[$index]['outline'] . '</outline>' . "\n" .
		$nfo_actors .
		'</movie>' . "\n";
	if (is_null($debug_unlist))
	{
		file_put_contents($nfo_file_storage . '/' . $index . '.nfo', $nfo_contents);
	}
	else
	{
		array_map('unlink', glob($nfo_file_storage . '/' . $index . '.*'));
	}

	/* All done writing and doing stuff, redirect so we can show changes */
	header('Location: ' . $_SERVER['PHP_SELF'] . '?edit=' . $index, 302);
	die();
}
/* Respond if we're using the "search" bar at the top */ 
if ( isset($_POST['title']) && (strlen($_POST['title']) > 0) ) /* Doing a search, going to results mode or edit mode */
{
	$results = [];
	$title = $_POST['title'];
	foreach ($data as $key => $value)
	{
		if (isset($value['title']) && strpos(strtolower($value['title']), strtolower($title)) !== false)
		{
			$results[] = $key;
		}
	}
	if (count($results) === 0)
	{
		$mode = 'search';
		$error = "search &quot;$title&quot; not found";
	}
	if (count($results) === 1)
	{
		/* go right to edit */
		header('Location: ' . $_SERVER['PHP_SELF'] . '?edit=' . $results[0], 302);
		exit;
	}
	if (count($results) > 1)
	{
		$mode = 'results';
	}
}
/* If we're editing, put us in proper edit mode */
if (isset($_GET['edit']))
{
	$mode = 'edit';
	$index = $_GET['edit'];
	$title = $data[$index]['title'];
}

/**
 * This is where we show some stuff:
 * - Search at top: Always, with what we searched
 * - Results: If we're performing a search
 * - Editor: If we've performed a 1-result search, or if we've select an item from results
 */

?><!doctype html>
<html lang="en">
<head>
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
 <title>home-video-editor</title>
</head>
<body>
 <div>
  <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
   Search: <input type="text" name="title" value="<?php echo $title; ?>" /> <input type="submit" value="Dew Eet" />
  </form>
 </div>
<?php
if (strlen($error) > 0)
{
	echo '<div>' . $error . '</div>';
}

/* Display search results */
if ($mode === 'results')
{
	echo "<hr />";
	foreach ($results as $key)
	{
		echo '<a href="?edit=' . $key . '">' . $data[$key]['title'] . '</a><br />';
	}
}

/* If we're editing our one result */
if ($mode === 'edit')
{
	echo "<hr />";
	$edit_title = isset($data[$index]['title']) ? $data[$index]['title'] : '';
	$edit_premiered = isset($data[$index]['premiered']) ? $data[$index]['premiered'] : '';
	$edit_country = isset($data[$index]['country']) ? $data[$index]['country'] : '';
	$edit_director = isset($data[$index]['director']) ? $data[$index]['director'] : '';
	$edit_outline = isset($data[$index]['outline']) ? $data[$index]['outline'] : '';
	$edit_actors = isset($data[$index]['actors']) ? implode(',', $data[$index]['actors'])  : '';
	$debug_unlist = isset($data[$index]['debug']['unlist']) ? true : false;
	$debug_badvideo = isset($data[$index]['debug']['badvideo']) ? true : false;
	$debug_badaudio = isset($data[$index]['debug']['badaudio']) ? true : false;
?>
 <div>
 	<div><span style='font-weight: bold;'>Editing <?php echo $data[$index]['title']; ?>:</span></div>
  <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  	<input type="hidden" name="editing" value="yes" />
  	<input type="hidden" name="index" value="<?php echo $index; ?>" />
  	<div>Title: <input type="text" name="edit_title" value="<?php echo $edit_title; ?>" /></div>
  	<div>Premiered: <input type="text" name="edit_premiered" value="<?php echo $edit_premiered; ?>" /> (yyyy-mm-dd)</div>
  	<div>Country: <input type="text" name="edit_country" value="<?php echo $edit_country; ?>" /></div>
  	<div>Director: <input type="text" name="edit_director" value="<?php echo $edit_director; ?>" /></div>
  	<div>Outline: <input type="text" name="edit_outline" value="<?php echo $edit_outline; ?>" /></div>
  	<div>Actors: <input type="text" name="edit_actors" value="<?php echo $edit_actors; ?>" /> (comma-separated list)</div>
  	<hr />
  	<div>Unlist: <input type="checkbox" name="debug_unlist" value="true" <?php if ($debug_unlist) { echo "checked"; } ?> /> (don't show up in plex library)</div>
  	<div>Bad Display: <input type="checkbox" name="debug_badvideo" value="true" <?php if ($debug_badvideo) { echo "checked"; } ?> /> (issues with displaying video)</div>
  	<div>Bad Audio: <input type="checkbox" name="debug_badaudio" value="true" <?php if ($debug_badaudio) { echo "checked"; } ?> /> (issues with audio)</div>
  	<hr />
  	<div><input type="submit" /></div>
  </form>
 </div>
<?php
}
?>
</body>
</html>