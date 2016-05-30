<?php

require_once dirname(dirname(__FILE__)) . '/config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');
//$path = $modx->getOption('alpacka.core_path', null, $modx->getOption('core_path') . 'components/alpacka/');
//$Alpacka = $modx->getService('alpacka', 'Alpacka', $path . 'model/alpacka/');

require_once dirname(dirname(__FILE__)) . '/core/components/alpacka/vendor/autoload.php';
