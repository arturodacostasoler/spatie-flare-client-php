<?php

namespace Spatie\FlareClient\Tests\Glows;

use Spatie\FlareClient\Glows\Glow;
use Spatie\FlareClient\Glows\GlowRecorder;
use Spatie\FlareClient\Tests\TestCase;

class RecorderTest extends TestCase
{
    /** @test */
    public function it_is_initially_empty()
    {
        $recorder = new GlowRecorder();

        $this->assertCount(0, $recorder->glows());
    }

    /** @test */
    public function it_stores_glows()
    {
        $recorder = new GlowRecorder();

        $glow = new Glow('Some name', 'info', [
            'some' => 'metadata',
        ]);

        $recorder->record($glow);

        $this->assertCount(1, $recorder->glows());

        $this->assertSame($glow, $recorder->glows()[0]);
    }

    /** @test */
    public function it_does_not_store_more_than_the_max_defined_number_of_glows()
    {
        $recorder = new GlowRecorder();

        $crumb1 = new Glow('One');
        $crumb2 = new Glow('Two');

        foreach (range(1, 40) as $i) {
            $recorder->record($crumb1);
        }

        $recorder->record($crumb2);
        $recorder->record($crumb1);
        $recorder->record($crumb2);

        $this->assertCount(GlowRecorder::GLOW_LIMIT, $recorder->glows());

        $this->assertSame([
            $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1,
            $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1,
            $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb1, $crumb2, $crumb1, $crumb2,
        ], $recorder->glows());
    }
}