<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelPart extends Model
{
    protected $fillable = [
        'model3d_id',
        'part_name',
        'mesh_name',
    ];

    public function model3d(): BelongsTo
    {
        return $this->belongsTo(Model3D::class, 'model3d_id');
    }

    public function customizationTextures(): HasMany
    {
        return $this->hasMany(CustomizationTexture::class, 'model_part_id');
    }
}
