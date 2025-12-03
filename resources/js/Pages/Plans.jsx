import { Link } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Button from '../Components/Shared/Button';
import Card from '../Components/Shared/Card';

export default function Plans({ plans, user }) {
    const isAuthenticated = !!user;

    return (
        <>
            <Head title="Choose Your Plan - Backlink Pro" />
            <div className="min-h-screen bg-gradient-to-br from-red-50 via-white to-green-50 py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="text-center mb-12">
                        <div className="inline-block mb-4">
                            <span className="bg-gradient-to-r from-red-600 to-green-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">
                                ⚡ POWERFUL AUTOMATION
                            </span>
                        </div>
                        <h1 className="text-5xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent mb-4">
                            Choose Your Plan
                        </h1>
                        <p className="text-xl text-gray-700 max-w-2xl mx-auto font-medium">
                            Select the perfect plan for your backlink building needs. All plans include our powerful automation tools.
                        </p>
                    </div>

                    {/* Plans Grid */}
                    <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4 mb-12">
                        {plans?.map((plan) => (
                            <Card
                                key={plan.id}
                                className={`relative h-full flex flex-col border-2 ${
                                    plan.slug === 'pro'
                                        ? 'border-green-500 scale-105 shadow-2xl bg-gradient-to-br from-green-50 to-white'
                                        : plan.price === 0
                                        ? 'border-red-500 bg-gradient-to-br from-red-50 to-white hover:shadow-xl'
                                        : 'border-gray-200 hover:border-red-300 hover:shadow-xl transition-all'
                                }`}
                            >
                                {plan.slug === 'pro' && (
                                    <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                        <span className="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">
                                            ⭐ Most Popular
                                        </span>
                                    </div>
                                )}
                                {plan.price === 0 && (
                                    <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                        <span className="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">
                                            🎁 Free Forever
                                        </span>
                                    </div>
                                )}

                                <div className="flex flex-col h-full">
                                    <div className="text-center mb-6">
                                        <h3 className="text-2xl font-bold text-gray-900 mb-2">
                                            {plan.name}
                                        </h3>
                                        <p className="text-gray-600 text-sm mb-4">
                                            {plan.description}
                                        </p>

                                        <div className="mb-6">
                                            <span className={`text-5xl font-bold ${
                                                plan.slug === 'pro' ? 'text-green-600' : plan.price === 0 ? 'text-red-600' : 'text-gray-900'
                                            }`}>
                                                ${plan.price}
                                            </span>
                                            <span className="text-gray-600 ml-2 font-medium">
                                                /{plan.billing_interval}
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
                                                            strokeLineCap="round"
                                                            strokeLineJoin="round"
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

                                    {/* Limits Info */}
                                    <div className="border-t border-gray-200 pt-4 mb-6 space-y-2 text-sm text-gray-600">
                                        {plan.max_domains && (
                                            <div className="flex justify-between">
                                                <span>Max Domains:</span>
                                                <span className="font-semibold">{plan.max_domains === -1 ? 'Unlimited' : plan.max_domains}</span>
                                            </div>
                                        )}
                                        {plan.max_campaigns && (
                                            <div className="flex justify-between">
                                                <span>Max Campaigns:</span>
                                                <span className="font-semibold">{plan.max_campaigns === -1 ? 'Unlimited' : plan.max_campaigns}</span>
                                            </div>
                                        )}
                                        {plan.daily_backlink_limit && (
                                            <div className="flex justify-between">
                                                <span>Daily Backlinks:</span>
                                                <span className="font-semibold">{plan.daily_backlink_limit === -1 ? 'Unlimited' : plan.daily_backlink_limit}</span>
                                            </div>
                                        )}
                                    </div>

                                    {/* CTA Button */}
                                    <div className="mt-auto">
                                        {isAuthenticated ? (
                                            <Link href={`/subscription/checkout/${plan.id}`}>
                                                <Button
                                                    variant={plan.slug === 'pro' ? 'success' : plan.price === 0 ? 'primary' : 'outline'}
                                                    className="w-full font-bold"
                                                >
                                                    {plan.price == 0 ? 'Get Started Free' : 'Subscribe Now'}
                                                </Button>
                                            </Link>
                                        ) : (
                                            <Link href="/register">
                                                <Button
                                                    variant={plan.slug === 'pro' ? 'primary' : 'outline'}
                                                    className="w-full"
                                                >
                                                    {plan.price == 0 ? 'Sign Up Free' : 'Get Started'}
                                                </Button>
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>

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
            </div>
        </>
    );
}

