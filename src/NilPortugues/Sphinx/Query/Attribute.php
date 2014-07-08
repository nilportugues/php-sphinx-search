<?php

namespace NilPortugues\Sphinx\Query;

use NilPortugues\Sphinx\SphinxClientException;

/**
 * Kept for compatibility issues.
 */
define ("SPH_ATTR_INTEGER", Attribute::INTEGER);
define ("SPH_ATTR_TIMESTAMP", Attribute::TIMESTAMP);
define ("SPH_ATTR_ORDINAL", Attribute::ORDINAL);
define ("SPH_ATTR_BOOL", Attribute::BOOL);
define ("SPH_ATTR_FLOAT", Attribute::FLOAT);
define ("SPH_ATTR_BIGINT", Attribute::BIGINT);
define ("SPH_ATTR_STRING", Attribute::STRING);
define ("SPH_ATTR_MULTI", Attribute::MULTI);
define ("SPH_ATTR_MULTI64", Attribute::MULTI64);

/**
 * Class Attribute
 * @package NilPortugues\Sphinx\Query
 */
class Attribute
{
    /**
     * Known attribute types.
     */
    const INTEGER = 1;
    const TIMESTAMP = 2;
    const ORDINAL = 3;
    const BOOL = 4;
    const FLOAT = 5;
    const BIGINT = 6;
    const STRING = 7;
    const MULTI = 0x40000001;
    const MULTI64 = 0x40000002;

    /**
     * Per-query attribute values overrides
     *
     * @var array
     */
    private $overrides = array();

    /**
     * Select-list (attributes or expressions, with optional aliases)
     *
     * @var string
     */
    private $select = '*';

    /**
     * @param $type
     * @return bool
     */
    public function isValid($type)
    {
        return ( $type === Attribute::INTEGER
            || $type === Attribute::TIMESTAMP
            || $type === Attribute::BOOL
            || $type === Attribute::FLOAT
            || $type === Attribute::BIGINT
        );
    }

    /**
     * Overrides set attribute values. Attributes can be overridden one by one.
     * $values must be a hash that maps document IDs to attribute values.
     *
     * @param $attributeName
     * @param $attributeType
     * @param array $values
     *
     * @return $this
     * @throws SphinxClientException
     */
    public function setOverride($attributeName, $attributeType, array $values)
    {
        $attributeType = (int) $attributeType;
        $attributeName = (string) $attributeName;

        if (!$this->isValid($attributeType)) {
            throw new SphinxClientException('Attribute is not valid');
        }

        $this->overrides[$attributeName] = array(
            "attr" => $attributeName,
            "type" => $attributeType,
            "values" => $values
        );

        return $this;
    }
}
