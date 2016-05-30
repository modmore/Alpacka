<?php
/**
 * @var modX $modx
 */

$tstart = explode(' ', microtime());
$tstart = $tstart[1] + $tstart[0];
set_time_limit(0);

if (!defined('MOREPROVIDER_BUILD')) {
    /* define version */
    define('PKG_NAME','Alpacka');
    define('PKG_NAME_LOWER',strtolower(PKG_NAME));
    define('PKG_VERSION','0.3.0');
    define('PKG_RELEASE','pl');

    /* load modx */
    require_once dirname(dirname(__FILE__)) . '/config.core.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
    $modx= new modX();
    $modx->initialize('mgr');
    $modx->setLogLevel(modX::LOG_LEVEL_INFO);
    $modx->setLogTarget('ECHO');
    $targetDirectory = dirname(dirname(__FILE__)) . '/_packages/';
}
else {
    $targetDirectory = MOREPROVIDER_BUILD_TARGET;
}
/* define build paths */
$root = dirname(dirname(__FILE__)).'/';
$sources = array(
    'root' => $root,
    'build' => $root.'_build/',
    'data' => $root.'_build/data/',
    'validators' => $root.'_build/validators/',
    'resolvers' => $root.'_build/resolvers/',
    'chunks' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/chunks/',
    'snippets' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/snippets/',
    'plugins' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/plugins/',
    'lexicon' => $root.'core/components/'.PKG_NAME_LOWER.'/lexicon/',
    'docs' => $root.'core/components/'.PKG_NAME_LOWER.'/docs/',
    'elements' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/',
    'source_assets' => $root.'assets/components/'.PKG_NAME_LOWER.'/',
    'source_core' => $root.'core/components/'.PKG_NAME_LOWER.'/',
);
unset($root);

$modx->loadClass('transport.xPDOTransport', XPDO_CORE_PATH, true, true);
/** @var xPDOTransport $package */
$package = new xPDOTransport($modx, PKG_NAME_LOWER, $targetDirectory);
$package->signature = PKG_NAME_LOWER . '-' . PKG_VERSION . '-' . PKG_RELEASE;

$modx->log(xPDO::LOG_LEVEL_INFO, 'Creating transport package for ' . PKG_NAME); flush();

/* include namespace */
$namespace = $modx->newObject('modNamespace');
$namespace->set('name', PKG_NAME_LOWER);
$namespace->set('path', '{core_path}components/' . PKG_NAME_LOWER . '/');
$namespace->set('assets_path', '{assets_path}components/' . PKG_NAME_LOWER . '/');
$attributes = array(
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => true,
);
$package->put($namespace, $attributes);

$modx->log(xPDO::LOG_LEVEL_INFO, 'Added namespace ' . PKG_NAME_LOWER); flush();

/** @var array $attributes */
$attributes = array(
    'vehicle_class' => 'xPDOFileVehicle',
);
$files = array();
$files[] = array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
);
/*$files[] = array(
    'source' => $sources['source_assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
);*/

foreach ($files as $fileset) {
    $package->put($fileset, $attributes);
}
$modx->log(xPDO::LOG_LEVEL_INFO, 'Added ' . count($files) . ' file locations.'); flush();

/* now pack in the license file, readme and setup options */
$attributes = array(
    'readme' => file_get_contents($sources['source_core'] . '/docs/readme.txt'),
    'license' => file_get_contents($sources['source_core'] . '/docs/license.txt'),
    'changelog' => file_get_contents($sources['source_core'] . '/docs/changelog.txt'),
);
foreach ($attributes as $k => $v) {
    $package->setAttribute($k, $v);
}

$modx->log(xPDO::LOG_LEVEL_INFO, 'Added package attributes.'); flush();

/* zip up the package */
$package->pack();

$tend = explode(" ", microtime());
$tend = $tend[1] + $tend[0];
$totalTime = sprintf("%2.4f s", ($tend - $tstart));

$modx->log(xPDO::LOG_LEVEL_INFO, 'Transport package created. Execution time: ' . $totalTime);
