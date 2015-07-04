<?php namespace Novanova\Tests;

use Faker\Factory;
use Faker\Generator;
use PHPUnit_Framework_TestCase;


/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    public function setUp() {
        parent::setUp();

        $this->faker = Factory::create();
    }
}