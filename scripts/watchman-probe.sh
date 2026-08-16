#!/usr/bin/env bash
#
# BLACK-BOX PROBE FOR THE DEAD-MAN'S SWITCH.
#
# This console has no PHPUnit harness, and its web process cannot autoload the
# shared package (open_basedir is this deployment and nothing else), so the
# endpoint's few lines cannot be unit-tested from the aquaculture suite the way
# the alert rules are. This script is the substitute, and it is the honest one:
# it drives the real endpoint over real HTTP and asserts the boundary, the
# never-checked case and - the part that matters most - that the body leaks
# nothing.
#
#   scripts/watchman-probe.sh
#
# Restores the heartbeat's original mtime on the way out, always.
set -uo pipefail
cd "$(dirname "$0")/.."

HB="$(pwd)/storage/app/site-check-heartbeat"
URL="https://saas.khansdine.com.bd/health/watchman"
THRESHOLD=900
FAIL=0

[ -f "$HB" ] || { echo "no heartbeat file; run saas:site-check first"; exit 2; }
ORIG=$(stat -c %Y "$HB")
restore () { touch -d "@$ORIG" "$HB"; }
trap restore EXIT

probe () {
    if [ "$1" = none ]; then mv "$HB" "$HB.probe"; else touch -d "@$(( $(date +%s) - $1 ))" "$HB"; fi
    BODY=$(curl -sk --resolve saas.khansdine.com.bd:443:127.0.0.1 "$URL")
    CODE=$(curl -sk --resolve saas.khansdine.com.bd:443:127.0.0.1 -o /dev/null -w '%{http_code}' "$URL")
    [ "$1" = none ] && mv "$HB.probe" "$HB"
    echo "$CODE|$BODY"
}

expect () {
    R=$(probe "$1"); C=${R%%|*}; B=${R#*|}
    if [ "$C" = "$2" ] && [[ "$B" =~ $3 ]]; then
        printf '  ok    %-34s -> %s %s\n' "$4" "$C" "$B"
    else
        printf '  FAIL  %-34s -> %s %s   (wanted %s matching %s)\n' "$4" "$C" "$B" "$2" "$3"; FAIL=1
    fi
}

echo "watchman probe, threshold ${THRESHOLD}s"
expect 0                 200 '^OK [0-9]+$'    "fresh"
expect $((THRESHOLD-1))  200 '^OK [0-9]+$'    "one second inside the threshold"
expect $((THRESHOLD+1))  503 '^STALE [0-9]+$' "one second outside it"
expect 172800            503 '^STALE [0-9]+$' "two days stale"
expect none              503 '^STALE -1$'     "never checked"

# THE LEAK TEST. Two tokens, ever. Anything else is a gift to a stranger.
R=$(probe 0); B=${R#*|}
if [[ "$B" =~ ^(OK|STALE)\ -?[0-9]+$ ]] && [ "$(echo "$B" | wc -w)" -eq 2 ]; then
    echo "  ok    body is exactly two tokens        -> '$B'"
else
    echo "  FAIL  body is not two bare tokens       -> '$B'"; FAIL=1
fi
for LEAK in khansdine aquaculture picnic hisab Laravel PHP nginx site; do
    if echo "$B" | grep -qi -- "$LEAK"; then echo "  FAIL  body mentions '$LEAK'"; FAIL=1; fi
done
[ $FAIL -eq 0 ] && echo "watchman probe: PASS" || echo "watchman probe: FAIL"
exit $FAIL
