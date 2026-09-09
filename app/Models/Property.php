<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'user_id',
        'property_category_id',
        'title',
        'description',
        'price',
        'area',
        'address',
        'district',
        'city',
        'bedrooms',
        'bathrooms',
        'image',
        'featured',
        'is_hidden',
        'status',
    ];

    protected $appends = [
        'image_url',
        'image_urls',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'area' => 'decimal:2',
            'featured' => 'boolean',
            'is_hidden' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            PropertyCategory::class,
            'property_category_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            $firstImage = $this->images->first();

            if (!$firstImage) {
                return null;
            }

            return $this->makeImageUrl(
                $firstImage->image
            );
        }

        return $this->makeImageUrl(
            $this->image
        );
    }

    public function getImageUrlsAttribute(): array
    {
        $urls = [];

        if ($this->image) {
            $urls[] = $this->makeImageUrl(
                $this->image
            );
        }

        foreach ($this->images as $image) {
            $url = $this->makeImageUrl(
                $image->image
            );

            if (!in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function makeImageUrl(
        string $image
    ): string {
        if (
            str_starts_with($image, 'http://') ||
            str_starts_with($image, 'https://')
        ) {
            return $image;
        }

        return '/storage/' . $image;
    }
}
