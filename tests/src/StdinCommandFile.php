<?php
namespace Consolidation\TestUtils;

use Consolidation\AnnotatedCommand\Input\StdinAwareInterface;
use Consolidation\AnnotatedCommand\Input\StdinAwareTrait;
use Consolidation\AnnotatedCommand\Input\StdinHandler;
use Symfony\Component\Console\Input\InputInterface;

class StdinCommandFile implements StdinAwareInterface
{
    use StdinAwareTrait;

    /**
     * @command cat
     * @param string $file
     * @default $file -
     */
    public function cat(InputInterface $input)
    {
        return $this->stdin()->select($input, 'file')->contents();
    }

    /**
     * @command cat:too
     * @option string $file
     * @default $file -
     */
    public function catToo(InputInterface $input)
    {
        return $this->stdin()->select($input, 'file')->contents();
    }

    /**
     * @command cat:no-di
     * @option string $file
     * @default $file -
     *
     * This implementation works even without the StdinAwareTrait.
     */
    public function catNoDI(InputInterface $input)
    {
        // This could be followed by 'getStream()' instead of 'contents()'.
        return StdinHandler::selectStream($input, 'file')->contents();
    }
}
