<template>
    <div>
        <p>Thanks for installing Voyager permissions.</p>
        <p>Run the installer by calling <code>php artisan permissions:install</code>.</p>
    
        <p>First the installer will publish a configuration file and migrations.</p>
        <p>After that you will be asked if you want to migrate now.</p>
        <p>If you want to change the published migrations select `No` and run the command again after you finished editing.</p>

        <p>The the installer will ask you to open your user model and add the trait `Emptynick\Permissions\Traits\HasRoles`:</p>

        <code>
        &lt;?php<br><br>

        namespace App\Models;<br><br>

        use Illuminate\Foundation\Auth\User as Authenticatable;<br>
        use Emptynick\Permissions\Traits\HasRoles;<br><br>

        class User extends Authenticatable<br>
        {<br>
            use HasRoles;<br><br>

            // ...<br>
        }
        </code>

        <p>In order to be allowed to open the permission manager, the installer will add some required permissions to your user.</p>
        <p>Enter your user model (normally <code>\App\Models\User</code>) hit enter and enter the ID of your user.</p>

        <p>Thats it! Now you are able to access the <code>Permission</code> page from the menu.</p>
    </div>
</template>

<script lang="ts">
export default {
    
}
</script>