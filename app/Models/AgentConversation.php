<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentConversation extends Model
{
    protected $fillable = ['user_id', 'title'];

    public function messages()
    {
        return $this->hasMany(AgentMessage::class)->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
