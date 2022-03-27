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
	if (is_null($index))
	{
		/* minimally need this */
		header("Location: " . $_SERVER['PHP_SELF'], 302);
		die();
	}
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
?>
 <div>
  <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  	<input type="hidden" name="editing" value="yes" />
  	<input type="hidden" name="index" value="<?php echo $index; ?>" />
  	<div>Title: <input type="text" name="edit_title" value="<?php echo $data[$index]['title']; ?>" /></div>
  	<div>Premiered: <input type="text" name="edit_premiered" value="<?php echo $data[$index]['premiered']; ?>" /> (yyyy-mm-dd)</div>
  	<div>Country: <input type="text" name="edit_country" value="<?php echo $data[$index]['country']; ?>" /></div>
  	<div>Director: <input type="text" name="edit_director" value="<?php echo $data[$index]['director']; ?>" /></div>
  	<div>Actors: <input type="text" name="edit_actors" value="" /> (comma-separated list)</div>
  	<hr />
  	<div>Unlist: <input type="checkbox" name="edit_unlist" /></div>
  	<div>Bad Display: <input type="checkbox" name="edit_baddisplay" /></div>
  </form>
 </div>
<?php
}
?>
</body>
</html>