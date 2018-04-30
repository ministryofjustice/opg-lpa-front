<?php

namespace ZfcTwig\Twig;

use Twig\Error;
use Twig\Loader;
use Twig\Source;

class MapLoader implements Loader\ExistsLoaderInterface, Loader\SourceContextLoaderInterface
{
    /**
     * Array of templates to filenames.
     * @var array
     */
    protected $map = [];

    /**
     * Add to the map.
     *
     * @param string $name
     * @param string $path
     * @throws Error\LoaderError
     * @return MapLoader
     */
    public function add($name, $path)
    {
        if ($this->exists($name)) {
            throw new Error\LoaderError(sprintf(
                'Name "%s" already exists in map',
                $name
            ));
        }
        $this->map[$name] = $path;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->map);
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceContext($name)
    {
        if (!$this->exists($name)) {
            throw new Error\LoaderError(sprintf(
                'Unable to find template "%s" from template map',
                $name
            ));
        }
        if(!file_exists($this->map[$name])) {
            throw new Error\LoaderError(sprintf(
                'Unable to open file "%s" from template map',
                $this->map[$name]
            ));
        }
        return new Source(file_get_contents($this->map[$name]), $name, $this->map[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * {@inheritDoc}
     */
    public function isFresh($name, $time)
    {
        return filemtime($this->map[$name]) <= $time;
    }
}
