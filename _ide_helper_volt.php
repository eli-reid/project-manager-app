<?php

namespace Illuminate\Contracts\View;

use Illuminate\Contracts\Support\Renderable;

interface View extends Renderable
{
    /** @return static */
    public function title($title);

    /** @return static */
    public function layout($view = null);

    /** @return static */
    public function section($name = null);
}
