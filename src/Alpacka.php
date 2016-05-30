<?php

namespace modmore\Alpacka;

// Load functions that may be missing in certain environments
require_once dirname(__FILE__) . '/functions.php';

use Pimple\Container;

class Alpacka
{
    /**
     * The namespace for this service class. This is used for a bunch of stuff, including loading system settings
     * that belong to the namespace, setting the paths in the config array and loading the xPDO package and lexicon.
     *
     * @var string
     */
    protected $namespace = 'alpacka';
    /**
     * Set to trueto automatically load the xPDO package with the name of $this->namespace.
     *
     * @var bool
     */
    protected $loadPackage = true;
    /**
     * Set to true to automatically load the `default` lexicon topic in the $this->namespace namespace.
     *
     * @var bool
     */
    protected $loadLexicon = true;

    /**
     * The working context for this request. This is set by calling $this->setResource or $this->setWorkingContext
     * and needs to be an initialised context. Used for getting context-specific settings for example.
     *
     * @var \modContext
     */
    public $wctx;

    /**
     * The resource for this request. Make sure to call $this->setResource somewhere you have access to the resource
     * (in a plugin, or snippet) to fill this properly. The resource object is used in path placeholders among other
     * things.
     *
     * @var \modResource
     */
    public $resource;

    /**
     * The current version. This will contain a Version object after calling $this->setVersion in the constructor.
     *
     * @var Version
     */
    public $version;

    /**
     * An instance of the modX class.
     *
     * @todo Before 1.0, change this to an Adapter as per Commerce
     *
     * @var \modX
     */
    public $modx;

    /**
     * A dependency injection container (Pimple). In $this->registerServices a set of standard dependencies are
     * registered, but you can add your own as well.
     *
     * @var Container
     */
    public $services;

    /**
     * An array of Chunk objects, used to cache chunk information for $this->getChunk().
     *
     * @var array
     */
    public $chunks = array();

    /**
     * An array of configuration options. This contains snake_case settings like core_path, templates_path,
     * controllers_path, model_path, processors_path, elements_path, assets_url, connector_url and other system
     * settings that follow the "namespace.setting_key" format
     *
     * @var array
     */
    public $config = array();

    /**
     * An array of additional pathVariables (key => value) that $this->parsePathVariables will replace.
     *
     * @var array
     */
    public $pathVariables = array();

    /**
     * The main constructor for Alpacka. This doesn't hardcode the instance to the modX class as that might change in
     * the future, and we don't want to manually update all derivative service classes when that happens.
     *
     * Your derivative constructor should look something like this:
     *
     * ```` php
     * public function __construct($instance, array $config = array())
     * {
     *      parent::__construct($instance, $config);
     *      $this->setVersion(1, 3, 0, 'dev1');
     * }
     * ````
     *
     * Of course you can add additional logic in there as you please.
     *
     * @param \modX $instance
     * @param array $config
     */
    public function __construct($instance, array $config = array())
    {
        $this->modx = $instance;
        $this->services = new Container();
        $this->registerServices();
        $this->config = array_merge($this->loadSettingsFromNamespace(), $config);

        // Automatically load the xPDO package if specified, as well as the default lexicon topic.
        if ($this->loadPackage && ($this->namespace !== 'alpacka')) {
            $this->modx->addPackage($this->namespace, $this->config['model_path']);
        }
        if ($this->loadLexicon && ($this->namespace !== 'alpacka')) {
            $this->modx->lexicon->load($this->namespace . ':default');
        }
    }

    /**
     * Sets the internal version object. This can be used for cache busting assets and stuff like that. Call this once
     * in the service class constructor.
     *
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @param string $release
     */
    public function setVersion($major, $minor = 0, $patch = 0, $release = 'pl')
    {
        $this->version = new Version($major, $minor, $patch, $release);
    }

    /**
     * Register standard services into the $this->services dependency injection container.
     */
    public function registerServices()
    {
        // @todo
    }

    /**
     * Gets a Chunk and caches it; also falls back to file-based templates
     * for easier development.
     *
     * @author Shaun McCormick
     * @access public
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     */
    public function getChunk($name, $properties = array())
    {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->modx->getObject('modChunk', array('name' => $name));
            if (empty($chunk) || !is_object($chunk)) {
                $chunk = $this->_getTplChunk($name);
                if ($chunk == false) return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        return $chunk->process($properties);
    }

    /**
     * Returns a modChunk object from a template file.
     *
     * @access private
     * @param string $name The name of the Chunk. Will parse to name.chunk.tpl
     * @param string $postFix The postfix to append to the name
     * @return \modChunk|boolean Returns the modChunk object if found, otherwise false.
     * @author Shaun "splittingred" McCormick
     */
    private function _getTplChunk($name, $postFix = '.tpl')
    {
        $chunk = false;
        $f = $this->config['templates_path'] . strtolower($name) . $postFix;
        if (file_exists($f)) {
            $o = file_get_contents($f);
            /* @var \modChunk $chunk */
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name', $name);
            $chunk->setContent($o);
        }

        return $chunk;
    }

    /**
     * Grabs a setting value by its key, looking at the current working context (see setWorkingContext) first.
     *
     * @param $key
     * @param null $options
     * @param null $default
     * @param bool $skipEmpty
     * @return mixed
     */
    public function getOption($key, $options = null, $default = null, $skipEmpty = false)
    {
        if ($this->wctx) {
            $value = $this->wctx->getOption($key, $default, $options);
            if ($skipEmpty && $value == '') {
                return $default;
            } else {
                return $value;
            }
        }

        return $this->modx->getOption($key, $options, $default, $skipEmpty);
    }

    /**
     * Set the internal working context for grabbing context-specific options.
     *
     * @param $key
     * @return bool|\modContext
     */
    public function setWorkingContext($key)
    {
        if ($key instanceof \modResource) {
            $key = $key->get('context_key');
        }
        if (empty($key)) {
            return false;
        }
        $this->wctx = $this->modx->getContext($key);
        if (!$this->wctx) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, 'Error loading working context ' . $key, '', __METHOD__, __FILE__,
                __LINE__);

            return false;
        }

        $this->loadContextSettingsFromNamespace($key);

        return $this->wctx;
    }


    /**
     * Sanitises and transliterates a value for use as paths.
     *
     * @param $value
     * @return string
     */
    public function sanitize($value)
    {
        $iconv = function_exists('iconv');
        $charset = strtoupper((string)$this->getOption('modx_charset', null, 'UTF-8'));
        $translit = $this->getOption($this->namespace . '.translit', null,
            $this->getOption('friendly_alias_translit', null, 'none'), true);
        $translitClass = $this->getOption($this->namespace . '.translit_class', null,
            $this->getOption('friendly_alias_translit_class', null, 'translit.modTransliterate'), true);
        $translitClassPath = $this->getOption($this->namespace . '.translit_class_path', null,
            $this->getOption('friendly_alias_translit_class_path', null,
                $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/'), true);
        switch ($translit) {
            case '':
            case 'none':
                // no transliteration
                break;

            case 'iconv':
                // if iconv is available, use the built-in transliteration it provides
                if ($iconv) {
                    $value = iconv($charset, 'ASCII//TRANSLIT//IGNORE', $value);
                }
                break;

            default:
                // otherwise look for a transliteration service class (i.e. Translit package) that will accept named transliteration tables
                if ($this->modx instanceof \modX) {
                    if ($transliterate = $this->modx->getService('translit', $translitClass, $translitClassPath)) {
                        $value = $transliterate->translate($value, $translit);
                    }
                }
                break;
        }

        $replace = $this->getOption($this->namespace . '.sanitize_replace', null, '_');
        $pattern = $this->getOption($this->namespace . '.sanitize_pattern', null, '/([[:alnum:]_\.-]*)/');
        $value = str_replace(str_split(preg_replace($pattern, $replace, $value)), $replace, $value);
        $value = preg_replace('/[\/_|+ -]+/', $replace, $value);
        $value = trim(trim($value, $replace));

        return $value;
    }


    /**
     * Sets the current resource to an internal variable, and also updates the working context.
     *
     * @param \modResource $resource
     */
    public function setResource(\modResource $resource)
    {
        $this->resource = $resource;
        $this->setWorkingContext($resource->get('context_key'));

        // Make sure the resource is also added to $modx->resource if there's nothing set there
        // This provides compatibility for dynamic media source paths using snippets relying on $modx->resource
        if (!$this->modx->resource) {
            $this->modx->resource =& $resource;
            $this->modx->resourceIdentifier = $resource->get('id');
        }

        if ($this->getBooleanOption($this->namespace . '.parse_parent_path', null,
                true) && $parent = $resource->getOne('Parent')
        ) {
            $this->setPathVariables(array(
                'parent_alias' => $parent->get('alias'),
            ));
            $pids = $this->modx->getParentIds($resource->get('id'),
                (int)$this->getOption($this->namespace . '.parse_parent_path_height', null, 10),
                array('context' => $resource->get('context_key')));
            $pidx = count($pids) - 2;
            if ($pidx >= 0 && $ultimateParent = $this->modx->getObject('modResource', $pids[$pidx])) {
                $this->setPathVariables(array(
                    'ultimate_parent' => $ultimateParent->get('id'),
                    'ultimate_parent_alias' => $ultimateParent->get('alias'),
                    'ultimate_alias' => $ultimateParent->get('alias'),
                ));
            } else {
                $this->setPathVariables(array(
                    'ultimate_parent' => '',
                    'ultimate_parent_alias' => '',
                    'ultimate_alias' => ''
                ));
            }
        } else {
            $this->setPathVariables(array(
                'parent_alias' => '',
                'ultimate_parent' => '',
                'ultimate_parent_alias' => '',
                'ultimate_alias' => ''
            ));
        }
    }


    /**
     * Parses a path by replacing placeholders with dynamic values. This supports the following placeholders:
     * - [[+year]]
     * - [[+month]]
     * - [[+date]]
     * - [[+day]]
     * - [[+user]]
     * - [[+username]]
     * - [[++assets_url]]
     * - [[++site_url]]
     * - [[++base_url]]
     * - [[+<any resource field>]]
     * - [[+tv.<any template variable name>]]
     *
     * In $this->setResource, support is also added for the following through $this->setPathVariables:
     * - [[+parent_alias]]
     * - [[+ultimate_parent]]
     * - [[+ultimate_parent_alias]]
     *
     * @param $path
     * @return mixed
     */
    public function parsePathVariables($path)
    {
        $path = str_replace('[[+year]]', date('Y'), $path);
        $path = str_replace('[[+month]]', date('m'), $path);
        $path = str_replace('[[+date]]', date('d'), $path);
        $path = str_replace('[[+day]]', date('d'), $path);
        $path = str_replace('[[+user]]', $this->modx->getUser()->get('id'), $path);
        $path = str_replace('[[+username]]', $this->modx->getUser()->get('username'), $path);
        $path = str_replace('[[++assets_url]]', $this->getOption('assets_url', null, 'assets/'), $path);
        $path = str_replace('[[++site_url]]', $this->getOption('site_url', null, ''), $path);
        $path = str_replace('[[++base_url]]', $this->getOption('base_url', null, ''), $path);

        foreach ($this->pathVariables as $key => $value) {
            $path = str_replace('[[+'.$key.']]', $value, $path);
        }

        if ($this->resource) {
            $path = str_replace('[[+resource]]', $this->resource->get('id'), $path);
            // Match all placeholders in the string so we can replace it with the proper values.
            if (preg_match_all('/\[\[\+(.*?)\]\]/', $path, $matches) && !empty($matches[1])) {
                foreach ($matches[1] as $key) {
                    $ph = '[[+'.$key.']]';
                    if (substr($key, 0, 3) == 'tv.') {
                        $tvName = substr($key, 3);
                        $tvValue = $this->resource->getTVValue($tvName);
                        $path = str_replace($ph, $tvValue, $path);
                    }
                    elseif (array_key_exists($key, $this->resource->_fieldMeta)) {
                        $path = str_replace($ph, $this->resource->get($key), $path);
                    }
                    else {
                        $this->modx->log(\modX::LOG_LEVEL_WARN, "Unknown placeholder '{$key}' in redactor path {$path}", '', __METHOD__, __FILE__, __LINE__);
                    }
                }
            }
        }

        /**
         * Prevent changing double slashes in a protocol (e.g. http://) to a single slash, while cleaning up other
         * duplicate slashes in the path.
         */
        $path = str_replace('://', '__:_/_/__', $path);
        $path = str_replace('//', '/', $path);
        $path = str_replace('__:_/_/__', '://', $path);

        return $path;
    }

    /**
     * Sets path variables which are processed in the upload/browse paths.
     *
     * @param array $array
     */
    public function setPathVariables(array $array = array())
    {
        $this->pathVariables = array_merge($this->pathVariables, $array);
    }

    /**
     * Runs a snippet identified by its class name, passing along the provided properties.
     *
     * @param string $class
     * @param array $properties
     * @return string
     */
    public function runSnippet($class, array $properties = array(), $strict = false)
    {
        if (!class_exists($class) && file_exists($this->config['elements_path'] . 'snippets/' . $class . '.php')) {
            include_once $this->config['elements_path'] . 'snippets/' . $class . '.php';
        }

        if (class_exists($class)) {
            /** @var Snippet $snippet */
            $snippet = new $class($this, $strict);
            return $snippet->run($properties);
        }
        return 'Could not load ' . $class;
    }

    /**
     * Loads all system settings that start with the configured namespace.
     *
     * @return array
     */
    public function loadSettingsFromNamespace()
    {
        $ns = $this->namespace;
        $config = array();

        $corePath = $this->modx->getOption($ns . '.core_path', null, MODX_CORE_PATH . 'components/' . $ns . '/');
        $config['core_path'] = $corePath;
        $config['templates_path'] = $corePath . 'templates/';
        $config['controllers_path'] = $corePath . 'controllers/';
        $config['model_path'] = $corePath . 'model/';
        $config['processors_path'] = $corePath . 'processors/';
        $config['elements_path'] = $corePath . 'elements/';

        $assetsUrl = $this->modx->getOption($ns . '.assets_url', null, MODX_ASSETS_URL . 'components/' . $ns . '/');
        $config['assets_url'] = $assetsUrl;
        $config['connector_url'] = $assetsUrl . 'connector.php';

        $c = $this->modx->newQuery('modSystemSetting');
        $c->where(array(
            'key:LIKE' => $ns . '.%'
        ));
        $c->limit(0);

        /** @var \modSystemSetting[] $iterator */
        $iterator = $this->modx->getIterator('modSystemSetting', $c);
        foreach ($iterator as $setting) {
            $key = $setting->get('key');
            $key = substr($key, strlen($ns) + 1);
            $config[$key] = $setting->get('value');
        }

        return $config;
    }

    /**
     * Grabs context specific settings from the current namespace, and loads them into $this->config.
     * Also returns the newly overridden values in an array.
     *
     * @param $contextKey
     * @return array
     */
    public function loadContextSettingsFromNamespace($contextKey)
    {
        $config = array();

        $c = $this->modx->newQuery('modContextSetting');
        $c->where(array(
            'context_key' => $contextKey,
            'key:LIKE' => $this->namespace . '.%'
        ));
        $c->limit(0);

        /** @var \modSystemSetting[] $iterator */
        $iterator = $this->modx->getIterator('modContextSetting', $c);
        foreach ($iterator as $setting) {
            $key = $setting->get('key');
            $key = substr($key, strlen($this->namespace) + 1);
            $config[$key] = $setting->get('value');
        }
        $this->config = array_merge($this->config, $config);
        return $config;
    }

    /**
     * Utility method to explodes a string into an array based on the $separator, trimming each item in the array
     * as well.
     *
     * @param string $string The string to split up.
     * @param string $separator The separator between items. Defaults to a comma.
     *
     * @return array
     */
    public function explode($string, $separator = ',') {
        if ($string === false) return $string;
        $array = explode($separator, $string);
        return array_map('trim', $array);
    }

    /**
     * Gets a context-aware setting through $this->getOption, and casts the value to a true boolean automatically,
     * including strings "false" and "no" which are sometimes set that way by ExtJS.
     *
     * @param string $name
     * @param array $options
     * @param bool $default
     * @return bool
     */
    public function getBooleanOption($name, array $options = null, $default = null) {
        $option = $this->getOption($name, $options, $default);
        return $this->castValueToBool($option);
    }

    /**
     * Turns a value into a boolean. This checks for "false" and "no" strings, as well as anything PHP can automatically
     * cast to a boolean value.
     *
     * @param $value
     * @return bool
     */
    public function castValueToBool($value)
    {
        if (in_array(strtolower($value), array('false', 'no'))) {
            return false;
        }
        return (bool)$value;
    }

    /**
     * @param int $resource
     * @param string $context
     * @return mixed
     */
    public function getUltimateParent($resource = 0, $context = '')
    {
        $parents = $this->modx->getParentIds($resource, 10, array('context' => $context));
        $parents = array_reverse($parents);
        return isset($parents[1]) ? $parents[1] : 0;
    }
}