<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * perPage() clamps the client-controlled ?per_page so a caller cannot force the
 * app to load a whole table in one response. See zerp-pk/zerp#40.
 */
class PerPageTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_per_page_is_clamped(?string $input, int $default, int $max, int $expected): void
    {
        if ($input !== null) {
            request()->merge(['per_page' => $input]);
        }

        $this->assertSame($expected, perPage($default, $max));
    }

    public static function cases(): array
    {
        return [
            'absent falls back to default'   => [null, 10, 100, 10],
            'absent honours custom default'  => [null, 24, 100, 24],
            'huge value clamps to max'       => ['1000000', 10, 100, 100],
            'in-range passes through'        => ['50', 10, 100, 50],
            'zero clamps up to one'          => ['0', 10, 100, 1],
            'negative clamps up to one'      => ['-5', 10, 100, 1],
            'non-numeric falls back'         => ['abc', 10, 100, 10],
            'custom max allows higher'       => ['150', 20, 200, 150],
            'custom max still caps'          => ['250', 20, 200, 200],
        ];
    }
}
