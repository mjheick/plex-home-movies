<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Configuration */
$storage = '/mnt/usb14/Home-Video/home-video-scanner.yml';
$data = yaml_parse(file_get_contents($storage));
$nfo_file_storage = '/mnt/usb14/';

/* swapped based on how we're handling things */
$mode = 'search';
$error = '';

/* Do whatever we need with POST/GET */ 
if ( isset($_POST['title']) && (strlen($_POST['title']) > 0) ) /* Doing a search, going to results mode or edit mode */
{
	$results = [];
	$title = $_POST['title'];
	foreach ($data as $key => $value)
	{
		if (isset($value['title']))
		{
			if (strpos(strtolower($value['title']), strtolower($title)) !== false)
			{
				$results[] = $key;
			}
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
		header('Location: ' . $_SERVER['PHP_SELF'] . '?edit=' . $results[0], 301);
		exit;
	}
	if (count($results) > 1)
	{
		$mode = 'results';
	}
}
if (isset($_GET['edit']))
{
	$mode = 'edit';
	$index = $_GET['edit'];
}

if ($mode === 'search')
{
	?><!doctype html>
<html>
<head>
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
 <title>search</title>
</head>
<body>
 <div>
 <form method="post">
 Title: <input type="text" name="title" value="" /> <input type="submit" value="Search" />
 </form>
 </div>
<?php
if (strlen($error) > 0)
{
	echo '<div>' . $error . '</div>';
}
?>
</body>
</html>
<?php
	exit;
}

if ($mode === 'results')
{
	?><!doctype html>
<html>
<head>
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
 <title>results:</title>
</head>
<body>
 <div>results</div>
<?php
	foreach ($results as $key)
	{
		echo '<a href="?edit=' . $key . '">' . $data[$key]['title'] . '</a><br />';
	}
?>
</body>
</html>
<?php
	exit;
}

if ($mode === 'edit')
{
	?><!doctype html>
<html>
<head>
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
 <title>edit</title>
</head>
<body>
 <div>
 <form method="post">
 </form>
 </div>
</body>
</html>
<?php
	exit;
}
?>