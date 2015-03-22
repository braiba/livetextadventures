<?php
	$page = $this->prerenderPage();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<?php 
			$this->renderFragment('html_head');
		?>
	</head>
	<body style="background: black; color: green; font-family: courier">	
		<?php
			echo $page;				
		?>
	</body>
</html>