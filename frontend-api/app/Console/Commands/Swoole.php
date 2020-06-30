<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Swoole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:run {action : 操作}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swoole';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action){
            case 'websocket':
                $websocket = new \Common\API\Websocket($this);
                break;
        }

    }
}
