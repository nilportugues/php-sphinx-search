<?php

namespace NilPortugues\Sphinx\Client;
use NilPortugues\Sphinx\Searchd\Command;

/**
 * Class Connection
 * @package NilPortugues\Sphinx\Client
 */
class Connection
{
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
        $this->multiByte->Push();
        
        if (!($fp = $this->connect())) {
            $this->multiByte->Pop();

            return false;
        }

        $req = pack("nnNN", Command::STATUS, Version::STATUS, 4, 1); // len=4, body=1
        if (!($this->send($fp, $req, 12)) ||
            !($response = $this->_GetResponse($fp, Version::STATUS))
        ) {
            $this->multiByte->Pop();

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

        $this->multiByte->Pop();

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

        $errno = 0;
        $errstr = "";
        $this->connectionError = false;

        if ($this->path) {
            $host = $this->path;
            $port = 0;
        } else {
            $host = $this->host;
            $port = $this->port;
        }

        if ($this->timeout <= 0)
            $fp = @fsockopen($host, $port, $errno, $errstr);
        else
            $fp = @fsockopen($host, $port, $errno, $errstr, $this->timeout);

        if (!$fp) {
            if ($this->path)
                $location = $this->path;
            else
                $location = "{$this->host}:{$this->port}";

            $errstr = trim($errstr);
            $this->error = "connection to $location failed (errno=$errno, msg=$errstr)";
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
}
 