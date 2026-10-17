<?php

namespace Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * ★★ AN EXCEPTION IN A LOG CARRIES ITS SHAPE, NEVER THE VALUES — R-0589 (AG-0083).
 *
 * A QueryException's message is its SQL with the bindings substituted, and
 * MySQL's own text quotes the offending value. A mail transport's names the
 * recipient. RedactsLoggedExceptions (subdomain-shared), registered in saas by bootstrap/app.php because saas lists the package in dont-discover,
 * logs the SQL with its placeholders, the codes and the table instead.
 *
 * The host app's TestCase sends every file channel to a null handler (R-0587),
 * so the channels are pointed at a private temporary directory here, never at
 * storage/logs, and each test asserts its line IS written before asserting what
 * is absent from it.
 */
class AnExceptionLogCarriesNoValuesTest extends TestCase
{
    private string $logDir;

    private const NAME = 'Shirin R0589 Akter';
    private const PHONE = '01755508899';
    private const EMAIL = 'r0589.person@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = sys_get_temp_dir() . '/r0589-logs-' . bin2hex(random_bytes(6));
        mkdir($this->logDir, 0700);
        $file = require config_path('logging.php');
        foreach ($file['channels'] as $name => $channel) {
            if (isset($channel['path'])) {
                $channel['path'] = $this->logDir . '/' . basename($channel['path']);
                config(["logging.channels.{$name}" => $channel]);
            }
        }
        $this->app->forgetInstance('log');
        Log::clearResolvedInstance('log');
    }

    protected function tearDown(): void
    {
        foreach ((array) glob($this->logDir . '/*') as $f) {
            @unlink($f);
        }
        @rmdir($this->logDir);

        parent::tearDown();
    }

    private function logged(): string
    {
        $out = '';
        foreach ((array) glob($this->logDir . '/*') as $f) {
            $out .= file_get_contents($f);
        }

        return $out;
    }

    private function assertNoValues(string $what): void
    {
        foreach ([self::NAME, self::PHONE, self::EMAIL, '1755508899'] as $v) {
            $this->assertStringNotContainsString($v, $this->logged(), "{$what}: a value reached the log");
        }
    }

    public function test_a_failed_insert_logs_its_sql_with_placeholders_and_no_values(): void
    {
        try {
            DB::table('cc_r0589_people')->insert(['name' => self::NAME, 'phone' => self::PHONE, 'email' => self::EMAIL]);
            $this->fail('the insert was meant to fail');
        } catch (QueryException $e) {
            $this->assertStringContainsString(self::EMAIL, $e->getMessage(), 'precondition: the raw message carries the value');
            report($e);
        }

        $text = $this->logged();
        $this->assertStringContainsString('cc_r0589_people', $text, 'nothing was logged, so nothing below would prove anything');
        $this->assertStringContainsString('values (?, ?, ?)', $text);
        $this->assertStringContainsString('"table":"cc_r0589_people"', $text);
        $this->assertNoValues('query exception');
    }

    public function test_the_drivers_quoted_value_is_redacted_and_the_key_name_kept(): void
    {
        // MySQL's own wording for a duplicate: the value is quoted in the driver text.
        $pdo = new \PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '" . self::EMAIL . "' for key 'parties_email_unique'");
        $pdo->errorInfo = ['23000', 1062, "Duplicate entry '" . self::EMAIL . "' for key 'parties_email_unique'"];
        $e = new QueryException('mysql', 'insert into `parties` (`name`, `phone`, `email`) values (?, ?, ?)',
            [self::NAME, self::PHONE, self::EMAIL], $pdo);
        $this->assertStringContainsString(self::NAME, $e->getMessage(), 'precondition');

        report($e);

        $text = $this->logged();
        $this->assertStringContainsString("Duplicate entry '?' for key 'parties_email_unique'", $text);
        $this->assertStringContainsString('"sqlstate":"23000"', $text);
        $this->assertStringContainsString('"driver_code":1062', $text);
        $this->assertStringContainsString('"table":"parties"', $text);
        $this->assertNoValues('duplicate entry');
    }

    public function test_a_mail_failure_logs_no_address(): void
    {
        report(new TransportException('Expected response code "250" but got code "550", with message "550 5.1.1 <'
            . self::EMAIL . '>: Recipient address rejected".'));

        $text = $this->logged();
        $this->assertStringContainsString('Recipient address rejected', $text);
        $this->assertStringContainsString('[address]', $text);
        $this->assertNoValues('mail transport');
    }

    public function test_what_the_user_sees_is_unchanged_and_other_exceptions_log_as_before(): void
    {
        $e = new QueryException('mysql', 'select 1', [], new \PDOException('x'));
        $response = $this->app[ExceptionHandler::class]->render(Request::create('/'), $e);
        $this->assertSame(500, $response->getStatusCode());

        report(new \RuntimeException('R0589 plain exception ' . self::EMAIL));
        $this->assertStringContainsString('R0589 plain exception ' . self::EMAIL, $this->logged(),
            'an exception with no SQL and no transport must be logged exactly as before');
    }
}
