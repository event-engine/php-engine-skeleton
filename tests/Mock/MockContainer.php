<?php
declare(strict_types=1);

namespace MyServiceTest\Mock;

use Psr\Container\ContainerInterface;

final class MockContainer implements ContainerInterface
{
    /**
     * @var array
     */
    private $mocks;

    public function __construct(array $mocks)
    {
        $this->mocks = $mocks;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if(!$this->has($id)) {
            throw new \RuntimeException("Service $id can't be resolved. No mock provided!");
        }

        return $this->mocks[$id];
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return \array_key_exists($id, $this->mocks);
    }
}
