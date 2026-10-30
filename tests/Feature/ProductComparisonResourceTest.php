<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductComparisons\Pages\CreateProductComparison;
use App\Filament\Resources\ProductComparisons\Pages\ViewProductComparison;
use App\Models\ProductComparison;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductComparisonResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_specification_name_is_copied_to_the_same_row_for_each_product(): void
    {
        $this->actingAs(User::factory()->create());

        $comparisonForm = Livewire::test(CreateProductComparison::class)
            ->fillForm([
                'products' => [
                    [
                        'product_name' => 'Product A',
                        'specifications' => [
                            ['name' => '', 'value' => '100'],
                            ['name' => 'Old second row', 'value' => 'A'],
                        ],
                    ],
                    [
                        'product_name' => 'Product B',
                        'specifications' => [
                            ['name' => '', 'value' => '150'],
                        ],
                    ],
                ],
            ])
            ->call('enableSchemaStateUpdateHooksForTesting');

        $products = $comparisonForm->get('data.products');
        $productKeys = array_keys($products);
        $firstProductSpecificationKeys = array_keys($products[$productKeys[0]]['specifications']);
        $secondProductSpecificationKeys = array_keys($products[$productKeys[1]]['specifications']);

        $comparisonForm
            ->set("data.products.{$productKeys[0]}.specifications.{$firstProductSpecificationKeys[0]}.name", 'Price')
            ->assertSet("data.products.{$productKeys[1]}.specifications.{$secondProductSpecificationKeys[0]}.name", 'Price')
            ->assertSet("data.products.{$productKeys[1]}.specifications.{$secondProductSpecificationKeys[0]}.value", '150')
            ->set("data.products.{$productKeys[0]}.specifications.{$firstProductSpecificationKeys[1]}.name", 'Warranty');

        $productsAfterSynchronization = $comparisonForm->get('data.products');
        $secondProductSpecifications = $productsAfterSynchronization[$productKeys[1]]['specifications'];
        $newSpecificationKey = array_keys($secondProductSpecifications)[1];

        $this->assertSame('Warranty', $secondProductSpecifications[$newSpecificationKey]['name']);
        $this->assertSame('', $secondProductSpecifications[$newSpecificationKey]['value']);
    }

    public function test_a_product_can_upload_multiple_images_and_documents(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('public');

        $comparisonForm = Livewire::test(CreateProductComparison::class)
            ->assertSee('Product Images and Documents')
            ->fillForm([
                'title' => 'Product Attachments',
                'products' => [
                    [
                        'product_name' => 'Product A',
                        'specifications' => [
                            ['name' => 'Price', 'value' => '100'],
                        ],
                    ],
                    [
                        'product_name' => 'Product B',
                        'specifications' => [
                            ['name' => 'Price', 'value' => '150'],
                        ],
                    ],
                ],
            ]);

        $products = $comparisonForm->get('data.products');
        $productKey = array_key_first($products);

        $comparisonForm
            ->set("data.products.{$productKey}.attachment", [
                UploadedFile::fake()->image('product.png'),
                UploadedFile::fake()->create('specification.pdf', 10, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $comparison = ProductComparison::query()
            ->where('title', 'Product Attachments')
            ->sole();
        $attachments = array_values($comparison->products)[0]['attachment'];

        $this->assertCount(2, $attachments);
        $this->assertModelExists($comparison);
        Storage::disk('public')->assertExists($attachments[0]);
        Storage::disk('public')->assertExists($attachments[1]);
        $this->assertSame(['png', 'pdf'], array_map(
            fn (string $path): string => pathinfo($path, PATHINFO_EXTENSION),
            $attachments,
        ));
    }

    public function test_a_comparison_can_be_created_with_manual_specifications_and_viewed_as_a_matrix(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('product-comparisons/laptop-a.png', 'image');
        Storage::disk('public')->put('product-comparisons/laptop-b.png', 'image');
        Storage::disk('public')->put('product-comparisons/specification.pdf', 'document');
        Storage::disk('local')->put('legacy-laptop.png', 'image');
        Storage::disk('local')->buildTemporaryUrlsUsing(
            fn (string $path): string => '/storage/'.$path,
        );

        Livewire::test(CreateProductComparison::class)
            ->assertOk()
            ->assertSee('Add Product')
            ->assertSee('Add Specification')
            ->assertDontSee('Pictures')
            ->fillForm([
                'title' => 'Laptop Comparison',
                'description' => 'Comparing laptops for the design team.',
                'products' => [
                    [
                        'product_name' => 'Laptop A',
                        'supplier_name' => 'Supplier A',
                        'specifications' => [
                            ['name' => 'RAM', 'value' => '16 GB'],
                            ['name' => 'Storage', 'value' => '512 GB SSD'],
                        ],
                    ],
                    [
                        'product_name' => 'Laptop B',
                        'supplier_name' => 'Supplier B',
                        'specifications' => [
                            ['name' => 'RAM', 'value' => '32 GB'],
                            ['name' => 'Processor', 'value' => 'Core i7'],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $comparison = ProductComparison::query()
            ->where('title', 'Laptop Comparison')
            ->sole();
        $comparison->update([
            'products' => [
                [
                    'product_name' => 'Laptop A',
                    'supplier_name' => 'Supplier A',
                    'specifications' => [
                        ['name' => 'RAM', 'value' => '16 GB'],
                        ['name' => 'Storage', 'value' => '512 GB SSD'],
                    ],
                    'attachment' => [
                        'product-comparisons/laptop-a.png',
                        'product-comparisons/specification.pdf',
                        'legacy-laptop.png',
                    ],
                ],
                [
                    'product_name' => 'Laptop B',
                    'supplier_name' => 'Supplier B',
                    'specifications' => [
                        ['name' => 'RAM', 'value' => '32 GB'],
                        ['name' => 'Processor', 'value' => 'Core i7'],
                    ],
                    'attachment' => ['product-comparisons/laptop-b.png'],
                ],
            ],
        ]);
        $comparison->refresh();

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
            'title' => 'Laptop Comparison',
        ]);
        $this->assertSame([
            ['name' => 'RAM', 'values' => ['16 GB', '32 GB']],
            ['name' => 'Storage', 'values' => ['512 GB SSD', '-']],
            ['name' => 'Processor', 'values' => ['-', 'Core i7']],
        ], $comparison->comparisonMatrix());

        Livewire::test(ViewProductComparison::class, ['record' => $comparison->getRouteKey()])
            ->assertOk()
            ->assertSee('Laptop Comparison')
            ->assertSee('Technical Specification')
            ->assertSee('Laptop A')
            ->assertSee('Supplier B')
            ->assertSee('RAM')
            ->assertSee('16 GB')
            ->assertSee('32 GB')
            ->assertSee('Storage')
            ->assertSee('512 GB SSD')
            ->assertSee('Processor')
            ->assertSee('Core i7')
            ->assertSeeHtml('alt="Laptop A picture"')
            ->assertSeeHtml('alt="Laptop B picture"')
            ->assertSeeHtml('<dialog')
            ->assertSeeHtml('@click="$refs.imagePreview.showModal()"')
            ->assertSeeHtml('@click="$refs.imagePreview.close()"')
            ->assertDontSeeHtml('<a href="'.Storage::disk('public')->url('product-comparisons/laptop-a.png').'"')
            ->assertSeeHtml('src="'.Storage::disk('public')->url('product-comparisons/laptop-a.png').'"')
            ->assertSeeHtml('src="'.Storage::disk('public')->url('product-comparisons/laptop-b.png').'"')
            ->assertSeeHtml('class="h-12 w-12 rounded-lg object-cover"')
            ->assertSeeHtml('href="'.Storage::disk('public')->url('product-comparisons/specification.pdf').'"')
            ->assertSee('specification.pdf')
            ->assertSeeHtml('src="/storage/legacy-laptop.png"');
    }

    public function test_a_comparison_cannot_be_saved_with_only_one_product(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateProductComparison::class)
            ->fillForm([
                'title' => 'Laptop Comparison',
                'products' => [
                    [
                        'product_name' => 'Laptop A',
                        'specifications' => [
                            ['name' => 'RAM', 'value' => '16 GB'],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['products' => 'min']);

        $this->assertDatabaseCount('product_comparisons', 0);
    }
}
