<?php echo $_TPL->_renderBlock("content", 'framework/test/base'); $__extends = true;?>

<?php if($detail):?>
    <?php $_TPL->_renderTag("include", array('_arg' => 'framework/test/Tester/detail.html', ));?>
<?php else:?>
    <h2>Status: <b><?php echo htmlspecialchars((string)($result['result'] ? 'SUCCESS' : 'FAIL'))?></b></h2>
    <div id="detail">
        <table class="list">
            <tr>
                <th width="25px"></th>
                <th width="140px"><?php echo htmlspecialchars(\framework\libs\I18n::get('Test class'))?></th>
                <th><?php echo htmlspecialchars(\framework\libs\I18n::get('Methods'))?></th>
            </tr>
            <?php foreach($result['tests'] as $code => $test):?>
                <tr>
                    <td>
                        <?php if($test['skip']):?>
                        &#160;...
                        <?php else:?>
                            <span class="ico <?php echo htmlspecialchars((string)($test['result'] ? 'success' : 'fail'))?>">&#160;</span>
                        <?php endif?>
                    </td>
                    <td>
                        <?php if($test['skip']):?>
                            <span style="color: silver"><?php echo $_TPL->_makeObjectVar($code )->substring(6)?></span>
                        <?php else:?>

                            <a href="<?php $_TPL->_renderTag("path", array('id' => $code, ));?>"><?php echo $_TPL->_makeObjectVar($code )->substring(6)?></a>
                        <?php endif?>
                    </td>
                    <td>
                        <?php if($test['skip']):?>
                            <span style="color: silver">SKIP

                            </span>
                        <?php else:?>
                            <?php foreach($test['log'] as $code => $info):?>
                                <?php if(in_array($code, $test['fails'], true)):?>
                                    <span style="color: red">.<?php echo htmlspecialchars((string)($code))?>()</span>
                                <?php else:?>
                                    <span style="color: gray">.<?php echo htmlspecialchars((string)($code))?>()</span>
                                <?php endif?>
                            <?php endforeach?>
                        <?php endif?>
                    </td>
                </tr>
            <?php endforeach?>
        </table>
    </div>
    <h3><?php echo htmlspecialchars(\framework\libs\I18n::get('Project'))?>: <b><?php echo htmlspecialchars((string)($project->getName()))?></b></h3>
<?php endif?><?php if($__extends){ $_TPL->_renderContent(); } ?>