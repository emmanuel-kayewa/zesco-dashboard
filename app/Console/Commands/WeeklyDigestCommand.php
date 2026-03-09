<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppTemplateMessage;
use App\Mail\WeeklyDigestMail;
use App\Models\User;
use App\Services\AiAnalysisService;
use App\Services\WhatsAppCloudService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WeeklyDigestCommand extends Command
{
    protected $signature = 'dashboard:weekly-digest {--test : Send only to the first admin user} {--sync : Send emails immediately (no queue)}';
    protected $description = 'Generate and send AI-powered weekly performance digest';

    public function handle(AiAnalysisService $aiService, WhatsAppCloudService $whatsApp): int
    {
        if (!$aiService->isAvailable()) {
            $this->error('AI service is not available. Is Ollama running?');
            return self::FAILURE;
        }

        $this->info('Generating weekly digest with AI analysis...');

        $digest = $aiService->generateWeeklyDigest();

        if (empty($digest)) {
            $this->error('Failed to generate digest — AI returned empty response.');
            return self::FAILURE;
        }

        $this->info('Digest generated. Sending emails...');

        // Determine recipients
        if ($this->option('test')) {
            $recipients = User::where('is_active', true)
                ->whereHas('role', fn($q) => $q->where('name', 'admin'))
                ->limit(1)
                ->get();
        } else {
            $recipients = User::where('is_active', true)
                ->whereHas('role', fn($q) => $q->whereIn('name', ['admin', 'executive', 'directorate_head']))
                ->get();
        }

        $sent = 0;
        $whatsAppSent = 0;

        $whatsAppEnabled = (bool) config('dashboard.alerts.whatsapp_notifications', false);
        $whatsAppTemplate = (string) config('services.whatsapp.templates.weekly_digest');
        $dashboardUrl = rtrim((string) config('app.url'), '/');
        $buttonUrlParameter = config('services.whatsapp.url_button.enabled', false)
            ? (string) config('services.whatsapp.url_button.parameter', '/dashboard')
            : null;
        $buttonIndex = (int) config('services.whatsapp.url_button.index', 0);

        $headline = is_array($digest)
            ? (string) ($digest['headline'] ?? 'Weekly Digest')
            : 'Weekly Digest';

        $summaryText = is_array($digest)
            ? (string) ($digest['executive_summary'] ?? '')
            : (string) $digest;

        $digestSnippet = Str::of($summaryText)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->limit(500)
            ->toString();

        foreach ($recipients as $user) {
            try {
                if ($this->option('sync')) {
                    Mail::to($user->email)->send(new WeeklyDigestMail($digest, $user));
                } else {
                    Mail::to($user->email)->queue(new WeeklyDigestMail($digest, $user));
                }
                $sent++;
            } catch (\Exception $e) {
                Log::error("Failed to send digest to {$user->email}", ['error' => $e->getMessage()]);
                $this->warn("Failed to send to {$user->email}: {$e->getMessage()}");
            }

            if ($whatsAppEnabled && $user->whatsapp_opt_in && !empty($user->whatsapp_phone)) {
                if (empty($whatsAppTemplate)) {
                    Log::warning('WhatsApp weekly digest template not configured; skipping WhatsApp sends');
                    continue;
                }

                $bodyParameters = [
                    $headline,
                    $digestSnippet,
                    $dashboardUrl,
                ];

                try {
                    if ($this->option('sync')) {
                        $whatsApp->sendTemplateMessage(
                            to: $user->whatsapp_phone,
                            templateName: $whatsAppTemplate,
                            bodyParameters: $bodyParameters,
                            languageCode: 'en_US',
                            buttonUrlParameter: $buttonUrlParameter,
                            buttonIndex: $buttonIndex,
                        );
                    } else {
                        SendWhatsAppTemplateMessage::dispatch(
                            to: $user->whatsapp_phone,
                            templateName: $whatsAppTemplate,
                            bodyParameters: $bodyParameters,
                            languageCode: 'en_US',
                            buttonUrlParameter: $buttonUrlParameter,
                            buttonIndex: $buttonIndex,
                            context: [
                                'user_id' => $user->id,
                                'channel' => 'whatsapp',
                                'type' => 'weekly_digest',
                            ],
                        );
                    }
                    $whatsAppSent++;
                } catch (\Exception $e) {
                    Log::error('Failed to send WhatsApp weekly digest', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Weekly digest sent to {$sent} recipient(s).");
        if ($whatsAppEnabled) {
            $this->info("WhatsApp weekly digest queued/sent to {$whatsAppSent} recipient(s).");
        }

        return self::SUCCESS;
    }
}
