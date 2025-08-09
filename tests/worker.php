<?php

declare(strict_types=1);

use Brick\Lock\Tests\Util\Worker;

require __DIR__ . '/../vendor/autoload.php';

(new Worker())->run();
