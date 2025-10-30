<?php

namespace Coding9\KIDataSelector\Compatibility;

class VersionCompare
{
    /**
     * @var string
     */
    private $swVersion;

    /**
     * @param string $swVersion
     */
    public function __construct(string $swVersion)
    {
        $this->swVersion = $swVersion;

        // Remove RC suffixes for proper version comparison
        $this->swVersion = str_replace('-RC2', '', $this->swVersion);
        $this->swVersion = str_replace('-RC1', '', $this->swVersion);
        $this->swVersion = str_replace('-RC3', '', $this->swVersion);
        $this->swVersion = str_replace('-RC4', '', $this->swVersion);
    }

    /**
     * Greater than or equal
     * @param string $versionB
     * @return bool
     */
    public function gte(string $versionB): bool
    {
        return version_compare($this->swVersion, $versionB, '>=');
    }

    /**
     * Greater than
     * @param string $versionB
     * @return bool
     */
    public function gt(string $versionB): bool
    {
        return version_compare($this->swVersion, $versionB, '>');
    }

    /**
     * Less than
     * @param string $version
     * @return bool
     */
    public function lt(string $version): bool
    {
        return version_compare($this->swVersion, $version, '<');
    }

    /**
     * Less than or equal
     * @param string $version
     * @return bool
     */
    public function lte(string $version): bool
    {
        return version_compare($this->swVersion, $version, '<=');
    }

    /**
     * Get current Shopware version
     * @return string
     */
    public function getVersion(): string
    {
        return $this->swVersion;
    }
}
