import os
import glob

migration_dir = r"c:\Users\celal.karaman\Desktop\HEDEFLER\database\migrations"

events_mig = glob.glob(os.path.join(migration_dir, "*_create_notification_events_table.php"))[0]
matrix_mig = glob.glob(os.path.join(migration_dir, "*_create_notification_matrix_table.php"))[0]
templates_mig = glob.glob(os.path.join(migration_dir, "*_create_notification_templates_table.php"))[0]

# Update Events Migration
with open(events_mig, 'w', encoding='utf-8') as f:
    f.write('''<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
''')

# Update Matrix Migration
with open(matrix_mig, 'w', encoding='utf-8') as f:
    f.write('''<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->cascadeOnDelete();
            $table->string('target_type'); // e.g., 'role:sistem_yoneticisi', 'special:group_manager', 'special:creator'
            $table->boolean('send_email')->default(false);
            $table->boolean('send_in_app')->default(true);
            $table->timestamps();
            
            $table->unique(['notification_event_id', 'target_type'], 'matrix_event_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_matrix');
    }
};
''')

# Update Templates Migration
with open(templates_mig, 'w', encoding='utf-8') as f:
    f.write('''<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
''')

# Update Models
models_dir = r"c:\Users\celal.karaman\Desktop\HEDEFLER\app\Models"

with open(os.path.join(models_dir, 'NotificationEventM.php'), 'w', encoding='utf-8') as f:
    f.write('''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEventM extends Model
{
    protected $table = 'notification_events';
    protected $fillable = ['name', 'slug'];

    public function matrix()
    {
        return $this->hasMany(NotificationMatrixM::class, 'notification_event_id');
    }

    public function template()
    {
        return $this->hasOne(NotificationTemplateM::class, 'notification_event_id');
    }
}
''')

with open(os.path.join(models_dir, 'NotificationMatrixM.php'), 'w', encoding='utf-8') as f:
    f.write('''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationMatrixM extends Model
{
    protected $table = 'notification_matrix';
    protected $fillable = ['notification_event_id', 'target_type', 'send_email', 'send_in_app'];

    protected $casts = [
        'send_email' => 'boolean',
        'send_in_app' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(NotificationEventM::class, 'notification_event_id');
    }
}
''')

with open(os.path.join(models_dir, 'NotificationTemplateM.php'), 'w', encoding='utf-8') as f:
    f.write('''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplateM extends Model
{
    protected $table = 'notification_templates';
    protected $fillable = ['notification_event_id', 'subject', 'body'];

    public function event()
    {
        return $this->belongsTo(NotificationEventM::class, 'notification_event_id');
    }
}
''')

print("Migrations and models updated successfully.")
