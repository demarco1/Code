<!DOCTYPE html>
<html lang="en">
	<head>
		<title>PortholeProxy</title>
		<script type="text/javascript" src="/resources/porthole.js"></script>
		<script type=\"text/javascript\">
			window.onload = function() { 
				window.proxy = new Porthole.WindowProxy("<?php echo $_REQUEST['parent'];?>");
			};
		</script>
	</head>
	<body>
	</body>
</html>
