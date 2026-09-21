<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Settings\Models\Setting;

return new class extends Migration
{
    /**
     * Remove the dynamic Reverb/broadcast settings rows. Reverb configuration is
     * now driven purely by env via config/reverb.php and config/broadcasting.php;
     * these editable rows are no longer read at boot, so drop them to avoid stale
     * credentials lingering in the settings table.
     */
    public function up(): void
    {
        Setting::whereIn('key', [
            'broadcast_driver',
            'reverb_app_key',
            'reverb_app_secret',
            'reverb_app_id',
            'reverb_host',
            'reverb_port',
            'reverb_scheme',
        ])->delete();
    }

    /**
     * The dynamic Reverb settings feature was removed; nothing to restore.
     */
    public function down(): void
    {
        //
    }
};
