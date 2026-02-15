<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\MeridianServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[AsCommand(name: 'meridian:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meridian:install
        {--force : Overwrite existing published files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes Meridian package assets (config, migrations).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('Publishing Meridian package assets...');

        // Publish config
        $this->comment('Publishing configuration...');
        Artisan::call('vendor:publish', [
            '--provider' => MeridianServiceProvider::class,
            '--tag' => 'meridian-config',
            '--force' => $force,
        ]);
        $this->info('Configuration published.');

        // Publish migrations
        $this->comment('Publishing migrations...');
        Artisan::call('vendor:publish', [
            '--provider' => MeridianServiceProvider::class,
            '--tag' => 'meridian-migrations',
            '--force' => $force,
        ]);
        $this->info('Migrations published.');

        // Optionally, publish language files if they exist
        // $this->comment('Publishing language files...');
        // Artisan::call('vendor:publish', [
        //     '--provider' => 'Denprog\Meridian\MeridianServiceProvider',
        //     '--tag' => 'meridian-lang',
        //     '--force' => $this->option('force'),
        // ]);
        // $this->info('Language files published.');

        $this->info('Meridian package assets published successfully.');

        return CommandAlias::SUCCESS;
    }
}
