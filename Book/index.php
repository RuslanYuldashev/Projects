<html>
<body>

<?php
	//header('Content-Type: text/html; charset=utf-8');
	mb_internal_encoding("utf-8");
	include_once("parsers.php");		
	if (isset($_POST['file_path'])) {
		$file_path = $_POST['file_path'];
		/*$file_path = "import.xml";*/
		if (file_exists($file_path))
		{
			
			$extension = substr($file_path, -3, 3);
			$parser_name = $extension.'_parser';
			if (class_exists($parser_name)) {
				
				//exit();		
				$in_file = new $parser_name();
				$in_file->parse($file_path);
					
			} 
			else {
				echo "invalid extension";
				exit();
			}
		}
		else {
			echo "File not found!";
			exit();
		}
	}
?>

<form method="POST"> 
File_Path: <input type="text" name="file_path">
<input type="submit" value="Send">
</form>


</body>
</html>