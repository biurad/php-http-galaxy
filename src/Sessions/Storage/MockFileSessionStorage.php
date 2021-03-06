<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http\Sessions\Storage;

use Biurad\Http\Sessions\MetadataBag;

/**
 * MockFileSessionStorage is used to mock sessions for
 * functional testing when done in a single PHP process.
 *
 * No PHP session is actually started since a session can be initialized
 * and shutdown only once per PHP execution cycle and this class does
 * not pollute any session related globals, including session_*() functions
 * or session.* PHP ini directives.
 *
 * @author Drak <drak@zikula.org>
 */
class MockFileSessionStorage extends MockArraySessionStorage
{
    private $savePath;

    /**
     * @param string $savePath Path of directory to save session files
     */
    public function __construct(string $savePath = null, string $name = 'MOCKSESSID', MetadataBag $metaBag = null)
    {
        if (null === $savePath) {
            $savePath = \sys_get_temp_dir();
        }

        if (!\is_dir($savePath) && !@\mkdir($savePath, 0777, true) && !\is_dir($savePath)) {
            throw new \RuntimeException(\sprintf('Session Storage was not able to create directory "%s".', $savePath));
        }

        $this->savePath = $savePath;
        parent::__construct($name, $metaBag);
    }

    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if (!$this->id) {
            $this->id = $this->generateId();
        }

        $this->read();

        $this->started = true;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(bool $destroy = false, int $lifetime = null): bool
    {
        if (!$this->started) {
            $this->start();
        }

        if ($destroy) {
            $this->destroy();
        }

        return parent::regenerate($destroy, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function save(): void
    {
        if (!$this->started) {
            throw new \RuntimeException('Trying to save a session that was not started yet or was already closed.');
        }

        $data = $this->data;

        foreach ($this->bags as $bag) {
            if (empty($data[$key = $bag->getStorageKey()])) {
                unset($data[$key]);
            }
        }

        if ([$key = $this->metadataBag->getStorageKey()] === \array_keys($data)) {
            unset($data[$key]);
        }

        try {
            if ($data) {
                \file_put_contents($this->getFilePath(), \serialize($data));
            } else {
                $this->destroy();
            }
        } finally {
            $this->data = $data;
        }

        // this is needed for Silex, where the session object is re-used across requests
        // in functional tests. In Symfony, the container is rebooted, so we don't have
        // this issue
        $this->started = false;
    }

    /**
     * Deletes a session from persistent storage.
     * Deliberately leaves session data in memory intact.
     */
    private function destroy(): void
    {
        if (\is_file($this->getFilePath())) {
            \unlink($this->getFilePath());
        }
    }

    /**
     * Calculate path to file.
     */
    private function getFilePath(): string
    {
        return $this->savePath . '/' . $this->id . '.mocksess';
    }

    /**
     * Reads session from storage and loads session.
     */
    private function read(): void
    {
        $filePath   = $this->getFilePath();
        $this->data = \is_readable($filePath) && \is_file($filePath) ? \unserialize(\file_get_contents($filePath)) : [];

        $this->loadSession();
    }
}
