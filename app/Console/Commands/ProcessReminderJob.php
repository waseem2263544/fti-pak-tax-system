<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientService;
use App\Models\Reminder;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ProcessReminderJob extends Command
{
    protected $signature = 'app:process-reminders';
    protected $description = 'Process service deadline reminders and create notifications';

    public function handle()
    {
        $this->info('Starting reminder processing...');

        $today = Carbon::now()->startOfDay();
        $processed = 0;

        // Get all active client services
        $clientServices = ClientService::with('client', 'service')->get();

        foreach ($clientServices as $clientService) {
            if (!$clientService->next_deadline) {
                continue;
            }

            $daysUntilDeadline = $today->diffInDays($clientService->next_deadline, false);
            $reminderDays = $clientService->reminder_days ?? $clientService->service->default_reminder_days ?? 7;

            // Determine reminder type
            $reminderType = null;
            if ($daysUntilDeadline == 7) {
                $reminderType = '7_days';
            } elseif ($daysUntilDeadline == 3) {
                $reminderType = '3_days';
            } elseif ($daysUntilDeadline == 1) {
                $reminderType = '1_day';
            } elseif ($daysUntilDeadline <= 0 && $daysUntilDeadline >= -30) {
                $reminderType = 'overdue';
            }

            if (!$reminderType) {
                continue;
            }

            // Check if reminder already exists
            $existingReminder = Reminder::where('client_id', $clientService->client_id)
                ->where('service_id', $clientService->service_id)
                ->where('reminder_type', $reminderType)
                ->where('deadline_date', $clientService->next_deadline)
                ->first();

            if ($existingReminder) {
                continue;
            }

            // Create reminder
            $reminder = Reminder::create([
                'client_id' => $clientService->client_id,
                'service_id' => $clientService->service_id,
                'deadline_date' => $clientService->next_deadline,
                'reminder_type' => $reminderType,
            ]);

            // Send email to assigned consultants/staff
            $this->sendReminderEmail($clientService, $reminder, $reminderType);

            // Create in-app notifications
            $this->createInAppNotifications($clientService, $reminder, $reminderType);

            $processed++;
        }

        $this->info("Processed {$processed} reminders");
    }

    private function sendReminderEmail($clientService, $reminder, $reminderType)
    {
        $client = $clientService->client;
        $service = $clientService->service;

        $reminderTexts = [
            '7_days' => '7 days',
            '3_days' => '3 days',
            '1_day' => '1 day',
            'overdue' => 'OVERDUE',
        ];

        $subject = "[REMINDER: {$reminderTexts[$reminderType]}] {$service->display_name} for {$client->name}";
        $message = "The deadline for {$service->display_name} for client {$client->name} is {$reminderTexts[$reminderType]} away.";
        if ($reminderType === 'overdue') {
            $message = "The deadline for {$service->display_name} for client {$client->name} has PASSED. Immediate action required!";
        }

        // Get consultants/staff for this client (you can customize this logic)
        // For now, send to all admin and consultant users
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'consultant']);
        })->get();

        foreach ($users as $user) {
            try {
                Mail::raw($message, function ($m) use ($user, $subject) {
                    $m->to($user->email)->subject($subject);
                });
            } catch (\Exception $e) {
                \Log::error("Failed to send reminder email: " . $e->getMessage());
            }
        }

        $reminder->update(['email_sent' => true]);
    }

    private function createInAppNotifications($clientService, $reminder, $reminderType)
    {
        $client = $clientService->client;
        $service = $clientService->service;

        $priorityMap = [
            '7_days' => 'low',
            '3_days' => 'medium',
            '1_day' => 'high',
            'overdue' => 'high',
        ];

        $titleMap = [
            '7_days' => "Reminder: {$service->display_name} due in 7 days",
            '3_days' => "Reminder: {$service->display_name} due in 3 days",
            '1_day' => "URGENT: {$service->display_name} due tomorrow",
            'overdue' => "OVERDUE: {$service->display_name} for {$client->name}",
        ];

        // Get consultants/staff and create notifications
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'consultant']);
        })->get();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'title' => $titleMap[$reminderType],
                'message' => "Client: {$client->name}, Service: {$service->display_name}, Deadline: {$clientService->next_deadline}",
                'type' => 'reminder',
                'priority' => $priorityMap[$reminderType],
                'related_reminder_id' => $reminder->id,
            ]);
        }

        // If overdue, escalate to manager
        if ($reminderType === 'overdue') {
            $adminUsers = User::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->get();

            foreach ($adminUsers as $user) {
                $reminder->update([
                    'escalated' => true,
                    'escalated_to' => $user->id,
                ]);
            }
        }

        $reminder->update(['in_app_notified' => true]);
    }
}
