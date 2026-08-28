<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentMessage extends Model
{
    protected $fillable = ['agent_conversation_id', 'role', 'content', 'steps'];

    protected $casts = ['steps' => 'array'];

    public function conversation()
    {
        return $this->belongsTo(AgentConversation::class, 'agent_conversation_id');
    }
}
