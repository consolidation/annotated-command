<?php
namespace Consolidation\TestUtils;

use Consolidation\AnnotatedCommand\Input\StdinAwareInterface;
use Consolidation\AnnotatedCommand\Input\StdinAwareTrait;

class StdinCommandFile implements StdinAwareInterface
{
    use StdinAwareTrait;

    /**
     * @command cat
     */
    public function cat()
    {
        return $this->stdin()->contents();
    }
}
