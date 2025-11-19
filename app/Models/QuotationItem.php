<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $table = 'quotation_items';

    protected $fillable = [
        'quotation_id',
        'requisition_item_id',

        'item_name',
        'description',
        'quantity',
        'uom_id',

        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    # ------------------------------------
    # Relationships
    # ------------------------------------

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function requisitionItem()
    {
        return $this->belongsTo(RequisitionItem::class, 'requisition_item_id');
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }

    # ------------------------------------
    # Business Logic
    # ------------------------------------

    /**
     * Automatically calculate total price using qty * unit_price.
     */
    public function computeTotal()
    {
        $this->total_price = $this->quantity * $this->unit_price;
        return $this;
    }

    /**
     * Initializes item fields using a requisition item
     */
    public static function fromRequisitionItem($requisitionItem)
    {
        return new static([
            'requisition_item_id' => $requisitionItem->id,
            'item_name' => $requisitionItem->description,
            'description' => $requisitionItem->description,
            'quantity' => $requisitionItem->quantity,
            'uom_id' => $requisitionItem->uom_id,
        ]);
    }
}
