<?php

namespace App\Console\Commands;

use App\Models\BookingOrder;
use App\Enums\BookingOrderApprovalStatusEnum;
use App\Models\BookingOrderApprovalLog;
use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpirePendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending booking orders that have passed their expired_at time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all pending statuses that should be expired
        $pendingStatuses = [
            BookingOrderApprovalStatusEnum::DRAFT->value,
        ];

        $expiredBookings = BookingOrder::whereIn('approval_status', $pendingStatuses)
            ->whereNull('recipient_status_id') // ini berarti booking order belum di proses oleh operator_unit
            ->where('is_expired', false)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', Carbon::now())
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No pending bookings found to expire.');
            return 0;
        }

        $this->info("{date('Y-m-d H:i:s')} - Found {$expiredBookings->count()} pending bookings to expire.");

        $expiredCount = 0;

        foreach ($expiredBookings as $booking) {
            try {
                DB::transaction(function () use ($booking) {
                    // ambil user dengan role super_admin
                    $user = User::whereHas('roles', function ($query) {
                        $query->where('name', 'super_admin');
                    })->first();

                    if (empty($user)) {
                        $this->error("User with role super_admin not found.");
                        return 0;
                    }

                    // Update approval_status to CANCELED and set is_expired flag
                    $booking->update([
                        'approval_status' => BookingOrderApprovalStatusEnum::CANCELED,
                        'is_expired' => true,
                    ]);
                    // tambahkan log ke table booking_order_approval_logs
                    BookingOrderApprovalLog::create([
                        'booking_order_id' => $booking->id,
                        'approval_status' => BookingOrderApprovalStatusEnum::CANCELED,
                        'action' => 'expire',
                        'notes' => 'Booking order expired by system',
                        'role_name' => 'super_admin',
                        'action_by' => $user->id,
                        'created_at' => now(),
                    ]);
                });

                $expiredCount++;
                $this->line("{date('Y-m-d H:i:s')} - Expired booking order: {$booking->order_number} (ID: {$booking->id})");
            } catch (\Exception $e) {
                $this->error("{date('Y-m-d H:i:s')} - Failed to expire booking order {$booking->order_number}: {$e->getMessage()}");
            }
        }

        $this->info("{date('Y-m-d H:i:s')} - Successfully expired {$expiredCount} booking orders.");

        return 0;
    }
}
