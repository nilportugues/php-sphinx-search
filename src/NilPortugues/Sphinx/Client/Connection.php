<?php

namespace NilPortugues\Sphinx\Client;
use NilPortugues\Sphinx\Helpers\MultiByte;
use NilPortugues\Sphinx\Helpers\Packer;
use NilPortugues\Sphinx\Searchd\Command;
use NilPortugues\Sphinx\Searchd\Version;
use NilPortugues\Sphinx\SphinxClientException;

/**
 * Class Connection
 * @package NilPortugues\Sphinx\Client
 */
class Connection
{
    /**
     * Searchd host (default is "localhost")
     *
     * @var string
     */
    private $host = 'localhost';

    /**
     * Searchd port (default is 9312)
     *
     * @var int
     */
    private $port = 9321;

    /**
     * @var bool
     */
    private $socket = false;

    /**
     * @var bool
     */
    private $path = false;

    /**
     * Max query time, milliseconds (default is 0, do not limit)
     *
     * @var int
     */
    private $maxQueryTime = 0;

    /**
     * Distributed retries count
     *
     * @var int
     */
    private $retryCount = 0;

    /**
     * Distributed retries delay
     *
     * @var int
     */
    private $retryDelay = 0;

    /**
     * Connect timeout
     *
     * @var int
     */
    private $timeout;

    /**
     * Connection error vs remote error flag
     *
     * @var bool
     */
    private $connectionError = false;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var MultiByte
     */
    private $multiByte;

    /**
     * @var ErrorBag
     */
    private $errorBag;

    /**
     * @param Packer $packer
     * @param MultiByte $multiByte
     * @param ErrorBag $errorBag
     */
    public function __construct(Packer $packer, MultiByte $multiByte, ErrorBag $errorBag)
    {
        $this->request = new Request();
        $this->response = new Response($this, $packer, $multiByte);
        $this->multiByte = $multiByte;
        $this->errorBag = $errorBag;
    }

    /**
     * @return \NilPortugues\Sphinx\Client\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return \NilPortugues\Sphinx\Client\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return boolean
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Sets the searchd host name and port.
     *
     * @param $host
     * @param  int $port
     * @return $this
     */
    public function setServer($host, $port = 0)
    {

        if ($host[0] == '/') {
            $this->path = 'unix://' . $host;
        }

        if (substr($host, 0, 7) == "unix://") {
            $this->path = $host;
        }

        if ($port) {
            $this->port = $port;
        }

        $this->path = '';

        return $this;
    }

    /**
     * Sets the server connection timeout (0 to remove).
     *
     * @param $timeout
     * @return $this
     */
    public function setConnectTimeout($timeout)
    {
        assert($timeout >= 0);
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Opens the connection to searchd.
     *
     * @return bool
     */
    public function open()
    {
        if ($this->socket !== false) {
            $this->error = 'already connected';

            return false;
        }
        if (!$fp = $this->connect()) {
            return false;
        }

        // command, command version = 0, body length = 4, body = 1
        $req = pack("nnNN", Command::PERSIST, 0, 4, 1);
        if (!$this->send($fp, $req, 12))
            return false;

        $this->socket = $fp;

        return true;
    }

    /**
     * Closes the connection to searchd.
     *
     * @return bool
     */
    public function close()
    {
        if ($this->socket === false) {
            $this->error = 'not connected';

            return false;
        }

        fclose($this->socket);
        $this->socket = false;

        return true;
    }

    /**
     * Checks the searchd status.
     * @return array|bool
     */
    public function status()
    {
        $this->multiByte->push();
        
        if (!($fp = $this->connect())) {
            $this->multiByte->pop();

            return false;
        }

        $req = pack("nnNN", Command::STATUS, Version::STATUS, 4, 1); // len=4, body=1
        if (!($this->send($fp, $req, 12)) ||
            !($response = $this->response->getResponse($fp, Version::STATUS))
        ) {
            $this->multiByte->pop();

            return false;
        }

        substr($response, 4); // just ignore length, error handling, etc
        $p = 0;
        list ($rows, $cols) = array_values(unpack("N*N*", substr($response, $p, 8)));
        $p += 8;

        $res = array();
        for ($i = 0; $i < $rows; $i++)
            for ($j = 0; $j < $cols; $j++) {
                list(, $len) = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $res[$i][] = substr($response, $p, $len);
                $p += $len;
            }

        $this->multiByte->pop();

        return $res;
    }


    /**
     * @param $handle
     * @param $data
     * @param $length
     * @return bool
     */
    public function send($handle, $data, $length)
    {
        if (feof($handle) || fwrite($handle, $data, $length) !== $length) {
            $this->error = 'connection unexpectedly closed (timed out?)';
            $this->connectionError = true;

            return false;
        }

        return true;
    }

    /**
     * Connect to searchd server
     * @return bool|resource
     */
    public function connect()
    {
        if ($this->socket !== false) {
            // we are in persistent connection mode, so we have a socket
            // however, need to check whether it's still alive
            if (!@feof($this->socket))
                return $this->socket;

            // force reopen
            $this->socket = false;
        }

        $errorNumber = 0;
        $errorMessage = "";
        $this->connectionError = false;

        if ($this->path) {
            $host = $this->path;
            $port = 0;
        } else {
            $host = $this->host;
            $port = $this->port;
        }

        if ($this->timeout <= 0)
            $fp = @fsockopen($host, $port, $errorNumber, $errorMessage);
        else
            $fp = @fsockopen($host, $port, $errorNumber, $errorMessage, $this->timeout);

        if (!$fp) {
            if ($this->path)
                $location = $this->path;
            else
                $location = "{$this->host}:{$this->port}";

            $errorMessage = trim($errorMessage);
            $this->error = "connection to $location failed (errno=$errorNumber, msg=$errorMessage)";
            $this->connectionError = true;

            return false;
        }

        // send my version
        // this is a subtle part. we must do it before (!) reading back from searchd.
        // because otherwise under some conditions (reported on FreeBSD for instance)
        // TCP stack could throttle write-write-read pattern because of Nagle.
        if (!$this->send($fp, pack("N", 1), 4)) {
            fclose($fp);
            $this->error = "failed to send client protocol version";

            return false;
        }

        // check version
        list(, $v) = unpack("N*", fread($fp, 4));
        $v = (int)$v;
        if ($v < 1) {
            fclose($fp);
            $this->error = "expected searchd protocol version 1+, got version '$v'";

            return false;
        }

        return $fp;
    }

    /**
     * Closes socket.
     *
     * @return bool
     */
    public function closeSocket()
    {
        if ($this->socket !== false) {
            return fclose($this->socket);
        }
        return false;
    }

    /**
     * Get last error flag.
     * It's thought to be used to inform of network connection errors from searchd errors or broken responses.
     *
     * @return boolean
     */
    public function isConnectError()
    {
        return $this->connectionError;
    }

    /**
     * Set maximum query time, in milliseconds, per-index.Integer, 0 means "do not limit".
     * @param $max
     *
     * @throws SphinxClientException
     * @return $this
     */
    public function setMaxQueryTime($max)
    {
        if($max < 0){
            throw new SphinxClientException('Maximum Query Time cannot be below zero');
        }
        $this->maxQueryTime = (int)$max;

        return $this;
    }

    /**
     * Sets distributed retries count and delay values.
     *
     * @param $count
     * @param int $delay
     * @return $this
     */
    public function setRetries($count, $delay = 0)
    {
        assert($count >= 0);
        assert($delay >= 0);

        $this->retryCount = (int)$count;
        $this->retryDelay = (int)$delay;

        return $this;
    }
}
 