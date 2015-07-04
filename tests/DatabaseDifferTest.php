<?php
namespace Novanova\Tests;
use Illuminate\Database\Query\Builder;
use Novanova\IPGeoBase\DatabaseDiffer;
use ArrayIterator;
use Mockery;
use Novanova\Tests\Mocks\ModelMock;

/**
 * @author Sergei Melnikov <me@rnr.name>
 */
class DatabaseDifferTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testInsert() {
        $items = $this->createItems([1, 2, 3, 5, 8]);
        $saved = $this->execute($items, [$items[0]]);
        array_shift($items);
        $this->assertEquals($items, $saved);
    }

    public function testUpdate() {
        $items = $this->createItems([1, 2, 3, 5, 8]);
        $items2 = $this->createItems([1, 2, 3, 5, 8]);
        $saved = $this->execute($items, $items2);
        $this->assertEquals($items, $saved);
    }

    public function testDelete() {
        $items = $this->createItems([1, 2, 3, 5, 8]);
        $items2 = $items;
        array_splice($items2, 3, 1);
        $saved = $this->execute($items, $items2);
        $this->assertEquals([$items[3]], $saved);
    }

    public function testEquals() {
        $items = $this->createItems([1, 2, 3, 5, 8]);
        $saved = $this->execute($items, $items);
        $this->assertEmpty($saved);
    }

    protected function execute($data, $items) {
        $differ = new DatabaseDiffer(new ArrayIterator($data), ['id', 'city', 'people']);
        $query = $this->createBuilder($items);
        $saved = [];

        $differ->getDiff($query, function($operation, $items) use (&$saved) {
            $saved = array_merge($saved, $items);
        });

        return $saved;
    }

    protected function createItems($ids) {
        $items = [];

        foreach ($ids as $id) {
            $items[] = [$id, $this->faker->city, $this->faker->numberBetween(10000, 5000000)];
        }

        return $items;
    }

    /**
     * @param $data
     * @param int $size
     * @return Builder
     */
    protected function createBuilder($data, $size = 2) {
        /**
         * @var Mockery\Mock|Builder $builder
         */
        $builder = Mockery::mock(Builder::class);
        $collections = [];

        foreach ($data as $item) {
            $collections[] = new ModelMock([
                'id' => $item[0],
                'city' => $item[1],
                'people' => $item[2]
            ]);
        }

        $chunks = new ArrayIterator(array_chunk($collections, $size));
        $chunks->rewind();

        $builder->shouldReceive('chunk')->with(Mockery::any(),
            Mockery::on(function($callback) use ($chunks) {
                while ($chunks->valid()) {
                    $chunk = $chunks->current();
                    $chunks->next();
                    $callback($chunk);
                }

                return true;
            })
        );

        return $builder;
    }
}