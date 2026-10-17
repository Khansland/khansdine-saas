<?php

namespace Tests\Feature;

use Illuminate\Contracts\Log\ContextLogProcessor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

/**
 * ★★ EVERY LOG LINE STRIPS VALUES, HOWEVER IT WAS BUILT — R-0591 (AG-0084).
 *
 * R-0589's handler covers exceptions that go through report(). About 25 app
 * calls build their own line instead - Log::warning('... failed: ' .
 * $e->getMessage()) - and a QueryException's message carries its bindings.
 * RedactsLogRecords (subdomain-shared; saas registers it itself in AppServiceProvider because it lists the package in dont-discover) is a Monolog processor on every channel: inside SQL text,
 * values(...) groups and bound literals become ?, and anywhere in a record
 * e-mail addresses become [address] and Bangladeshi mobiles [phone].
 *
 * ⛔ AND NOTHING ELSE: ids, amounts, dates, batch codes, tank names, the time
 * and the level come through untouched - the control below.
 *
 * The host app's TestCase nulls file channels (R-0587), so they are pointed at
 * a private temporary directory here, and each test asserts its line IS there.
 */
class EveryLogLineStripsValuesTest extends TestCase
{
    private string $logDir;

    private const NAME = 'Shirin R0591 Akter';
    private const PHONE = '01755508899';
    private const EMAIL = 'r0591.person@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = sys_get_temp_dir() . '/r0591-logs-' . bin2hex(random_bytes(6));
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

    public function test_a_line_built_from_a_query_exceptions_message_carries_no_value(): void
    {
        try {
            DB::table('cc_r0591_people')->insert(['name' => self::NAME, 'phone' => self::PHONE, 'email' => self::EMAIL]);
            $this->fail('the insert was meant to fail');
        } catch (QueryException $e) {
            $this->assertStringContainsString(self::NAME, $e->getMessage(), 'precondition: the raw message carries the value');
            Log::error('storeFeed transaction failed: ' . $e->getMessage());
        }

        // And MySQL's own wording, where the driver text quotes the value.
        $pdo = new \PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '" . self::EMAIL . "' for key 'parties_email_unique'");
        $mysql = new QueryException('mysql', 'insert into `parties` (`name`, `phone`, `email`) values (?, ?, ?)',
            [self::NAME, self::PHONE, self::EMAIL], $pdo);
        Log::warning("AccountingBridge: journal failed for sale#77: " . $mysql->getMessage());

        $text = $this->logged();
        $this->assertStringContainsString('storeFeed transaction failed:', $text, 'nothing was logged, so nothing below would prove anything');
        $this->assertStringContainsString('values (?, ?, ?)', $text);
        $this->assertStringContainsString("Duplicate entry '?' for key 'parties_email_unique'", $text);
        $this->assertStringContainsString('sale#77', $text);
        $this->assertNoValues('getMessage line');
    }

    public function test_a_context_array_with_an_email_and_a_phone_carries_neither(): void
    {
        Log::warning('SMS provider refused', [
            'customer' => ['email' => self::EMAIL, 'phone' => self::PHONE],
            'note' => 'call back on +880' . substr(self::PHONE, 1) . ' or 880' . substr(self::PHONE, 1),
        ]);

        $text = $this->logged();
        $this->assertStringContainsString('SMS provider refused', $text);
        $this->assertStringContainsString('[address]', $text);
        $this->assertStringContainsString('[phone]', $text);
        $this->assertNoValues('context');
    }

    /** ★ C2: what a log line is FOR comes through byte-identical. */
    public function test_ids_amounts_dates_batch_codes_tank_names_time_and_level_are_untouched(): void
    {
        $message = 'Blocked feed consumption: Mega Grower 3mm requested 125.5kg but only 80.25kg available on 2026-10-17 (feed #9921) batch B-2026-014 in Tank 7, JE 48213 reversed by JE 48214, amount 1,250,000.50 taka, party=1452 balance=-3200.00';
        $context = ['batch_id' => 48213, 'batch_code' => 'B-2026-014', 'tank' => 'Tank 7', 'amount' => 1250000.5,
            'date' => '2026-10-17', 'at' => '2026-10-17 07:00:00', 'ids' => [1, 22, 333], 'pct' => '12.5%'];
        $record = new LogRecord(new \DateTimeImmutable('2026-10-17 07:12:13.456789'), 'production', Level::Warning, $message, $context);

        $out = $this->app->make(ContextLogProcessor::class)($record);

        $this->assertSame($message, $out->message);
        $this->assertSame($context, $out->context);
        $this->assertSame(Level::Warning, $out->level);
        $this->assertEquals($record->datetime, $out->datetime);

        // And through a real channel: the written line holds the message and context unchanged.
        Log::warning($message, $context);
        $this->assertStringContainsString($message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->logged());
        $this->assertStringContainsString('.WARNING: ' . $message, $this->logged());
    }

    /** The processor is what the container hands every channel, so it is provably registered. */
    public function test_the_container_hands_every_channel_the_redacting_processor(): void
    {
        $this->assertInstanceOf(\Khansdine\SubdomainShared\Logging\RedactsLogRecords::class,
            $this->app->make(ContextLogProcessor::class));
    }
}
