<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResponsiveNavigationTest extends TestCase
{
    public function test_menu_mobile_fechado_recolhe_tambem_o_espacamento_vertical(): void
    {
        $css = file_get_contents(__DIR__ . '/../static/style-06-footer-responsive.css');
        self::assertNotFalse($css);

        preg_match(
            '/@media \(max-width: 768px\) \{\s*\.nav-links\s*\{(?<closed>.*?)\}'
                . '\s*\.nav-links\.open\s*\{(?<open>.*?)\}/s',
            $css,
            $rules
        );

        self::assertArrayHasKey('closed', $rules);
        self::assertArrayHasKey('open', $rules);
        self::assertMatchesRegularExpression('/padding:\s*0\s+20px\s*;/', $rules['closed']);
        self::assertMatchesRegularExpression('/padding:\s*20px\s*;/', $rules['open']);
    }
}
