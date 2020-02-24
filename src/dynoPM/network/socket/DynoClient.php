<?php
/**
 * ________
 * ___  __ \____  ______________
 * __  / / /_  / / /_  __ \  __ \
 * _  /_/ /_  /_/ /_  / / / /_/ /
 * /_____/ _\__, / /_/ /_/\____/
 *         /____/
 *
 * This program is free: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is based on PocketMine Software and Synapse.
 *
 * @copyright (c) 2020
 * @author Y$SS-YassLV
 */

declare(strict_types=1);

namespace dynoPM\network\socket;

use pocketmine\Thread;

class DynoClient extends Thread
{
    /** @var bool */
    public $needReconnect = false;
    /** @var \ThreadedLogger */
    private $logger;
    /** @var string */
    private $interface;
    /** @var int */
    private $port;
    /** @var bool */
    private $shutdown = true;
    /** @var \Threaded */
    private $externalQueue, $internalQueue;
    /** @var string */
    private $mainPath;
    /** @var bool */
    private $needAuth = false;
    /** @var bool */
    private $connected = true;

    /**
     * DynoClient constructor.
     * @param \ThreadedLogger $logger
     * @param \ClassLoader $loader
     * @param int $port
     * @param string $interface
     * @throws \Exception
     */
    public function __construct(\ThreadedLogger $logger, \ClassLoader $loader, int $port, string $interface = '127.0.0.1')
    {
        $this->logger = $logger;
        $this->interface = $interface;
        $this->port = (int)$port;
        if ($port < 1 or $port > 65536) {
            throw new \Exception('Invalid port range');
        }

        $this->setClassLoader($loader);

        $this->shutdown = false;
        $this->externalQueue = new \Threaded;
        $this->internalQueue = new \Threaded;

        if (\Phar::running(true) !== '') {
            $this->mainPath = \Phar::running(true);
        } else {
            $this->mainPath = \getcwd() . DIRECTORY_SEPARATOR;
        }

        $this->start();
    }

    public function reconnect()
    {
        $this->needReconnect = true;
    }

    /**
     * @return bool
     */
    public function isNeedAuth(): bool
    {
        return $this->needAuth;
    }

    public function setNeedAuth(bool $need)
    {
        $this->needAuth = $need;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setConnected(bool $con)
    {
        $this->connected = $con;
    }

    public function quit()
    {
        $this->shutdown();
        parent::quit();
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }

    public function run()
    {
        $this->registerClassLoader();
        gc_enable();
        error_reporting(-1);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');

        set_error_handler([$this, 'errorHandler'], E_ALL);
        register_shutdown_function([$this, 'shutdownHandler']);

        try {
            $socket = new DynoSocket($this->getLogger(), $this->port, $this->interface);
            new ServerConnection($this, $socket);
        } catch (\Throwable $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * @return \ThreadedLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function shutdownHandler()
    {
        if ($this->shutdown !== true) {
            $this->getLogger()->emergency('socket crashed!');
        }
    }

    public function errorHandler($errno, $errstr, $errfile, $errline, $context, $trace = null)
    {
        if (error_reporting() === 0) {
            return false;
        }
        $errorConversion = [
            E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE', E_NOTICE => 'E_NOTICE', E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING', E_COMPILE_ERROR => 'E_COMPILE_ERROR', E_COMPILE_WARNING => 'E_COMPILE_WARNING', E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING', E_USER_NOTICE => 'E_USER_NOTICE', E_STRICT => 'E_STRICT', E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR', E_DEPRECATED => 'E_DEPRECATED', E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        $errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
        if (($pos = strpos($errstr, '\n')) !== false) {
            $errstr = substr($errstr, 0, $pos);
        }
        $errfile = $this->cleanPath($errfile);

        $this->getLogger()->debug('An ' . $errno . ' error happened: "' . $errstr . '" in "' . $errfile . '" at line $errline');

        foreach (($trace = $this->getTrace($trace === null ? 3 : 0, $trace)) as $i => $line) {
            $this->getLogger()->debug($line);
        }

        return true;
    }

    public function cleanPath($path)
    {
        return rtrim(str_replace(['\\', '.php', 'phar://', rtrim(str_replace(['\\', 'phar://'], ['/', ''], $this->mainPath), '/')], ['/', '', '', ''], $path), '/');
    }

    public function getTrace($start = 1, $trace = null)
    {
        if ($trace === null) {
            if (function_exists('xdebug_get_function_stack')) {
                $trace = array_reverse(xdebug_get_function_stack());
            } else {
                $e = new \Exception();
                $trace = $e->getTrace();
            }
        }

        $messages = [];
        $j = 0;
        for ($i = (int)$start; isset($trace[$i]); ++$i, ++$j) {
            $params = '';
            if (isset($trace[$i]['args']) or isset($trace[$i]['params'])) {
                if (isset($trace[$i]['args'])) {
                    $args = $trace[$i]['args'];
                } else {
                    $args = $trace[$i]['params'];
                }
                foreach ($args as $name => $value) {
                    $params .= (is_object($value) ? get_class($value) . ' ' . (method_exists($value, '__toString') ? $value->__toString() : 'object') : gettype($value) . ' ' . @strval($value)) . ', ';
                }
            }
            $messages[] = '#' . $j . (isset($trace[$i]['file']) ? $this->cleanPath($trace[$i]['file']) : '') . '(' . (isset($trace[$i]['line']) ? $trace[$i]['line'] : '') . '): ' . (isset($trace[$i]['class']) ? $trace[$i]['class'] . (($trace[$i]['type'] === 'dynamic' or $trace[$i]['type'] === '->') ? '->' : '::') : '') . $trace[$i]['function'] . '(' . substr($params, 0, -2) . ')';
        }

        return $messages;
    }

    /**
     * @return \Threaded
     */
    public function getExternalQueue()
    {
        return $this->externalQueue;
    }

    public function getInternalQueue()
    {
        return $this->internalQueue;
    }

    public function pushMainToThreadPacket($str)
    {
        $this->internalQueue[] = $str;
    }

    /**
     * @return bool|mixed
     */
    public function readMainToThreadPacket()
    {
        return $this->internalQueue->shift();
    }

    public function pushThreadToMainPacket($str)
    {
        $this->externalQueue[] = $str;
    }

    /**
     * @return int
     */
    public function getInternalQueueSize()
    {
        return count($this->internalQueue);
    }

    /**
     * @return bool|mixed
     */
    public function readThreadToMainPacket()
    {
        return $this->externalQueue->shift();
    }

    /**
     * @return bool
     */
    public function isShutdown()
    {
        return $this->shutdown === true;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getInterface()
    {
        return $this->interface;
    }

    public function isGarbage(): bool
    {
        return parent::isGarbage();
    }

    /**
     * @return string
     */
    public function getThreadName(): string
    {
        return 'DynoClient';
    }
}
