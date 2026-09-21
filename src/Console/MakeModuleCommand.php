<?php

namespace Mrj\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Mrj\Foundation\Foundation;

class MakeModuleCommand extends Command
{
    protected $signature = 'foundation:make-module
        {name : Singular StudlyCase name, e.g. Invoice or ProductCategory}
        {--group=administration : The sidebar parent (a key in config/sidebar.php) its page appears under}';

    protected $description = 'Create a module that follows the foundation conventions';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly(Str::singular((string) $this->argument('name')));

        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $name)) {
            $this->components->error('The name must be StudlyCase letters and digits, e.g. Invoice.');

            return self::FAILURE;
        }

        $target = config('modules.paths.modules', base_path('Modules')).DIRECTORY_SEPARATOR.$name;

        if ($files->exists($target) || $files->isDirectory(Foundation::modulesPath().DIRECTORY_SEPARATOR.$name)) {
            $this->components->error("A module named [$name] already exists.");

            return self::FAILURE;
        }

        $replacements = $this->replacements($name);
        $source = Foundation::path('stubs/module');

        foreach ($files->allFiles($source, true) as $stub) {
            $relative = strtr(Str::beforeLast($stub->getRelativePathname(), '.stub'), $replacements);

            if (str_contains($relative, 'database'.DIRECTORY_SEPARATOR.'migrations')) {
                $relative = dirname($relative).DIRECTORY_SEPARATOR.now()->format('Y_m_d_His').'_'.basename($relative);
            }

            $path = $target.DIRECTORY_SEPARATOR.$relative;
            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, strtr($stub->getContents(), $replacements));
        }

        $this->enable($files, $name);

        $this->components->info("Module [$name] created in Modules/$name.");
        $this->components->bulletList([
            'composer dump-autoload',
            'php artisan migrate',
            'php artisan db:seed   (adds its permissions; give them to a role)',
            "Its page sits under the '{$this->option('group')}' sidebar parent: see Modules/$name/config/menu.php",
        ]);

        return self::SUCCESS;
    }

    /**
     * Longer placeholders come first so a shorter one never matches inside them.
     *
     * @return array<string, string>
     */
    private function replacements(string $name): array
    {
        $title = Str::headline($name);

        return [
            '__SNAKE_PLURAL__' => Str::snake(Str::pluralStudly($name)),
            '__LOWER_TITLES__' => Str::lower(Str::plural($title)),
            '__TITLES__' => Str::plural($title),
            '__TITLE__' => $title,
            '__TABLE__' => Str::snake(Str::pluralStudly($name)),
            '__SNAKE__' => Str::snake($name),
            '__ALIAS__' => Str::lower($name),
            '__SLUG__' => Str::kebab(Str::pluralStudly($name)),
            '__VARS__' => Str::camel(Str::pluralStudly($name)),
            '__VAR__' => Str::camel($name),
            '__NAME__' => $name,
            "'group' => 'administration'" => "'group' => '".$this->option('group')."'",
        ];
    }

    private function enable(Filesystem $files, string $name): void
    {
        $path = config('modules.activators.file.statuses-file', base_path('modules_statuses.json'));
        $statuses = $files->exists($path) ? (array) json_decode($files->get($path), true) : [];
        $statuses[$name] = true;

        $files->put($path, json_encode($statuses, JSON_PRETTY_PRINT).PHP_EOL);
    }
}
