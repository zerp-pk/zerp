<?php

namespace Tests\Unit;

use App\Classes\Module;
use PHPUnit\Framework\TestCase;

class ModuleAssetLinkTest extends TestCase
{
    public function test_it_builds_a_relative_target_from_the_links_own_directory(): void
    {
        $this->assertSame(
            '../../../../vendor/zerp/lead/favicon.png',
            Module::relativeSymlinkTarget(
                '/app/vendor/zerp/lead/favicon.png',
                '/app/public/packages/local/Lead/favicon.png',
            ),
        );

        $this->assertSame(
            '../../../../../../vendor/zerp/landing-page/src/Resources/assets',
            Module::relativeSymlinkTarget(
                '/app/vendor/zerp/landing-page/src/Resources/assets',
                '/app/public/packages/local/LandingPage/src/Resources/assets',
            ),
        );
    }

    public function test_the_relative_target_is_stable_when_the_tree_moves(): void
    {
        $here = Module::relativeSymlinkTarget(
            '/home/a/zerp/vendor/zerp/pos/favicon.png',
            '/home/a/zerp/public/packages/local/Pos/favicon.png',
        );
        $moved = Module::relativeSymlinkTarget(
            '/srv/ZERP/zerp/vendor/zerp/pos/favicon.png',
            '/srv/ZERP/zerp/public/packages/local/Pos/favicon.png',
        );

        $this->assertSame($here, $moved);
    }
}
