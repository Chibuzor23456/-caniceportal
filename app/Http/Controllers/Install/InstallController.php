<?php

namespace App\Http\Controllers\Install;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EnvFileWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PDO;
use PDOException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class InstallController extends Controller
{
    public function showDatabase(): View
    {
        return view('install.database');
    }

    public function testDatabase(Request $request): JsonResponse
    {
        $data = $request->validate($this->databaseRules());

        try {
            $this->connect($data);

            return response()->json(['success' => true, 'message' => 'Connected successfully.']);
        } catch (PDOException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function saveDatabase(Request $request): RedirectResponse|View
    {
        $data = $request->validate($this->databaseRules());

        EnvFileWriter::set([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['host'],
            'DB_PORT' => $data['port'],
            'DB_DATABASE' => $data['database'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => $data['password'],
        ]);

        config(['database.connections.mysql' => array_merge(
            config('database.connections.mysql'),
            [
                'host' => $data['host'],
                'port' => $data['port'],
                'database' => $data['database'],
                'username' => $data['username'],
                'password' => $data['password'],
            ],
        )]);
        config(['database.default' => 'mysql']);
        DB::purge('mysql');

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            return view('install.database', ['migrationError' => $e->getMessage()]);
        }

        $request->session()->put('install.db_done', true);

        return redirect()->route('install.mail');
    }

    public function showMail(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('install.db_done')) {
            return redirect()->route('install.database');
        }

        return view('install.mail');
    }

    public function testMail(Request $request): JsonResponse
    {
        $data = $request->validate($this->mailRules());

        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $data['host'];
            $mailer->Port = (int) $data['port'];
            $mailer->SMTPSecure = $data['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            if (! empty($data['username'])) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $data['username'];
                $mailer->Password = $data['password'] ?? '';
            }

            $mailer->Timeout = 10;

            if (! $mailer->smtpConnect()) {
                return response()->json(['success' => false, 'message' => $mailer->ErrorInfo ?: 'Could not connect.']);
            }

            $mailer->smtpClose();

            return response()->json(['success' => true, 'message' => 'Connected and authenticated successfully.']);
        } catch (PHPMailerException $e) {
            return response()->json(['success' => false, 'message' => $mailer->ErrorInfo ?: $e->getMessage()]);
        }
    }

    public function saveMail(Request $request): RedirectResponse
    {
        if (! $request->session()->get('install.db_done')) {
            return redirect()->route('install.database');
        }

        $data = $request->validate($this->mailRules());

        EnvFileWriter::set([
            'MAIL_MAILER' => 'smtp',
            'MAIL_SCHEME' => 'smtp',
            'MAIL_HOST' => $data['host'],
            'MAIL_PORT' => $data['port'],
            'MAIL_USERNAME' => $data['username'] ?? '',
            'MAIL_PASSWORD' => $data['password'] ?? '',
            'MAIL_FROM_ADDRESS' => $data['from_address'],
            'MAIL_FROM_NAME' => $data['from_name'],
        ]);

        $request->session()->put('install.mail_done', true);

        return redirect()->route('install.admin');
    }

    public function showAdmin(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('install.mail_done')) {
            return redirect()->route('install.mail');
        }

        return view('install.admin');
    }

    public function saveAdmin(Request $request): RedirectResponse
    {
        if (! $request->session()->get('install.mail_done')) {
            return redirect()->route('install.mail');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Admin,
            'must_change_password' => false,
        ]);

        file_put_contents(storage_path('app/installed.lock'), now()->toDateTimeString());

        $request->session()->forget(['install.db_done', 'install.mail_done']);

        return redirect()->route('admin.login')->with('status', 'Setup complete. Log in with the admin account you just created.');
    }

    private function databaseRules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'numeric'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function mailRules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'numeric'],
            'encryption' => ['required', 'in:tls,ssl'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ];
    }

    private function connect(array $data): PDO
    {
        return new PDO(
            "mysql:host={$data['host']};port={$data['port']};dbname={$data['database']}",
            $data['username'],
            $data['password'] ?? '',
            [PDO::ATTR_TIMEOUT => 10],
        );
    }
}
