<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Post;
use Closure;
use Illuminate\View\Component;

/**
 * Show a card preview of the Blog Post.
 *
 * @see https://flowbite.com/docs/components/card/#card-with-image for Frontend source
 *
 * @license MIT (Frontend Base Card)
 */
final class PostCard extends Component
{
    public Post $post;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        return view('components.post-card');
    }
}
