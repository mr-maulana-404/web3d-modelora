<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomizationTexture extends Model
{
    protected $fillable = [
        'model_customization_id',
        'model_part_id',
        'texture_type',
        'texture_path',
        'color_value',
    ];

    public function customization(): BelongsTo
    {
        return $this->belongsTo(ModelCustomization::class, 'model_customization_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(ModelPart::class, 'model_part_id');
    }
}
