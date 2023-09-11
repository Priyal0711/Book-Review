<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function scopeTitle(Builder $query, string $title): Builder
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }
    public function scopePopular(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        
        return $query->withCount([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ]);
    }
    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withAvg([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ], 'rating')
            ->orderBy('reviews_avg_rating', 'desc');
    }
    public function scopeWithRecentReviews(Builder $query, \Closure $interval): Builder
    {
       
        return $query->whereHas(
            'reviews',
            function (Builder $q) use ($interval) {
                $q->whereBetween(
                    'created_at',
                    [$interval(now()), now()]
                );
            }
        );

    private function dateRangeFilter(Builder $query, $from = null, $to = null)
    {
       
        if ($from && !$to) {
            $query->where('created_at', '>=', $from);
        } elseif (!$from && $to) {
            $query->where('created_at', '<=', $to);
        } elseif ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
    }
}