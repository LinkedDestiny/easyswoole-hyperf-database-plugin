<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace EasySwoole\Hyperf\Database\Command;

use Common\Helper\ArrayHelper;

/**
 * Read composer.json autoload psr-4 rules to figure out the namespace or path.
 */
class Project extends \Hyperf\Utils\CodeGen\Project
{
    protected function getAutoloadRules(): array
    {
        $path = EASYSWOOLE_ROOT . '/composer.json';
        $data = collect(json_decode(file_get_contents($path), true));
        return ArrayHelper::get($data, 'autoload.psr-4', []);
    }
}
