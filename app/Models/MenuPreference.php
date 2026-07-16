<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuPreference extends Model
{
    protected $fillable = ['user_id', 'scope', 'order', 'hidden_items'];

    protected $casts = [
        'order' => 'array',
        'hidden_items' => 'array',
    ];

    /**
     * The sidebar layout to render for a user: their own override if they have made
     * one, otherwise the company default, otherwise nothing (built-in order).
     *
     * Resolved here rather than in the frontend so both the sidebar and the menu
     * manager agree on what "current" means.
     */
    public static function resolveFor(?User $user): array
    {
        if (!$user) {
            return ['order' => [], 'hidden' => []];
        }

        $companyId = creatorId();

        $personal = static::where('user_id', $user->id)->where('scope', 'user')->first();
        $company = static::where('user_id', $companyId)->where('scope', 'company')->first();

        $chosen = $personal ?: $company;

        return [
            'order' => $chosen->order ?? [],
            'hidden' => $chosen->hidden_items ?? [],
            // The manager needs to know whether the user is looking at their own
            // layout or inheriting the company's, to offer "reset to company default".
            'source' => $personal ? 'user' : ($company ? 'company' : 'default'),
        ];
    }
}
