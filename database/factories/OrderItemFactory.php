<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'sku' => 'SKU-' . $this->faker->unique()->numerify('#####'),
            'description' => $this->faker->sentence(),
            'qty' => $this->faker->numberBetween(1, 100),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
} 