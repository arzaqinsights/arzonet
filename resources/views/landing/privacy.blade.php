@extends('layouts.landing')
@section('title', 'Privacy Policy — Arzonet')

@section('content')
    <section class="py-24 container">
        <div class="max-w-4xl mx-auto">
        <div class="mb-12">
            <h1 class="text-4xl font-black text-gray-900 mb-4" style="font-family:'Outfit',sans-serif;">
                Privacy Policy
            </h1>

            <p class="text-gray-500">
                Last updated: {{ date('F j, Y') }}
            </p>
        </div>

        <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed space-y-6">

            <p>
                Arzonet (“we”, “our”, or “us”) provides communication, customer engagement,
                and messaging infrastructure tools for businesses, educational organizations,
                and professional entities. This Privacy Policy explains how we collect, use,
                process, and protect information when you use our platform, applications,
                websites, APIs, and related services.
            </p>

            <h2 class="text-xl font-bold text-gray-900">1. Information We Collect</h2>

            <p>
                We may collect information including:
            </p>

            <ul>
                <li>Name, organization name, and contact details</li>
                <li>Account credentials and authentication information</li>
                <li>Billing and transaction information</li>
                <li>Domain, sender identity, and business verification details</li>
                <li>Communication content processed through our platform</li>
                <li>Usage logs, IP addresses, browser/device information, and analytics data</li>
            </ul>

            <h2 class="text-xl font-bold text-gray-900">2. How We Use Information</h2>

            <p>
                We use collected information to:
            </p>

            <ul>
                <li>Provide, maintain, and improve our services</li>
                <li>Authenticate users and secure accounts</li>
                <li>Enable business communication workflows</li>
                <li>Monitor platform reliability, abuse, fraud, and unauthorized activity</li>
                <li>Comply with applicable laws, regulations, and platform policies</li>
                <li>Provide customer support and technical assistance</li>
            </ul>

            <h2 class="text-xl font-bold text-gray-900">3. Acceptable Use & Compliance</h2>

            <p>
                Arzonet strictly prohibits the use of its platform for:
            </p>

            <ul>
                <li>Spam or unsolicited communications</li>
                <li>Purchased, scraped, rented, or third-party contact lists</li>
                <li>Phishing, deceptive practices, or fraudulent activity</li>
                <li>Malware distribution or harmful content</li>
                <li>Illegal, abusive, or misleading communications</li>
                <li>Violations of Meta, AWS, or third-party platform policies</li>
            </ul>

            <p>
                Customers are solely responsible for ensuring they have obtained
                appropriate consent and legal authorization before communicating
                with recipients through our services.
            </p>

            <p>
                We reserve the right to monitor usage patterns, suspend accounts,
                remove content, or terminate services for activities that violate
                our policies, harm platform reputation, or negatively impact delivery
                infrastructure and service providers.
            </p>

            <h2 class="text-xl font-bold text-gray-900">4. Data Protection & Security</h2>

            <p>
                We implement reasonable administrative, technical, and organizational
                safeguards designed to protect information against unauthorized access,
                misuse, disclosure, alteration, or destruction.
            </p>

            <p>
                Security measures may include encrypted connections, access controls,
                activity monitoring, and infrastructure-level protections. However,
                no method of electronic transmission or storage is completely secure.
            </p>

            <h2 class="text-xl font-bold text-gray-900">5. Third-Party Services</h2>

            <p>
                Our platform may integrate with third-party providers and infrastructure
                partners including cloud providers, communication platforms, analytics
                tools, payment processors, and authentication providers.
            </p>

            <p>
                Use of such services may also be subject to the respective third-party
                privacy policies and terms.
            </p>

            <h2 class="text-xl font-bold text-gray-900">6. Data Retention</h2>

            <p>
                We retain information only for as long as necessary to provide services,
                comply with legal obligations, resolve disputes, enforce agreements,
                and maintain platform integrity and security.
            </p>

            <h2 class="text-xl font-bold text-gray-900">7. User Rights</h2>

            <p>
                Depending on applicable laws and jurisdiction, users may have rights
                to access, update, correct, or request deletion of their personal data.
            </p>

            <h2 class="text-xl font-bold text-gray-900">8. Policy Updates</h2>

            <p>
                We may update this Privacy Policy periodically. Continued use of the
                platform after changes become effective constitutes acceptance of the
                updated policy.
            </p>

            <h2 class="text-xl font-bold text-gray-900">9. Contact Us</h2>

            <p>
                If you have any questions regarding this Privacy Policy or our data
                practices, contact us at:
            </p>

            <p>
                <a href="mailto:privacy@arzonet.com" style="color:var(--color-brand)">
                    privacy@arzonet.com
                </a>
            </p>

        </div>
        </div>
    </section>
@endsection