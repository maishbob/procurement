<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\LowStockDetectedEvent;

class CheckLowStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'procurement:check-low-stock
                            {--notify : Send notifications to managers}
                            {--store= : Filter by store}';

    /**
     * The console command description.
     */
    protected $description = 'Check for inventory items below reorder level';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for low stock items...');

        try {
            // Find items below reorder level using stockLevels
            $query = \App\Models\InventoryItem::whereHas('stockLevels', function($q) {
                $q->whereRaw('stock_levels.quantity_on_hand <= inventory_items.reorder_point');
            });

            if ($this->option('store')) {
                $query->whereHas('stockLevels', function($q) {
                    $q->where('store_id', $this->option('store'));
                });
            }

            $lowStockItems = $query->with(['stockLevels' => function($q) {
                if ($this->option('store')) {
                    $q->where('store_id', $this->option('store'));
                }
            }])->get();

            if ($lowStockItems->isEmpty()) {
                $this->info('✓ All items are adequately stocked');
                return self::SUCCESS;
            }

            $this->warn("Found {$lowStockItems->count()} items below reorder level");

            // Display low stock items
            $tableData = [];
            foreach ($lowStockItems as $item) {
                foreach ($item->stockLevels as $stockLevel) {
                    $tableData[] = [
                        $item->name,
                        $stockLevel->store->name ?? 'N/A',
                        $stockLevel->quantity_on_hand,
                        $item->reorder_point,
                        max(0, $item->maximum_stock_level - $stockLevel->quantity_on_hand),
                    ];
                }
            }

            $this->table(
                ['Item', 'Store', 'Current Qty', 'Reorder Point', 'Suggested Order'],
                $tableData
            );

            // Send notifications if requested
            if ($this->option('notify')) {
                $this->info('Sending notifications...');

                foreach ($lowStockItems as $item) {
                    $totalQty = $item->getTotalStockOnHand();
                    event(new LowStockDetectedEvent(
                        $item,
                        $totalQty,
                        $item->reorder_point ?? 0
                    ));
                }

                $this->info("✓ Notifications sent for {$lowStockItems->count()} items");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to check low stock: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
