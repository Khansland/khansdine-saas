@extends('presentation._layout', ['lang' => 'en'])
@section('doctitle', 'Aquaculture Management System — from the pond to the ledger')
@section('docdesc', 'Complete management for fish farms: feed, growth, health, staff and the real cost per kilo — in Bengali, on a phone.')
@section('product', 'Fish Farm Management')

@section('doc')

<h1>Is your farm making money today — and from which pond?</h1>

<p class="lede">
On most farms that question is answered at the end of the season, from a notebook,
by estimate. By then nothing can be done about it. This system answers it
<strong>today</strong>, pond by pond, in taka — and every line behind the figure can
be opened and checked.
</p>

<p>
This is not a foreign product with Bengali pasted over it. It was built inside a
<strong>working fish farm</strong> in Godagari, Rajshahi, around the work that farm
actually does — in Bangladesh, in Bengali, for the problems Bangladeshi farms have.
</p>

<figure class="shotfig">
  <img src="/img/system/container-list.png" alt="{{ __('saas.shot.container_list', [], 'en') }}" width="1366" height="768">
  <figcaption>{{ __('saas.shot.container_list', [], 'en') }}</figcaption>
</figure>

<h2>What this system found on a real, working farm</h2>

<p>
Not promises. Every item below happened on a live farm, with its real numbers.
They are the strongest thing we can show you, because they are not hypothetical.
</p>

<div class="proof">
  <span class="n">A 19,037 tk electricity bill was outside the costing</span>
  <p>
    When the system priced fish, it was charging <strong>5,000 tk</strong> for
    electricity — a guess somebody typed once and everybody then forgot. The real
    June bill was <strong>19,037 tk</strong>. Every kilo of fish was being costed on
    roughly a quarter of the electricity it actually used.
  </p>
  <span class="src">Source: electricity and electricity-pbs reports, 2026-08-14</span>
</div>

<div class="proof">
  <span class="n">One pond overfed by 3,429 g/day; another underfed by 2,079 g/day</span>
  <p>
    One tilapia batch was receiving <strong>3,429 g of extra feed every day</strong> —
    about <strong>297 tk a day</strong> into the water. Another tilapia batch on the
    same farm was <strong>2,079 g a day short</strong>, so it was growing more slowly
    than it should.
  </p>
  <p>
    ★ Here is the part that matters most: <strong>in the total, the two errors very
    nearly cancelled.</strong> The net difference was only 1,477 g a day. On a summary
    sheet the feeding looked fine. Nothing but a pond-by-pond view would ever have
    caught it.
  </p>
  <span class="src">Source: feed-thesis report, 2026-08-14 (batch 32 and batch 5)</span>
</div>

<div class="proof">
  <span class="n">Four batches were being fed food they physically cannot eat</span>
  <p>
    Silver carp and bighead carp are <em>filter feeders</em> — they strain plankton
    out of the water. Pellets were being bought for them, weighed out, thrown in,
    and charged to the batch as a cost. The fish did not eat them.
  </p>
  <span class="src">Source: feed-thesis report — "Filter feeders are given pellet rations"</span>
</div>

<div class="proof">
  <span class="n">The system <em>refused</em> to publish a price</span>
  <p>
    With the true electricity cost unknown, the cost basis for those batches was
    incomplete. The system did not invent a price. It <strong>held pricing</strong>
    until the real bill was entered. Published prices did not move: 180 before, 180
    after.
  </p>
  <p class="muted">
    Software that fills the gap with a guess will happily let you sell below cost,
    and you will never know it happened.
  </p>
  <span class="src">Source: electricity-apply report — "basis_complete false ... it is a hold, not a change"</span>
</div>

<div class="proof">
  <span class="n">23 of 25 batches had never been weighed</span>
  <p>
    So the system labels those weights <strong>"estimated"</strong> rather than
    presenting them as fact. On screen you can always tell which figures were
    measured and which were inferred.
  </p>
  <span class="src">Source: feed-thesis report — "23 of 25 live batches have never been sampled at all"</span>
</div>

<div class="callout">
  <strong>What do those five have in common?</strong>
  <p style="margin-bottom:0">
    None of them is theft and none of them is laziness. Every one was happening
    <strong>out of sight</strong> on an honest, hard-working farm, because nobody was
    keeping the money side pond by pond. What this system does is keep it — and, where
    it does not know something, <em>say so</em> instead of filling the gap.
  </p>
</div>

<h2>What does one kilo of your fish actually cost?</h2>

<p>
Most farmers know the feed bill. The real cost of a kilo has a good deal more in it,
and leaving any of it out makes the answer a lie. The system charges every batch with:
</p>

<table>
  <tr><th>Cost</th><th>How it is worked out</th></tr>
  <tr><td>Feed</td><td>what actually went into that batch, at what it was bought for</td></tr>
  <tr><td>Fingerlings</td><td>the real purchase price at stocking</td></tr>
  <tr><td>Labour</td><td>monthly wages, prorated by day across active batches</td></tr>
  <tr><td>Electricity</td><td>the monthly bill, prorated by day — <em>only to containers that use it</em></td></tr>
  <tr><td>Lease</td><td>the annual lease, prorated by day to that pond</td></tr>
  <tr><td>Depreciation</td><td>aerators, pumps, concrete — over their working life</td></tr>
  <tr><td>Medicine and water treatment</td><td>what was actually applied to that pond</td></tr>
</table>

<p>
The result is an answer in one sentence: <strong>"Tilapia in pond 3 costs this much
per kilo today"</strong> — with every line behind that number available to open.
</p>

<figure class="shotfig">
  <img src="/img/system/container-cost.png" alt="{{ __('saas.shot.container_cost', [], 'en') }}" width="1366" height="768" loading="lazy">
  <figcaption>{{ __('saas.shot.container_cost', [], 'en') }}</figcaption>
</figure>

<h3>An empty pond still costs money</h3>
<p>
While a pond sits empty the lease runs and the depreciation runs. The
<strong>tenure report</strong> shows what a container has cost and returned across its
whole life, so "should we keep this pond?" is answered with a number instead of a feeling.
</p>

<figure class="shotfig">
  <img src="/img/system/tenure-report.png" alt="{{ __('saas.shot.tenure', [], 'en') }}" width="1366" height="768" loading="lazy">
  <figcaption>{{ __('saas.shot.tenure', [], 'en') }}</figcaption>
</figure>

<h2>The daily work — done the way a worker will actually do it</h2>

<p>
The best accounts in the world are useless if the person holding the feed bucket will
not record anything. So there are <strong>four ways in</strong>, and all of them are
in Bengali:
</p>

<ul>
  <li><strong>By tap</strong> — on a cheap Android phone. Big buttons, little typing.</li>
  <li><strong>By chat</strong> — "gave 5 kg of feed in pond 3" becomes an entry.</li>
  <li><strong>By voice</strong> — send a voice note and the system listens in Bengali and
      writes it down. Somebody who does not like typing can still keep records.</li>
  <li><strong>On a computer</strong> — the full screens, for the owner.</li>
</ul>

<p>What gets recorded every day:</p>
<ul>
  <li>Feeding — suggested from a <strong>researched, per-species ration</strong>, which you can override</li>
  <li>Sampling — average weight, and the growth curve that comes out of it</li>
  <li>Water quality — DO, pH, ammonia, temperature, algae</li>
  <li>Mortality — count, cause, disease and treatment</li>
  <li>Sales and purchases — posted straight into the ledger</li>
</ul>

<figure class="shotfig">
  <img src="/img/system/feed-entry.png" alt="{{ __('saas.shot.feed_entry', [], 'en') }}" width="1366" height="768" loading="lazy">
  <figcaption>{{ __('saas.shot.feed_entry', [], 'en') }}</figcaption>
</figure>

<h2>People and administration</h2>
<ul>
  <li>Attendance, leave and wages — who was here, and who worked which pond</li>
  <li>Contracts and leases — landowner, term, conditions, renewal dates</li>
  <li>Daily task lists — who does what, and what was missed</li>
  <li>Permissions by role — a worker sees only what a worker needs</li>
</ul>

<figure class="shotfig">
  <img src="/img/system/attendance.png" alt="{{ __('saas.shot.attendance', [], 'en') }}" width="1366" height="768" loading="lazy">
  <figcaption>{{ __('saas.shot.attendance', [], 'en') }}</figcaption>
</figure>

<h2>AiMama — answers in Bengali, from the farm's own numbers</h2>
<p>
AiMama is an assistant you can ask in Bengali: "which pond took the most feed last
month?" The answer comes from <strong>your own farm's records</strong> and an
<strong>approved knowledge base</strong> — not from general internet advice.
</p>
<div class="warnbox">
  <strong>Said plainly:</strong> on the public demo, AiMama's answering is
  <strong>switched off</strong>. The demo is open to anyone and every question costs
  money to answer. Everything else in the system can be driven on the demo; AiMama
  comes alive on your own farm.
</div>

<h2>The selling side</h2>
<ul>
  <li>A <strong>public page</strong> for your fish, where a buyer can see what is available</li>
  <li>A price per batch, derived from cost rather than guessed</li>
  <li>Orders come in, customers build up, invoices come out</li>
</ul>

<h2>What this system does not do — the honest list</h2>
<p>Better said on day one than argued about later:</p>
<ul>
  <li>It <strong>does not grow fish</strong>. It keeps the accounts and points at mistakes. The feeding is still yours.</li>
  <li>It is <strong>not a water sensor</strong>. You or your worker measure the water and enter it.</li>
  <li>It <strong>does not know market prices</strong>. It tells you your cost; the price is your decision.</li>
  <li>It <strong>does not diagnose disease</strong>. There is a list of signs and treatments; it is not a vet.</li>
  <li>It <strong>cannot know a weight nobody measured</strong>. If you do not sample, it says "estimated" — and that is the correct answer.</li>
  <li>It <strong>does not work fully offline</strong>. Recording needs a connection.</li>
</ul>

<h2>For the Department of Fisheries and extension officers</h2>

<p>This section is written for officers who work with farmers.</p>

<ul>
  <li>
    <strong>Record-keeping that actually happens.</strong> The hardest problem in the
    field is that books are not kept. Here the record is not <em>extra</em> work: the
    entry is made in the act of feeding.
  </li>
  <li>
    <strong>Traceability.</strong> Every batch carries its own code, the source of its
    fingerlings, its stocking date, what feed went in, what medicine was applied, and
    what was sold when — all dated.
  </li>
  <li>
    <strong>An ordinary farmer can run it.</strong> Bengali first, works on a cheap
    phone, and a farmer who cannot write can record <em>by speaking</em>.
  </li>
  <li>
    <strong>Useful for extension.</strong> Per-species feeding rates and growth curves
    are drawn from theses and FAO-grade sources, and each carries a
    <em>confidence level</em> — where the evidence is thin, the system says so rather
    than sounding certain.
  </li>
  <li>
    <strong>Built in Bangladesh.</strong> In Godagari, Rajshahi, inside a working farm,
    out of Bangladeshi conditions — not a translation of a foreign product.
  </li>
  <li>
    <strong>Every farm's data is separate.</strong> Each farm gets its own address and
    its <strong>own database</strong>. One farm cannot see another's records.
  </li>
</ul>

<h2>Try it yourself, today, at no cost</h2>

<div class="cta">
  <div class="big">The demo farm (real data, none of your risk)</div>
  <p style="margin:.5rem 0">
    <a href="https://aquademo.khansdine.com.bd">aquademo.khansdine.com.bd</a><br>
    Login: <strong>demoadmin</strong> / <strong>demoadmin</strong> (owner)<br>
    or: <strong>demouser</strong> / <strong>demouser</strong> (worker)
  </p>
  <p style="margin:.5rem 0 0">
    Change anything, delete anything, add anything — the demo puts itself back
    <strong>every 30 minutes</strong>. There is nothing you can break.
  </p>
</div>

<div class="cta">
  <div class="big">Apply for your own farm</div>
  <p style="margin:.5rem 0">
    <a href="https://saas.khansdine.com.bd/apply">saas.khansdine.com.bd/apply</a><br>
    A phone number is enough to start.
  </p>
  <p style="margin:.5rem 0 0">
    Phone: <strong>{{ config('subdomain.phone') }}</strong> &nbsp;·&nbsp;
    Contact us for pricing — it is set by the size of the farm.
  </p>
</div>

<div class="foot">
  <span class="notranslate" translate="no">Khan's Systems</span> · Fish Farm Management ·
  Digram, Safina Park, Godagari, Rajshahi<br>
  Every figure in this document is taken from a working farm's real accounts and can be checked.
</div>

@endsection
