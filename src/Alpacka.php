<?php

namespace modmore\Alpacka;

use Pimple\Container;

class Alpacka
{
    public $services;
    public $modx;
    public $chunks = [];
    public $config = [];
    /** @var \modContext */
    public $wctx;
    /** @var \modResource */
    public $resource;

    /**
     * @param \modX $modx
     * @param array $config
     */
    public function __construct(\modX &$modx, array $config = array())
    {
        $this->modx = $modx;
        $this->services = new Container();
        $this->registerServices();
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
        $translit = $this->getOption('contentblocks.translit', null,
            $this->getOption('friendly_alias_translit', null, 'none'), true);
        $translitClass = $this->getOption('contentblocks.translit_class', null,
            $this->getOption('friendly_alias_translit_class', null, 'translit.modTransliterate'), true);
        $translitClassPath = $this->getOption('contentblocks.translit_class_path', null,
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
                    if ($this->modx->getService('translit', $translitClass, $translitClassPath)) {
                        $name = $this->modx->translit->translate($name, $translit);
                    }
                }
                break;
        }

        $replace = $this->getOption('contentblocks.sanitize_replace', null, '_');
        $pattern = $this->getOption('contentblocks.sanitize_pattern', null, '/([[:alnum:]_\.-]*)/');
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
        $path = str_replace('[[+resource]]', ($this->resource) ? $this->resource->get('id') : 0, $path);
        $path = str_replace('[[++assets_url]]', $this->getOption('assets_url', null, 'assets/'), $path);
        $path = str_replace('[[++site_url]]', $this->getOption('site_url', null, ''), $path);
        $path = str_replace('[[++base_url]]', $this->getOption('base_url', null, ''), $path);

        if ($this->resource) {
            $parent = $this->resource->getOne('Parent');
            if ($parent instanceof \modResource) {
                $path = str_replace('[[+parent_alias]]', $parent->get('alias'), $path);

                // Grab the ultimate parent as well, to set some of those placeholders too
                $pids = $this->modx->getParentIds($this->resource->get('id'),
                    array('context' => $this->resource->get('context_key')));
                $ultimateParent = $this->modx->getObject('modResource', $pids[count($pids) - 2]);
                if ($ultimateParent instanceof \modResource) {
                    $path = str_replace('[[+ultimate_parent]]', $ultimateParent->get('id'), $path);
                    $path = str_replace('[[+ultimate_alias]]', $ultimateParent->get('alias'), $path);
                    $path = str_replace('[[+ultimate_parent_alias]]', $ultimateParent->get('alias'), $path);
                }
            }

            // Match all placeholders in the string so we can replace it with the proper values.
            if (preg_match_all('/\[\[\+(.*?)\]\]/', $path, $matches) && !empty($matches[1])) {
                //$this->modx->log(modX::LOG_LEVEL_ERROR, 'Matches in '.$path.': ' . print_r($matches, true));
                foreach ($matches[1] as $key) {
                    $ph = '[[+' . $key . ']]';
                    if (substr($key, 0, 3) == 'tv.') {
                        $tvName = substr($key, 3);
                        $tvValue = $this->resource->getTVValue($tvName);
                        $path = str_replace($ph, $tvValue, $path);
                    } elseif (array_key_exists($key, $this->resource->_fieldMeta)) {
                        $path = str_replace($ph, $this->resource->get($key), $path);
                    } else {
                        $this->modx->log(\modX::LOG_LEVEL_WARN, "Unknown placeholder '{$key}' in redactor path {$path}",
                            '', __METHOD__, __FILE__, __LINE__);
                    }
                }
            }
        }
        $path = str_replace('//', '/', $path);

        return $path;
    }

}