<?php

namespace App\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'identifier',
        'proxy_id',
        'src_ip',
        'src_port',
        'dest_ip',
        'dest_port',
        'node_id',
        'subscription_id',
        'status',
        'domain',
    ];

    /**
     * Get the user that owns the server.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the node where the server is provisioned.
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Get the subscription associated with the server.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
