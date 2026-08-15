@extends('layouts.app')

@section('title', 'Privacy Policy — Guaranteed Correct')

@section('content')
<x-legal-shell eyebrow="Legal" heading="Privacy Policy"
    :operator="$operator" :address="$address" :updated-at="$updatedAt">

    <p>
        This policy explains what {{ $operator }} collects when you use Guaranteed Correct, why, and what you can
        do about it. It covers the website and the Telegram picks bot.
    </p>

    <h2>1. What we collect</h2>

    <h3>Account data</h3>
    <ul>
        <li>Your name and email address, and a one-way hash of your password &mdash; we never store the
            password itself.</li>
        <li>Your account tier, and whether your email address has been verified.</li>
    </ul>

    <h3>Subscription data</h3>
    <ul>
        <li>Which gateway you paid through, the reference it issued, your plan, status, and renewal date.</li>
        <li>We do <strong>not</strong> receive or store card numbers, bank details, or PayPal credentials.
            Those stay with Flutterwave and PayPal, who process the payment as independent controllers
            under their own privacy policies.</li>
    </ul>

    <h3>Usage data</h3>
    <ul>
        <li>Pages visited, with the URL, referring page, IP address, browser user-agent, timestamp, and
            your user id if you are signed in. This is what produces the traffic figures in the admin
            dashboard.</li>
        <li>Raw pageview records are deleted automatically after 90 days.</li>
    </ul>

    <h3>Telegram data (optional)</h3>
    <ul>
        <li>If you connect the bot we store your Telegram chat id and your notification preference, so we
            can send your daily picks. Disconnecting deletes the chat id.</li>
    </ul>

    <h3>Cookies</h3>
    <ul>
        <li>A session cookie to keep you signed in, and a CSRF token cookie to protect forms. Both are
            strictly necessary for the site to function.</li>
        <li>If advertising is enabled on the free tier, the ad provider may set its own cookies through
            its tags. Subscribers are served no ads and no ad tags at all.</li>
    </ul>

    <h2>2. Why we use it</h2>
    <ul>
        <li><strong>To provide the Service</strong> &mdash; authenticate you, decide what your tier
            unlocks, deliver picks. (Performance of our contract with you.)</li>
        <li><strong>To take payment</strong> and manage renewals, retries and cancellations. (Contract.)</li>
        <li><strong>To keep accounts secure</strong> &mdash; rate limiting, lockout after repeated failed
            sign-ins, fraud checks. (Legitimate interests.)</li>
        <li><strong>To understand traffic</strong> in aggregate and improve the product. (Legitimate
            interests.)</li>
        <li><strong>To send service email</strong> &mdash; verification and password reset. (Contract.)</li>
    </ul>

    <h2>3. Who we share it with</h2>
    <p>We do not sell your personal data. We share only what each provider needs to do its job:</p>
    <ul>
        <li><strong>Flutterwave</strong> and <strong>PayPal</strong> &mdash; to process subscriptions.</li>
        <li><strong>Telegram</strong> &mdash; only if you connect the bot.</li>
        <li><strong>Our email provider</strong> &mdash; to deliver verification and reset messages.</li>
        <li><strong>Our hosting provider</strong> &mdash; which stores the data on our behalf.</li>
        <li>Law enforcement or regulators where we are legally required to.</li>
    </ul>
    <p>
        Fixture and result data comes from third-party football data providers. That flow is one-way &mdash;
        we receive match data from them and send them nothing about you.
    </p>
    <p>
        The AI model that writes match previews receives fixture statistics only. Your personal data is
        never included in those requests.
    </p>

    <h2>4. How long we keep it</h2>
    <ul>
        <li>Account and subscription records: for as long as your account exists, then up to six years
            where we need them for tax and accounting.</li>
        <li>Pageview records: 90 days.</li>
        <li>Security rate-limit counters: hours to days.</li>
    </ul>

    <h2>5. Your rights</h2>
    <p>
        Depending on where you live, you may have the right to access a copy of your data, correct it,
        delete it, object to or restrict processing, or receive it in a portable format. Under the
        Nigeria Data Protection Act and, where applicable, the UK/EU GDPR, you may also complain to your
        data protection authority.
    </p>
    <p>
        To exercise any of these, email <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> from
        your account address. We respond within 30 days. Deleting your account removes your personal data,
        except records we must retain for accounting.
    </p>

    <h2>6. Security</h2>
    <p>
        Traffic is served over HTTPS, passwords are hashed with bcrypt, payment webhooks are signature-
        verified, and administrative areas are access-controlled. No system is perfectly secure, and we
        will notify you and the relevant authority if a breach affects your data.
    </p>

    <h2>7. International transfers</h2>
    <p>
        Our providers may process data outside {{ $jurisdiction }}. Where that happens we rely on the
        provider's own approved transfer safeguards.
    </p>

    <h2>8. Children</h2>
    <p>
        The Service is 18+. We do not knowingly collect data from anyone under 18; if we learn we have,
        we delete it.
    </p>

    <h2>9. Changes and contact</h2>
    <p>
        We will post any update here and change the date above. Questions, requests, or complaints:
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
    </p>

</x-legal-shell>
@endsection
