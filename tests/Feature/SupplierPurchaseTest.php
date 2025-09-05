<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierPurchaseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $owner;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'owner']);

        // Create owner user
        $this->owner = Admin::factory()->create();
        $this->owner->assignRole('owner');

        // Create supplier
        $this->supplier = Supplier::factory()->create();

        // Create product
        $this->product = Product::factory()->create();

        // Create stock for product
        Stock::create([
            'product_id' => $this->product->id,
            'quantity' => 0,
        ]);
    }

    /** @test */
    public function owner_can_view_supplier_purchase_index()
    {
        $response = $this->actingAs($this->owner)->get(route('owner.supplier_purchase.index'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.supplier_purchase.page.index');
    }

    /** @test */
    public function owner_can_view_create_supplier_purchase_page()
    {
        $response = $this->actingAs($this->owner)->get(route('owner.supplier_purchase.create'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.supplier_purchase.page.create');
        $response->assertViewHas('suppliers');
        $response->assertViewHas('products');
    }

    /** @test */
    public function owner_can_create_supplier_purchase()
    {
        $purchaseData = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-SUP-TEST-001',
            'notes' => 'Test purchase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'price' => 5000,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->postJson(route('owner.supplier_purchase.store'), $purchaseData);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Check if supplier purchase was created
        $this->assertDatabaseHas('supplier_purchases', [
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-SUP-TEST-001',
        ]);

        // Check if supplier purchase item was created
        $this->assertDatabaseHas('supplier_purchase_items', [
            'product_id' => $this->product->id,
            'quantity' => 10,
            'product_unit_price' => 5000,
        ]);

        // Check if stock was updated
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function owner_can_view_supplier_purchase_detail()
    {
        $supplierPurchase = SupplierPurchase::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        SupplierPurchaseItem::factory()->create([
            'supplier_purchase_id' => $supplierPurchase->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.supplier_purchase.show', $supplierPurchase->id));

        $response->assertStatus(200);
        $response->assertViewIs('owner.supplier_purchase.page.show');
        $response->assertViewHas('supplierPurchase');
    }

    /** @test */
    public function owner_can_view_edit_supplier_purchase_page()
    {
        $supplierPurchase = SupplierPurchase::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.supplier_purchase.edit', $supplierPurchase->id));

        $response->assertStatus(200);
        $response->assertViewIs('owner.supplier_purchase.page.edit');
        $response->assertViewHas('supplierPurchase');
        $response->assertViewHas('suppliers');
        $response->assertViewHas('products');
    }

    /** @test */
    public function owner_can_update_supplier_purchase()
    {
        $supplierPurchase = SupplierPurchase::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        SupplierPurchaseItem::factory()->create([
            'supplier_purchase_id' => $supplierPurchase->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'product_unit_price' => 4000,
        ]);

        // Update stock
        Stock::where('product_id', $this->product->id)->update(['quantity' => 5]);

        $updateData = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-SUP-TEST-002',
            'notes' => 'Updated test purchase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 15,
                    'price' => 6000,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->putJson(route('owner.supplier_purchase.update', $supplierPurchase->id), $updateData);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Check if supplier purchase was updated
        $this->assertDatabaseHas('supplier_purchases', [
            'id' => $supplierPurchase->id,
            'invoice_number' => 'INV-SUP-TEST-002',
            'notes' => 'Updated test purchase',
        ]);

        // Check if stock was updated correctly (old stock returned, new stock added)
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'quantity' => 15,
        ]);
    }

    /** @test */
    public function owner_can_delete_supplier_purchase()
    {
        $supplierPurchase = SupplierPurchase::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        SupplierPurchaseItem::factory()->create([
            'supplier_purchase_id' => $supplierPurchase->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'product_unit_price' => 5000,
        ]);

        // Update stock
        Stock::where('product_id', $this->product->id)->update(['quantity' => 10]);

        $response = $this->actingAs($this->owner)->deleteJson(route('owner.supplier_purchase.destroy', $supplierPurchase->id));

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Check if supplier purchase was deleted
        $this->assertDatabaseMissing('supplier_purchases', [
            'id' => $supplierPurchase->id,
        ]);

        // Check if stock was returned
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'quantity' => 0,
        ]);
    }

    /** @test */
    public function owner_can_get_supplier_purchase_data()
    {
        $response = $this->actingAs($this->owner)->getJson(route('owner.supplier_purchase.getAll'));

        $response->assertStatus(200);
    }

    /** @test */
    public function owner_can_get_products_data()
    {
        $response = $this->actingAs($this->owner)->getJson(route('owner.supplier_purchase.getProducts'));

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    /** @test */
    public function validation_fails_with_invalid_data()
    {
        $invalidData = [
            'supplier_id' => '',
            'purchase_date' => '',
            'invoice_number' => '',
            'items' => [],
        ];

        $response = $this->actingAs($this->owner)->postJson(route('owner.supplier_purchase.store'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supplier_id', 'purchase_date', 'invoice_number', 'items']);
    }

    /** @test */
    public function invoice_number_must_be_unique()
    {
        // Create first purchase
        $firstPurchase = SupplierPurchase::factory()->create([
            'invoice_number' => 'INV-SUP-TEST-001',
        ]);

        // Try to create second purchase with same invoice number
        $purchaseData = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-SUP-TEST-001',
            'notes' => 'Test purchase',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'price' => 5000,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->postJson(route('owner.supplier_purchase.store'), $purchaseData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice_number']);
    }

    /** @test */
    public function non_owner_cannot_access_supplier_purchase()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get(route('owner.supplier_purchase.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_supplier_purchase()
    {
        $response = $this->get(route('owner.supplier_purchase.index'));

        $response->assertRedirect(route('login'));
    }
}
