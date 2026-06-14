<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelCustomization extends Model
{
    protected $fillable = [
        'user_id',
        'model3d_id',
        'name',
        'thumbnail_path',
        'last_opened_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model3d(): BelongsTo
    {
        return $this->belongsTo(Model3D::class, 'model3d_id');
    }

    public function textures(): HasMany
    {
        return $this->hasMany(CustomizationTexture::class, 'model_customization_id');
    }
}
