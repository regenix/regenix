<?php
/** @var $resuest \framework\mvc\Request */

if ($request->isBase('http://regenix.ru'))
    return 'prod';

return 'dev';
