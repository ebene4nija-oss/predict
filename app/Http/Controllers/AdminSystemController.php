<?php

namespace App\Http\Controllers;

use App\Support\IntegrationTester;
use App\Support\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Maintenance operations for hosts without shell access.
 *
 * Shared cPanel accounts frequently have no Terminal, which otherwise leaves
 * an admin unable to clear a stale config cache or apply a migration after an
 * upload — the two things most likely to be needed right after a deploy.
 */
class AdminSystemController extends Controller
{
    /**
     * Commands an admin may run, keyed by the identifier the form submits.
     *
     * A whitelist rather than a free-text box: `Artisan::call()` with
     * user-supplied input is remote code execution, since the argument string
     * reaches commands that take file paths and class names. Nothing
     * destructive is listed — no migrate:fresh, no db:wipe, no db:seed (which
     * would create demo accounts whose password is "password").
     *
     * @var array<string, array{label: string, command: string, params: array, danger: bool, help: string}>
     */
    public const COMMANDS = [
        'config_cache' => [
            'label' => 'Rebuild config cache',
            'command' => 'config:cache',
            'params' => [],
            'danger' => false,
            'help' => 'Run after editing .env. The cached config is what the app actually reads.',
        ],
        'config_clear' => [
            'label' => 'Clear config cache',
            'command' => 'config:clear',
            'params' => [],
            'danger' => false,
            'help' => 'Falls back to reading .env directly. Use if a cached value looks stuck.',
        ],
        'route_cache' => [
            'label' => 'Rebuild route cache',
            'command' => 'route:cache',
            'params' => [],
            'danger' => false,
            'help' => 'Speeds up routing. Safe to re-run at any time.',
        ],
        'view_clear' => [
            'label' => 'Clear compiled views',
            'command' => 'view:clear',
            'params' => [],
            'danger' => false,
            'help' => 'Use after uploading changed Blade templates.',
        ],
        'cache_clear' => [
            'label' => 'Flush application cache',
            'command' => 'cache:clear',
            'params' => [],
            'danger' => false,
            'help' => 'Clears cached settings and track-record figures.',
        ],
        'storage_link' => [
            'label' => 'Create storage symlink',
            'command' => 'storage:link',
            'params' => [],
            'danger' => false,
            'help' => 'Needed once so uploaded images resolve under /storage.',
        ],
        'migrate' => [
            'label' => 'Run database migrations',
            'command' => 'migrate',
            'params' => ['--force' => true],
            'danger' => true,
            'help' => 'Applies pending schema changes. Back up the database first.',
        ],
        'queue_work' => [
            'label' => 'Drain the queue now',
            'command' => 'queue:work',
            'params' => ['--stop-when-empty' => true, '--max-time' => 20],
            'danger' => false,
            'help' => 'Sends any queued verification and password-reset mail immediately.',
        ],
    ];

    public function index()
    {
        return view('admin.system', [
            'commands' => self::COMMANDS,
            'services' => IntegrationTester::SERVICES,
            'mailConfigured' => MailSettings::isConfigured(),
            'mailer' => config('mail.default'),
            'mailHost' => config('mail.mailers.smtp.host'),
            'mailFrom' => config('mail.from.address'),
            'queueDepth' => $this->queueDepth(),
        ]);
    }

    public function runCommand(Request $request)
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'in:'.implode(',', array_keys(self::COMMANDS))],
        ]);

        $spec = self::COMMANDS[$validated['command']];

        try {
            $exitCode = Artisan::call($spec['command'], $spec['params']);
            $output = trim(Artisan::output());
        } catch (\Throwable $e) {
            Log::error('Admin maintenance command failed', [
                'command' => $spec['command'],
                'admin_id' => $request->user()->id,
                'exception' => $e->getMessage(),
            ]);

            return back()->with('command_output', [
                'ok' => false,
                'label' => $spec['label'],
                'output' => 'Failed: '.$e->getMessage(),
            ]);
        }

        Log::info('Admin ran maintenance command', [
            'command' => $spec['command'],
            'admin_id' => $request->user()->id,
            'exit_code' => $exitCode,
        ]);

        return back()->with('command_output', [
            'ok' => $exitCode === 0,
            'label' => $spec['label'],
            'output' => $output === '' ? 'Completed with no output.' : $output,
        ]);
    }

    /**
     * Send a real message through the configured transport.
     *
     * Deliberately unqueued: a queued test would report success the moment the
     * job was stored, which is exactly the failure mode being diagnosed.
     */
    public function testMail(Request $request)
    {
        $validated = $request->validate([
            'recipient' => ['required', 'email', 'max:255'],
        ]);

        try {
            Mail::raw(
                "This is a test message from ".config('app.name').".\n\n"
                ."If you are reading this, SMTP is working: host "
                .config('mail.mailers.smtp.host').", port "
                .config('mail.mailers.smtp.port').".\n\n"
                ."Sent at ".now()->toDateTimeString().'.',
                function ($message) use ($validated) {
                    $message->to($validated['recipient'])
                        ->subject(config('app.name').' — SMTP test');
                }
            );
        } catch (\Throwable $e) {
            return back()->with('mail_result', [
                'ok' => false,
                'message' => 'Send failed: '.$e->getMessage(),
            ]);
        }

        return back()->with('mail_result', [
            'ok' => true,
            'message' => 'Message handed to '.config('mail.default').' for '.$validated['recipient']
                .'. If it does not arrive, check cPanel > Email Deliverability for SPF and DKIM.',
        ]);
    }

    public function testIntegration(Request $request, IntegrationTester $tester)
    {
        $validated = $request->validate([
            'service' => ['required', 'string', 'in:'.implode(',', array_keys(IntegrationTester::SERVICES))],
        ]);

        $result = $tester->test($validated['service']);

        return back()->with('integration_result', [
            'ok' => $result['ok'],
            'service' => IntegrationTester::SERVICES[$validated['service']],
            'message' => $result['message'],
        ]);
    }

    /** Pending jobs, or null when the queue is not database-backed. */
    protected function queueDepth(): ?int
    {
        if (config('queue.default') !== 'database') {
            return null;
        }

        try {
            return DB::table('jobs')->count();
        } catch (\Throwable) {
            return null;
        }
    }
}
