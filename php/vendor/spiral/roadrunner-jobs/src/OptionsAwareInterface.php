<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

interface OptionsAwareInterface
{
    /**
     * Returns the {@see OptionsInterface} from the current implementation.
     */
    public function getOptions(): OptionsInterface;

    /**
     * Updates the {@see OptionsInterface} in the current implementation.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new {@see OptionsInterface} implementation.
     *
     * @param OptionsInterface $options
     * @return $this
     */
    public function withOptions(OptionsInterface $options): self;
}
