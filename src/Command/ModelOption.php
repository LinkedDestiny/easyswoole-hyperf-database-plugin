<?php
declare(strict_types=1);


namespace EasySwoole\Hyperf\Database\Command;


class ModelOption extends \Hyperf\Database\Commands\ModelOption
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @param string $namespace
     * @return ModelOption
     */
    public function setNamespace(string $namespace): ModelOption
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

}