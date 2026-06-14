<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Model3D extends Model
{
    protected $table = 'model3ds';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'age_category',
        'gender_category',
        'model_path',
        'model_format',
        'is_published',
        'thumbnail_path',
        'processing_status',
        'source_project_id',
        'source_type',
    ];

    public function owner(): BelongsTo
    {
        // pemilik model (kalau custom scan)
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ModelPart::class, 'model3d_id');
    }

    public function customizations(): HasMany
    {
        return $this->hasMany(ModelCustomization::class, 'model3d_id');
    }

    public function sourceProject(): BelongsTo
    {
        return $this->belongsTo(GlbTextureEnhancementProject::class, 'source_project_id');
    }
}
