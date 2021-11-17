<?php

namespace Emptynick\Permissions\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'permissions:install';

    private $requiredPermissions = [
        'browse permissions',
        'add permissions',
        'delete permissions',
        'assign permissions to roles',
        'assign permissions to users',
        
        'browse permission-roles',
        'add permission-roles',
        'delete permission-roles',
        'assign roles to users',
    
        'browse permission-users',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install permissions for Voyager II';

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
     * @return void
     */
    public function handle()
    {
        // Publish and migrate migrations
        $this->info('Publishing migrations');
        $this->call('vendor:publish', ['--provider' => \Spatie\Permission\PermissionServiceProvider::class]);
        if ($this->confirm('Do you wish to migrate now? Skip this if you want to modify the migration file', true)) {
            $this->call('migrate');
            $this->info('Successfully migrated!');
        }

        // Add trait to users model
        $this->info('Now go ahead and open your user model and add the "HasRoles" Trait (see the README for more information)');
        if ($this->confirm('Do you want to continue?', true)) {
            // Grant required permissions
            if ($this->confirm('To be able to access Voyager and manage permissions you need to grant some permissions to yourself. Do you want to do this now?', true)) {
                $name = $this->ask('Please enter the fully qualified user model', '\\App\\Models\\User');
                $id = $this->ask('Please enter the ID of your user');
                $this->ensurePermissionsExist($this->requiredPermissions);
                app($name)->findOrFail($id)->givePermissionTo($this->requiredPermissions);
            }
        }

        $this->info('Everything done. You can re-run this command to repeat steps if needed.');
    }

    private function ensurePermissionsExist(array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (Permission::where('name', $permission)->count() == 0) {
                Permission::create(['name' => $permission]);
            }
        }
    }
}