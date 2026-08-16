<?php

namespace App\Services;

use Khansdine\SubdomainShared\Support\SiteVerdict;

/**
 * ASK EVERY SITE IN THE LIST WHETHER IT IS THERE.
 *
 * Report site-uptime-watch. The rules for reading an answer live in
 * Khansdine\SubdomainShared\Support\SiteVerdict, where the aquaculture suite
 * can run negative controls against them. This class does the asking, and the
 * one thing it must never do is be the reason something breaks.
 *
 * ── BOTH WAYS ROUND, RECORDED SEPARATELY ──────────────────────────────────
 * EDGE   the URL as a customer types it, through Cloudflare. This is what
 *        Habib's customer actually experiences, and it is the only probe that
 *        can catch a DNS failure or an edge outage.
 * ORIGIN the same path with the name resolved to this box, bypassing the edge
 *        entirely. This is the app's own answer.
 *
 * Both, because "origin fine, edge broken" and "edge cached, origin broken"
 * are different emergencies and one number cannot tell them apart. Two
 * requests per site every five minutes is 0.1 requests a second across the
 * whole fleet — nothing.
 *
 * ── IT CANNOT TAKE ANYTHING DOWN ──────────────────────────────────────────
 * A 5 second connect timeout and a 10 second total per request; every probe in
 * its own try/catch so one unreachable name never abandons the rest; HEAD-like
 * behaviour is NOT used because the byte floor needs a body; and the whole run
 * is bounded by the per-request timeouts times the entry count.
 */
class SiteCheck
{
    /**
     * @return array{checked_at:string, could_not_check:bool, sites:array<int, array<string, mixed>>}
     */
    public function run(?array $only = null): array
    {
        $cfg = config('sites');
        $entries = $cfg['entries'] ?? [];
        $rows = [];

        foreach ($entries as $e) {
            if ($only && ! in_array($e['key'], $only, true)) {
                continue;
            }

            $edge = $this->probe($e['url'], null, $cfg);
            $verdict = SiteVerdict::judge($edge['status'], $edge['bytes'],
                (int) $e['expect'], (int) $e['min_bytes'], $edge['error']);

            $row = [
                'key' => $e['key'],
                'url' => $e['url'],
                'note' => $e['note'] ?? null,
                'expect' => (int) $e['expect'],
                'min_bytes' => (int) $e['min_bytes'],
                'state' => $verdict['state'],
                'why' => $verdict['why'],
                'status' => $edge['status'],
                'bytes' => $edge['bytes'],
                'ms' => $edge['ms'],
            ];

            if (! empty($e['origin'])) {
                $o = $this->probe($e['url'], (string) ($cfg['origin_ip'] ?? '127.0.0.1'), $cfg);
                $ov = SiteVerdict::judge($o['status'], $o['bytes'],
                    (int) $e['expect'], (int) $e['min_bytes'], $o['error']);
                $row['origin'] = [
                    'state' => $ov['state'], 'why' => $ov['why'],
                    'status' => $o['status'], 'bytes' => $o['bytes'], 'ms' => $o['ms'],
                ];
            }

            $rows[] = $row;
        }

        return [
            'checked_at' => now()->toIso8601String(),
            // ★ NOT "everything is down". See SiteVerdict::runCouldNotCheck.
            'could_not_check' => SiteVerdict::runCouldNotCheck($rows),
            'sites' => $rows,
        ];
    }

    /**
     * One request. Never throws: a probe that dies is a probe that reported
     * nothing, which is a state the caller knows how to render.
     *
     * @return array{status:?int, bytes:?int, ms:?int, error:?string}
     */
    private function probe(string $url, ?string $resolveTo, array $cfg): array
    {
        try {
            $ch = curl_init();
            $opts = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,   // a 302 is an answer, not a step
                CURLOPT_CONNECTTIMEOUT => (int) ($cfg['connect_timeout'] ?? 5),
                CURLOPT_TIMEOUT => (int) ($cfg['timeout'] ?? 10),
                CURLOPT_USERAGENT => 'khansdine-site-check/1.0',
                CURLOPT_ENCODING => '',            // accept compression, count decoded bytes
            ];
            if ($resolveTo !== null) {
                $host = parse_url($url, PHP_URL_HOST);
                $port = (parse_url($url, PHP_URL_SCHEME) === 'http') ? 80 : 443;
                $opts[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $resolveTo];
                // The origin answers for many names on one certificate; going
                // straight at it by IP is not a certificate test and must not
                // be turned into one.
                $opts[CURLOPT_SSL_VERIFYPEER] = false;
                $opts[CURLOPT_SSL_VERIFYHOST] = 0;
            }
            curl_setopt_array($ch, $opts);

            $started = microtime(true);
            $body = curl_exec($ch);
            $ms = (int) round((microtime(true) - $started) * 1000);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_errno($ch) ? curl_error($ch) : null;
            curl_close($ch);

            if ($status === 0) {
                // curl answered with no HTTP status at all: DNS, refused,
                // timeout. Ignorance, not a verdict.
                return ['status' => null, 'bytes' => null, 'ms' => $ms, 'error' => $err ?: 'no response'];
            }

            return ['status' => $status, 'bytes' => is_string($body) ? strlen($body) : 0,
                    'ms' => $ms, 'error' => $err];
        } catch (\Throwable $e) {
            return ['status' => null, 'bytes' => null, 'ms' => null,
                    'error' => get_class($e) . ': ' . $e->getMessage()];
        }
    }
}
