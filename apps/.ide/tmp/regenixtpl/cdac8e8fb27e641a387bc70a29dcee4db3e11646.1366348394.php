<!DOCTYPE html>
<html>
<head>
    <title>Not found</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<h1>Not found</h1>
<p>
    <?php echo htmlspecialchars((string)($e->getMessage()))?>
</p>
</body>
</html>
<?php if($__extends){ $_TPL->_renderContent(); } ?>