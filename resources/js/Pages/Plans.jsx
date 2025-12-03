import { Link } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Button from '../Components/Shared/Button';
import Card from '../Components/Shared/Card';

export default function Plans({ plans = [], user = null }) {
    const isAuthenticated = !!user;

    return (
        <>
            <Head title="Choose Your Plan - Backlink Pro" />
            <div className="min-h-screen bg-white">
                {/* Navigation Header */}
                <nav className="bg-white border-b border-gray-200 sticky top-0 z-50 backdrop-blur-sm bg-white/95 shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-20">
                            <div className="flex items-center">
                                <Link href="/" className="text-2xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                                    ⚡ Backlink Pro
                                </Link>
                            </div>
                            <div className="hidden md:flex items-center space-x-8">
                                <Link href="/" className="text-gray-700 hover:text-red-600 font-medium transition-colors">
                                    Home
                                </Link>
                                <Link href="/pricing" className="text-red-600 font-semibold border-b-2 border-red-600 pb-1">
                                    Pricing
                                </Link>
                                <Link href="/help" className="text-gray-700 hover:text-red-600 font-medium transition-colors">
                                    Help
                                </Link>
                                {isAuthenticated ? (
                                    <Link href="/dashboard">
                                        <Button variant="primary" className="px-6 py-2.5">
                                            Dashboard
                                        </Button>
                                    </Link>
                                ) : (
                                    <>
                                        <Link href="/login" className="text-gray-700 hover:text-red-600 font-medium transition-colors">
                                            Login
                                        </Link>
                                        <Link href="/register">
                                            <Button variant="primary" className="px-6 py-2.5">
                                                Get Started
                                            </Button>
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </nav>

                {/* Hero Header Section */}
                <section className="relative overflow-hidden bg-gradient-to-br from-red-50 via-white to-green-50 pt-16 pb-20">
                    {/* Decorative Background Elements */}
                    <div className="absolute inset-0 overflow-hidden">
                        <div className="absolute top-0 left-1/4 w-72 h-72 bg-red-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
                        <div className="absolute top-0 right-1/4 w-72 h-72 bg-green-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
                    </div>

                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                        <div className="text-center mb-12">
                            <div className="inline-block mb-6">
                                <span className="bg-gradient-to-r from-red-600 to-green-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg animate-pulse">
                                    ⚡ POWERFUL AUTOMATION
                                </span>
                            </div>
                            <h1 className="text-5xl md:text-6xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent mb-6">
                                Choose Your Plan
                            </h1>
                            <p className="text-xl md:text-2xl text-gray-700 max-w-3xl mx-auto font-medium leading-relaxed">
                                Select the perfect plan for your backlink building needs. All plans include our powerful automation tools.
                            </p>

                            {/* Trust Indicators */}
                            <div className="mt-8 flex flex-wrap justify-center gap-6 text-sm text-gray-600">
                                <div className="flex items-center gap-2">
                                    <span className="text-green-500">✓</span>
                                    <span>No credit card required</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="text-green-500">✓</span>
                                    <span>Cancel anytime</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="text-green-500">✓</span>
                                    <span>14-day money-back guarantee</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Plans Section */}
                <section className="py-12 bg-gradient-to-b from-white to-gray-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Plans Grid */}
                    {plans && plans.length > 0 ? (
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mb-12">
                        {plans.map((plan) => (
                            <div
                                key={plan.id}
                                className={`relative h-full flex flex-col rounded-2xl border-2 transition-all duration-300 ${
                                    plan.slug === 'pro'
                                        ? 'border-green-300 bg-green-50 shadow-xl scale-105'
                                        : 'border-gray-200 bg-white hover:shadow-lg hover:border-gray-300'
                                }`}
                            >
                                {/* Popular Badge - Top Right for Pro Plan */}
                                {plan.slug === 'pro' && (
                                    <div className="absolute -top-3 -right-3">
                                        <span className="bg-green-500 text-white px-4 py-1.5 rounded-full text-xs font-bold shadow-md">
                                            Popular
                                        </span>
                                    </div>
                                )}

                                <div className="flex flex-col h-full p-6">
                                    {/* Plan Header */}
                                    <div className="mb-6">
                                        <h3 className="text-2xl font-bold text-gray-900 mb-2">
                                            {plan.name}
                                        </h3>
                                        <p className="text-gray-600 text-sm mb-6">
                                            {plan.description}
                                        </p>

                                        {/* Price */}
                                        <div className="mb-6">
                                            <span className={`text-4xl font-bold ${
                                                plan.slug === 'pro' ? 'text-green-600' : 'text-gray-900'
                                            }`}>
                                                ${(plan.price || 0).toFixed(2)}
                                            </span>
                                            <span className="text-gray-600 ml-2 text-base font-normal">
                                                /{plan.billing_interval === 'monthly' ? 'monthly' : plan.billing_interval || 'monthly'}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Features List */}
                                    <div className="flex-1 mb-6">
                                        <ul className="space-y-3">
                                            {plan.features?.map((feature, index) => (
                                                <li key={index} className="flex items-start">
                                                    <svg
                                                        className="h-5 w-5 text-green-500 mr-3 flex-shrink-0 mt-0.5"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M5 13l4 4L19 7"
                                                        />
                                                    </svg>
                                                    <span className="text-sm text-gray-700">
                                                        {feature}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>

                                    {/* CTA Button */}
                                    <div className="mt-auto pt-4">
                                        {isAuthenticated ? (
                                            <a href={`/subscription/checkout/${plan.id}`} className="inline-block w-full">
                                                <Button
                                                    variant={plan.slug === 'pro' ? 'success' : plan.price === 0 ? 'primary' : 'outline'}
                                                    className="w-full font-semibold py-3"
                                                >
                                                    {plan.price == 0 ? 'Get Started Free' : 'Subscribe Now'}
                                                </Button>
                                            </a>
                                        ) : (
                                            <Link href="/register" className="inline-block w-full">
                                                <Button
                                                    variant={plan.slug === 'pro' ? 'success' : plan.price === 0 ? 'primary' : 'outline'}
                                                    className="w-full font-semibold py-3"
                                                >
                                                    {plan.price == 0 ? 'Sign Up Free' : 'Get Started'}
                                                </Button>
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-8 bg-gray-100 rounded-full mb-6">
                                <span className="text-5xl">💳</span>
                            </div>
                            <h3 className="text-2xl font-bold text-gray-900 mb-2">No Plans Available</h3>
                            <p className="text-gray-600 mb-8">Plans are currently being set up. Please check back soon.</p>
                        </div>
                    )}

                    {/* FAQ Section */}
                    <div className="mt-16 bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-xl border-2 border-red-100 p-8">
                        <h2 className="text-3xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent mb-8 text-center">
                            Frequently Asked Questions
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">
                                    Can I change plans later?
                                </h3>
                                <p className="text-gray-600 text-sm">
                                    Yes! You can upgrade or downgrade your plan at any time. Changes will be prorated.
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">
                                    What payment methods do you accept?
                                </h3>
                                <p className="text-gray-600 text-sm">
                                    We accept all major credit cards through Stripe. Your payment information is secure and encrypted.
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">
                                    Can I cancel anytime?
                                </h3>
                                <p className="text-gray-600 text-sm">
                                    Yes, you can cancel your subscription at any time. You'll continue to have access until the end of your billing period.
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">
                                    Is there a free trial?
                                </h3>
                                <p className="text-gray-600 text-sm">
                                    Yes! Our free plan allows you to get started with basic features. No credit card required.
                                </p>
                            </div>
                        </div>
                    </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-900 text-gray-400 py-12 mt-20">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                            <div>
                                <h3 className="text-white font-bold text-xl mb-4 bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                                    Backlink Pro
                                </h3>
                                <p className="text-sm">Automated backlink building platform for SEO professionals.</p>
                            </div>
                            <div>
                                <h4 className="text-white font-semibold mb-4">Product</h4>
                                <ul className="space-y-2 text-sm">
                                    <li><Link href="/pricing" className="hover:text-green-400 transition-colors">Pricing</Link></li>
                                    <li><Link href="/features" className="hover:text-green-400 transition-colors">Features</Link></li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="text-white font-semibold mb-4">Support</h4>
                                <ul className="space-y-2 text-sm">
                                    <li><Link href="/help" className="hover:text-green-400 transition-colors">Help Center</Link></li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="text-white font-semibold mb-4">Account</h4>
                                <ul className="space-y-2 text-sm">
                                    {isAuthenticated ? (
                                        <li><Link href="/dashboard" className="hover:text-green-400 transition-colors">Dashboard</Link></li>
                                    ) : (
                                        <>
                                            <li><Link href="/login" className="hover:text-green-400 transition-colors">Login</Link></li>
                                            <li><Link href="/register" className="hover:text-green-400 transition-colors">Sign Up</Link></li>
                                        </>
                                    )}
                                </ul>
                            </div>
                        </div>
                        <div className="pt-8 border-t border-gray-800 text-center text-sm">
                            <p>&copy; {new Date().getFullYear()} Backlink Pro. Made with <span className="text-red-500">❤️</span> for marketers.</p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

