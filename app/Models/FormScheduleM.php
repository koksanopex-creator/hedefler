<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormScheduleM extends Model
{
    protected $table = 'form_schedules';
    protected $fillable = [
        'form_template_id', 'target_type', 'target_id', 
        'is_indefinite', 'start_time', 'end_time', 'is_locked'
    ];

    protected function casts(): array
    {
        return [
            'is_indefinite' => 'boolean',
            'is_locked' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function template()
    {
        return $this->belongsTo(FormTemplateM::class, 'form_template_id');
    }

    // Polimorfik bir yapı yerine basit if-else ile relationship çözebiliriz
    public function getTargetNameAttribute()
    {
        if ($this->target_type === 'all') {
            return 'Herkes (Tüm Kullanıcılar)';
        }
        
        if ($this->target_type === 'group' && $this->target_id) {
            $group = GroupM::find($this->target_id);
            return $group ? 'Grup: ' . $group->name : 'Bilinmeyen Grup';
        }
        
        if ($this->target_type === 'user' && $this->target_id) {
            $user = UserM::find($this->target_id);
            return $user ? 'Kişi: ' . $user->name : 'Bilinmeyen Kişi';
        }

        return '-';
    }
}
