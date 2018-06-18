<?php

namespace PhalconExt\Test\View;

use PhalconExt\View\Twig;
use PhalconExt\Test\WebTestCase;

class TwigTest extends WebTestCase
{
    public function test_render_block()
    {
        $block = $this->di('twig')->renderBlock('render.block', 'a', []);

        $this->assertNotContains('It comes from outside', $block);
        $this->assertContains('It comes from block A', $block);
    }
}
