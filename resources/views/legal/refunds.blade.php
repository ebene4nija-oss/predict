@extends('layouts.app')

@section('title', 'Refund & Cancellation Policy — Guaranteed Correct')

@section('content')
<x-legal-shell eyebrow="Legal" heading="Refund &amp; Cancellation Policy"
    :operator="$operator" :address="$address" :updated-at="$updatedAt">

    <p>
        Guaranteed Correct is a monthly subscription, charged in advance. This page sets out how to cancel and
        when we refund. It forms part of the <a href="{{ route('legal.terms') }}">Terms of Service</a>.
    </p>

    <h2>Cancelling</h2>
    <ul>
        <li>Cancel any time from your <a href="{{ route('account') }}">account page</a> &mdash; no notice
            period, no cancellation fee, no phone call.</li>
        <li>Cancelling stops the next charge. Your access continues to the end of the period you have
            already paid for, then drops to the free tier.</li>
        <li>Cancelling a PayPal subscription from inside PayPal also works; it may take a few minutes for
            our records to catch up.</li>
    </ul>

    <h2>When we refund</h2>
    <p>We refund in full in these cases:</p>
    <ul>
        <li><strong>Duplicate charge</strong> &mdash; billed twice for the same period.</li>
        <li><strong>Charged after cancelling</strong> &mdash; a renewal taken after you cancelled.</li>
        <li><strong>Unauthorised charge</strong> &mdash; you did not authorise the payment, confirmed with
            the gateway.</li>
        <li><strong>Sustained outage</strong> &mdash; paid features were unavailable for more than 72
            consecutive hours in a billing period; refunded pro rata for the days lost.</li>
        <li><strong>Billed in error</strong> by us in any other way.</li>
    </ul>

    <h2>When we do not refund</h2>
    <p>
        We do not refund on the basis of prediction outcomes. Predictions are probabilities, not
        guarantees, and a losing week is not a service failure &mdash; this is the single most important
        thing to understand before subscribing. Specifically, we do not refund for:
    </p>
    <ul>
        <li>Picks that did not win, a losing run, or money lost on bets you placed.</li>
        <li>Published accuracy being lower than you hoped, or lower than a previous period.</li>
        <li>Simply not having used the Service during a period you paid for.</li>
        <li>Fixtures postponed, abandoned, or altered by the competition organiser.</li>
        <li>Periods already elapsed, where you cancel mid-month.</li>
    </ul>

    <h2>New subscribers</h2>
    <p>
        If you subscribe and change your mind within <strong>14 days</strong>, and you have not viewed any
        paid pick in that time, email us for a full refund of your first payment. Once paid content has
        been accessed the service has been delivered, and the exclusions above apply.
    </p>

    <h2>How to request one</h2>
    <p>
        Email <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> from your account address with
        the payment reference and what happened. We reply within 5 business days. Approved refunds go back
        to the original payment method through the gateway that took the payment &mdash; typically 5&ndash;10
        business days for Flutterwave and 3&ndash;5 for PayPal, depending on your bank.
    </p>

    <h2>Chargebacks</h2>
    <p>
        Please contact us before raising a chargeback &mdash; almost everything is faster to fix directly.
        Accounts with an open chargeback are suspended until it is resolved.
    </p>

    <h2>Your statutory rights</h2>
    <p>
        Nothing in this policy removes any refund right you have under the consumer law of
        {{ $jurisdiction }} or of your own country.
    </p>

</x-legal-shell>
@endsection
