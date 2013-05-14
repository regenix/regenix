<?php echo $_TPL->_renderBlock("content", 'system/base'); $__extends = true;?>
<?php $_TPL->_renderTag("set", array('title' => 'Application exception', ));?>

<?php if(IS_DEV):?>
<div class="error">
    <h1><?php echo htmlspecialchars((string)($info->getShortName()))?></h1>
    <p id="detail">
        <?php echo htmlspecialchars((string)($desc))?>
    </p>
    <?php if($source):?>
        <?php if($file):?>
            <h2>In `/<?php echo htmlspecialchars((string)($file))?>` at line <b><?php echo htmlspecialchars((string)($stack[line]))?></b>.</h2>
        <?php endif?>
    <div id="source-code">
        <?php foreach($source as $i => $line):?>
            <pre class="<?php echo htmlspecialchars((string)($i == $stack[line] ? 'error':''))?>"><span class="line"><?php echo htmlspecialchars((string)($i))?></span><span class="code"><?php echo htmlspecialchars((string)($line))?></span></pre>
        <?php endforeach?>
    </div>
    <?php endif?>
</div>
    <?php if($stack):?>
        <b class="stackTraceTitle">Stack Stace:</b>
        <pre class="stackTrace"><?php echo htmlspecialchars((string)($exception -> getTraceAsString()))?></pre>
    <?php endif?>
<?php else:?>
<div class="error">
    <h1>Application Error</h1>
    <p id="detail">
        Please contact with administration.
    </p>
    <div id="source-code">
        <pre><span class="line"> </span><span class="code">Error ID = <b><?php echo htmlspecialchars((string)($hash))?></b></span></pre>
    </div>
</div>
<?php endif?><?php if($__extends){ $_TPL->_renderContent(); } ?>