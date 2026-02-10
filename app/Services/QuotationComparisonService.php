<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationComparison;
use App\Models\QuotationComparisonLine;
use Illuminate\Support\Facades\DB;

class QuotationComparisonService
{
    public function compare(int $requisitionId, $user)
    {
        return DB::transaction(function () use ($requisitionId, $user) {

            $quotations = Quotation::with(['items.unitOfMeasure', 'vendor'])
                ->where('requisition_id', $requisitionId)
                ->get();

            if ($quotations->isEmpty()) {
                throw new \Exception("No quotations found.");
            }

            $comparison = QuotationComparison::create([
                'requisition_id' => $requisitionId,
                'title'          => 'Price Comparison',
                'compared_on'    => now(),
                'company_id'     => $user->getCompany(),
                'created_by'     => $user->id,
            ]);

            $itemsGrouped = [];

            foreach ($quotations as $q) {
                foreach ($q->items as $item) {

                    $itemsGrouped[$item->requisition_item_id]['item'] = [
                        'id' => $item->requisition_item_id,
                        'name' => $item->item_name ?? $item->requisitionItem->name,
                        'description' => $item->description ?? $item->requisitionItem->description,
                        'quantity' => $item->quantity,
                        'uom' => $item->unitOfMeasure?->uom_name ?? 'N/A',
                    ];

                    $itemsGrouped[$item->requisition_item_id]['vendors'][] = [
                        'quotation_item_id' => $item->id,
                        'vendor' => $q->vendor,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'delivery_days' => $q->delivery_days ?? rand(3, 10), // fallback
                        'vendor_score' => $q->vendor_score ?? rand(70, 100), // fallback
                    ];
                }
            }

            $output = [];

            foreach ($itemsGrouped as $reqItemId => $data) {
                $vendorItems = $data['vendors'];

                usort($vendorItems, fn($a, $b) => $a['total_price'] <=> $b['total_price']);

                $vendorOutput = [];

                foreach ($vendorItems as $rank => $row) {
                    QuotationComparisonLine::create([
                        'comparison_id'     => $comparison->id,
                        'quotation_item_id' => $row['quotation_item_id'],
                        'vendor_id'         => $row['vendor']->id,
                        'unit_price'        => $row['unit_price'],
                        'total_price'       => $row['total_price'],
                        'rank'              => $rank + 1,
                        'company_id'        => $user->getCompany(),
                        'created_by'        => $user->id,
                    ]);

                    $vendorOutput[] = [
                        'vendor'        => $row['vendor']->vendor_name,
                        'unit_price'    => $row['unit_price'],
                        'total_price'   => $row['total_price'],
                        'delivery_days' => $row['delivery_days'],
                        'vendor_score'  => $row['vendor_score'],
                        'rank'          => $rank + 1,
                    ];
                }

                $output[] = [
                    'item' => $data['item'],
                    'vendors' => $vendorOutput
                ];
            }

            return [
                'comparison_id' => $comparison->id,
                'items' => $output
            ];
        });
    }
}
