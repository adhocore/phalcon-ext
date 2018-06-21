<?php

namespace PhalconExt\Test\View;

use PhalconExt\Test\WebTestCase;
use PhalconExt\View\Twig;

class TwigTest extends WebTestCase
{
    protected $twig;

    public function setUp()
    {
        parent::setUp();

        $this->twig = $this->di('twig');
    }

    public function test_render()
    {
        $content = $this->twig->render('render.block.twig', []);
        $block   = $this->twig->renderBlock('render.block', 'a', []);

        $this->assertContains('It comes from outside', $content);
        $this->assertNotContains('It comes from outside', $block);
        $this->assertContains('It comes from block A', $block);
    }

    public function test_filter()
    {
        $this->twig->addFilter(new \Twig_SimpleFilter('p', function ($x) {
            return "<p>$x</p>";
        }));

        $this->twig->setLoader(new \Twig_Loader_String);

        $string = $this->twig->render("{{ fruit|p|raw }}", ['fruit' => 'Apple']);

        $this->assertSame('<p>Apple</p>', $string);
    }
}
