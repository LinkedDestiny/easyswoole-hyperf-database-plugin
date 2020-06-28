<?php
declare(strict_types=1);

namespace EasySwoole\Hyperf\Database\Command;


use Common\Helper\ArrayHelper;
use EasySwoole\EasySwoole\Command\CommandInterface;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Hyperf\Database\Command\Ast\ModelUpdateVisitor;
use EasySwoole\Hyperf\Database\ConnectionResolver;
use EasySwoole\Hyperf\Database\Pool\MysqlPool;
use EasySwoole\Pool\Manager;
use Hyperf\Database\Commands\Ast\ModelRewriteConnectionVisitor;
use Hyperf\Database\Commands\ModelData;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Schema\MySqlBuilder;
use Hyperf\Utils\Str;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Throwable;

class ModelCommand implements CommandInterface
{

    /**
     * @var Parser
     */
    protected $astParser;

    /**
     * @var PrettyPrinterAbstract
     */
    protected $printer;

    public function __construct()
    {
        $this->astParser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard();
    }


    public function commandName(): string
    {
        return 'gen:model';
    }

    /**
     * @param array $args
     * @return string|null
     * @throws Throwable
     */
    public function exec(array $args): ?string
    {
        go(function () use ($args) {
            $table = $args[0];
            $pool = $args[1] ?? 'default';

            $config = Config::getInstance()->getConf("MYSQL");

            foreach ($config as $name => $conf) {
                Manager::getInstance()->register(new MysqlPool($conf),$name);
            }

            $option = new ModelOption();
            $option->setPool($pool)
                ->setNamespace($this->getOption('namespace', 'commands.gen:model.namespace', $pool, '\\App\\Model\\'))
                ->setPath($this->getOption('path', 'commands.gen:model.path', $pool, 'app/Model'))
                ->setPrefix($this->getOption('prefix', 'prefix', $pool, ''))
                ->setInheritance($this->getOption('inheritance', 'commands.gen:model.inheritance', $pool, 'Model'))
                ->setUses($this->getOption('uses', 'commands.gen:model.uses', $pool, 'Hyperf\DbConnection\Model\Model'))
                ->setForceCasts($this->getOption('force-casts', 'commands.gen:model.force_casts', $pool, false))
                ->setRefreshFillable(true)
                ->setTableMapping($this->getOption('table-mapping', 'commands.gen:model.table_mapping', $pool, []))
                ->setIgnoreTables($this->getOption('ignore-tables', 'commands.gen:model.ignore_tables', $pool, []))
                ->setWithComments($this->getOption('with-comments', 'commands.gen:model.with_comments', $pool, false))
                ->setVisitors($this->getOption('visitors', 'commands.gen:model.visitors', $pool, []))
                ->setPropertyCase($this->getOption('property-case', 'commands.gen:model.property_case', $pool));

            try {
                if ($table) {
                    $this->createModel($table, $option);
                } else {
                    $this->createModels($option);
                }
            } catch (Throwable $e) {
                var_dump($e->getMessage());
            }
        });

        return "success";
    }

    public function help(array $args): ?string
    {
        return "";
    }

    /**
     * @param string $poolName
     * @return MySqlBuilder
     * @throws Throwable
     */
    protected function getSchemaBuilder(string $poolName): MySqlBuilder
    {
        $connection = ConnectionResolver::getInstance()->connection($poolName);
        return $connection->getSchemaBuilder();
    }

    /**
     * @param ModelOption $option
     * @throws Throwable
     */
    protected function createModels(ModelOption $option)
    {
        $builder = $this->getSchemaBuilder($option->getPool());
        $tables = [];

        foreach ($builder->getAllTables() as $row) {
            $row = (array) $row;
            $table = reset($row);
            if (! $this->isIgnoreTable($table, $option)) {
                $tables[] = $table;
            }
        }

        foreach ($tables as $table) {
            $this->createModel($table, $option);
        }
    }

    protected function isIgnoreTable(string $table, ModelOption $option): bool
    {
        if (in_array($table, $option->getIgnoreTables())) {
            return true;
        }

        $config = Config::getInstance()->getConf("MYSQL");

        return $table === ($config['migrations'] ?? 'migrations');
    }

    /**
     * @param string $table
     * @param ModelOption $option
     * @throws Throwable
     */
    protected function createModel(string $table, ModelOption $option)
    {
        $builder = $this->getSchemaBuilder($option->getPool());
        $table = Str::replaceFirst($option->getPrefix(), '', $table);
        $columns = $this->formatColumns($builder->getColumnTypeListing($table));

        $project = new Project();
        $class = $option->getTableMapping()[$table] ?? Str::studly(Str::singular($table));
        $class = $project->namespace($option->getPath()) . $class;
        $path = EASYSWOOLE_ROOT . '/' . $project->path($class);

        if (! file_exists($path)) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            file_put_contents($path, $this->buildClass($table, $class, $option));
        }

        $columns = $this->getColumns($class, $columns, $option->isForceCasts());

        $stms = $this->astParser->parse(file_get_contents($path));
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ModelUpdateVisitor($class, $columns, $option));
        $traverser->addVisitor(new ModelRewriteConnectionVisitor($class, $option->getPool()));

        foreach ($option->getVisitors() as $visitorClass) {
            $data = (new ModelData())->setClass($class)->setColumns($columns);
            $traverser->addVisitor(new $visitorClass($option, $data));
        }
        $stms = $traverser->traverse($stms);
        $code = $this->printer->prettyPrintFile($stms);

        file_put_contents($path, $code);
    }

    /**
     * Format column's key to lower case.
     * @param array $columns
     * @return array
     */
    protected function formatColumns(array $columns): array
    {
        return array_map(function ($item) {
            return array_change_key_case($item, CASE_LOWER);
        }, $columns);
    }

    protected function getColumns($className, $columns, $forceCasts): array
    {
        /** @var Model $model */
        $model = new $className();
        $dates = $model->getDates();
        $casts = [];
        if (! $forceCasts) {
            $casts = $model->getCasts();
        }

        foreach ($dates as $date) {
            if (! isset($casts[$date])) {
                $casts[$date] = 'datetime';
            }
        }

        foreach ($columns as $key => $value) {
            $columns[$key]['cast'] = $casts[$value['column_name']] ?? null;
        }

        return $columns;
    }

    protected function getOption(string $name, string $key, string $pool = 'default', $default = null)
    {
        $config = Config::getInstance()->getConf("MYSQL");
        return ArrayHelper::get($config[$pool], $key, $default);
    }

    /**
     * Build the class with the given name.
     * @param string $table
     * @param string $name
     * @param ModelOption $option
     * @return string
     */
    protected function buildClass(string $table, string $name, ModelOption $option): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Model.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceInheritance($stub, $option->getInheritance())
            ->replaceConnection($stub, $option->getPool())
            ->replaceUses($stub, $option->getUses())
            ->replaceClass($stub, $name)
            ->replaceTable($stub, $table);
    }

    /**
     * Replace the namespace for the given stub.
     * @param string $stub
     * @param string $name
     * @return ModelCommand
     */
    protected function replaceNamespace(string &$stub, string $name): self
    {
        $stub = str_replace(
            ['%NAMESPACE%'],
            [$this->getNamespace($name)],
            $stub
        );

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     * @param string $name
     * @return string
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    protected function replaceInheritance(string &$stub, string $inheritance): self
    {
        $stub = str_replace(
            ['%INHERITANCE%'],
            [$inheritance],
            $stub
        );

        return $this;
    }

    protected function replaceConnection(string &$stub, string $connection): self
    {
        $stub = str_replace(
            ['%CONNECTION%'],
            [$connection],
            $stub
        );

        return $this;
    }

    protected function replaceUses(string &$stub, string $uses): self
    {
        $uses = $uses ? "use {$uses};" : '';
        $stub = str_replace(
            ['%USES%'],
            [$uses],
            $stub
        );

        return $this;
    }

    /**
     * Replace the class name for the given stub.
     * @param string $stub
     * @param string $name
     * @return ModelCommand
     */
    protected function replaceClass(string &$stub, string $name): self
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $stub = str_replace('%CLASS%', $class, $stub);

        return $this;
    }

    /**
     * Replace the table name for the given stub.
     * @param string $stub
     * @param string $table
     * @return string
     */
    protected function replaceTable(string $stub, string $table): string
    {
        return str_replace('%TABLE%', $table, $stub);
    }

    /**
     * Get the destination class path.
     * @param string $name
     * @return string
     */
    protected function getPath(string $name): string
    {
        return EASYSWOOLE_ROOT . '/' . str_replace('\\', '/', $name) . '.php';
    }
}