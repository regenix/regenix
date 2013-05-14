<h2><?php echo htmlspecialchars((string)($detail['file']))?>, Status:
    <b><?php echo htmlspecialchars((string)($detail['result'] ? 'SUCCESS' : 'FAIL'))?></b></h2>

<div id="detail">
    <a href="<?php $_TPL->_renderTag("path", array('id' => null, ));?>"><?php echo htmlspecialchars(\framework\libs\I18n::get('To back'))?></a>
    <p></p>
    <table class="list">
        <tr>
            <th width="25px"></th>
            <th width="120px"><?php echo htmlspecialchars(\framework\libs\I18n::get('Method'))?></th>
            <th width="90px"><?php echo htmlspecialchars(\framework\libs\I18n::get('Assert'))?></th>
            <th width="50px"><?php echo htmlspecialchars(\framework\libs\I18n::get('Line'))?></th>
            <th><?php echo htmlspecialchars(\framework\libs\I18n::get('Source'))?></th>
        </tr>

        <?php foreach($detail['log'] as $method => $calls):?>
            <?php foreach($calls as $call):?>
            <tr>
                <td><span class="ico <?php echo htmlspecialchars((string)($call['result'] ? 'success' : 'fail'))?>">&#160;</span></td>
                <td><?php echo htmlspecialchars((string)($method))?></td>
                <td><?php echo htmlspecialchars((string)($call['method']))?></td>
                <td>[ <a href="<?php $_TPL->_renderTag("path", array('id' => $detail['class'], 'line' => $call['line'], ));?>"><?php echo htmlspecialchars((string)($call['line']))?></a> ]</td>
                <td><pre><?php echo htmlspecialchars((string)( trim(current($call['source'])) ))?></pre></td>
            </tr>
            <?php endforeach?>
        <?php endforeach?>
    </table>
</div><?php if($__extends){ $_TPL->_renderContent(); } ?>