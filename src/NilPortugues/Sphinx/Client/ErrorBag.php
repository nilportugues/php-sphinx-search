<?php

namespace NilPortugues\Sphinx\Client;

/**
 * Class ErrorBag
 * @package NilPortugues\Sphinx\Client
 */
class ErrorBag
{
    /**
     * Last error message
     *
     * @var string
     */
    private $error = '';

    /**
     * Last warning message
     *
     * @var string
     */
    private $warning = '';

    /**
     * Gets last error message.
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->error;
    }

    /**
     * Gets last warning message.
     *
     * @return string
     */
    public function getLastWarning()
    {
        return $this->warning;
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @param string $warning
     */
    public function setWarning($warning)
    {
        $this->warning = $warning;
    }
}
 