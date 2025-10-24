<?php

namespace Otomaties\VisualRentingDynamicsSync\Command;

use Illuminate\Container\Container;

class CommandRegistrar
{
    protected array $commands = [
        SyncCommand::class,
    ];

    public function __construct(public Container $container)
    {
        if (! defined('WP_CLI') || ! WP_CLI) {
            return;
        }

        foreach ($this->commands as $commandClass) {
            \WP_CLI::add_command(
                $commandClass::COMMAND_NAME,
                function ($args, $assocArgs) use ($commandClass) {
                    $commandInstance = $this->container->make($commandClass);
                    $commandInstance->handle($args, $assocArgs);
                },
                [
                    'shortdesc' => $commandClass::COMMAND_DESCRIPTION,
                    'synopsis' => $commandClass::COMMAND_ARGUMENTS,
                ]
            );
        }
    }
}
