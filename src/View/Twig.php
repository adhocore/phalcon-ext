<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\View;

use Phalcon\Mvc\View\Engine;

/**
 * Twig engine for Phalcon.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Twig extends Engine
{
    /** @var \Twig_Environment */
    protected $twig;

    /** @var array */
    protected $viewsDirs;

    /**
     * Renders a view using the twig template engine.
     *
     * @param string $path      View path
     * @param array  $params    View params
     * @param bool   $mustClean [<internal>]
     *
     * @return string Rendered content
     */
    public function render($path, $params = [], $mustClean = false)
    {
        $this->initTwig();

        $content = $this->twig->render($this->normalizePath($path), empty($params) ? [] : (array) $params);

        $this->_view->setContent($content);

        return $content;
    }

    /**
     * Renders a view block using the twig template engine.
     *
     * @param string $path   View path
     * @param string $block  Block name
     * @param array  $params View params
     *
     * @return string Rendered block content
     */
    public function renderBlock(string $path, string $block, array $params = []): string
    {
        $this->initTwig();

        return $this->twig->loadTemplate($path . '.twig')->renderBlock($block, $params) ?: '';
    }

    /**
     * Adapt path to be twig friendly.
     *
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        foreach ($this->viewsDirs as $dir => $len) {
            if (\strpos($path, $dir) === 0) {
                return \substr($path, $len);
            }
        }

        return $path;
    }

    /**
     * Initialize twig once and for all.
     *
     * @return void
     */
    protected function initTwig()
    {
        if ($this->twig) {
            return;
        }

        $config = $this->getDI()->get('config')->toArray()['twig'];

        $this->viewsDirs = \array_combine(
            $config['view_dirs'],
            \array_map('strlen', $config['view_dirs'])
        );

        $this->twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(\array_keys($this->viewsDirs)),
            $config
        );
    }

    /**
     * Delegate calls to twig.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call(string $method, array $args = [])
    {
        $this->initTwig();

        return $this->twig->$method(...$args);
    }
}
