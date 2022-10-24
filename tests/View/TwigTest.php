<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\View;

use PhalconExt\Test\WebTestCase;

class TwigTest extends WebTestCase
{
    protected $twig;

    public function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->di('twig');
    }

    public function test_render()
    {
        $content = $this->twig->render('render.block.twig', []);
        $block   = $this->twig->renderBlock('render.block', 'a', []);

        $this->assertStringContainsString('It comes from outside', $content);
        $this->assertStringNotContainsString('It comes from outside', $block);
        $this->assertStringContainsString('It comes from block A', $block);
    }

    public function test_filter()
    {
        $this->twig->addFilter(new \Twig_SimpleFilter('p', function ($x) {
            return "<p>$x</p>";
        }));

        $this->twig->setLoader(new \Twig_Loader_Array(['test' => '{{ fruit|p|raw }}']));

        $string = $this->twig->render('test', ['fruit' => 'Apple']);

        $this->assertSame('<p>Apple</p>', $string);
    }
}
