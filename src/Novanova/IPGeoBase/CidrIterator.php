<?php
namespace Novanova\IPGeoBase;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class CidrIterator extends CSVIterator
{
    public function getGenerator()
    {
        foreach (parent::getGenerator() as $item) {
            $matches = [];

            if (!preg_match('/(\d+\.\d+\.\d+\.\d+)\s-\s(\d+\.\d+\.\d+\.\d+)/', $item[2], $matches)) {
                throw new \RuntimeException("'{$item[2]}' has unsupported format");
            }

            yield [$item[0], $item[1], $matches[1], $matches[2], $item[3],
                ($item[4] === '-') ? (null) : ($item[4])];
        }
    }
}