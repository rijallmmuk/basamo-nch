<?php

namespace App\Services;

use App\Models\KontakMasuk;
use Illuminate\Support\Facades\DB;

class ProductionRetentionService
{
    /** @return array{notifications: int, contacts: int, trashed_contacts: int} */
    public function prune(): array
    {
        $notifications = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays(config('production.retention.read_notifications_days')))
            ->delete();

        $trashedContacts = KontakMasuk::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(config('production.retention.trashed_contact_messages_days')))
            ->forceDelete();

        $contacts = KontakMasuk::query()
            ->where('created_at', '<', now()->subDays(config('production.retention.contact_messages_days')))
            ->forceDelete();

        return [
            'notifications' => $notifications,
            'contacts' => $contacts,
            'trashed_contacts' => $trashedContacts,
        ];
    }
}
