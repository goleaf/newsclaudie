<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\View\Component;

final class PostTags extends Component
{
    /**
     * @var array do display
     */
    public array $tags;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(array $tags = [])
    {
        $this->tags = $tags;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        return view('components.post-tags');
    }
}
