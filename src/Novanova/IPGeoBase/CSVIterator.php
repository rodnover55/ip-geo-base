<?php
namespace Novanova\IPGeoBase;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class CSVIterator
{
    private $handle;
    private $delimiter;
    private $enclosure;
    private $escape;
    private $fileName;

    public function __construct($fileName, $delimiter = ',', $enclosure = '"', $escape = '\\') {
        $this->handle = @fopen($fileName, 'rt');
        $this->fileName = $fileName;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;

        if ($this->handle === false) {
            throw new \RuntimeException("File '{$fileName}' cannot be open.");
        }
    }

    public function getGenerator() {
        while (!feof($this->handle)) {
            $out = fgetcsv($this->handle, null, $this->delimiter, $this->enclosure, $this->escape);

            if ($out === null) {
                throw new \RuntimeException("Something wrong when CSV file '{$this->fileName}' was read.");
            }

            if ($out !== false) {
                yield $out;
            }
        }
    }
}