<?php
declare(strict_types=1);


namespace EasySwoole\Hyperf\Database\Command\Ast;

use PhpParser\Node;

class ModelUpdateVisitor extends \Hyperf\Database\Commands\Ast\ModelUpdateVisitor
{

    protected function rewriteFillable(Node\Stmt\PropertyProperty $node): Node\Stmt\PropertyProperty
    {
        $items = [];
        foreach ($this->columns as $column) {
            if(in_array($column['column_name'], [
                'id', 'create_at', 'update_at', 'enable'
            ])) {
                continue;
            }
            $items[] = new Node\Expr\ArrayItem(new Node\Scalar\String_($column['column_name']));
        }

        $node->default = new Node\Expr\Array_($items, [
            'kind' => Node\Expr\Array_::KIND_SHORT,
        ]);
        return $node;
    }

    protected function formatDatabaseType(string $type): ?string
    {
        switch ($type) {
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
                return 'integer';
            case 'bigint':
            case 'decimal':
            case 'float':
            case 'double':
            case 'real':
                return 'string';
            case 'bool':
            case 'boolean':
                return 'boolean';
            default:
                return null;
        }
    }

    protected function formatPropertyType(string $type, ?string $cast): ?string
    {
        if (! isset($cast)) {
            $cast = $this->formatDatabaseType($type) ?? 'string';
        }

        switch ($cast) {
            case 'integer':
                return 'int';
            case 'date':
            case 'datetime':
                return 'string';
            case 'json':
                return 'array';
        }

        return $cast;
    }
}