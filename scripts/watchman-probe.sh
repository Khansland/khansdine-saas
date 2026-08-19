#!/usr/bin/env bash
#
# BLACK-BOX PROBE FOR THE DEAD-MAN'S SWITCH.
#
# This console has no PHPUnit harness, and its web process cannot autoload the
# shared package (open_basedir is this deployment and nothing else), so the
# endpoint's few lines cannot be unit-tested from the aquaculture suite the way
# the alert rules are. This script is the substitute, and it is the honest one:
# it drives the real endpoint over real HTTP and asserts the boundary, the
# never-reported case and - the part that matters most - that the body leaks
# nothing.
#
#   scripts/watchman-probe.sh
#
# ── ★ TWO SIGNALS SINCE 2026-08-19 (report watch-the-watcher) ────────────────
# The switch used to answer for the site check alone. It now answers for the
# BACKUPS as well, so that one monitored URL covers the whole chain. The backup
# verdict is a file aqua:backup-watch writes from the OTHER application: its
# mtime is when the verdict was reached, its contents are how many seconds that
# verdict is good for. Nothing in this repository decides whether a backup is
# healthy - that argument, and its three thresholds, live in aqua:backup-watch
# where they are tested.
#
# It never fabricates a backup failure. It moves a timestamp and writes a
# number into a heartbeat file, and it puts both files back exactly as it found
# them on the way out, always.
set -uo pipefail
cd "$(dirname "$0")/.."

HB="$(pwd)/storage/app/site-check-heartbeat"          # the site check
BK="$(pwd)/storage/app/backup-heartbeat"              # the backup verdict
URL="https://saas.khansdine.com.bd/health/watchman"
THRESHOLD=900
FAIL=0

[ -f "$HB" ] || { echo "no site-check heartbeat; run saas:site-check first"; exit 2; }
[ -f "$BK" ] || { echo "no backup verdict; run aqua:backup-watch first"; exit 2; }

HB_ORIG=$(stat -c %Y "$HB")
BK_ORIG=$(stat -c %Y "$BK")
BK_BODY=$(cat "$BK")
SHELF=$BK_BODY                                        # whatever the watcher said

restore () {
    [ -f "$HB.probe" ] && mv "$HB.probe" "$HB"
    [ -f "$BK.probe" ] && mv "$BK.probe" "$BK"
    printf '%s' "$BK_BODY" > "$BK"
    touch -d "@$HB_ORIG" "$HB"
    touch -d "@$BK_ORIG" "$BK"
}
trap restore EXIT

# age <file> <seconds-ago>|none    — "none" hides the file for one probe
age () {
    if [ "$2" = none ]; then mv "$1" "$1.probe"
    else touch -d "@$(( $(date +%s) - $2 ))" "$1"; fi
}

# ⚠ ONE REQUEST, NOT TWO, AND THE REASON IS THE THROTTLE ITSELF.
# This used to fetch the body and the status code with two separate curls,
# which cost ~32 requests a run against the endpoint's own 30-per-minute
# limit: the probe could exhaust the throttle it is testing and then report
# a wall of 429s as if the switch were broken. It did exactly that on
# 2026-08-19 when run twice in a minute. One request also removes a subtler
# fault - the body and the code no longer come from two different responses.
fetch () {
    local R
    R=$(curl -sk --resolve saas.khansdine.com.bd:443:127.0.0.1 -w '\n%{http_code}' "$URL")
    CODE=${R##*$'\n'}
    BODY=${R%$'\n'*}
}

# expect <site-age> <backup-age> <backup-body> <code> <regex> <label>
expect () {
    printf '%s' "$3" > "$BK"
    age "$HB" "$1"; age "$BK" "$2"
    fetch
    [ "$1" = none ] && mv "$HB.probe" "$HB"
    [ "$2" = none ] && mv "$BK.probe" "$BK"
    if [ "$CODE" = "$4" ] && [[ "$BODY" =~ $5 ]]; then
        printf '  ok    %-42s -> %s %s\n' "$6" "$CODE" "$BODY"
    else
        printf '  FAIL  %-42s -> %s %s   (wanted %s matching %s)\n' "$6" "$CODE" "$BODY" "$4" "$5"; FAIL=1
    fi
}

echo "watchman probe: site check ${THRESHOLD}s, backup shelf life ${SHELF}s"

echo "  -- the site check, the backup verdict fresh and good --"
expect 0                0 "$SHELF" 200 '^OK [0-9]+$'    "both fresh"
expect $((THRESHOLD-1)) 0 "$SHELF" 200 '^OK [0-9]+$'    "one second inside the site threshold"
expect $((THRESHOLD+1)) 0 "$SHELF" 503 '^STALE [0-9]+$' "one second outside it"
expect 172800           0 "$SHELF" 503 '^STALE [0-9]+$' "site check two days stale"
expect none             0 "$SHELF" 503 '^STALE -1$'     "site check never ran"

echo "  -- ★ the backups, the site check fresh throughout --"
expect 0 $((SHELF-5))  "$SHELF" 200 '^OK [0-9]+$'    "verdict five seconds inside its shelf life"
expect 0 $((SHELF+60)) "$SHELF" 503 '^STALE [0-9]+$' "★ the watcher stopped: verdict aged out"
expect 0 0             "0"      503 '^STALE [0-9]+$' "★ a backup job failed: expired the same second"
expect 0 none          "$SHELF" 503 '^STALE -1$'     "no verdict has ever been stamped"
expect 0 0             "yes"    503 '^STALE -1$'     "verdict unreadable is not verdict healthy"

# THE LEAK TEST. Two tokens, ever. Anything else is a gift to a stranger.
echo "  -- what a stranger can learn --"
expect 0 0 "$SHELF" 200 '^OK [0-9]+$' "healthy body for the leak test"
if [[ "$BODY" =~ ^(OK|STALE)\ -?[0-9]+$ ]] && [ "$(echo "$BODY" | wc -w)" -eq 2 ]; then
    echo "  ok    body is exactly two tokens             -> '$BODY'"
else
    echo "  FAIL  body is not two bare tokens            -> '$BODY'"; FAIL=1
fi
for LEAK in khansdine aquaculture picnic hisab Laravel PHP nginx site backup b2 sql gz storage binlog; do
    if echo "$BODY" | grep -qi -- "$LEAK"; then echo "  FAIL  body mentions '$LEAK'"; FAIL=1; fi
done

# ⚠ AND THE STALE BODY MUST LEAK NOTHING EITHER. It is the response a stranger
# is most likely to catch, because it is the one that persists.
expect 0 0 "0" 503 '^STALE [0-9]+$' "broken body for the leak test"
for LEAK in khansdine aquaculture picnic hisab Laravel PHP nginx site backup b2 sql gz storage binlog; do
    if echo "$BODY" | grep -qi -- "$LEAK"; then echo "  FAIL  stale body mentions '$LEAK'"; FAIL=1; fi
done
[ "$(echo "$BODY" | wc -w)" -eq 2 ] && echo "  ok    stale body is two tokens too          -> '$BODY'" \
    || { echo "  FAIL  stale body is not two tokens"; FAIL=1; }

# THE PROPERTIES THAT MAKE IT WORK WHEN NOTHING ELSE DOES, read off the wire.
echo "  -- the properties, from the response and not from the routes file --"
restore
H=$(curl -sk --resolve saas.khansdine.com.bd:443:127.0.0.1 -D- -o /dev/null "$URL")
if echo "$H" | grep -qi '^set-cookie'; then
    echo "  FAIL  it sets a cookie: a session was started"; FAIL=1
else
    echo "  ok    no set-cookie: no session, so no session database write"
fi
if echo "$H" | grep -qi '^x-ratelimit-limit'; then
    echo "  ok    throttled                              -> $(echo "$H" | grep -i '^x-ratelimit-limit' | tr -d '\r')"
else
    echo "  FAIL  no rate-limit header: the throttle is gone"; FAIL=1
fi

# ── ★★ AND THE ONE THIS PROBE EXISTS FOR: NO MySQL ON THE ROUTE ─────────────
# The switch answers when other things are broken, so it must not ask MySQL
# anything. That was asserted twice from the handler and was false both times:
# CACHE_STORE is database here, so `throttle:30,1` opened a connection on every
# request. The handler was never the problem - the ROUTE was, and a property of
# a route is a property of its whole middleware stack.
#
# So it is measured here, at the wire, and it is the check that would have
# caught it: the server's global Connections counter across five requests. The
# READER opens one connection itself each time it is called, which is why the
# expected raw delta is 1 and not 0. Five requests, not ten, because this probe
# must stay well inside the endpoint's own 30-a-minute limit.
echo "  -- ★ the route asks MySQL nothing (R-0219) --"
conns () {
    php -r "
        require 'vendor/autoload.php';
        \$a = require 'bootstrap/app.php';
        \$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        echo Illuminate\Support\Facades\DB::select(\"SHOW GLOBAL STATUS LIKE 'Connections'\")[0]->Value;
    " 2>/dev/null
}
# ⚠ The counter is the SERVER's, and this MySQL serves the ledger and seven
# other sites, so a busy second shows up here as connections this endpoint did
# not open. The per-user counter in performance_schema would isolate it and
# this app's database user is not granted it - widening that grant is not a
# thing a probe gets to decide. So an idle window of the same length is
# measured first and printed, and the assertion is deliberately coarse: FEWER
# THAN ONE CONNECTION PER REQUEST. Before R-0219 it was exactly one per
# request - five requests, five connections - so the threshold is pinned to
# the size of the defect and not to zero, and background noise cannot fail it.
I0=$(conns); sleep 2; I1=$(conns); NOISE=$(( I1 - I0 - 1 ))
C0=$(conns)
for _ in 1 2 3 4 5; do fetch; done
C1=$(conns)
DELTA=$(( C1 - C0 - 1 ))          # -1 for the reader's own second connection
if [ "$DELTA" -lt 5 ]; then
    echo "  ok    5 requests opened $DELTA MySQL connections (idle control $NOISE, threshold 5)"
else
    echo "  FAIL  5 requests opened $DELTA MySQL connections (idle control $NOISE) - one per request: the limiter is back on the database"; FAIL=1
fi

[ $FAIL -eq 0 ] && echo "watchman probe: PASS" || echo "watchman probe: FAIL"
exit $FAIL
