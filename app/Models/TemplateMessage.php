<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateMessage extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Replace {variable} placeholders with actual values.
     */
    public function resolveVariables(array $vars): string
    {
        $text = $this->isi_template;
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    protected static function boot()
    {
        parent::boot();

        // Ensure only one template can be default
        static::saving(function ($model) {
            if ($model->is_default) {
                static::where('id', '!=', $model->id ?? 0)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
