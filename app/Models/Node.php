<?php

namespace App\Models;

use Database\Factories\NodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    /** @use HasFactory<NodeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
        'api_url',
        'api_token',
        'status',
        'latency_ms',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * Get the servers for the node.
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }
}
