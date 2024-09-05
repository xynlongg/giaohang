<?php
// app/Events/ProductCreated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;


class ProductCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->product->image_url = $product->image ? Storage::url($product->image) : null;

    }

    public function broadcastOn()
    {
        return new Channel('product-channel');
    }

    public function broadcastAs()
    {
        return 'product-created';
    }
}
