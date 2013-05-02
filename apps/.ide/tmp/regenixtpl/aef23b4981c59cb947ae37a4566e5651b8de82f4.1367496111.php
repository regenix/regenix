<!DOCTYPE html>
<html>
<head>
    <title>IDE: <?php $_TPL->_renderTag("get", array('_arg' => 'title', ));?></title>
    <meta charset="UTF-8">

    <script type="text/javascript">
        ROOT        = "<?php $_TPL->_renderTag("path", array('_arg' => 'IDE.index', ));?>";
        ASSETS_PATH = "<?php $_TPL->_renderTag("asset", array('_arg' => null, ));?>";
    </script>

    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'css/main.css', ));?>
    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/async.js', ));?>
    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/head.min.js', ));?>
    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/jquery-1.9.1.min.js', ));?>
    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/jquery-migrate-1.1.1.min.js', ));?>

    <?php if(IS_DEV):?>
        <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/ext-all-debug-w-comments.js', ));?>
    <?php else:?>
        <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/ext-all.js', ));?>
    <?php endif?>

    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'css/ext-all.css', ));?>
    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/plugin.js', ));?>
    <?php $_TPL->_renderTag("html.asset", array('_arg' => 'js/ide.js', ));?>
</head>
<body>
    %__BLOCK_doLayout__%
</body>
</html><?php if($__extends){ $_TPL->_renderContent(); } ?>