<?php

namespace modmore\Alpacka;

use Pimple\Container;

class Alpacka
{
    protected $namespace = 'alpacka';

    public $services;
    public $modx;
    public $chunks = [];
    public $config = [];
    /** @var \modContext */
    public $wctx;
    /** @var \modResource */
    public $resource;
    public $version;
    public $pathVariables;

    /**
     * The main contructor for Alpacka. This doesn't hardcode the instance to the modX class as that might change in
     * the future, and we don't want to manually update all derivative service classes when that happens.
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

    public function registerServices()
    {
        // @todo
    }


    /**
     * Gets a Chunk and caches it; defaults to file based chunks.
     *
     * @access public
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     * @author Shaun "splittingred" McCormick
     */
    public function getChunk($name, $properties = array())
    {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->_getTplChunk($name);
            if (empty($chunk)) {
                $chunk = $this->modx->getObject('modChunk', array('name' => $name), true);
                if ($chunk == false) {
                    return false;
                }
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
        $f = $this->config['templatesPath'] . strtolower($name) . $postFix;
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
     * Grabs the setting by its key, looking at the current working context (see setWorkingContext) first.
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

        return $this->wctx;
    }


    /**
     * @param $name
     * @return string
     */
    public function sanitize($name)
    {
        $iconv = function_exists('iconv');
        $charset = strtoupper((string)$this->getOption('modx_charset', null, 'UTF-8'));
        $translit = $this->getOption('alpacka.translit', null,
            $this->getOption('friendly_alias_translit', null, 'none'), true);
        $translitClass = $this->getOption('alpacka.translit_class', null,
            $this->getOption('friendly_alias_translit_class', null, 'translit.modTransliterate'), true);
        $translitClassPath = $this->getOption('alpacka.translit_class_path', null,
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
                    $name = iconv($charset, 'ASCII//TRANSLIT//IGNORE', $name);
                }
                break;

            default:
                // otherwise look for a transliteration service class (i.e. Translit package) that will accept named transliteration tables
                if ($this->modx instanceof \modX) {
                    if ($translit = $this->modx->getService('translit', $translitClass, $translitClassPath)) {
                        $name = $translit->translate($name, $translit);
                    }
                }
                break;
        }

        $replace = $this->getOption('alpacka.sanitize_replace', null, '_');
        $pattern = $this->getOption('alpacka.sanitize_pattern', null, '/([[:alnum:]_\.-]*)/');
        $name = str_replace(str_split(preg_replace($pattern, $replace, $name)), $replace, $name);
        $name = preg_replace('/[\/_|+ -]+/', $replace, $name);
        $name = trim(trim($name, $replace));

        return $name;
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
        }

        if ($this->getBooleanOption('alpacka.parse_parent_path', null,
                true) && $parent = $resource->getOne('Parent')
        ) {
            $this->setPathVariables(array(
                'parent_alias' => $parent->get('alias'),
            ));
            $pids = $this->modx->getParentIds($resource->get('id'),
                (int)$this->getOption('alpacka.parse_parent_path_height', null, 10),
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

        $path = str_replace('://', '__:_/_/__', $path);
        $path = str_replace('//', '/', $path);
        $path = str_replace('__:_/_/__', '://', $path);

        return $path;
    }

    /**
     * Sets path variables which are processed in the upload/browse paths.
     * @param array $array
     */
    public function setPathVariables(array $array = array())
    {
        $this->pathVariables = array_merge($this->pathVariables, $array);
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
        $config['processors_path'] = $corePath . 'processors/';
        $config['elements_path'] = $corePath . 'elements/';

        $assetsUrl = $this->modx->getOption($ns . '.assets_url', null, MODX_ASSETS_URL . 'components/' . $ns . '/');
        $config['assets_url'] = $assetsUrl;
        $config['connector_url'] = $assetsUrl . ' connector.php';

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

}