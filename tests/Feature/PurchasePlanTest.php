<?php

namespace Tests\Feature;

use App\Models\PurchasePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PurchasePlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function blankRichTextValues(): array
    {
        return [
            'empty string' => [''],
            'single empty paragraph' => ['<p></p>'],
            'paragraph with line break' => ['<p><br></p>'],
            'paragraph with whitespace' => ['<p>   </p>'],
            'paragraph with non-breaking spaces' => ['<p>&nbsp;&nbsp;</p>'],
            'several empty paragraphs' => ['<p></p><p></p>'],
        ];
    }

    #[DataProvider('blankRichTextValues')]
    public function test_a_description_without_visible_content_is_stored_as_null(string $blankDescription): void
    {
        $plan = PurchasePlan::factory()->create([
            'description' => $blankDescription,
            'purpose' => $blankDescription,
        ]);

        $this->assertNull($plan->fresh()->description);
        $this->assertNull($plan->fresh()->purpose);
    }

    public function test_a_description_with_content_is_kept_as_is(): void
    {
        $description = '<p>Pemasangan CCTV di <strong>Balikpapan</strong></p>';

        $plan = PurchasePlan::factory()->create(['description' => $description]);

        $this->assertSame($description, $plan->fresh()->description);
    }

    public function test_content_that_only_renders_media_is_kept_as_is(): void
    {
        $description = '<p></p><img src="https://example.com/denah.png" alt="Denah"><p></p>';

        $plan = PurchasePlan::factory()->create(['description' => $description]);

        $this->assertSame($description, $plan->fresh()->description);
    }
}
