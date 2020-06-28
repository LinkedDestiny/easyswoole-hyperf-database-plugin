<?php
declare(strict_types=1);

use EasySwoole\EasySwoole\Command\CommandContainer;
use EasySwoole\Hyperf\Database\Command\ModelCommand;

CommandContainer::getInstance()->set(new ModelCommand());
