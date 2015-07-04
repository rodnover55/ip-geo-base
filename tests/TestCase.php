<?php namespace Novanova\Tests;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;


/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class TestCase extends IlluminateTestCase
{
    /**
     * @var Generator
     */
    protected $faker;
//    protected $baseUrl = 'http://localhost';

    public function setUp() {
        parent::setUp();

        $this->faker = Factory::create();
    }

    public function createApplication()
    {
        $app = new Application(
            realpath(__DIR__ . '/../')
        );

        $app->singleton(
            'Illuminate\Contracts\Console\Kernel',
            'Novanova\Tests\Support\Kernel'
        );

        return $app;
    }
}