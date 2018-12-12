<?php

namespace Consolidation\AnnotatedCommand\Input;

use Symfony\Component\Console\Input\StreamableInputInterface;

/**
 * StdinHandler is a thin wrapper around php://stdin. It provides
 * methods for redirecting input from a file, possibly conditionally
 * under the control of an Input object.
 *
 * Example trivial usage (always reads from stdin):
 *
 *      class Example implements StdinAwareInterface
 *      {
 *          public function cat()
 *          {
 *               print($this->stdin()->contents());
 *          }
 *      }
 *
 * Command that reads from stdin or file via an option:
 *
 *      /**
 *       * @command example
 *       * /
 *      public function example($options = ['source-file' => '-'])
 *      {
 *          $data = $this->stdin()->select($this->input, 'source-file')->contents();
 *      }
 *
 * Command that reads from stdin or file via an argument:
 *
 *      /**
 *       * @command example
 *       * /
 *      public function example($file)
 *      {
 *          $data = $this->stdin()->select($this->input, 'file')->contents();
 *      }
 *
 * It is also possible to inject the selected stream into the input object,
 * e.g. if you want the contents of the source file to be fed to any Question
 * helper et. al. that the $input object is used with.
 *
 *      /**
 *       * @command example
 *       * /
 *      public function example($file)
 *      {
 *          $this->stdin()->setStream($this->input, 'file');
 *      }
 *
 *
 * Inject an alternate source for standard input in tests.  Presumes that
 * the object under test gets a reference to the StdinHandler via dependency
 * injection from the container.
 *
 *      $container->get('stdinHandler')->redirect($pathToTestStdinFileFixture);
 *
 * You may also inject your stdin file fixture stream into the $input object
 * as usual, and then use it with 'select()' or 'setStream()' as shown above.
 */
class StdinHandler
{
    protected $path;
    protected $stream;

    /**
     * hasPath returns 'true' if the stdin handler has a path to a file.
     *
     * @return bool
     */
    public function hasPath()
    {
        // Once the stream has been opened, we mask the existence of the path.
        return !$this->hasStream() && !empty($this->path);
    }

    /**
     * hasStream returns 'true' if the stdin handler has opened a stream.
     *
     * @return bool
     */
    public function hasStream()
    {
        return !empty($this->stream);
    }

    /**
     * path returns the path to any file that was set as a redirection
     * source, or `php://stdin` if none have been.
     *
     * @return string
     */
    public function path()
    {
        return $this->path ?: 'php://stdin';
    }

    /**
     * close closes the input stream if it was opened.
     */
    public function close()
    {
        if ($this->hasStream()) {
            fclose($this->stream);
            $this->stream = null;
        }
        return $this;
    }

    /**
     * redirect specifies a path to a file that should serve as the
     * source to read from. If the input path is '-' or empty,
     * then output will be taken from php://stdin (or whichever source
     * was provided via the 'redirect' method).
     *
     * @return $this
     */
    public function redirect($path)
    {
        if (!empty($path) && ($path != '-')) {
            $this->path = $path;
        }

        return $this;
    }

    /**
     * select chooses the source of the input stream based on whether or
     * not the user provided the specified option or argument on the commandline.
     * Stdin is selected if there is no user selection.
     *
     * @param InputInterface $input
     * @param string $optionOrArg
     * @return $this
     */
    public function select(InputInterface $input, $optionOrArg)
    {
        $this->path = $this->getOptionOrArg($input, $optionOrArg);
        if (!$this->hasPath() && ($input instanceof StreamableInputInterface)) {
            $this->stream = $input->getStream();
        }

        return $this;
    }

    /**
     * getStream opens and returns the stdin stream (or redirect file).
     */
    public function getStream()
    {
        if (!$this->hasStream()) {
            $this->stream = fopen($this->path());
        }
        return $this->stream;
    }

    /**
     * setStream functions like 'select', and also sets up the $input
     * object to read from the selected input stream e.g. when used
     * with a question helper.
     */
    public function setStream(InputInterface $input, $optionOrArg)
    {
        $this->select($input, $optionOrArg);
        if ($input instanceof StreamableInputInterface) {
            $stream = $this->getStream();
            $input->setStream($stream);
        }
        return $this;
    }

    /**
     * contents reads the entire contents of the standard input stream.
     *
     * @return string
     */
    public function contents()
    {
        // Optimization: use file_get_contents if we have a path to a file
        // and the stream has not been opened yet.
        if (!$this->hasStream()) {
            return file_get_contents($this->path());
        }
        $stream = $this->getStream();
        stream_set_blocking($stream, false); // TODO: We did this in backend invoke. Necessary here?
        $contents = stream_get_contents($stream);
        $this->close();

        return $contents;
    }

    protected function getOptionOrArg(InputInterface $input, $optionOrArg)
    {
        if ($input->hasOption($optionOrArg)) {
            return $input->getOption($optionOrArg);
        }
        return $input->getArgument($optionOrArg);
    }
}
