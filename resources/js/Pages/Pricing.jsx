import { Link } from '@inertiajs/react';
import Button from '../Components/Shared/Button';
import Card from '../Components/Shared/Card';

export default function Pricing({ plans }) {
    return (
        <div className="min-h-screen bg-gray-50 py-12">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-12">
                    <h1 className="text-4xl font-bold text-gray-900 mb-4">Choose Your Plan</h1>
                    <p className="text-xl text-gray-600">Select the perfect plan for your backlink building needs</p>
                </div>

                <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    {plans?.map((plan) => (
                        <Card
                            key={plan.id}
                            className={`relative ${plan.slug === 'pro' ? 'ring-2 ring-indigo-500 scale-105' : ''}`}
                        >
                            {plan.slug === 'pro' && (
                                <div className="absolute top-0 left-0 right-0 bg-indigo-500 text-white text-center py-1 text-xs font-semibold">
                                    Most Popular
                                </div>
                            )}
                            <div className="text-center">
                                <h3 className="text-2xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                                <p className="text-gray-600 mb-6">{plan.description}</p>
                                
                                <div className="mb-6">
                                    <span className="text-4xl font-bold text-gray-900">${plan.price}</span>
                                    <span className="text-gray-600">/{plan.billing_interval}</span>
                                </div>

                                <ul className="text-left space-y-3 mb-8">
                                    {plan.features?.map((feature, index) => (
                                        <li key={index} className="flex items-start">
                                            <svg className="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span className="text-sm text-gray-700">{feature}</span>
                                        </li>
                                    ))}
                                </ul>

                                <Link href={`/subscription/checkout/${plan.id}`}>
                                    <Button
                                        variant={plan.slug === 'pro' ? 'primary' : 'outline'}
                                        className="w-full"
                                    >
                                        {plan.price === 0 ? 'Get Started' : 'Subscribe'}
                                    </Button>
                                </Link>
                            </div>
                        </Card>
                    ))}
                </div>
            </div>
        </div>
    );
}

