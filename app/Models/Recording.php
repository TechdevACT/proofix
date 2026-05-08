<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Recording extends Model
{
    use Prunable;
    protected $fillable = [
        'order_number',
        'user_id',
        'station',
        'video_path',
        'duration_seconds',
        'upload_status',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at'      => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        $settings = Storage::exists('settings.json')
            ? json_decode(Storage::get('settings.json'), true)
            : [];
        
        $retentionDays = $settings['retention_days'] ?? 90;

        // Jika retensi 0, jangan hapus apapun (kembalikan query kosong)
        if ($retentionDays <= 0) {
            return static::where('id', '<', 0); 
        }

        return static::where('recorded_at', '<', now()->subDays($retentionDays));
    }

    /**
     * Prepare the model for pruning (dipanggil sebelum data dihapus dari DB).
     */
    protected function pruning(): void
    {
        if ($this->video_path && Storage::exists($this->video_path)) {
            Storage::delete($this->video_path);
        }
    }
}
