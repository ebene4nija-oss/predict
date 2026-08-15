@extends('layouts.app')

@section('title', 'Terms of Service — Guaranteed Correct')

@section('content')
<x-legal-shell eyebrow="Legal" heading="Terms of Service"
    :operator="$operator" :address="$address" :updated-at="$updatedAt">

    <p>
        These terms govern your use of Guaranteed Correct (the &ldquo;Service&rdquo;), operated by {{ $operator }}.
        By creating an account, subscribing, or otherwise using the Service you agree to them. If you do
        not agree, do not use the Service.
    </p>

    <h2>1. Eligibility &mdash; 18+</h2>
    <p>
        You must be at least 18 years old, and old enough to lawfully view gambling-related content where
        you live. We may terminate any account we believe belongs to a minor, without refund of the
        current period. The Service is not directed at anyone under 18 and we do not knowingly collect
        their data.
    </p>

    <h2>2. What the Service is &mdash; and is not</h2>
    <p>
        Guaranteed Correct publishes statistical football predictions produced by an expected-goals model, written
        up by an AI language model, alongside opinions submitted by human contributors. Everything on the
        Service is <strong>information and entertainment only</strong>.
    </p>
    <ul>
        <li>Predictions are probabilities, not guarantees, forecasts of fact, or betting advice.</li>
        <li>Published accuracy figures describe past results. They do not predict future results.</li>
        <li>We are not a bookmaker, we do not accept or place bets, and we do not handle stakes.</li>
        <li>Any decision to stake money is yours alone, and you bear its full financial consequences.</li>
    </ul>
    <p>
        Nothing on the Service is financial, investment, or professional advice. If gambling is causing
        you harm, stop and seek support from a responsible-gambling service in your country.
    </p>

    <h2>3. Accounts</h2>
    <ul>
        <li>Give accurate registration details and keep your password confidential.</li>
        <li>You are responsible for everything done through your account.</li>
        <li>One account per person. Sharing subscriber credentials, or redistributing paid picks in bulk,
            is grounds for termination without refund.</li>
        <li>Tell us promptly at <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> if you believe
            your account has been accessed by someone else.</li>
    </ul>

    <h2>4. Subscriptions and billing</h2>
    <ul>
        <li>Paid access is a recurring monthly subscription, charged in advance through Flutterwave or
            PayPal. We do not store your card details.</li>
        <li>Your subscription renews automatically each month until cancelled.</li>
        <li>If a renewal payment fails we retry it and keep your access live for a short grace period.
            Access ends if no payment is received by the end of that period.</li>
        <li>You may cancel at any time from your account page. Cancellation stops future charges; it does
            not shorten the period you have already paid for.</li>
        <li>Prices may change. We will give notice before a change affects your renewal, and you may
            cancel rather than accept it.</li>
    </ul>
    <p>See the <a href="{{ route('legal.refunds') }}">Refund &amp; Cancellation Policy</a> for refunds.</p>

    <h2>5. Contributor picks</h2>
    <p>
        Picks labelled as coming from a human contributor are that person's own opinion, published under
        their name and clearly badged as distinct from model output. They are not verified, endorsed, or
        adopted by us. Contributors who submit picks grant us a non-exclusive licence to publish and score
        them, and confirm the picks are their own work.
    </p>

    <h2>6. Acceptable use</h2>
    <ul>
        <li>No scraping, automated bulk collection, or resale of predictions or track-record data.</li>
        <li>No attempt to bypass the paywall, probe, or interfere with the Service or its security.</li>
        <li>No use of the Service where doing so would breach your local law.</li>
    </ul>

    <h2>7. Availability</h2>
    <p>
        The Service is provided &ldquo;as is&rdquo;. We do not warrant that predictions will be accurate,
        that fixtures or results sourced from third-party data providers will be complete or correct, or
        that the Service will be uninterrupted. Scheduled and unscheduled downtime happens; it does not by
        itself entitle you to a refund.
    </p>

    <h2>8. Liability</h2>
    <p>
        To the fullest extent permitted by law, our total liability to you for any claim connected with
        the Service is limited to the subscription fees you paid us in the three months before the claim
        arose. We are not liable for gambling losses, lost profits, or any indirect or consequential loss.
        Nothing here limits liability that cannot lawfully be limited.
    </p>

    <h2>9. Suspension and termination</h2>
    <p>
        We may suspend or terminate an account that breaches these terms, that we reasonably believe is
        fraudulent, or where required by law or by our payment providers. You may close your account at
        any time by contacting us.
    </p>

    <h2>10. Changes</h2>
    <p>
        We may update these terms. Material changes will be announced on the Service before they take
        effect, and continuing to use the Service after that constitutes acceptance.
    </p>

    <h2>11. Governing law</h2>
    <p>
        These terms are governed by the laws of {{ $jurisdiction }}, and its courts have exclusive
        jurisdiction over any dispute, subject to any mandatory consumer rights in your own country.
    </p>

    <h2>12. Contact</h2>
    <p>
        Questions about these terms: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
    </p>

</x-legal-shell>
@endsection
